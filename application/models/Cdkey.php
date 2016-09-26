<?php
/**
 * @name: coupon.php
 * @time: 2016-06-20 下午2:28
 * @author: liuxuefeng
 * @desc: 生成兑换码
 */


class CdkeyModel {

    const CDKEY_CREATE = 'create/exec';  //创建
    const CDKEY_LIST = 'search/getlist'; //获取批次列表
    const CDKEY_DETAIL_LIST = 'searchdetail/getlist';
    const CDKEY_INFO = 'searchdetail/info'; //查询cdkey详情
    const CDKEY_NULLIFY_EXEC = 'nullify/exec'; //作废兑换码
    const CDKEY_NULLIFY_RESTORE = 'nullify/restore'; //恢复已经作废的兑换码
    const CDKEY_LOG = 'log/write'; //写入日志
    
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
        $result = Cdkey::request(self::CDKEY_DETAIL_LIST, $params, "POST");

        return $result;
    }

    /**
     * 作废兑换码
     */
    public function nullify($params) {
        $result = Cdkey::request(self::CDKEY_NULLIFY_EXEC, $params, "POST");

        return $result;
    }

    /**
     * 恢复已经作废的兑换码
     */
    public function restore($params) {
        $result = Cdkey::request(self::CDKEY_NULLIFY_RESTORE, $params, "POST");

        return $result;
    }

    /**
     * 查询兑换码状态
     */
    public function info($params) {
        $result = Cdkey::request(self::CDKEY_INFO, $params, "POST");

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
            'params'  => json_encode($data, 1),
            'time'    => date('Y-m-d H:i:s', time())
        ];

        Cdkey::request(self::CDKEY_LOG, $params, "POST");
    }

}