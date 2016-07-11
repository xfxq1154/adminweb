<?php
/**
 * @name UserModel
 * @desc 用户操作类
 */

class StoreUserModel {

    const USER_LOGIN_CHECK = 'user/login_check';
    const USER_INFO_BATCH = 'user/getinfo_batch'; //批量获取用户信息
    const USER_INFO = 'user/getinfo';
    const USER_REGISTER = 'user/register';
    const USER_BIND_WECHAT = 'api/bindUserWechat';
    const USER_BIND_MOBILE = 'user/bind_mobile';
    const USER_UPDATE_PWD = 'user/update_pwd';
    const USER_SEARCH = 'user/search';

    /**
     * 获取用户信息
     * @param $params
     * @return array|bool|mixed
     */
    public function getinfo($params) {
        $data = Passport::request(self::USER_INFO, $params);
        return $this->format_user_struct($data);
    }

    /**
     * 批量获取用户信息
     * @param $user_ids
     * @return array|bool|mixed
     */
    public function getinfo_batch($user_ids) {
        $params['uids'] = $user_ids;
        $data = Passport::request(self::USER_INFO_BATCH, $params);
        return $this->format_user_batch($data);
    }

    /**
     * 用户登录检查
     * @param $mobile
     * @param $passwd
     * @return array|bool
     */
    public function login_check($mobile, $passwd) {
        $params['mobile'] = $mobile;
        $params['passwd'] = $passwd;
        return Passport::request(self::USER_LOGIN_CHECK, $params, 'POST');
    }
    
    /**
     * 注册用户
     */
    public function register($params) {
        return Passport::request(self::USER_REGISTER, $params, 'POST');
    }
    
    /**
     * 绑定手机号
     */
    public function bind_mobile($params) {
        return Passport::request(self::USER_BIND_MOBILE, $params, 'POST');
    }
    
    /**
     * 根据手机号查询
     */
    public function search($params){
        return Passport::request(self::USER_SEARCH, $params);
    }
    
    /**
     * 修改密码
     */
    public function update_pwd($params) {
        return Passport::request(self::USER_UPDATE_PWD, $params, 'POST');
    }

    /*
     * 格式化数据
     */
    public function tidy($data){
        $userinfo['id'] = $data['id'];
        $userinfo['nickname'] = $data['nickname'];
        $userinfo['regtime'] = $data['regtime'];
        $userinfo['province'] = $data['info']['province'];
        $userinfo['city'] = $data['info']['city'];
        $userinfo['district'] = $data['info']['district'];
        $userinfo['address'] = $data['info']['address'];
        return $userinfo;
    }
    
    public function format_user_struct($data){
        if ($data === false) {
            return false;
        }
        if (empty($data)) {
            return array();
        }

        return $this->tidy($data);
    }

    public function format_user_batch($datas){
        if ($datas === false) {
            return false;
        }
        if (empty($datas)) {
            return array();
        }

        foreach ($datas as &$data){
            $data = $this->tidy($data);
        }

        return $datas;
    }

    public function getError(){
        return Passport::getErrorMessage();
    }
    
}
