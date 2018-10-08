<?php
namespace Api\Controller;

use Api\Common\Controller\AuthController;

class OrdersController extends AuthController
{

    /***
     * 创建订单
     */
    public function create($type,$proid)
    {
        $memberId = $this->uid;

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
        
        //根据类别设置数据内容；type 1:专栏;2:课程;3:测试;4:在线预约,5:赠送,
        $proModelName = '';
        $proField = 'id,title,teacher_id,thumb,price';
        $orderName = '';
        switch ($type) {
            case 1:
                $proModelName = 'Columnist';
                $proInfo = M($proModelName)->field($proField)->where(array('id' => $proid))->find();
                $orderName = '开通专栏:';
                break;
            case 2:
                $proModelName = 'Course';
                $proInfo = M($proModelName)->field($proField)->where(array('id' => $proid))->find();
                $orderName = '购买课程:';
                break;
            case 3 :
                $proModelName = 'TestsList';
                $proField = 'id,title,price';
                $proInfo = M($proModelName)->field($proField)->where(array('id' => $proid))->find();
                $orderName = '购买测试题:';
                break;
            case 4:
                $phaseInfo = M("BookingPhase")->field('booking_id,title')->where(array('id' => $proid))->find();
                $bookingInfo = M("Booking")->field("title,thumb,price")->where("id=".$phaseInfo['booking_id'])->find();
                $proInfo = array(
                    "title" =>$bookingInfo['title']."-".$phaseInfo['title'],
                    "thumb" =>$bookingInfo['thumb'],
                    "price" =>$bookingInfo['price']
                );
                $orderName = '在线预约:';
                break;
        }

        
        //获取余额
        $balance = $Account->fetchBalance();
        $orderAmount = $proInfo['price'] - $balance;

        //创建订单
        $orderNum = time() . str_pad($memberId, 7, 0, STR_PAD_LEFT) . str_pad(rand(1, 999), 3, 0, STR_PAD_LEFT);
        $goodsData = array(array(
            "pro_id" => $proid,
            "pro_type" => $type,
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
            "order_type" => $type,
            "amount" => $orderAmount,
            "goods" => serialize($goodsData)
        );
        
        if ($type == 1 || $type == 2) {//课程和专栏有讲师ID
            $orderData['teacher_id'] = $proInfo['teacher_id'];
        }

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
                    "errorCode" => 10000,
                    "errorMsg" => "购买成功" 
                );
                $model->commit();
            }else {
                $backData=array(
                    "errorCode" => 10001,
                    "errorMsg" => "系统繁忙,请稍后再试" 
                );
                $model->rollback();
            }
            $this->ajaxReturn($backData);
        }else {
            //预支付
            $orderInsert = $orderModel->data($orderData)->fetchSql(false)->add();

            //创建统一下单
            $backUrl = "http://www.xinzhinetwork.com/api.php/Wxpay/index";//支付成功回调地址
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
            $this->ajaxReturn($backData);
        }
        

    }


    /** 
     * buy present
     */
    public function buypresent(){

        $memberId = $this->uid;
        //1.2获取
        $proId = I("post.proid");
        $orderType = I("post.type");
        $proType = I("post.protype");
        $proModelName = $proType ==1 ? "Columnist" : "Course";
        $proInfo = M($proModelName)->field("title,price,thumb")->where("id=$proId")->find(); 

        $orderName = "购买赠送礼品包";
        $orderNum = time() . str_pad($memberId, 7, 0, STR_PAD_LEFT) . str_pad(rand(1, 999), 3, 0, STR_PAD_LEFT);
        $backUrl = "http://www.xinzhinetwork.com/api.php/Wxpay/present/";//支付成功回调地址

        //1.3 获取账户余额,如果金额足够，直接完成购买
        $balance = $Account->fetchBalance();
        $orderAmount = $proInfo['price'] - $balance;
        $orderGoodsData = array(array(
            "pro_id" => $proId,
            "pro_type" => $proType,
            "pro_name" => $proInfo['title'],
            "pro_price" => $proInfo['price'],
            "pro_thumb" => $proInfo['thumb']
        ));
        if($balance >= $proInfo['price']){
            //1.3.1
            $model = M();
            $model->startTrans();
            //member
            $updateData = array(
                "balance"   =>$balance - $proInfo['price']
            );
            $updateBalance = $Account->subBalance($proInfo['price'],$orderName."扣除",$memberId);

            //present
            $insertData = array(
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
                "title"     =>$proInfo['title'],
                "value"     =>$proInfo['price'],
                "thumb"     =>$proInfo['thumb'],
                "url"       =>$url
            );
            $insertData['content'] = serialize($giftContent);
            $presentInsert = M("Present")->add($insertData);
            //order

            $orderData = array(
                "order_num" => $orderNum,
                "member_id" => $memberId,
                "order_type"  => $orderType,
                "amount"    => $proInfo['price'],
                "goods"     => serialize($orderGoodsData),
                "status"    =>2,
                "pay_way"   =>"余额",
                "pay_time"  =>date("YmdHis",time())
            );
            $orderInsert = M("Orders")->data($orderData)->add();
            if($updateBalance && $presentInsert && $orderInsert){
                $model->commit();
                $backData = array(
                    "errorCode" => 10000,
                    "errorMsg" => "购买成功",
                    "key"       => $insertData['secret']
                );
            }else {
                $model->rollback();
                $backData = array(
                    "errorCode" => 10001,
                    "errorMsg" => "系统繁忙，请稍后再试"
                );
            }
            $this->ajaxReturn($backData);
        }else {
            $model = M();
            $model->startTrans();
            //1.3.2 创建订单
            $orderData = array(
                "order_num" => $orderNum,
                "member_id" => $memberId,
                "order_type"  => $orderType,
                "amount"    => $orderAmount,
                "goods"     => serialize($orderGoodsData),
                "status"    =>1
            );
            $orderInsert = M("Orders")->data($orderData)->add();


            //1.3.4 创建统一下单
            $payMentXml = $this->tyxd($orderName, $orderNum, $orderAmount,$backUrl);
            $payMentObj = simplexml_load_string($payMentXml, null, LIBXML_NOCDATA);
            if ($payMentObj->return_code != 'SUCCESS'){
                $backData = array(
                    "errorCode" => 13004,
                    "errorMsg" => "统一下单创建错误",
                    "info" => $payMentObj
                );
                $model->rollback();
                $this->ajaxReturn($backData);
            }

            if(!$orderInsert){
                $backData = array(
                    "errorCode" => 13003,
                    "errorMsg" => "数据写入错误"
                );
                $model->rollback();
                $this->ajaxReturn($backData);
            }

            
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
            $backData = array(
                "errorCode" => 10000,
                "errorMsg" => "success",
                'payMent' => $resInfo,
                "key"   =>md5($orderNum)
            );

            $model->commit();

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