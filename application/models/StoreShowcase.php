<?php

/**
 * @name ShowcaseModel
 * @desc 商城店铺操作类
 */
class StoreShowcaseModel {

    const SHOWCASE_LIST = 'showcase/getlist';  
    const SHOWCASE_DETAIL = 'showcase/detail';
    const SHOWCASE_PASS = 'showcase/pass';
    const SHOWCASE_UNPASS = 'showcase/unpass';
    const SHOWCASE_CREATE = 'showcase/create';
    const PAYMENT_SELLER_ACCOUNT = 'api/shared_merchant/';

    private $showcase_status = array(
        '0' => '草稿',
        '1' => '待审核',
        '2' => '已驳回审核',
        '3' => '已通过审核',
        '4' => '已过期'
    );

    
    /**
     * 店铺列表
     */
    public function getlist($params) {
        if(empty($params)){
            return FALSE;
        }
        $result = Sapi::request(self::SHOWCASE_LIST, $params);
        return $this->format_showcase_batch($result);
    }
    
    /**
     * 创建店铺
     */
    public function create($params){
        return Sapi::request(self::SHOWCASE_CREATE, $params, 'POST');
    }

    /**
     * @param $showcase_id
     * @param $showcase_name
     * @return array
     * @desc 通知支付平台
     */
    public function createPaymentSellerAccount($showcase_id, $showcase_name){
        $url = PAYMENT_HOST.self::PAYMENT_SELLER_ACCOUNT;
        $params['merchant_id'] = $showcase_id;
        $params['channels'] = 'WECHAT,JDPAY';
        $params['system_en'] = 'PLATFORM';
        $params['merchant_name'] = $showcase_name;
        $result = Curl::request($url, $params, 'post');
        return $result;
    }
    
    /**
     * 通过认证
     */
    public function pass($showcase_id) {
        if(!$showcase_id){
            return FALSE;
        }
        $params['showcase_id'] = $showcase_id;
        return Sapi::request(self::SHOWCASE_PASS, $params, "POST");
    }
    
    /**
     * 店铺认证驳回
     */
    public function unpass($showcase_id, $refuse_reason) {
        if(!$showcase_id || !$refuse_reason){
            return FALSE;
        }
        $params['showcase_id'] = $showcase_id;
        $params['refuse_reason'] = $refuse_reason;
        return Sapi::request(self::SHOWCASE_UNPASS, $params, "POST");
    }
    
    /**
     * 店铺简介
     */
    public function getInfoById($showcase_id) {
        if (!$showcase_id) {
            return false;
        }
        $params['showcase_id'] = $showcase_id;
        $result = Sapi::request(self::SHOWCASE_DETAIL, $params);

        return $this->format_showcase_struct($result);
    }

    /*
     * 格式化数据
     */
    public function tidy_approve($approve) {
        if(!$approve){
            return array();
        }
        $s['user_id'] = $approve['user_id'];
        $s['realname'] = $approve['realname'];
        $s['intro'] = $approve['intro'];
        $s['mobile'] = $approve['mobile'];
        $s['wechat'] = $approve['wechat'];
        $s['id_num'] = $approve['id_num'];
        $s['id_photo'] = $approve['id_photo'];
        $s['com_name'] = $approve['com_name'];
        $s['com_number'] = $approve['com_number'];
        $s['com_reg_num'] = $approve['com_reg_num'];
        $s['com_type'] = $approve['com_type'];
        $s['com_scope'] = $approve['com_scope'];
        $s['com_scope_pro'] = $approve['com_scope_pro'];
        $s['com_expire'] = date('Y-m-d', strtotime($approve['com_expire']));
        $s['create_time'] = $approve['create_time'];
        $s['status_person'] = $approve['status_person'];
        $s['status_com_name'] = $this->showcase_status[$approve['status_com']];
        $s['status_com'] = $approve['status_com'];
        $s['register_branch'] = $approve['register_branch'];
        $s['com_id_pic1'] = $approve['com_id_pic1'];
        $s['com_id_pic2'] = $approve['com_id_pic2'];
        $s['com_id_pic3'] = $approve['com_id_pic3'];
        $s['com_id_pic4'] = $approve['com_id_pic4'];
        $s['com_id_pic5'] = $approve['com_id_pic5'];
        $s['com_id_pic6'] = $approve['com_id_pic6'];
        $s['com_id_pic7'] = $approve['com_id_pic7'];
        $s['com_register_address'] = $approve['com_register_address'];
        
        return $s;
    }
    
    /*
     * 格式化数据
     */
    public function tidy($showcase) {
        $s['showcase_id'] = $showcase['showcase_id'];
        $s['user_id'] = $showcase['user_id'];
        $s['name'] = $showcase['name'];
        $s['nickname'] = $showcase['nickname'];
        $s['signature'] = $showcase['signature'];
        $s['alias'] = $showcase['alias'];
        $s['appid'] = $showcase['appid'];
        $s['appsecret'] = $showcase['appsecret'];
        $s['logo'] = $showcase['logo'];
        $s['intro'] = $showcase['intro'];
        $s['status_person'] = $showcase['status_person'];
        $s['status_com'] = $showcase['status_com'];
        $s['status_com_name'] = $this->showcase_status[$showcase['status_com']];
        $s['block'] = $showcase['block'];
        $s['ctime'] = $showcase['ctime'];
        return $s;
    }

    public function format_showcase_struct($data) {
        if ($data === false) {
            return false;
        }
        if (empty($data)) {
            return array();
        }

        return $this->tidy($data);
    }

    public function format_showcase_batch($datas) {
        if ($datas === false) {
            return false;
        }
        if (empty($datas)) {
            return array();
        }
        foreach ($datas['showcases'] as &$data) {
            $data = $this->tidy($data);
        }
        return $datas;
    }

}
