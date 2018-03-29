<?php
namespace Api\Controller;
use Think\Controller;
class SurveyController extends Controller {
    protected $memberId;
    protected function _initialize(){
        $this->memberId = A("Account")->getMemberId();
    }

    /** 
     * 调查问卷
     * View
     * 
     */
    public function index($id){
        $logWhere = array(
            "survey_id" =>$id,
            "member_id" =>$this->memberId
        );
        $logCount = M("SurveyLog")->where($logWhere)->count();
        if($logCount > 0){
            $backData = array(
                "errorCode" =>10002,
                "errorMsg"  =>"已经参与过此项调查，感谢您的支持！"
            );
            $this->ajaxReturn($backData);
        }

        $surveyInfo = M("Survey")->where("id = $id")->find();

        // $questionListSql = "select * ,(select * from __SURVEY_ANSWER__ ) as answer from __SURVEY_QUESTION__ where s_id=$id";
        $questionList = M("SurveyQuestion")->where("s_id=$id")->order("sort,id")->limit(50)->select();
        $questionIdArr= array();
        foreach($questionList as $key=>$val){
            $questionIdArr[]=$val['id'];
        }
        $questionId = implode(",",$questionIdArr);
        //answer
        $answerWhere = array();
        $answerList = M("SurveyAnswer")->field("id,q_id,name")->where("q_id in (".$questionId.")")->select();
        foreach($questionList as $key=>$val){
            $questionList[$key]['answerList'] = array(); 
            $questionList[$key]['selected'] = false; 
            $questionList[$key]['showError'] = false; 
            $questionList[$key]['selectedAnswer'] = ""; 
            foreach($answerList as $k=>$v){
                if($v['q_id'] == $val['id']){
                    array_push($questionList[$key]['answerList'],$v);
                }
            }
        }
        $backData = array(
            "errorCode" =>10000,
            "errorMsg"  =>"success",
            "surveyInfo"     =>$surveyInfo,
            "questionList"  =>$questionList
        );
        $this->ajaxReturn($backData);
    }

    /** 
     * Action
     */
    public function dopoll(){
        $model = M();
        $model->startTrans();
        $memberId = $this->memberId;
        $id = I("post.id");
        $answerId = I("post.answerid");
        
        // fetch survey info
        $surveyInfo = M("Survey")->field("give_balance")->where("id=$id")->find();
        // update balance
        $updateBalance = true;
        if($surveyInfo['give_balance'] > 0){
            $updateBalance = A("Account")->addBalance($surveyInfo['give_balance'],$memberId);
        }

        //update Survey
        $updateSurveySql = "update __SURVEY__ set partake_num = partake_num +1 where id=$id";
        $updateSurvey = M()->execute($updateSurveySql);

        //update Answer poll num
        $updateAnswerSql = "update __SURVEY_ANSWER__ set poll_num = poll_num + 1 where id in ($answerId)";
        $updateAnswer = M()->execute($updateAnswerSql);
        
        //insert log
        $insertLogData = array(
            "member_id"     =>$memberId,
            "survey_id"     =>$id
        );
        $insertLog = M("SurveyLog")->data($insertLogData)->add();

        




        if($updateSurvey && $updateAnswer && $insertLog){
            $backData = array(
                "errorCode"     => 10000,
                "errorMsg"      => "OK",
                "give_balance"  =>$surveyInfo["give_balance"]
            );
            $model->commit();
        }else {
            $backData = array(
                "errorCode" => 10004,
                "errorMsg" => "系统繁忙,请稍后再试"
            );
            $model->rollback();
        }
        return $this->ajaxReturn($backData);
    }

}