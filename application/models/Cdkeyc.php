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
    const CDKEY_COUNT = 'cdkeylist/getlistcount';
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
        if(empty($params)){
            return FALSE;
        }
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
        if(empty($params)){
            return FALSE;
        }
        $result = Cdkey::request(self::CDKEY_COUNT, $params, "POST");
        if($result === FALSE){
            return FALSE;
        }
        return $result;
    }

    


}