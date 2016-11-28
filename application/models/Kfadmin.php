<?php
/**
 * @name: Kfadmin.php
 * @time: 2016-07-01 上午11:45
 * @author: liuxuefeng
 * @desc:
 */



class KfadminModel {

    const USER_LIST  = 'register/getlist';  //获取用户列表
    const USER_ADD   = 'register/add';      //添加用户
    const USER_EDIT  = 'register/edit';     //编辑用户
    const USER_INFO  = 'register/userinfo';     //获取用户信息
    const USER_RESET_PASS = 'register/resetpass'; //重置密码
    const AUTH_LIST  = 'auth/getlist';      //获取权限列表
    const AUTH_ONE   = 'auth/getauthbyid';  //获取一个权限
    const AUTH_PARENT= 'auth/getauthparent';    //获取所有父权限
    const AUTH_ADD   = 'auth/add';          //添加一个权限
    const AUTH_EDIT  = 'auth/edit';         //编辑权限
    const ROLE_LIST  = 'role/getlist';     //获取身份列表

    private $user_status = [ 1 => '正常', 2 => '冻结'];

    /**
     * 获取客服列表
     * @param array $params
     * @return array|bool
     */
    public function getUserList($params = array()) {
        $result = Kfapi::request(self::USER_LIST, $params, "GET");
        if($result === FALSE){
            return FALSE;
        }

        $result['data'] = $this->_format_user_list($result['data']);
        return $result;
    }

    /**
     * 获取权限列表
     * @param array $params
     * @return array|bool
     */
    public function getAuthList($params = array()) {
        $result = Kfapi::request(self::AUTH_LIST, $params, "GET");
        if($result === FALSE){
            return FALSE;
        }
        return $result;
    }

    /**
     * 添加用户
     * @param array $params
     * @return array|bool
     */
    public function addUser($params = array()) {
        $result = Kfapi::request(self::USER_ADD, $params, "POST");
        if($result === FALSE){
            return FALSE;
        }
        return $result;
    }

    /**
     * 获取用户信息
     * @param $params
     * @return array|bool
     */
    public function getUserInfo($params) {
        $result = Kfapi::request(self::USER_INFO, $params, "GET");
        if($result === FALSE){
            return FALSE;
        }
        return $result;
    }

    /**
     * 修改账户信息
     * @param $params
     * @return array|bool
     */
    public function edit($params) {
        $result = Kfapi::request(self::USER_EDIT, $params, "POST");
        if($result === FALSE){
            return FALSE;
        }
        return $result;
    }

    /**
     * 身份列表
     * @param array $params
     * @return array|bool
     */
    public function getRoleList($params = array()) {
        $result = Kfapi::request(self::ROLE_LIST, $params, "GET");
        if($result === FALSE){
            return FALSE;
        }
        return $result;
    }

    /**
     * 获取单条权限信息
     * @param $params
     * @return array|bool
     */
    public function getAuthById($params) {
        $result = Kfapi::request(self::AUTH_ONE, $params, "GET");
        if($result === FALSE){
            return FALSE;
        }
        return $result;
    }

    /**
     * 获取所有父级权限
     * @param array $params
     * @return array|bool
     */
    public function getAuthParent($params = array()) {
        $result = Kfapi::request(self::AUTH_PARENT, $params, "GET");
        if($result === FALSE){
            return FALSE;
        }
        return $result;
    }

    /**
     * 添加权限
     * @param $params
     * @return array|bool
     */
    public function addAuth($params) {
        $result = Kfapi::request(self::AUTH_ADD, $params, "POST");
        if($result === FALSE){
            return FALSE;
        }
        return $result;
    }

    /**
     * 修改权限
     * @param $params
     * @return array|bool
     */
    public function updateAuth($params) {
        $result = Kfapi::request(self::AUTH_EDIT, $params, "POST");
        if($result === FALSE){
            return FALSE;
        }
        return $result;
    }

    /**
     * 格式化获取用户列表数据
     * @param $data
     * @return mixed
     */
    private function _format_user_list($data) {
        $role_list = $this->getRoleList();
        foreach ($data as $k => $v) {
            foreach ($role_list as &$value) {
                if ($v['role'] == $value['id']) {
                    $data[$k]['role'] = $value['name'];
                }
            }
            $data[$k]['status_cn'] = $this->user_status[$data[$k]['status']];
        }
        return $data;
    }

}