<?php

/**
 * @author why
 * @name TestController
 * @desc 测试号管理
 */
class TestController extends Base{
    
    use Trait_Layout,
        Trait_Pagger;
    
    public $test_model;
    
    public function init(){
        $this->test_model = new TestModel();
    }
    
    public function indexAction(){
        $user_bind = array();
        if (!empty($GLOBALS['testAccount']['openid'])) {
           $user_bind = $this->test_model->getUserBind($GLOBALS['testAccount']['openid']);
        }
        $this->assign('data', $user_bind);
        $this->layout('test/index.phtml');
    }
    
    public function mobileAction(){
        $user = array();
        if (!empty($GLOBALS['testAccount']['mobile'])) {
           $user = $this->test_model->getUser($GLOBALS['testAccount']['mobile']);
        }
        $this->assign('data', $user);
        $this->layout('test/mobile.phtml');
    }
    
    public function deleteAction(){
        if ($this->getRequest()->isPost()) {
            $uid = json_decode($this->getRequest()->getPost('data'), true)['uid'];
            $openid = json_decode($this->getRequest()->getPost('data'), true)['openid'];
            $mobile = json_decode($this->getRequest()->getPost('data'), true)['mobile'];
        } else {
            Tools::output(array('info'=>'非法操作','status'=>0));
        }
        
        if (empty($openid) && empty($mobile)) {
            Tools::output(array('info'=>'参数非法1','status'=>0));
        }
        
        if (!empty($openid)) {
            if (strpos($GLOBALS['testAccount']['openid'], $openid) < 1) {
                Tools::output(array('info'=>'参数非法2','status'=>0));
            }
        }
        
        if (!empty($mobile)) {
            if (strpos($GLOBALS['testAccount']['mobile'], $mobile) < 1 ) {
                Tools::output(array('info'=>'参数非法3','status'=>0));
            }
        }
        
        $res = $this->test_model->delete($uid);
        if($res){
            Tools::output(array('info'=>'删除成功','status'=>1));
        }
        
        Tools::output(array('info'=>'删除失败','status'=>0));
    }
}

