<?php
namespace Api\Controller;

use Think\Controller;

class OrdersController extends Controller
{

    /***
     * 创建订单
     */
    public function create($type,$proid)
    {
        $account = A("Account");
        //检测用户注册以及认证状态
        $memberId = $account->getMemberId();
        if (!$memberId) {
            $backData = array(
                "errorCode" => 110001,
                "errorMsg" => "请重新登录"
            );
            return $this->ajaxReturn($backData);
        }

        //检测是否已经购买过
        $haveCondition = array(
            "member_id" =>$memberId,
            "pro_id"    =>$proid
        );
        $isHave = M("MyGoods")->where($haveCondition)->find();
        if($isHave){
            $backData = array(
                "errorCode" => 110002,
                "errorMsg" => "无需重复购买"
            );
            return $this->ajaxReturn($backData);
        }

        //获取产品信息；type 1:专栏;2:课程;3:测试;4:在线预约
        $proModelName = '';
        $proField = 'id,title,teacher_id,thumb,price';
        switch ($type) {
            case 1:
                $proModelName = 'Columnist';
                $proInfo = M($proModelName)->field($proField)->where(array('id' => $proid))->find();
                break;
            case 2:
                $proModelName = 'Course';
                $proInfo = M($proModelName)->field($proField)->where(array('id' => $proid))->find();
                break;
            case 3 :
                $proModelName = 'TestsList';
                $proField = 'id,title,price';
                $proInfo = M($proModelName)->field($proField)->where(array('id' => $proid))->find();
                break;
            case 4:
                $phaseInfo = M("BookingPhase")->field('booking_id,title')->where(array('id' => $proid))->find();
                $bookingInfo = M("Booking")->field("title,thumb,price")->where("id=".$phaseInfo['booking_id'])->find();
                $proInfo = array(
                    "title" =>$bookingInfo['title']."-".$phaseInfo['title'],
                    "thumb" =>$bookingInfo['thumb'],
                    "price" =>$bookingInfo['price']
                );
                break;
        }



        //创建订单
        $orderNum = time() . str_pad($memberId, 7, 0, STR_PAD_LEFT) . str_pad(rand(1, 999), 3, 0, STR_PAD_LEFT);
        $orderModel = M("Orders");
        $goodsData = array(array(
            "pro_id" => $proid,
            "pro_name" => $proInfo['title'],
            "pro_price" => $proInfo['price']
        ));
        if ($type != 3) {
            $goodsData[0]['pro_thumb'] = $proInfo['thumb'];
        }

        $orderData = array(
            "order_num" => $orderNum,
            "member_id" => $memberId,
            "pro_id" => $proid,
            "pro_type" => $type,
            "amount" => $proInfo['price'],
            "goods" => serialize($goodsData)
        );
        if ($type == 1 || $type == 2) {//课程和专栏有讲师ID
            $orderData['teacher_id'] = $proInfo['teacher_id'];
        }

        //开启事务
        $model = M();
        $model->startTrans();
        $orderInsert = $orderModel->data($orderData)->add();
        if ($orderInsert) {
            //生成订单名称
            $orderName = '';
            switch ($type) {
                case 1:
                    $orderName = '开通专栏:';
                    break;
                case 2:
                    $orderName = '购买课程:';
                    break;
                case 3 :
                    $orderName = '购买测试题:';
                    break;
                case 4 :
                    $orderName = '在线预约:';
                    break;
            }
            //创建统一下单
            $payMentXml = $this->tyxd($orderName, $orderNum, $orderData['amount']);
            $payMentObj = simplexml_load_string($payMentXml, null, LIBXML_NOCDATA);
            if ($payMentObj->return_code == 'SUCCESS') {

                //支付数据签名
                $WePay = $this->getWePay();
                $payMentArr = json_decode(json_encode($payMentObj), true);
                $signArr = array(
                    'appId' => $payMentArr['appid'],
                    'timeStamp' => (string)time(),
                    "nonceStr" => $WePay->createRandom(16),
                    "package" => 'prepay_id=' . $payMentArr['prepay_id'],
                    "signType" => "MD5"
                );
                $resInfo = $signArr;
                $resInfo['sign'] = $WePay->sign($signArr);
                $backData = array(
                    "errorCode" => 10000,
                    "errorMsg" => "success",
                    'info' => $resInfo
                );
                $model->commit();
            } else {
                $model->rollback();
                $backData = array(
                    "errorCode" => 10002,
                    "errorMsg" => "统一下单创建错误",
                    "info" => $payMentObj
                );
            }

        } else {
            $backData = array(
                "errorCode" => 10001,
                "errorMsg" => "订单提交错误"
            );
        }
        $this->ajaxReturn($backData);
    }



    /**统一下单*/
    public function tyxd($order_name, $order_num, $amount)
    {
        $WePay = $this->getWePay();
        $amount = $amount * 100;//将订单金额单位转化为分
//        $amount = ceil($amount) == $amount ? $amount : $amount * 100;//将订单金额单位转化为分
        $arr = array("order_name" => $order_name, "order_num" => $order_num, "amount" => $amount);
        return $WePay->prepay($arr);
    }

    protected function getWePay()
    {
        $account = A("Account");
        $appId = APP_ID;
        $mch_id = MERCHANT_NUMBER;
        $mch_key = MERCHANT_SECRET;
        $url = 'http://www.xinzhinetwork.com/api.php/Wxpay/index';
        $openid = $account->getopenid();
        $a = new \Org\Util\WePay($appId, $mch_id, $mch_key, $url, $openid);
        return $a;
    }

    /***
     * orders operation
     */

    public function cancel_order($id)
    {
        //取消订单,删除订单
        $model = M("Orders");
        $result = $model->where(array('id' => $id))->delete();
        if ($result === false) {
            $backData = array(
                "errorCode" => 10001,
                "errorMsg" => "操作错误"
            );
        } else {
            $backData = array(
                "errorCode" => 10000,
                "errorMsg" => "ok",
                "info" => array()
            );
        }
        $this->ajaxReturn($backData);
    }

    public function del_order($id)
    {
        //删除订单,伪删除
        $model = M("Orders");
        $updateData = array(
            "status" => 0
        );
        $result = $model->where(array('id' => $id))->data($updateData)->save();
        if ($result === false) {
            $backData = array(
                "errorCode" => 10001,
                "errorMsg" => "操作错误"
            );
        } else {
            $backData = array(
                "errorCode" => 10000,
                "errorMsg" => "ok",
                "info" => array()
            );
        }
        $this->ajaxReturn($backData);
    }

    public function repay($id)
    {
        //重新提交订单
        $model = M("Orders");
        $orderInfo = $model->where(array('id' => $id, "status" => 1))->find();
        if ($orderInfo) {
            $goods = unserialize($orderInfo['goods'])[0];

            $orderName = '';
            switch ($orderInfo['pro_type']) {
                case 1:
                    $orderName = '开通专栏:';
                    break;
                case 2:
                    $orderName = '购买课程:';
                    break;
                case 3 :
                    $orderName = '购买测试题:';
                    break;
                case 4 :
                    $orderName = "在线预约";
                    break;
            }
            // 2.检测是否重复购买,课程和测试试卷不能重复购买
            if ($orderInfo['pro_type'] == 2 || $orderInfo['pro_type'] == 3 || $orderInfo['pro_type'] == 4) {
                $myGoodsWhere = array(
                    "type" => $orderInfo['pro_type'],
                    "pro_id" => $orderInfo['pro_id']
                );
                $myGoods = M("MyGoods")->where($myGoodsWhere)->find();
                if ($myGoods) {
                    $backData = array(
                        "errorCode" => 10009,
                        "errorMsg" => '已经购买过不需重复购买'
                    );
                    $this->ajaxReturn($backData);
                }
            }


            //统一下单
            $payMentXml = $this->tyxd($orderName, $orderInfo['order_num'], $orderInfo['amount']);
            $payMentObj = simplexml_load_string($payMentXml, null, LIBXML_NOCDATA);
            if ($payMentObj->return_code == 'SUCCESS') {

                //支付数据签名
                $WePay = $this->getWePay();
                $payMentArr = json_decode(json_encode($payMentObj), true);
                $signArr = array(
                    'appId' => $payMentArr['appid'],
                    'timeStamp' => (string)time(),
                    "nonceStr" => $WePay->createRandom(16),
                    "package" => 'prepay_id=' . $payMentArr['prepay_id'],
                    "signType" => "MD5"
                );
                $resInfo = $signArr;
                $resInfo['sign'] = $WePay->sign($signArr);
                $backData = array(
                    "errorCode" => 10000,
                    "errorMsg" => "success",
                    'info' => $resInfo
                );
            } else {
                $backData = array(
                    "errorCode" => 10002,
                    "errorMsg" => "订单接口错误",
                    "info"      =>$payMentObj
                );
            }

        } else {
            $backData = array(
                "errorCode" => 10001,
                "errorMsg" => "操作错误"
            );
        }
        $this->ajaxReturn($backData);
    }

}