<?php
/**
 * @name: coupon.php
 * @time: 2016-06-20 下午2:28
 * @author: liuxuefeng
 * @desc: 生成兑换码
 */


class CdkeyModel {

    const CDKEY_CREATE = 'cdkey/add';  //创建
    const CDKEY_LIST   = 'cdkeylist/getlistofouter'; //获取批次列表
    const CDKEY_COUNT  = 'cdkeylist/getlistcount';
    
    /**
     * 生成优惠券并提交到数据库
     */
    public function addCdkey($params) {
        if(empty($params)){
            return FALSE;
        }
        $result = Cdkey::request(self::CDKEY_CREATE, $params, "POST");
        if($result === FALSE){
            return FALSE;
        }
        return $result;
    }

    /**
     * 获取兑换码批次列表
     */
    public function getListOfCdkey($params) {
        $result = Cdkey::request(self::CDKEY_LIST, $params, "GET");
        if($result === FALSE){
            return FALSE;
        }
        return $result;
    }

    /**
     * 导出兑换码
     */
    public function export($params) {
        $result = Cdkey::request(self::CDKEY_COUNT, $params, "POST");

        return $result;
    }

    /**
     * 操作日志
     */
    public function cdkeyLog($action, $data) {
        $params = [
            'user'    => $_SESSION['a_user']['user'],  //账号
            'name'    => $_SESSION['a_user']['name'],  //用户名
            'user_ip' => $_SERVER['REMOTE_ADDR'],   //IP地址
            'action'  => $action,
            'params'  => $data,
            'time'    => date('Y-m-d H:i:s', time())
        ];

        error_log(var_export(json_encode($params, JSON_UNESCAPED_UNICODE), 1), 3, '/data/logs/cdkey/action.log');
    }

}