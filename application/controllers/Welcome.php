<?php

/**
 * @name WelcomeController
 * @author why
 * @desc 欢迎页控制器
 */
class WelcomeController extends Base{
    
    use Trait_Layout;
    
    public $sdata_model;
    
    public function init(){
        $this->initAdmin();
        $this->sdata_model = new SdataModel();
    }
    
    /**
     * 首页
     */
    public function indexAction(){
        $this->checkLogin();
//        $this->checkRole();
        if($_POST){
            $time1 = $this->getRequest()->getPost('time1');
            $params['start_created'] = $time1;
            $params['end_created'] = date('Y-m-d', strtotime($time1.'+1 day'));
            $result = $this->sdata_model->getList($params);
            exit;
        }
        $this->assign('time', date('Y-m-d', time()));
        $this->layout('welcome/index.phtml');
    }
}
