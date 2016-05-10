<?php

/**
 * @name Sms
 * @author wzd
 * @desc 短信发送类
 */
class Sms{

    use Trait_Api;
    
    public $apiSms;//短信通知接口
    const SMS_API = '/api/v1/sms/';
    
    public function __construct() {
        $this->apiSms = $this->getApi("notification");
    }
    
    /**
     * 发送短信接口
     */
    public function sendmsg($message, $phonenumber){
        $api = self::SMS_API;
        $params = array(
            'sys_code' => 6,//系统编码, 3 生活作风 4 得到 6罗辑思维
            'message_content' => $message,
            'phone_num' => $phonenumber,
        );
        
        $rs = $this->apiSms->post($api, $params);
        if($rs){
            return json_decode($rs,true);
        }else{
            return false;
        }
    }
    
}
