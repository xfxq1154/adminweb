<?php

/**
 * @name WelcomeController
 * @author why
 * @desc 欢迎页控制器
 */
class WelcomeController extends Base{
    
    use Trait_Layout;
    
    public $wel_model;
    
    public function init(){
        $this->initAdmin();
    }
    
    /**
     * 首页
     */
    public function indexAction(){
        $this->checkLogin();
//        $this->checkRole();
        $this->layout('welcome/index.phtml');
    }
}
