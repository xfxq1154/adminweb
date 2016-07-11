<?php

/**
 * 商户列表
 *
 * @author yanbo
 */
class ShowcaseController extends Storebase {

    
    const ADMIN = '0'; //店长
    
    public function init() {
        parent::init();
    }
    
    /**
     * 店铺列表
     */
    public function indexAction() {
        $t = (int) $this->getRequest()->get('t');
        $p = (int) $this->getRequest()->getParam('p', 1);
        $block = $this->getRequest()->get('block');
        $kw = $this->getRequest()->get('kw');
        $nickname = $this->getRequest()->get('nickname');
        $showcase_id = $this->getRequest()->get('showcase_id');
        $size = 20;

        $params = array();
        
        switch ($t) {
            case 1:
                $params['status_person'] = 1;
                break;
            case 2:
                $params['status_com'] = 1;
                break;
            case 3:
                $params['status_com'] = 3;
                break;
            case 11:
                $params['block'] = 1;
                break;  
        }
        $params['page_no'] = $p;
        $params['page_size'] = $size;
        $params['block'] = $block > 0 ? $block : '0';
        $params['kw'] = $kw;
        $params['nickname'] = $nickname;
        $params['showcase_id'] = $showcase_id;
        
        $showcasesList = $this->showcase_model->getlist($params);
        $this->assign("list", $showcasesList['showcases']);
        $this->assign('kw', $kw);
        $this->assign('nickname', $nickname);
        $this->assign('id', $showcase_id);
        $this->renderPagger($p, $showcasesList['total_nums'], '/store/showcase/index/p/{p}/t/'.$t, $size);
        $this->layout("showcase/showlist.phtml");
    }
    
    /**
     * 店铺简介
     */
    public function infoAction() {
        $showcase_id = $this->getrequest()->get('id'); 
        $showcases = $this->showcase->getInfoById($showcase_id);
        $this->assign("showcase", $showcases);
        $this->layout('platform/showcase_info.phtml');
    }
    
    /**
     * 创建店铺
     */
    public function createAction(){
        if($_POST){
            $data['name'] = $_POST['showcase_name'];
            $data['phone'] = $_POST['phone'];
            $data['password'] = $_POST['password'];
            $data['nickname'] = $_POST['nickname'];

            //passport 用户注册
            $params['mobile'] = $_POST['phone'];
            $params['passwd'] = $_POST['password'];
            $rs = $this->showcase->register($params);
            if(empty($rs)){
                Tools::output(array('info'=>'用户注册失败','status'=>1));
            }

            $data['user_id'] = $rs['user_id'];
            $resule = $this->showcase->create($data);
            if($resule === FALSE){
                $error = $this->showcase->getError();
                switch ($error){
                    case 40001:
                        $msg = '店铺名称已存在';
                    break;
                    case 40002:
                        $msg = '此用户已创建店铺';
                    break;
                    case 10001:
                        $msg = '缺少参数';
                    break;
                    case 10000:
                        $msg = '系统异常';
                    break;
                }
                Tools::output(array('info'=>$msg,'status'=>1));
            }
            //通知支付平台
            $this->showcase->createPaymentSellerAccount($resule, $data['name']);
            //添加到管理员表
            $clerk_date['user_id'] = $data['user_id'];
            $clerk_date['group_id'] = self::ADMIN;
            $clerk_date['phone'] = $data['phone'];
            $clerk_date['showcase_id'] = $resule;
            $clerk_date['nickname'] = $data['nickname'];
            $clerk_date['realname'] = $data['realname'];
            
            $this->showcase->addClerk($clerk_date);
            
            Tools::output(array('info'=>'创建成功','status'=>1,'url'=>'/bpshowcase'));
        }
        $this->layout('platform/add_showcase.phtml');
    }
    
    /*
     * 重设密码
     */
    public function resetAction(){
        if($_POST){
            $user_id = $_POST['user_id'];
            $pw = $_POST['pw'];
            
            if(!$user_id || !$pw){
                Tools::output(array('info'=>'缺少参数','status'=>1));
            }
            //ucapi 用户详情
            $info = $this->showcase->getInfo(array('uid'=>$user_id));
            if($info['phone']){
                $phone = $info['phone'];
            }else{
                Tools::output(array('info'=>'手机号为空','status'=>1));
            }
            //ucapi 修改密码
            $result = $this->showcase->UpPwd(array('mobile'=>$phone,'newpwd'=>$pw));
            if($result){
                Tools::output(array('info'=>'修改成功','status'=>1, 'url'=>'/bpshowcase/index'));
            }
            Tools::output(array('info'=>'修改失败','status'=>1));
        }
        $name = $this->getRequest()->get('title');
        $user_id = $this->getRequest()->get('user_id');
        $this->assign('title', urldecode($name));
        $this->assign('user_id', $user_id);
        $this->layout('platform/reset.phtml');
    }
    
    
    /**
     * 商户审核
     */
    public function auditingAction() {
        $showcase_id = $this->getrequest()->get('showcase_id');
        if(!$showcase_id){
            Tools::output(['info' => '店铺ID为空', 'status' => 0]);
        }
        $info = $this->showcase->approve_detail($showcase_id);
        $this->assign("info", $info);
        $this->assign("showcase_id", $showcase_id);
        $this->assign("refuse", json_encode($this->getView()->render('platform/showcase_refuse.phtml')));
        $this->layout("platform/showcase_auditing_com.phtml");
        
    }
    
    /**
     * API:驳回
     */
    public function unpassAction() {
        $showcase_id = json_decode($this->getRequest()->getPost('data'), true)['id'];
        $refuse_reason = json_decode($this->getRequest()->getPost('data'), true)['refuse_reason'];
        if(!$showcase_id){
            Tools::output(['info' => '店铺ID为空', 'status' => 0]);
        }
        $result = $this->showcase->unpass($showcase_id,$refuse_reason);
        if($result){
            Tools::output(['info'=>'驳回失败', 'status' => 0]);
        }else{
            Tools::output(['info'=>'驳回成功', 'status' => 1, 'url' => '/bpshowcase/index']);
        }
        exit;
    }
    
     /**
     * API:审核通过
     */
    public function passAction() {
        $showcase_id = json_decode($this->getRequest()->getPost('data'), true)['id'];
        if(!$showcase_id){
            Tools::output(['info' => '店铺ID为空', 'status' => 0]);
        }
        $result = $this->showcase->pass($showcase_id);
        if($result){
            Tools::output(['info'=>'认证失败', 'status' => 0]);
        } else {
            Tools::output(['info'=>'认证成功', 'status' => 1, 'url' => '/bpshowcase/index']);
        }
        exit;
    }
}
