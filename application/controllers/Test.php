<?php

/**
 * @author why
 * @name TestController
 * @desc 测试号管理
 */
class TestController extends Base{
    
    use Trait_Layout;
    public $test_model;
    
    public function init(){
        $this->test_model = new TestModel();
    }
    
    public function indexAction(){
        $page_no = (int)$this->getRequest()->get('page_no', 1);
        $page_size = (int)$this->getRequest()->get('page_size', 20);
        $openid = $this->getRequest()->getParam('openid');
        $result = $this->test_model->getList($page_no,$page_size,$openid);
        $this->assign('data', $result);
        $this->assign('openid', $openid);
        $this->layout('test/index.phtml');
    }
}

