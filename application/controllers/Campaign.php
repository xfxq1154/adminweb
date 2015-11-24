<?php

/**
 * @name campaignController
 * @desc 活动控制器
 * @show
 * @author hph
 */
class CampaignController extends Base {

    use Trait_Layout,
        Trait_Pagger,Trait_Api;

    public $charge,$coin,$_ucapi,$userBuy,$audioapi,$audioCampaign;

    /**
     * 入口文件
     */
    public function init() {
        $this->initAdmin();
        $this->charge = new ChargeModel();
        $this->coin = new CoinModel();
        $this->userBuy = new UserBuyModel();
        $this->_ucapi = $this->getApi('ucapi');
        $this->audioapi = $this->getApi('audio');
        $this->audioCampaign = new AudioCampaignModel();
    }

    //首页列表
    public function indexAction() {
        die('audio campaign index');
    }

    public function listAction() {
        $this->checkRole();

        $vipTypeList = array('10' => 0, '12' => 200, '13' => 300, '14' => 1200, '15' => 1500);
        $p = (int) $this->getRequest()->getParam('p', 1);
        $res = $this->audioCampaign->getlist($p);
        $pagesize = 20;

        $pageUrl = '/campaign/list/p/{p}';
        $total = (isset($res[count])) ? ($res[count]) : (0);

        $list = array();
        if (isset($res['results'])) {
            foreach ($res['results'] as $k => $v) {
                $user = ($v['identity_category'] == 11) ? ('会员') : '不限制';
                $res['results'][$k]['identity_category'] = $user;
                $res['results'][$k]['type'] = '充值赠送节操币';
            }

            $list = $res['results'];
        }

        $this->_view->list = $list;
        $this->_view->ctype = 2;
        $this->renderPagger($p, $total, $pageUrl, $pagesize);
        $this->layout('audio/list_audio_campaign.phtml');
    }
    
    
    public function refundListAction(){
        $this->checkRole('list');

        $vipTypeList = array('0' => 0, '12' => 200, '13' => 300, '14' => 1200, '15' => 1500);
        $p = (int) $this->getRequest()->getParam('p', 1);
        
        $pagesize = 20;
        $res = $this->audioCampaign->getlist($p,$pagesize,'returns');
        
        
        $pageUrl = '/campaign/refundList/p/{p}';
        $total = (isset($res[count])) ? ($res[count]) : (0);

        $list = array();
        if (isset($res['results'])) {
            foreach ($res['results'] as $k => $v) {
                $user = ($v['identity_category']>0) ? ($vipTypeList[$v['identity_category']].'元会员') : '不限制';
                $res['results'][$k]['identity_category'] = $user;
                $res['results'][$k]['type'] = '会员返节操币';
            }

            $list = $res['results'];
        }

        $this->_view->list = $list;
        $this->_view->ctype = 1;
        $this->renderPagger($p, $total, $pageUrl, $pagesize);
        $this->layout('audio/list_audio_campaign.phtml');
    }
    
    public function editAction(){
        $this->checkRole('list');
        $cid = (int) $this->getRequest()->getParam('cid', 0);
        $ctype = (int) $this->getRequest()->getParam('ctype', 0);
        $interface = ($ctype==1)?('returns'):('offers');
        $res = $this->audioCampaign->getOne($cid,$interface);
        
        if($_POST){
            
            if(empty($ctype)){
                $response = array('info'=>'缺少参数','status'=>0,'url'=>'');
                Tools::output($response);
            }
            
            $res = $this->setArr($_POST);
            $res['top_up_amount'] = $_POST['top_up_amount'];
            $res['gift_amount']   = $_POST['gift_amount'];
            
            if($ctype == 1){
                $res['identity_category'] = $_POST['vip_user'];
                unset($res['top_up_amount']);
                $backurl = 'refundList';
            }elseif($ctype == 2){
                $res['identity_category'] = ($_POST['vip_user']== 1)?(11):(0);
                $backurl = 'list';
            }
           
            $result = $this->audioCampaign->edit($cid,$res,$interface);
            
           
            if(isset($result['id'])){
                $response = array('info'=>'更新成功','status'=>1,'url'=>'/campaign/'.$backurl);
            }else{
                $response = array('info'=>'更新失败','status'=>0,'url'=>'');
            }
            Tools::output($response);
            
        }
        $vipTypeList = array('10' => 0, '12' => 200, '13' => 300, '14' => 1200, '15' => 1500);
        $res['utype'] = ($res['identity_category'] == 11)?(1):(2);
        $res['ctype'] = $ctype;
        $this->_view->info = $res;
        $this->_view->vlist = $vipTypeList;
        $this->layout('audio/edit_audio_campaign.phtml');
    }

    public function addAction(){
        $this->checkRole();        
        $fillList = $this->fillList();
        $vipTypeList = array('12'=>200,'13'=>300,'14'=>1200,'15'=>1500);

        if($_POST){
            
            $res = $this->setArr($_POST);
            $type = $_POST['campaign_type'];//1:返还会员节操币; 2:充值赠送节操币
            
            //返还会员节操币
            if($type == 1)
            {
                foreach ($vipTypeList as $vk=>$vv){
                    $userlevel = $_POST['user_level_'.$vv];
                    $res['gift_amount'] = $userlevel;
                    $res['identity_category'] = $vk;
                    
                    if(!empty($userlevel) && is_numeric($userlevel) && ($userlevel>0)){
                       
                        $result  = $this->audioCampaign->add($res,'returns');
                        print_r($result);
                    }
                }
            }
            //充值赠送
            elseif($type == 2)   
                
            {
                
               
                    foreach($fillList as $fk=>$fv){
                        $res['gift_amount'] = $_POST['recharge_coin_'.$fv];
                        $res['top_up_amount'] = $fv;
                        $res['identity_category'] = ($_POST['vip_user']== 1)?(11):(0);
                        $result  = $this->audioCampaign->add($res);
                        
                        if(isset($result['id'])){
                            $response = array('info'=>'更新成功','status'=>1,'url'=>'/campaign/list');
                        }else{
                            $response = array('info'=>'更新失败','status'=>0,'url'=>'');
                        }
                        Tools::output($response);
                    }
                
                
            }
            
            exit;
            
        }
        
        $this->_view->vipTypeList = $vipTypeList;
        $this->_view->fillList = $fillList;
        $this->layout('audio/add_audio_campaign.phtml');
        
    }
    
    
    public function setArr($data){
        $res = array();
        if(empty($data)) return $res;
        $res = array('rule_name'=>$data['campaign_title'],
                         'sys_code'=>1,
                         'start_date'=>$data['start_date'],
                         'valid_thru'=>$data['end_date'],
                         'num_of_usage'=>($data['is_repeat']==1)?(1):(0),
                         'is_activated'=>($data['campaign_status']==1)?('true'):('false'),
                         
                        );
        return $res;
    }
    
    public function fillList(){
        $res = array();
        $paylist = $this->audioCampaign->audioapi('/pay/product');
        if(isset($paylist['c']['p'])){
            foreach ($paylist['c']['p'] as $v){
                $res[] = $v['p'];
            }
        }
        return $res;
        
    }
    
  
    
    

}
