<?php

class CrondController extends Yaf_Controller_Abstract{
    
    public $crond;//七牛SDK
    
    public function init(){
        $this->crond = new CrondModel();
    }
    
    /**
     * 七牛音频 转换状态脚本
     */
    public function qiniuAction(){
        $this->crond->qiniu();
        die("ok\n");
    }
    
    public function qiniuiosAction(){
        $this->crond->qiniuios();
        die(date('Y-m-d H:i:s'));
    }
    
    public function topicAudioAction() {
        $this->crond->rsyncAudioEbook();
        die("ok\n"); 
    }
    
    public function pushMsgAction(){
        $this->crond->pushTopicMsg();
        die("ok\n");
    }
    
    public function updateUserPhoneAction(){
        $this->crond->rsyncUserPhone();
        die("ok\n");
    }
    
}
