<?php

/* 
 * @name:BpexpressController
 * @author yanbo
 * @desc 快递控制器
 */
class BpexpressController extends Base{
    
    use Trait_Api,
        Trait_Pagger,
        Trait_Layout;
    
    public $express; 
    
    public function init(){
        $this->initAdmin();
        $this->express = new BpExpressModel();
    }
    
    public function indexAction(){
        $this->checkRole();
        
        $p = (int) $this->getRequest()->getParam('p', 1);
        $size = 20;
        
        $order_id = $this->getRequest()->get('order_id');
        $excomsn = $this->getRequest()->get('excomsn');
        $exnum = $this->getRequest()->get('exnum');
        $state = $this->getRequest()->get('status');
        $sub_state = $this->getRequest()->get('sub_status');
        $start_created = $this->getRequest()->get('start_time');
        $end_created = $this->getRequest()->get('end_time');
        
        $params['order_id'] = $order_id;
        $params['excomsn'] = $excomsn;
        $params['exnum'] = $exnum;
        $params['state'] = $state;
        $params['sub_state'] = $sub_state;
        $params['start_created'] = $start_created;
        $params['end_created'] = $end_created;
        
        $params['page_no'] = $p;
        $params['page_size'] = $size;
        
        $result = $this->express->getList($params);
        
        $this->assign('list', $result['expresses']);
        $this->renderPagger($p, $result['total_nums'], '/bpexpress/index/p/{p}', $size);
        $this->layout('platform/express_list.phtml');
    }
    
}

