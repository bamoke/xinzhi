<?php
/**
 * Created by PhpStorm.
 * User: joy.wangxiangyin
 * Date: 2017/6/26
 * Time: 16:59
 */

namespace Manage\Controller;

use Manage\Common\Controller\AuthController;

class TestsController extends AuthController
{


    public function index()
    {
        $curModel = M("TestsList");
        // paging set
        $count = $curModel->count();
        $page = new \Think\Page($count, 15);
        $page->setConfig('next', '下一页');
        $page->setConfig('prev', '上一页');
        $paging = $page->show();

        $where = "1 = 1";
        $testList = $curModel
            ->alias("T")
            ->field("T.*,(select name from x_main_cate where id=T.cate_id) as cate_name")
            ->where($where)
            ->order('id desc')->limit($page->firstRow . "," . $page->listRows)->select();
        $outData = array(
            'list' => $testList,
            'paging' => $paging
        );
        $this->assign('output', $outData);
        $this->display();
    }

    /***编辑 View**/
    public function edit($id)
    {
        $testsInfo = M("TestsList")->where(array('id' => $id))->find();
        $cateModel = M('MainCate');
        $cateList = $cateModel->where(array('identification' => 'examination', 'pid' => array('neq', 0)))->select();
        $outData = array(
            'info' => $testsInfo,
            "script" => CONTROLLER_NAME . "/main",
            "cateList" => $cateList
        );
        $this->assign('output', $outData);
        $this->assign('pageName', "编辑试卷");
        $this->display();

    }

    /***View add**/
    public function add()
    {
        $cateModel = M('MainCate');
        $cateList = $cateModel->where(array('identification' => 'examination', 'pid' => array('neq', 0)))->select();
        $outData = array(
            "script" => CONTROLLER_NAME . "/main",
            "cateList" => $cateList
        );
        $this->assign('output', $outData);
        $this->assign('pageName', "添加试卷");
        $this->display();
    }


    /****VIEW 题库****/
    public function lib()
    {
        $curModel = M("TestsQuestion");
        // paging set
        $count = $curModel->count();
        $page = new \Think\Page($count, 15);
        $page->setConfig('next', '下一页');
        $page->setConfig('prev', '上一页');
        $paging = $page->show();

        $where = "1 = 1";
        $testList = $curModel
            ->alias("T")
            ->field('T.id,T.ask,(select name from x_main_cate where id=T.question_cate) as question_cate_name,CASE T.answer_type when 1 then "单选" when 2 then "多选" else "判断" end answer_type_name')
            ->where($where)
            ->order('T.id desc')
            ->limit($page->firstRow . "," . $page->listRows)
            ->select();
        $outData = array(
            "script" => CONTROLLER_NAME . "/main",
            'list' => $testList,
            'paging' => $paging
        );
        $this->assign('output', $outData);
        $this->display();
    }

    /***VIEW add question***/
    public function add_question()
    {
        $cateModel = M('MainCate');
        $cateList = $cateModel->where(array('identification' => 'examination', 'pid' => array('neq', 0)))->select();
        $outData = array(
            "script" => CONTROLLER_NAME . "/main",
            "cateList" => $cateList
        );
        $this->assign('output', $outData);
        $this->assign('pageName', "添加试题");
        $this->show();
    }

    /***VIEW edit question***/
    public function edit_question($id)
    {
        $cateModel = M('MainCate');
        $cateList = $cateModel->where(array('identification' => 'examination', 'pid' => array('neq', 0)))->select();
        $questionInfo = M("TestsQuestion")->where(array("id" => $id))->find();
        $info = $questionInfo;
        $info['answer'] = unserialize($questionInfo['answer']);
        $info['answerInputType'] = $questionInfo['answer_type'] == 2 ? "checkbox" : "radio";
        $outData = array(
            "script" => CONTROLLER_NAME . "/main",
            "cateList" => $cateList,
            "info" => $info
        );
        $this->assign('output', $outData);
        $this->assign('pageName', "编辑试题");
        $this->show();
    }

    /***action ****/
    public function a_update($id)
    {
        if (IS_POST) {
            $backData = array();
            $model = D("TestsList");
            $result = $model->create($_POST);
            if ($result) {
                $update = $model->where('id=' . $id)->fetchSql(false)->save();
                if ($update !== false) {
                    $backData['status'] = 1;
                    $backData['msg'] = $update === 0 ? "数据没有变动" : "修改成功";
                    $backData['jump'] = U('index');
//                    $backData['data'] = $result;

                } else {
                    $backData['status'] = 0;
                    $backData['msg'] = $model->getError();
                }

            } else {
                $backData['status'] = 0;
                $backData['msg'] = $model->getError();
            }


            $this->ajaxReturn($backData);

        }
    }


    public function a_add()
    {
        if (IS_POST) {
            $backData = array();
            $model = M("TestsList");
            $result = $model->create($_POST);

            if ($result) {
                $add = $model->fetchSql(false)->add();
                if ($add) {
                    $backData['status'] = 1;
                    $backData['msg'] = "添加成功";
                    $backData['jump'] = U('index');

                } else {
                    $backData['status'] = 0;
                    $backData['msg'] = $model->getError();
                }

            } else {
                $backData['status'] = 0;
                $backData['msg'] = $model->getError();
            }


            $this->ajaxReturn($backData);

        }
    }

    /***试卷详情***/
    function question($tid)
    {
        $questionModel = M("TestsQuestion");
        $questionList = $questionModel
            ->field('id,ask,CASE answer_type when 1 then "单选" when 2 then "多选" else "判断" end answer_type_name')
            ->where(array('tests_id' => $tid))
            ->select();
        $testsInfo = M("TestsList")->field('id,title,status')->where(array('id' => $tid))->find();
        $outData = array(
            "script" => CONTROLLER_NAME . "/main"
        );
        $this->assign('output', $outData);
        $this->assign('questionList', $questionList);
        $this->assign('testInfo', $testsInfo);
        $this->assign('pageName', "试卷详情");
        $this->display();
    }


    /***添加测试题***/
    function a_add_question()
    {
        if (IS_POST) {
            $curModel = M("TestsQuestion");
            $insertData = I('post.');
            $insertData['answer'] = serialize(I("post.answer"));
            $insertData['correct'] = implode(",", I('post.correct'));
            if ($curModel->create($insertData)) {
                $insert = $curModel->add();
                if ($insert) {
                    $backData = array(
                        "status" => 1,
                        "msg" => "添加成功",
                        "jump" => U("lib")
                    );
                    if (empty($_POST['tests_id'])) {
                        $backData['jump'] = U("lib");
                    } else {
                        $backData['jump'] = U("question", array('tid' => I("post.tests_id")));
                    }
                } else {
                    $backData = array(
                        "status" => 0,
                        "msg" => "数据保存错误"
                    );
                }
            } else {
                $backData = array(
                    "status" => 0,
                    "msg" => "数据创建错误"
                );
            }
            $this->ajaxReturn($backData);
        }

    }

    /***修改测试题***/
    public function a_update_question()
    {
        if (IS_POST) {
            $curModel = M("TestsQuestion");
            $saveData = I('post.');
            $saveData['answer'] = serialize(I("post.answer"));
            $saveData['correct'] = implode(",", I('post.correct'));
            if ($curModel->create($saveData)) {
                $update = $curModel->save();
                if ($update !== false) {
                    $backData = array(
                        "status" => 1,
                        "msg" => "修改成功"
                    );
                } else {
                    $backData = array(
                        "status" => 0,
                        "msg" => "数据保存错误"
                    );
                }
            } else {
                $backData = array(
                    "status" => 0,
                    "msg" => "数据创建错误"
                );
            }
            $this->ajaxReturn($backData);
        }
    }

    /***删除测试题*/
    public function a_del_question($id)
    {
        $result = M("TestsQuestion")->delete($id);
        if ($result) {
            $backData = array(
                "status" => 1,
                "msg" => "修改成功"
            );
        } else {
            $backData = array(
                "status" => 0,
                "msg" => "数据错误,请稍后再试或联系技术人员"
            );
        }
        $this->ajaxReturn($backData);
    }


    /*=============================*/
}