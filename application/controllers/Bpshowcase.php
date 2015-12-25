<?php

/**
 * 商户列表
 *
 * @author yanbo
 */
class BpShowcaseController extends Base {

    use Trait_Layout,
        Trait_Pagger;
    
    public $showcase;
    
    const UCAPI_URL = 'user/register';
    const UPAPI_URL_UPPW = 'user/update_pwd';
    const UPAPI_GETINFO = 'user/getinfo';
    
    public function init() {
        $this->initAdmin();
        $this->checkRole();
        $this->showcase = new BpShowcaseModel();
    }
    
    function indexAction() {
        $t = (int) $this->getRequest()->get('t');
        $p = (int) $this->getRequest()->getParam('p', 1);
        $size = 10;

        $params = array();
        
        switch ($t) {
            case 1:
                $params['status_person'] = 1;
                break;
            case 2:
                $params['status_com'] = 1;
                break;
            case 3:
                $params['block'] = 1;
                break;  
        }
        $params[page_no] = $p;
        $params[page_size] = $size;
        $showcasesList = $this->showcase->getList($params);
        $count = $showcasesList['total_nums'];

        $this->assign("list", $showcasesList['showcases']);
        $this->renderPagger($p, $count, '/bpshowcase/index/p/{p}/t/'.$t, $size);
        $this->layout("platform/showcase.phtml");
    }

    function infoAction() {
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
            $data['phone'] = $_POST['phone'];
            $data['password'] = $_POST['pw'];
            $data['resname'] = $_POST['resname'];
            $data['nickname'] = $_POST['nickname'];
            $data['signature'] = $_POST['signature'];
            $data['wechat'] = $_POST['wechat'];
            $data['phone_message'] = $_POST['message'];
            $data['intro'] = $_POST['summary'];
            $data['name'] = $_POST['sname'];
            $params['mobile'] = $_POST['phone'];
            $params['passwd'] = $_POST['pw'];
            $rs = Ucapi::request(self::UCAPI_URL, $params, 'POST');
            
            if(!empty($rs)){
                $data['user_id'] = $rs['user_id'];
            }
            
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
            $this->showcase->createPaymentSellerAccount($resule);
            Tools::output(array('info'=>'创建成功','status'=>1,'url'=>'/bpshowcase/create'));
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
            $info = Ucapi::request(self::UPAPI_GETINFO, array('uid'=>$user_id));
            if($info['phone']){
                $phone = $info['phone'];
            }else{
                Tools::output(array('info'=>'手机号为空','status'=>1));
            }
            $result = Ucapi::request(self::UPAPI_URL_UPPW, array('mobile'=>$phone,'newpwd'=>$pw), 'POST');
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
    function auditingAction() {
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
     * API:冻结
     */
    function blockAction() {
        $showcase_id = json_decode($this->getRequest()->getPost('data'), true)['id'];
        if(!$showcase_id){
            return FALSE;
        }
        $params['showcase_id'] = $showcase_id;
        $result = $this->showcase->block($params);
        if($result){
            Tools::output(['info' => '冻结失败', 'status' => 0]);
        }  else {
            Tools::output(['info' => '冻结成功', 'status' => 1, 'url' => '/bpshowcase/index']);
        }
        exit;
    }
    
    /**
     * API:解冻
     */
    function unblockAction() {
        $showcase_id = json_decode($this->getRequest()->getPost('data'), true)['id'];
        if(!$showcase_id){
            return FALSE;
        }
        $params['showcase_id'] = $showcase_id;
        $result = $this->showcase->unblock($params);
        if($result){
            Tools::output(['info' => '解冻失败', 'status' => 0]);
        }  else {
            Tools::output(['info' => '解冻成功', 'status' => 1, 'url' => '/bpshowcase/index']);
        }
        exit;
    }
    
    /**
     * API:驳回
     */
    function unpassAction() {
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
    function passAction() {
        $showcase_id = json_decode($this->getRequest()->getPost('data'), true)['id'];
        if(!$showcase_id){
            Tools::output(['info' => '店铺ID为空', 'status' => 0]);
        }
        $result = $this->showcase->pass($showcase_id,$type);
        if($result){
            Tools::output(['info'=>'认证失败', 'status' => 0]);
        } else {
            Tools::output(['info'=>'认证成功', 'status' => 1, 'url' => '/bpshowcase/index']);
        }
        exit;
    }
    
    /**
     * API:升级通过
     */
    function upgradesuccessAction() {
        $showcase_id = json_decode($this->getRequest()->getPost('data'), true)['id'];
        $result = $this->showcase->upgradesuccess($showcase_id,$refuse_reason);
        $msg = ($result == "") ? "审核成功" : "审核失败";
        $status = ($result == "") ? 1 : 0;
        echo json_encode(['info' => $msg, 'status' => $status]);
        exit;
    }
    /**
     * API:升级驳回
     */
    function upgradefailAction() {
        $showcase_id = json_decode($this->getRequest()->getPost('data'), true)['id'];
        $refuse_reason = json_decode($this->getRequest()->getPost('data'), true)['refuse_reason'];
        $result = $this->showcase->upgradefail($showcase_id,$refuse_reason);
        $msg = ($result == "") ? "驳回成功" : "驳回失败";
        $status = ($result == "") ? 1 : 0;
        echo json_encode(['info' => $msg, 'status' => $status]);
        exit;
    }
}
