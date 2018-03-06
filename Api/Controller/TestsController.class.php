<?php
/**
 * Created by PhpStorm.
 * User: wetz1
 * Date: 2017/6/12
 * Time: 22:56
 */

namespace Api\Controller;

use Think\Controller;

class TestsController extends Controller
{
    public function index($page = 1)
    {
        $curModel = M("TestsList");
        $mainWhere = array(
            "type" => 2,
            "status" => 1
        );
        $mainList = $curModel->field("id,title,test_num")->where($mainWhere)->order("recommend desc,id desc")->page($page, 15)->select();
        $navList = array();
        if ($page == 1) {
            $navWhere = array(
                "type" => 1,
                "status" => 1
            );
            $navList = $curModel->field("id,title,test_num")->where($navWhere)->order("id asc")->limit(3)->select();
        }
        $backData = array(
            "errorCode" => 10000,
            "errorMsg" => "success",
            "navList" => $navList,
            "mainList" => $mainList
        );
        $this->ajaxReturn($backData);
    }

    /**试卷详情**/
    public function detail($id)
    {
        $testsInfo = M("TestsList")->where(array("id" => $id))->find();
        $questionWhere = array();
        $questionModel = M("TestsQuestion");
        if ($testsInfo['type'] == 2) {
            //如果是固定题
            $questionWhere['tests_id'] = $id;
        } elseif ($testsInfo['type'] == 1) {
            //如果是随机题
            $allId = $questionModel->field('id')->where(array("question_cate" => $testsInfo['cate_id']))->select();
            $allIdArr = array();
            foreach ($allId as $k => $v) {
                $allIdArr[] = $v['id'];
            }
            shuffle($allIdArr);
            $newQuestionID = implode(",", array_slice($allIdArr, 0, $testsInfo['question_num']));
            $questionWhere['id'] = array("in", $newQuestionID);
        }
        $questionList = $questionModel->where($questionWhere)->select();
        //重组数据返回前端所需格式和内容
        $newQuestionData = array();
        foreach ($questionList as $k => $v) {
            $newQuestionData[] = array(
                'id' => $v['id'],
                'type' => $v['answer_type'],
                "ischecked" => false,
                'ask' => $v['ask'],
                'answer' => unserialize($v['answer']),
                'correct' => $v['correct'],
                'analysis' => $v['analysis'],
                'answered' => false,
                'selected' => '',
                'answeredResult' => 0//回答是否正确,0未回答,1正确，2不正确
            );
        }

        //查询是否已经购买
        $havaPurchaset = M("MyGoods")->where(array("type"=>3,"pro_id"=>$id))->find();

        $backData = array(
            "errorCode" => 10000,
            "errorMsg" => "success",
            "havaPurchaset" =>!!$havaPurchaset,
            "totalNum"      =>count($newQuestionData),
            "question" => $newQuestionData,
            "examinationInfo" => array(
                "title"         =>$testsInfo['title'],
                "description"   =>$testsInfo['description'],
                "isfree"        =>!!$testsInfo['isfree'],
                "price"         =>$testsInfo['price']
            )
        );
        $this->ajaxReturn($backData);
    }

    /***
     * 测试结果
     * 1.保存测试记录
     * 2.保存错误题
     * 3.更新试卷测试数
    **/
    public function result(){
        $memberId = A('Account')->getMemberId();
        $model = M();
        $model->startTrans();
        //1.insert log
        $logData = array(
            "member_id"     =>$memberId,
            "tests_id"      =>I('post.tests_id/d'),
            "score"         =>I("post.score")
        );
        $insertLog = M("TestsLogs")->data($logData)->fetchSql(false)->add();

        // 2. insert error answer
        $insertError = true;
        if(!empty(I("post.error_list"))){
            $errorList = explode(",",I('post.error_list'));
            $errorData = array();
            foreach($errorList as $k=>$v){
                 parse_str(str_replace("amp;","",$v),$errorData[$k]);
                $errorData[$k]['member_id'] = $memberId;
            }
            $insertError = M('TestsError')->fetchSql(false)->addAll($errorData);
        }

        // 3. update
        $updateSql = "update __TESTS_LIST__ set `test_num` = test_num + 1 where id=".I('post.tests_id');
        $updateTests = $model->fetchSql(false)->execute($updateSql);
//        var_dump($insertError);
        if($insertLog && $insertError && $updateTests){
            $model->commit();
            $backData = array(
                "errorCode" => 10000,
                "errorMsg" => "success"
            );
        }else {
            $model->rollback();
            $backData = array(
                "errorCode" => 10001,
                "errorMsg" => "数据错误"
            );
        }
        $this->ajaxReturn($backData);

    }
}