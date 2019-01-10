<?php
namespace Api\Controller;

use Api\Common\Controller\AuthController;

class OrdersController extends AuthController
{

    /**
     * 订单确认
     * @params  type    1:专栏;2:课程;3:线下;
     * @params  proid
     */
    public function confirm(){
        if(empty($_GET['type']) && empty($_GET['proid'])) {
            $backData = array(
                "code" => 10001,
                "msg" => "参数错误"
            );
            return $this->ajaxReturn($backData);
        }
        $type = I("get.type/d");
        $proId = I("get.proid");
        $thumbUrl = XZSS_BASE_URL .'/thumb/';
        switch($type) {
            case 1:
            $tableName = "Columnist";
            break;
            case 2:
            $tableName = "Course";
            break;
            case 3:
            $tableName = "Booking";
            break;
        }
        $proInfo = M($tableName)
        ->field("id,CONCAT('$thumbUrl',thumb) as thumb,title,price,has_yh,yh_limit,yh_price")
        ->where(array("id"=>$proId))
        ->find();
        // 1.1 计算优惠是否过期,计算优惠金额
        $favourableAmount = 0;
        $total = $proInfo['price'];
        if($proInfo['has_yh'] == 1){
            $oldYhLimit = strtotime($proInfo['yh_limit']);
            if($oldYhLimit > time()){
                $favourableAmount = (($proInfo['price']*100) - ($proInfo['yh_price']*100))/100;
                $total = $proInfo['yh_price'];
            }else {
                $proInfo['has_yh'] = 0;
            }
        }
        $backData = array(
            "code" =>200,
            "msg"  =>"success",
            "data" =>array(
                "goodsInfo"     =>$proInfo,
                "favourable"       =>$favourableAmount,
                "total"         =>$total,
                "ordersType"  =>$type
            )
        );
        $this->ajaxReturn($backData);
        
    }
    /***
     * 创建订单
     */
    public function create()
    {
        $type = I("get.type/d");
        $proid = I("get.proid/d");
        $memberId = $this->uid;
        //检测是否已经购买过
        $haveCondition = array(
            "member_id" =>$memberId,
            "pro_id"    =>$proid,
            "type"      =>$type
        );
        $isHave = M("MyGoods")->where($haveCondition)->find();
        if($isHave){
            $backData = array(
                "code" => 130002,
                "msg" => "无需重复购买"
            );
            return $this->ajaxReturn($backData);
        }
        //根据类别设置数据内容；type 1:专栏;2:课程;3:线下预约,
        $proModelName = '';
        $proField = "id,title,org_id,thumb,price,has_yh,yh_limit,yh_price";
        $orderName = '';
        switch ($type) {
            case 1:
                $proModelName = 'Columnist';
                $proInfo = M($proModelName)->field($proField)->where(array('id' => $proid))->find();
                $orderName = '开通专栏';
                break;
            case 2:
                $proModelName = 'Course';
                $proInfo = M($proModelName)->field($proField)->where(array('id' => $proid))->find();
                $orderName = '购买课程';
                break;
            case 3 :
                $proModelName = 'Booking';
                $proInfo = M($proModelName)->field($proField)->where(array('id' => $proid))->find();
                $orderName = '线下预约';
                break;
        }

        $orderAmount = $proInfo['price'];
        /**
         * 计算订单金额，检测是否有限时优惠和抵扣优惠券
         */
        // 2.1

        if($proInfo['has_yh'] == 1){
            $oldYhLimit = strtotime($proInfo['yh_limit']);
            if($oldYhLimit > time()){
                $orderAmount = $proInfo['yh_price'];
            }
        }
        // 2.2 抵扣优惠券
        if(isset($_GET['couponid']) && $_GET['couponid']) {
            //待完善 by:2019-01-09
            $couponInfo = array();
        }

        //获取余额
        $Account = A("Account");
        $balance = $Account->fetchBalance();
        $orderAmount -= $balance;

        //创建订单
        $orderNum = time() . str_pad($memberId, 7, 0, STR_PAD_LEFT) . str_pad(rand(1, 999), 3, 0, STR_PAD_LEFT);
        $goodsData = array(array(
            "pro_id" => $proid,
            "pro_type" => $type,
            "pro_name" => $proInfo['title'],
            "pro_price" => $proInfo['price'],
            "pro_thumb" => XZSS_BASE_URL .'/thumb/'.$proInfo['thumb']
        ));

        $orderData = array(
            "order_num" => $orderNum,
            "org_id"    =>$proInfo['org_id'],
            "member_id" => $memberId,
            "pro_id" => $proid,
            "order_type" => $type,
            "amount" => $orderAmount,
            "goods" => serialize($goodsData)
        );
        

        //开启事务
        $model = M();
        $model->startTrans();
        $orderModel = M("Orders");
        //如果余额足够直接完成
        if($orderAmount <= 0 ){
            //insert order
            $orderData['amount'] =0;
            $orderData['status'] =2;
            $orderData['pay_way'] ="余额";
            $orderData['pay_time'] =date("YmdHis",time());
            $orderInsert = $orderModel->data($orderData)->fetchSql(false)->add();

            //update balance
            $updateData = array(
                "balance"   =>$balance - $proInfo['price']
            );
            // $updateBalance = M("Member")->where("id=$memberId")->data($updateData)->save();
            $updateBalance = $Account->subBalance($proInfo['price'],$orderName."扣除",$memberId);

            //insert my goods
            $myGoodsData = array(
                "type" => $type,
                "pro_id" => $proid,
                "member_id" => $memberId,
            );
            if ($type == 1) {
                $myGoodsData['start_time'] = time();
                $myGoodsData['end_time'] = strtotime("+1 year");
            }
            $myGoodsInsert = M("MyGoods")->data($myGoodsData)->add();

            //update buy number
            $updateBuyNum = A("Wxpay")->updateBuyNum($type,$proid);
            if($orderInsert && $updateBalance && $myGoodsInsert && $updateBuyNum){
                $backData=array(
                    "code" => 200,
                    "msg" => "购买成功" 
                );
                $model->commit();
            }else {
                $backData=array(
                    "code" => 13001,
                    "msg" => "系统繁忙,请稍后再试" 
                );
                $model->rollback();
            }
            $this->ajaxReturn($backData);
        }else {
            //预支付
            $orderInsert = $orderModel->data($orderData)->fetchSql(false)->add();

            //创建统一下单
            $backUrl = "http://www.xinzhinetwork.com/gongfu/api.php/Wxpay/index";//支付成功回调地址
            $payMentXml = $this->tyxd($orderName, $orderNum, $orderAmount,$backUrl);
            $payMentObj = simplexml_load_string($payMentXml, null, LIBXML_NOCDATA);
            if ($payMentObj->return_code == 'SUCCESS') {

                //支付数据签名
                $payMentArr = json_decode(json_encode($payMentObj), true);
                $signArr = array(
                    'appId' => $payMentArr['appid'],
                    'timeStamp' => (string)time(),
                    "nonceStr" => createRandom(16),
                    "package" => 'prepay_id=' . $payMentArr['prepay_id'],
                    "signType" => "MD5"
                );

                $resInfo = $signArr;
                $resInfo['sign'] = wxSign($signArr,MERCHANT_SECRET);
                $resInfo['orderNo'] = $orderNum;
                $backData = array(
                    "code" => 200,
                    "msg" => "success",
                    'data' => $resInfo
                );

                $model->commit();
            } else {
                $model->rollback();
                $backData = array(
                    "code" => 13002,
                    "msg" => "统一下单创建错误",
                    "info" => $payMentObj
                );
            }
            $this->ajaxReturn($backData);
        }
        

    }


 




    

    /**
     * 统一下单
     * @param   string  order name
     * @param   string  order number
     * @param   number  price
     * @param   string  pay result callback url
     * */
    public function tyxd($order_name, $order_num, $amount,$backUrl)
    {
        // $WePay = $this->getWePay();

        //实例化WePay对象
        $account = A("Account");
        $appId = APP_ID;
        $mch_id = MERCHANT_NUMBER;
        $mch_key = MERCHANT_SECRET;
        $openid = $account->getopenid();
        $WePay = new \Org\Util\WePay($appId, $mch_id, $mch_key, $backUrl, $openid);

        $amount = $amount * 100;//将订单金额单位转化为分
//        $amount = ceil($amount) == $amount ? $amount : $amount * 100;//将订单金额单位转化为分
        $arr = array("order_name" => $order_name, "order_num" => $order_num, "amount" => $amount);
        return $WePay->prepay($arr);
    }

/*     protected function getWePay($backUrl)
    {
        $account = A("Account");
        $appId = APP_ID;
        $mch_id = MERCHANT_NUMBER;
        $mch_key = MERCHANT_SECRET;
        // $url = 'http://www.xinzhinetwork.com/api.php/Wxpay/index';
        $openid = $account->getopenid();
        $a = new \Org\Util\WePay($appId, $mch_id, $mch_key, $backUrl, $openid);
        return $a;
    } */




    /***
     * orders operation
     * 前端已隐藏订单操作下面方法可以作废2018-03-31
     */

    public function cancel_order($id)
    {
        //取消订单,删除订单
        $model = M("Orders");
        $result = $model->where(array('id' => $id))->delete();
        if ($result === false) {
            $backData = array(
                "code" => 13001,
                "msg" => "操作错误"
            );
        } else {
            $backData = array(
                "code" => 200,
                "msg" => "ok",
                "data" => array()
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
                "code" => 13001,
                "msg" => "操作错误"
            );
        } else {
            $backData = array(
                "code" => 200,
                "msg" => "ok",
                "data" => array()
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
                        "code" => 13009,
                        "msg" => '已经购买过不需重复购买'
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
                    "code" => 200,
                    "msg" => "success",
                    'data' => $resInfo
                );
            } else {
                $backData = array(
                    "code" => 12002,
                    "msg" => "订单接口错误",
                    "info"      =>$payMentObj
                );
            }

        } else {
            $backData = array(
                "code" => 13001,
                "msg" => "操作错误"
            );
        }
        $this->ajaxReturn($backData);
    }

    /**
     * 订单信息
     */
    public function detail(){
        if(empty($_GET['orderno'])){
            $backData = array(
                "code" => 10001,
                "msg" => "参数错误"
            );
            return $this->ajaxReturn($backData);
        }
        $orderNo = I("get.orderno");
        $orderInfo = M("Orders")->where(array('order_num'=>$orderNo))->find();
        $backData = array(
            "code" => 200,
            "msg" => "success",
            "data"      =>array(
                "goodsInfo" =>unserialize($orderInfo['goods'])[0],
                "derateInfo"=>array(),
                "orderInfo" =>array(
                    "orderNo"   =>$orderNo,
                    "orderType" =>$orderInfo['order_type'],
                    "tradeNo"   =>$orderInfo['trade_num'],
                    "time"      =>$orderInfo['pay_time'],
                    "amount"    =>$orderInfo['amount']
                )
            )
        );
        $this->ajaxReturn($backData);
    }

}