<?php
/**
 * @name: Kfadmin.php
 * @time: 2016-07-01 上午11:45
 * @author: liuxuefeng
 * @desc:
 */



class KfadminModel {

    const KF_USER_LIST  = 'register/getList';  //获取用户列表
    const KF_AUTH_LIST  = 'auth/getinfo';
    const KF_GROUP_LIST = 'group/getinfo';
    const KF_ONE_AUTH   = 'auth/getauthbyid';
    const KF_AUTH_PARENT= 'auth/getauthparent';
    const KF_ADD_AUTH   = 'auth/add';
    const KF_UPDATE_AUTH = 'auth/update';

    public function __construct()
    {

    }

    /**
     * 获取客服列表
     * @param array $params
     * @return array|bool
     */
    public function getUserList($params = array()) {
        $result = Kfapi::request(self::KF_USER_LIST, $params, "GET");
        if($result === FALSE){
            return FALSE;
        }
        return $result;
    }

    /**
     * 获取权限列表
     * @param array $params
     * @return array|bool
     */
    public function getAuthList($params = array()) {
        $result = Kfapi::request(self::KF_AUTH_LIST, $params, "GET");
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
    public function getGroupList($params = array()) {
        $result = Kfapi::request(self::KF_GROUP_LIST, $params, "GET");
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
        $result = Kfapi::request(self::KF_ONE_AUTH, $params, "GET");
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
        $result = Kfapi::request(self::KF_AUTH_PARENT, $params, "GET");
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
        $result = Kfapi::request(self::KF_ADD_AUTH, $params, "POST");
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
        $result = Kfapi::request(self::KF_UPDATE_AUTH, $params, "POST");
        if($result === FALSE){
            return FALSE;
        }
        return $result;
    }


}