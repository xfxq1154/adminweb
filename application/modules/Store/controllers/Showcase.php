<?php

/**
 * ShowcaseController
 * @author yanbo
 */
class ShowcaseController extends Storebase {

    public function init() {
        parent::init();
    }
    
    /**
     * 店铺列表
     */
    public function indexAction() {
        $type = $this->input_get_param('t');
        $block = $this->input_get_param('block');
        $kw = $this->input_get_param('kw');
        $nickname = $this->input_get_param('nickname');
        $page_no = $this->input_get_param('page_no');
        $page_size = 20;

        $params = array();
        switch ($type) {
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
        $params['page_no'] = $page_no;
        $params['page_size'] = $page_size;
        $params['block'] = $block > 0 ? $block : '0';
        $params['kw'] = $kw;
        $params['nickname'] = $nickname;
        $showcasesList = $this->showcase_model->getlist($params);

        $this->assign("list", $showcasesList['showcases']);
        $this->assign('kw', $kw);
        $this->assign('nickname', $nickname);
        $this->renderPagger($page_no, $showcasesList['total_nums'], '/store/showcase/index?page_no={p}&t='.$type, $page_size);
        $this->layout("showcase/showlist.phtml");
    }
    
    /**
     * 店铺简介
     */
    public function infoAction() {
        $showcase_id = $this->input_get_param('id');
        $showcases = $this->showcase_model->getInfoById($showcase_id);

        $this->assign("showcase", $showcases);
        $this->layout('showcase/detail.phtml');
    }
    
    /**
     * 创建店铺
     */
    public function createAction(){
        if(!$_POST){
            $this->layout('showcase/create.phtml');
        }

        $showcase_name = $this->input_post_param('showcase_name');
        $nickname =$this->input_post_param('nickname');
        $phone =$this->input_post_param('phone');
        $password =$this->input_post_param('password');

        //查询此手机号是否注册
        $user_model = new StoreUserModel();
        $user_info = $user_model->search(array('phone'=> $phone));
        if($user_info){
            $user_id = $user_info[0]['id'];
        } else {
            $result = $user_model->register(array('mobile'=>$phone,'passwd'=>$password));
            if($result === FALSE){
                Tools::output(array('info'=> $user_model->getError() ,'status'=>1));
            }
            $user_id = $result['user_id'];
        }

        //创建店铺
        $data['user_id'] = $user_id;
        $data['name'] = $showcase_name;
        $data['nickname'] = $nickname;
        $result = $this->showcase_model->createShowcase($data);
        if($result === FALSE){
            Tools::output(array('info' => Sapi::getErrorMessage(), 'status' => 1));
        }
        
        //创建支付账户
        $result = $this->showcase_model->createPaymentSellerAccount($result['showcase_id'], $data['name']);
        if ($result['code'] != 0){
            Tools::output(array('info'=>"创建支付账户失败({$result['msg']})", 'status'=>1, 'url'=>'/store/showcase/index'));
        }

        Tools::output(array('info'=>'创建成功', 'status'=>1, 'url'=>'/store/showcase/index'));
    }

    /**
     * 商户审核
     */
    public function auditingAction() {
        $showcase_id = $this->input_get_param('showcase_id');
        if(!$showcase_id){
            Tools::output(['info' => '店铺ID为空', 'status' => 0]);
        }

        $info = $this->showcase_model->approve_detail($showcase_id);
        var_dump($info);
        $this->assign("info", $info);
        $this->assign("showcase_id", $showcase_id);
        $this->assign("refuse", json_encode($this->getView()->render('showcase/model.phtml')));
        $this->layout("showcase/auditing_com.phtml");
        
    }
    
    /**
     * API:驳回
     */
    public function unpassAction() {
        $data = json_decode($this->input_post_param('data'), true);
        $showcase_id = $data['id'];
        $refuse_reason = $data['refuse_reason'];
        if(!$showcase_id){
            Tools::output(['info' => '店铺ID为空', 'status' => 0]);
        }
        $result = $this->showcase_model->unpass($showcase_id,$refuse_reason);
        if($result){
            Tools::output(['info'=>'驳回失败', 'status' => 0]);
        }else{
            Tools::output(['info'=>'驳回成功', 'status' => 1, 'url' => '/store/showcase/index']);
        }
        exit;
    }
    
     /**
     * API:审核通过
     */
    public function passAction() {
        $data = json_decode($this->input_post_param('data'), true);
        $showcase_id = $data['id'];
        if(!$showcase_id){
            Tools::output(['info' => '店铺ID为空', 'status' => 0]);
        }
        $result = $this->showcase_model->pass($showcase_id);
        if($result){
            Tools::output(['info'=>'认证失败', 'status' => 0]);
        } else {
            Tools::output(['info'=>'认证成功', 'status' => 1, 'url' => '/store/showcase/index']);
        }
        exit;
    }
}
