<?php
namespace Api\Controller;
use Think\Controller;

class WxpayController {

    /**支付结果*/
    public function index()
    {
        $inputData = file_get_contents('php://input');

        if (IS_POST && $inputData) {

            $weData = simplexml_load_string($inputData, null, LIBXML_NOCDATA);
            $weData = json_decode(json_encode($weData));
            $orderNum = $weData->out_trade_no;
            $tradeNum = $weData->transaction_id;

            //插入日志
            $logData = array(
                "order_no"      =>$weData->out_trade_no,
                "result_code"   =>$weData->result_code,
                "content"       =>"ok"
            );
            $log = M("PayresultLog")->add($logData);

            if ($weData->return_code == 'SUCCESS' && $weData->result_code == 'SUCCESS') {
                $orderModel = M('Orders');
                $where = array(
                    "order_num" => $orderNum
                );
                $orderInfo = $orderModel->field('member_id,order_type,pro_id,status')->where($where)->find();

                //如果订单未处理
                if ($orderInfo['status'] == 1) {
                    //1.开启事务
                    $mode = M();
                    $mode->startTrans();

                    //2.加入我的商品
                    $myGoodsData = array(
                        "type" => $orderInfo['order_type'],
                        "pro_id" => $orderInfo['pro_id'],
                        "member_id" => $orderInfo['member_id'],
                    );
                    if ($orderInfo['order_type'] == 1) {
                        $myGoodsData['start_time'] = time();
                        $myGoodsData['end_time'] = strtotime("+1 year");
                    }
                    $myGoodsInsert = M("MyGoods")->data($myGoodsData)->add();

                    //3.修改订单状态
                    $updateOrder = $orderModel->where($where)->data(array('status' => 2, "trade_num" => $tradeNum,"pay_time"=>$weData->time_end))->save();

                    //4.更新产品购买数
                    $updatePro = $this->updateBuyNum($orderInfo['order_type'],$orderInfo['pro_id']);


                    //5. 判断事务
                    if ($myGoodsInsert && $updateOrder && $updatePro) {
                        $mode->commit();
                        echo '<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>';
                    } else {
                        $mode->rollback();
                    }


                }elseif($orderInfo['status'] == 2){
                    //如果订单已经处理
                    echo '<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>';
                }

            }
        }

    }


    /**
     * Present result
     */
    public function present(){
        $inputData = file_get_contents('php://input');
        if($inputData){
            $weData = simplexml_load_string($inputData, null, LIBXML_NOCDATA);
            $weData = json_decode(json_encode($weData));
            $orderNum = $weData->out_trade_no;
            $tradeNum = $weData->transaction_id;
/*             $orderNum = "15214558120000001633";
            $tradeNum = "0293"; */

            //1. 插入日志
            $logData = array(
                "order_no"      =>$weData->out_trade_no,
                "result_code"   =>$weData->result_code
                // "content"       =>(string)$inputData
            );
            $log = M("PayresultLog")->add($logData);

            //2. fetch order info
            $orderInfo = M("Orders")->field("id,member_id,goods,amount,status")->where("order_num = '$orderNum'")->fetchSql(false)->find();
            if($orderInfo['status'] == 2){
                echo '<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>';
                return;
            }
            $orderGoods = unserialize($orderInfo['goods'])[0];
            $amountDiff = (float)$orderGoods['pro_price'] - $orderInfo['amount'];
            $proId = $orderGoods['pro_id'];
            $proType = $orderGoods['pro_type'];
            $proName = $orderGoods['pro_name'];
            $memberId = $orderInfo['member_id'];

            //3. 差价大于0，意味着有从余额扣除，必须更新账户余额
            $model = M();
            $model->startTrans();
            $balanceUpdate =  true;
            if($amountDiff > 0){
                $balanceSql = "update __MEMBER__ set balance=balance-".$amountDiff." where id=".$memberId;
                $balanceUpdate = M()->execute($balanceSql);
            }

            //4. update orders info
            $updateOrderData = array(
                "status"        =>2,
                "trade_num"     =>$tradeNum,
                "pay_time"      =>$weData->time_end
            );
            $updateOrder = M("Orders")->where(array("order_num"=>$orderNum))->data($updateOrderData)->fetchSql(false)->save();



            //5. insert present record
            $presentInsertData = array(
                "member_id"     =>$memberId,
                "pro_type"      =>$proType,
                "pro_id"        =>$proId,
                "secret"        =>md5($orderNum)
            );
            $url ="";
/*             switch($proType){
                case 1:
                $url .= "column/column-detail/index";
                break;
                case 2:
                $url .= "course/detail/index";
                break;
            } */
            $giftContent = array(
                "proid"     =>$proId,
                "title"     =>$proName,
                "value"     =>$orderGoods['pro_price'],
                "thumb"     =>$orderGoods['pro_thumb'],
                "url"       =>$url
            );
            $presentInsertData['content'] = serialize($giftContent);
            $presentInsert = M("Present")->fetchSql(false)->add($presentInsertData);

            //6. update buy number
            $updatePro = $this->updateBuyNum($proType,$proId);

            //7 commit
            if($balanceUpdate && $updateOrder && $presentInsert && $updatePro){
                $model->commit();
                echo '<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>';
            }else {
                $model->rollback();
            }
        }

    }


    public function test(){
        // echo  '<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>';
        echo "ss";
    }

    /**
     * 更新产品购买数
     * @param   number  product type
     * @param   number  product id
     * @return  boolean 
     */
    protected function updateBuyNum($type,$id){
        $updateModelName = '';
        $updateProSql ='update ';
        $setData = "";
        switch($type) {
            case 1:
                //4.1 如果是专栏需要更新订阅数
                $updateModelName = '__COLUMNIST__';
                $setData = ' ,`subscribers`= subscribers+1';
                break;
            case 2 :
                $updateModelName = '__COURSE__';
                break;
            case 3 :
                $updateModelName = '__TESTS_LIST__';
                break;
            case 4 :
                $updateModelName = '__BOOKING_PHASE__';
                break;
        }
        $updateProSql .=  $updateModelName." set `buy_num` = buy_num +1".$setData." where id=".$id;
        $updatePro = M()->execute($updateProSql);
        return $updatePro;
    }

}