<?php

/* 
 * @name:SdataController
 * @author yanbo
 * @desc 数据统计控制器
 */
class SdataController extends Base{
    
    use Trait_Api,
        Trait_Pagger,
        Trait_Layout;
    
    public $sdata; 
    
    public function init(){
        $this->initAdmin();
        $this->sdata = new SdataModel();
    }
    
    public function orderAction(){
        $this->checkRole();
        
        $start_created = $this->getRequest()->get('start_time');
        $end_created = $this->getRequest()->get('end_time');
        $showcase_id = $this->getRequest()->get('showcase_id');
        if(!$start_created || !$end_created){
            $start_created = date('Y-m-d',strtotime('-7 day')).' 00:00:00';
            $end_created = date('Y-m-d',strtotime('today')).' 00:00:00';
        }
        $params['showcase_id'] = $showcase_id;
        $params['start_created'] = $start_created;
        $params['end_created'] = $end_created;
        
        $result = $this->sdata->getList($params);
        var_dump($result);
        foreach ($result as $val){
            $order_num[] = $val['order_num'];  //下单笔数
            $paied_num[] = $val['paied_num'];  //付款笔数
            $paied_sum[] = $val['paied_sum'];  //付款金额
        }
        $order_num_string = implode(',', $order_num);
        $paied_num_string = implode(',', $paied_num);
        $paied_sum_string = implode(',', $paied_sum);
        
        $this->assign('dates', $this->_get_time_string($start_created, $end_created));
        $this->assign('result', array('下单笔数'=>$order_num_string, '付款笔数'=>$paied_num_string, '付款金额'=>$paied_sum_string));
        $this->assign('rgba', array('order_num'=>'120,20,20,0.8', 'paied_num'=>'20,120,220,0.8', 'paied_sum'=>'50,22,120,0.8'));
        
        $this->layout('sdata/index.phtml');
    }
    
    public function _get_time_string($start_created, $end_created) {
        
        $start  = strtotime($start_created);  
        $stop   = strtotime($end_created);  
        $extend = ($stop-$start)/86400;
        for ($i = 0; $i < $extend; $i++) {
            $date[] = '"'.date('m-d',$start + 86400 * $i).'"';
        }
        return implode(',', $date);
    }
    
}

