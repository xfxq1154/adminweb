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
    const  UCAPI_URL = 'user/register';
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
            $rs = Ucapi::request(self::UCAPI_URL, $params, 'POST' ,TRUE);
            
            if(!empty($rs)){
                $data['user_id'] = $rs['user_id'];
            }
            
            $resule = $this->showcase->create($data);
            if($resule === FALSE){
                $error = $this->showcase->getError();
                switch ($error){
                    case 40001:
                        $msg = '店铺名称已存在';
                        $code = 40001; 
                    break;
                    case 40002:
                        $msg = '此用户以创建店铺';
                        $code = 40002; 
                    break;
                    case 10007:
                        $msg = '缺少参数';
                        $code = 10007; 
                    break;
                    case 10004:
                        $msg = '系统异常';
                        $code = 10004; 
                    break;
                }
                echo json_encode(['info' => $msg, 'status' => $code, 'url' => 'bpshowcase/create']);exit;
            }
            echo json_encode(['info' => '创建成功', 'status' => 1]);
            exit;
        }
        $this->layout('platform/add_showcase.phtml');
    }
    
    /**
     * 商户审核
     */
    function auditingAction() {
        $showcase_id = $this->getrequest()->get('showcase_id');
        $type = $this->getrequest()->get('type');
        $info = $this->showcase->approve_detail($showcase_id);
        $this->assign("info", $info);
        $this->assign("type", $type);
        $this->assign("showcase_id", $showcase_id);
        $this->assign("refuse", json_encode($this->getView()->render('platform/showcase_refuse.phtml')));
        
        if ($type == 1) {
            $this->layout("platform/showcase_auditing_person.phtml");
        }else{
            $this->layout("platform/showcase_auditing_com.phtml");
        }
        
    }

    /**
     * API:冻结
     */
    function blockAction() {
        $showcase_id = json_decode($this->getRequest()->getPost('data'), true)['id'];
//        echo "id is ".$showcase_id;
        $result = $this->showcase->block($showcase_id);
//        var_dump($result);
        $msg = ($result === "") ? "冻结成功" : "冻结失败";
        $status = ($result === "") ? 1 : 0;
        echo json_encode(['info' => $msg, 'status' => $status]);
        exit;
    }
    
    /**
     * API:解冻
     */
    function unblockAction() {
        $showcase_id = json_decode($this->getRequest()->getPost('data'), true)['id'];
        $result = $this->showcase->unblock($showcase_id);
        $msg = ($result == "") ? "解冻成功" : "解冻失败";
        $status = ($result == "") ? 1 : 0;
        echo json_encode(['info' => $msg, 'status' => $status]);
        exit;
    }
    
    /**
     * API:驳回
     */
    function unpassAction() {
        $showcase_id = json_decode($this->getRequest()->getPost('data'), true)['id'];
        $refuse_reason = json_decode($this->getRequest()->getPost('data'), true)['refuse_reason'];
        $type = json_decode($this->getRequest()->getPost('data'), true)['type'];
        $result = $this->showcase->unpass($showcase_id,$refuse_reason,$type);
        $msg = ($result == "") ? "驳回成功" : "驳回失败";
        $status = ($result == "") ? 1 : 0;
        echo json_encode(['info' => $msg, 'status' => $status]);
        exit;
    }
    
     /**
     * API:审核通过
     */
    function passAction() {
        $showcase_id = json_decode($this->getRequest()->getPost('data'), true)['id'];
        $type = json_decode($this->getRequest()->getPost('data'), true)['type'];
        $result = $this->showcase->pass($showcase_id,$type);
        $msg = ($result == "") ? "审核成功" : "审核失败";
        $status = ($result == "") ? 1 : 0;
        echo json_encode(['info' => $msg, 'status' => $status]);
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
