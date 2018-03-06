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
                "result_code"   =>$weData->result_code
            );
            $log = M("PayresultLog")->add($logData);

            if ($weData->return_code == 'SUCCESS' && $weData->result_code == 'SUCCESS') {
                $orderModel = M('Orders');
                $where = array(
                    "order_num" => $orderNum
                );
                $orderInfo = $orderModel->field('member_id,pro_type,pro_id,status')->where($where)->find();

                //如果订单未处理
                if ($orderInfo['status'] == 1) {
                    //1.开启事务
                    $mode = M();
                    $mode->startTrans();

                    //2.加入我的商品
                    $myGoodsData = array(
                        "type" => $orderInfo['pro_type'],
                        "pro_id" => $orderInfo['pro_id'],
                        "member_id" => $orderInfo['member_id'],
                    );
                    if ($orderInfo['pro_type'] == 1) {
                        $myGoodsData['start_time'] = time();
                        $myGoodsData['end_time'] = strtotime("+1 year");
                    }
                    $myGoodsInsert = M("MyGoods")->data($myGoodsData)->add();

                    //3.修改订单状态
                    $updateOrder = $orderModel->where($where)->data(array('status' => 2, "trade_num" => $tradeNum,"pay_time"=>$weData->time_end))->save();

                    //4.更新产品购买数
                    $updateModelName = '';
                    $updateProSql ='update ';
                    $setData = "";
                    switch($orderInfo['pro_type']) {
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

/*                    if($orderInfo['pro_type'] ==  1){
                        //4.1 如果是专栏需要更新订阅数
                        $updateModelName = '__COLUMNIST__';
                        $setData = ' ,`subscribers`= subscribers+1';
                    }elseif($orderInfo['pro_type'] == 2){
                        $updateModelName = '__COURSE__';
                    }elseif($orderInfo['pro_type'] == 3){
                        $updateModelName = '__TESTS_LIST__';
                    }*/


                    $updateProSql .=  $updateModelName." set `buy_num` = buy_num +1".$setData." where id=".$orderInfo['pro_id'];
                    $updatePro = M()->execute($updateProSql);


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

    public function test(){
        echo  '<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>';
    }

}