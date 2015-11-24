<?php

/**
 * @name chargeController
 * @desc 用户订单控制器
 * @show
 * @author hph
 */
class ChargeController extends Base {

    use Trait_Layout,
        Trait_Pagger,Trait_Api;

    public $charge,$coin,$_ucapi,$userBuy;

    /**
     * 入口文件
     */
    public function init() {
        $this->initAdmin();
        $this->charge = new ChargeModel();
        $this->coin = new CoinModel();
        $this->userBuy = new UserBuyModel();
        $this->_ucapi = $this->getApi('ucapi');
    }

    //首页列表
    public function indexAction() {
        die('audio index');
    }

    public function listAction() {
        $this->checkRole();
        $p = (int) $this->getRequest()->getParam('p', 1);
        $kwd = $this->getRequest()->getParam('kwd', '');
        $kwd = urldecode($kwd);
        $type = $this->getRequest()->getParam('type', '');
        $startDate = $this->getRequest()->getParam('sd', '');
        $sd = urldecode($startDate);
        $endDate = $this->getRequest()->getParam('ed', '');
        $ed = urldecode($endDate);
        
        $st = $this->getRequest()->getParam('st', '');
                
        $where = '';
        $pageUrl = '/charge/list/p/{p}'; 
        
        if(!empty($startDate) && !empty($endDate)){
            $startDate = date($startDate.' 00:00:00');
            $endDate   = date($endDate.' 23:59:59');
            $where.=" and c_datetime > '".$startDate."'  and  c_datetime < '".$endDate."' ";
            $pageUrl.='/sd/'.$sd.'/ed/'.$ed;
        }

        if(!empty($kwd) && $kwd >0){
            $where.=" and c_uid = '".$kwd."' ";
            $pageUrl.='/kwd/'.$kwd;
        }else{
            $kwd='';
        }
        
        if(!empty($type)){
            $where.=" and c_device = '".$type."' ";
            $pageUrl.='/type/'.$type;
        }
        $wh  = $where;
        if(is_numeric($st)){
            $where.=" and c_status = '".$st."' ";
            $pageUrl.='/st/'.$st;
            
        }

        $pagesize = 20;
        //读取列表
        $total = $this->charge->getNumber($where);

        $this->renderPagger($p, $total, $pageUrl, $pagesize);

        $list = $this->charge->getList($p, $pagesize,$where);
        $coin = $this->charge->getTotalCoin($wh);
        
        $this->_view->alist = $list;
        $this->_view->kwd   = $kwd;
        $this->_view->coin  = $coin;
        $this->_view->startDate = $sd;
        $this->_view->endDate = $ed;
        $this->_view->type = $type;
        $this->_view->status = $st;
        $this->layout('charge/list.phtml');
    }
    
    public function upAction(){
        $this->checkRole('list');
        $st = $this->getRequest()->getParam('st');
        $fid = $this->getRequest()->getParam('fid');
        
        $res = $this->charge->update(array('id'=>$fid,'status'=>$st));
        $return = array(
            "info" => '更新失败！',
            "status" => 0,
            "url" => "",
        );
        if($res>=0){
            $return['info'] = '更新排期成功！';
            $return['status'] = 1;
        }
        Tools::output($return);
    }
    
    
    
    
    
    public function uinfoAction() {
        $this->checkRole('list');
        $uid = $this->getRequest()->getParam('uid',0);
        
        
        $where = ' and c_uid='.$uid;
        $coin = $this->charge->getTotalCoin($where);
        $android_balance = $this->coin->getUserCoin($uid);
        if(isset($android_balance['detail']) && $android_balance['detail']){
            $android_balance['balance'] = 0;
        }
        $android_balance = $android_balance['balance'];
        
        
        $ios_balance = $this->coin->getUserCoin($uid,'IOS');
        if(isset($ios_balance['detail']) && $ios_balance['detail']){
            $ios_balance['balance'] = 0;
        }
        $ios_balance =$ios_balance['balance'];
        
        
        //user info
        $buildQuery = [
                'sourceid' => API_UCAPI_KEY, // 请求UCAPI 必传参数
                'timestamp' => time(), // 请求UCAPI 必传参数
                'uid' => $uid
            ];
        $queryUrl = '/user/getinfo?' . http_build_query($buildQuery);
        $ret = $this->_ucapi->get($queryUrl);
        $ret = json_decode($ret, true);

        if (isset($ret['data']) && is_array($ret['data'])) {
            $user['nickname'] = $ret['data']['nickname'];
            $user['avatar'] = $ret['data']['info']['avatar'];
            $user['sex'] = $ret['data']['info']['sex'];
            $user['phone'] = $ret['data']['phone'];
        }



        $uBuy = $this->userBuy->getPurchased($uid);
        
        
        $this->_view->fillCoin  = $coin;
        $this->_view->iosCoin = $ios_balance;
        $this->_view->androidCoin = $android_balance;
        $this->_view->uinfo = $user;
        $this->_view->uid = $uid;
        $this->_view->ubuy = $uBuy;
        $this->layout('u/u.phtml');
    }
    
    
    
    //user charge list 
    public function uchargelistAction() {
        $this->checkRole('list');
        $p = (int) $this->getRequest()->getParam('p', 1);
        $uid = $this->getRequest()->getParam('uid',0);
        $type = $this->getRequest()->getParam('type', '');
       
        $where = '';
        $pageUrl = '/charge/uchargelist/p/{p}'; 
        
        if(!empty($uid)){
            $where.=" and c_uid = '".$uid."' ";
            $pageUrl.='/uid/'.$uid;
        }
        
        if(!empty($type)){
            $where.=" and c_device = '".$type."' ";
            $pageUrl.='/type/'.$type;
        }

        $pagesize = 20;
        //读取列表
        $total = $this->charge->getNumber($where);

        $this->renderPagger($p, $total, $pageUrl, $pagesize);

        $list = $this->charge->getList($p, $pagesize,$where);
        $coin = $this->charge->getTotalCoin($where);
        
        $this->_view->alist = $list;
        $this->_view->coin  = $coin;
        $this->_view->uid = $uid;
        $this->_view->type = $type;
        $this->layout('charge/uchargelist.phtml');
    }
    
    
    
    
     public function buylistAction() {
        $this->checkRole('list');
        $p = (int) $this->getRequest()->getParam('p', 1);
        $uid = $this->getRequest()->getParam('uid',0);
       
        $where = array();
        $wh = '';
        $pageUrl = '/charge/buylist/p/{p}'; 
        
        if(!empty($uid)){
            $where['uid'] = $uid;
            $pageUrl.='/uid/'.$uid;
            $wh.=' and  b_uid =  '.$uid;
        }
        

        $pagesize = 20;
        //读取列表
        $total = $this->userBuy->getNumber($wh,$uid);

        $this->renderPagger($p, $total, $pageUrl, $pagesize);

        $limit = (($p-1)*$pagesize).','.$pagesize;
        $list = $this->userBuy->getList($limit,$where,array('id','uid','tid','pay_status','type','book_id','price','ctime','utime'));
        $uBuy = $this->userBuy->getPurchased($uid);
        
        $this->_view->alist = $list;
        $this->_view->coin  = $uBuy;
        $this->_view->uid = $uid;
        $this->layout('charge/buylist.phtml');
    }
    
    

}
