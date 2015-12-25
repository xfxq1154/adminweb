<?php

/**
 * @name OrderModel
 * @desc 订单操作类
 */
class BpShowcaseModel {

    use Trait_Api;

    const SHOWCASE_LIST = 'showcase/getlist';  
    const SHOWCASE_DETAIL = 'showcase/detail';
    const SHOWCASE_UPDATE = 'showcase/update';
    const SHOWCASE_DELETE = 'showcase/delete';
    const SHOWCASE_BLOCK = 'showcase/block';
    const SHOWCASE_UNBLOCK = 'showcase/unblock';
    const SHOWCASE_PASS = 'showcase/pass';
    const SHOWCASE_UNPASS = 'showcase/unpass';
    const SHOWCASE_CREATE = 'showcase/create';
    const PAYMENT_SELLER_ACCOUNT = 'api/accounts/';
    
    
    const SHOWCASE_UPGRADESUCCESS ='showcase/upgradesuccess';
    const SHOWCASE_UPGRADEFAIL ='showcase/upgradefail';
    const SHOWCASE_APPROVE_DETAIL = 'showcase/approve_detail';
    private $_error = null;

    
    
    private $showcase_status = array(
        '0' => '草稿',
        '1' => '待审核',
        '2' => '已驳回审核',
        '3' => '已通过审核'
    );
    
    
    /**
     * 店铺申请认证
     */
    public function approve_detail($showcase_id){
        if(!$showcase_id){
            return FALSE;
        }
        $params['showcase_id'] = $showcase_id;
        $result = Sapi::request(self::SHOWCASE_APPROVE_DETAIL, $params);
        return $this->tidy_approve($result);
    }
    
    /**
     * 店铺列表
     */
    public function getList($params) {
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
        $result = Sapi::request(self::SHOWCASE_CREATE, $params, 'POST');
        if($result === FALSE){
            $this->_setError();
        }
        return $result;
    }
    
    /**
     * 通知支付平台
     */
    public function createPaymentSellerAccount($showcase_id){
        $url = PAYMENT_HOST.self::PAYMENT_SELLER_ACCOUNT;
        $params['user_id'] = $showcase_id;
        $params['channels'] = 'WECHAT,JDPAY';
        $params['sys_code'] = 'PLATFORM';
        $result = Curl::request($url, $params, 'post');
        return $result;
    }
    
    /**
     * 冻结店铺
     */
    public function block($params) {
        if(empty($params)){
            return FALSE;
        }
        $result = Sapi::request(self::SHOWCASE_BLOCK, $params, "POST");
        return $result;
    }
    
    /**
     * 解冻店铺
     */
    public function unblock($params) {
        if(empty($params)){
            return;
        }
        return Sapi::request(self::SHOWCASE_UNBLOCK, $params, "POST");
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
        $s['com_expire'] = $approve['com_expire'];
        $s['create_time'] = $approve['create_time'];
        $s['status_person'] = $approve['status_person'];
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
        $s['logo'] = $showcase['logo'];
        $s['intro'] = $showcase['intro'];
        $s['status_person'] = $showcase['status_person'];
        $s['status_person_name'] = $this->showcase_status[$showcase['status_person']];
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
    
    private function _setError(){
        $error_info = Sapi::getError();
        $code = $error_info['code'];
        switch ($code){
            case 10006:
                $this->_error = 10002;
                break;
            case 10007:
                $this->_error = 10001;
                break;
            case 40001:
                $this->_error = 40001;
                break;
            case 40002:
                $this->_error = 40002;
                break;
            case 40003:
                $this->_error = 40003;
                break;
            case 40004:
                $this->_error = 40004;
                break;
            default :
                $this->_error = 10000;
                break;
        }
    }
    
    public function getError(){
        return $this->_error;
    }

}
