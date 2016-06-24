<?php

abstract class Base extends Yaf_Controller_Abstract {

    public $userInfo;
    public $admin, $adminNav, $adminUser, $adminGroup;
    
    /**
     * 后台操作入口
     */
    public function initAdmin(){
        $this->admin = new AdminModel();
        $this->adminUser = new AdminUserModel();
        $this->adminGroup = new AdminGroupModel();
        $this->adminNav = new AdminNavModel();
        $this->checkLogin();
    }


    /**
     * 模板变量赋值
     * @param string $name 变量名
     * @param any $value 变量值
     */
    public function assign($name, $value){
        $this->getView()->assign($name, $value);
    }

    /**
     * 检测 是否登陆
     * @return boolean
     */
    public function isLogin(){
        $this->userInfo();
        if(empty($this->userInfo)){
            return false;
        }
        return TRUE;
    }

    /**
     * 检测后台权限
     */
    public function checkRole($action=''){
        $request = $this->getRequest();
        
        //控制器
        $controller = strtolower($request->controller);
        //动作
        if(empty($action)){
           $action = $request->action; 
        }
        $action = strtolower($action);
        
        
        //获取登陆用户信息
        $userInfo = $this->userInfo();
        $group_id = $userInfo['group'];
        
        //角色权限
        $groupInfo = $this->adminGroup->getGroupById($group_id);
        $groupMenu = ','.$groupInfo['nav'] .','. $groupInfo['menu'].',';
        
        //菜单信息
        $navInfo = $this->adminNav->getNavByRole($controller, $action);
        $navId = ','.$navInfo['id'].',';
        
        $inAjax = false;
        if($_POST) $inAjax = true;
        
        
        //判断用户浏览权限
        if(false === strpos($groupMenu, $navId)){
            if($inAjax){
                 $data = array(
                    "info"=>"无权访问！",
                    "status"=>0,
                    "url"=>"",
                );
                Tools::output($data);
            }
            die('无权访问！');
        }
    }
    /*
     * 检查是否登录，并跳转到 登陆页面
     */
    public function checkLogin($ajax = 0) {
        $login = $_SESSION['a_user'];
        if(empty($login)){
            if($ajax == 0){
                if($_GET['logout'] == 'true'){
                    $this->redirect('/');
                }else{
                    $this->redirect("/user");
                }
            }  else {
                Tools::success('error', '请登录后再进行操作。');
            }
        }
        $this->setUserInfo();
    }

    /**
     * 获取用户信息
     * @return type
     */
    public function userInfo(){
        if(empty($this->userInfo)){
            $this->setUserInfo();
        }
        return $this->userInfo;
    }

    /**
     * 设置用户信息
     * @param array $info
     */
    public function setUserInfo($info = array()){
        $userInfo = $_SESSION['a_user'];
        if(!empty($info)){
            $userInfo['info'] = $info;
            $_SESSION['a_user'] = $userInfo;
        }
        if($userInfo){
            $this->userInfo = $userInfo;
        }
    }
    
    /**
     * 实现 页面跳转 并推出
     * @param type $redirect_url
     */
    public function location($redirect_url){

        $redirect_url = $this->addSpm($redirect_url);
        $this->redirect($redirect_url);
        exit;
    }
    
    /**
     * 添加页面跟踪参数，用户跟踪用户行为
     * @param type $url
     * @return string
     */
    public function addSpm($url){

        if(empty($_GET['spm'])){
            return $url;
        }
        $url_info = parse_url($url);
        if($url_info['query']){
            $http_query = array();
            parse_str($url_info['query'],$http_query);
            if(!isset($http_query['spm'])){
                $url .= '&spm=' . $_GET['spm'];
            }

        }else{
            $url .= '?spm=' . $_GET['spm'];
        }
        return $url;
    }
    
    /**
     * 导出数据
     * @param type $data
     * @param type $head 标题行，如果没有请传1
     */
    static function export($data, $head = '') {
        
        if ($head) {
            header('Content-Type: application/vnd.ms-excel');
            header('Content-Disposition: attachment;filename="'.date("YmdHis").'.xls"');  
            header('Cache-Control: max-age=0');
            if($head != 1){
                echo iconv('utf-8', 'gbk', implode("\t", $head)),"\n";
            }
        }
        foreach ($data as $value) {
            echo iconv('utf-8', 'gbk', implode("\t", $value)),"\n";
        }
    }

    /**
     * @param $data
     */
    public function print_d($data)
    {
        echo "<pre>";
        print_r($data);
    }

}
