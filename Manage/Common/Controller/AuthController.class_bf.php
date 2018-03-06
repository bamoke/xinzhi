<?php
/**
 * Created by PhpStorm.
 * User: joy.wangxiangyin
 * Date: 2016/9/14
 * Time: 11:52
 */

namespace Home\Common\Controller;

use Think\Controller;

class AuthController extends Controller
{
    static $nodeList;

    /**
     * 权限类初始化，检测用户是否登录以及访问权限,创建用户菜单
     */
    function _initialize()
    {
        if ($this->_checkLogin()) {
            $uid = 1 == $_SESSION['issuper'] ? "" : $_SESSION['uid'];
            $this->_create_nav($uid);
            var_dump($_SESSION);
//            $this->_check_auth($uid);

        };
    }

    /**
     * 检测是否登录
     * @return bool
     */
    protected function _checkLogin()
    {
        if (!isset($_SESSION['uid'])) {
            $this->error("请先登录!", U("Main/login"));
            return false;
        } else {
            return true;
        }
    }


    /*
     * 获取用户所属的角色列表
     * @param int
     * @return string | null
     * */
    protected function _get_roles($uid)
    {
        $rolesArr = array();
        $model = M("AuthRoleUser");
        $roles = $model->field("role_id")->where(array("uid" => $uid))->select();
        if (is_array($roles)) {
            foreach ($roles as $key => $val) {
                $rolesArr[] = $val['role_id'];
            }
            return join(",", $rolesArr);
        }
        return null;
    }


    /*
     * 获取角色权限节点ID列表
     * @param  array role_id
     * @return array
     * */
    protected function _get_node_id($role)
    {
        static $rulesIdArr = array();
        $model = M("AuthRole");
        $where = array(
            "id" => array("in", $role),
            "status" => array("eq", 1)
        );
        $rules = $model->field("rules")->where($where)->select();
        foreach ($rules as $key => $val) {
            $rulesIdArr = array_merge($rulesIdArr, explode(",", trim($val['rules'], ",")));
        }
        return array_unique($rulesIdArr);
    }

    /*
     * 获取所有权限节点信息
     * @param  int
     * @return array
    */
    protected function _get_auth_node($uid=null)
    {
        if (isset($_SESSION['node_arr'])) return $_SESSION['node_arr'];
        $model = M("AuthRule");
        if($uid === null) {
            $where = array(
                "status" => array("eq", 1)
            );
        }else {
            $roles = $this->_get_roles($uid);
            if (is_null($roles)) {
                return array();
            }
            $nodeId = $this->_get_node_id($roles);
            $where = array(
                "id" => array("in", join(",", $nodeId)),
                "status" => array("eq", 1)
            );
        }
        $nodeArr = $model->where($where)->select();
        return $_SESSION['node_arr'] = $nodeArr;
    }


    /*
     * 获取访问节点条件
     * @param array
     * @return array
     * */
    protected function _get_auth_rules($nodeArr)
    {
        $auth_rules = array();
        foreach ($nodeArr as $key => $val) {
            $auth_rules[] = strtolower($val['url']);
        }
        $_SESSION['auth_rules'] = $auth_rules;
        return $auth_rules;
    }


    /*
     * 创建主导航菜单
     * */
    protected function _create_nav($uid=null)
    {
        $node = $this->_get_auth_node($uid);
        $navHtml = '';
        $curContro = strtolower(CONTROLLER_NAME);
        foreach ($node as $key => $val) {
            if (0 == $val['parent_id']) {
                $controName = explode("/",strtolower($val['url']));
                $className = in_array($curContro,$controName)? "cur" : "";
                $navHtml .= '<li class="'.$className.'"><a href="'.U($val['url']).'"><i class="icon '.$val['icon'].'"></i>'.$val['title'].'</a><i class="arrow"></i></li>';
            }
        }
        $this->assign("sideNav", $navHtml);
    }


    /*
     * 验证访问权限
     * @param int
     * @return bool
     * */
    protected function _check_auth($uid=null)
    {
        if($uid === null) return true;

        $url = strtolower(CONTROLLER_NAME . "/" . ACTION_NAME);
        $nodeArr = $this->_get_auth_node($uid);
        $auth_rules = $this->_get_auth_rules($nodeArr);

        if (in_array($url, $auth_rules)) {
            return true;
        }
        $this->error("没有访问权限");
        return false;
    }


}