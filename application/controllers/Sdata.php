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
    public $date;
    
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
            $start_created = date('Y-m-d',strtotime('-7 day'));
            $end_created = date('Y-m-d',strtotime('today'));
        }
        $params['showcase_id'] = $showcase_id;
        $params['start_created'] = $start_created;
        $params['end_created'] = $end_created;
        
        $result = $this->sdata->getList($params);
        $order_people = 0;
        $paied_people = 0;
        $paied_num_total = 0;
        $paied_sum_total = 0;
        foreach ($result as $val){
            $key = '"'.date('m-d',strtotime($val['date'])).'"';
            $data[$key] = $val;
            
            $order_people += $val['order_people'];  //付款人数
            $paied_people += $val['paied_people'];  //付款人数
            $paied_num_total += $val['paied_num'];     //付款笔数
            $paied_sum_total += $val['paied_sum'];  //付款金额
        }
        $paied_people_sum = 0;
        if($paied_people > 0){
            $paied_people_sum = round($paied_sum_total / $paied_people, 2);  //客单价
        }
        
        $this->assign('start_time', $start_created);
        $this->assign('end_time', $end_created);
        $this->assign('showcase_id', $showcase_id);
        $this->assign('order_people', $order_people);
        $this->assign('paied_people', $paied_people);
        $this->assign('paied_num', $paied_num_total);
        $this->assign('paied_sum', $paied_sum_total);
        $this->assign('paied_people_sum', $paied_people_sum);
        
        $dates = $this->_get_time_string($start_created, $end_created);
        $order_num_string = '';
        $paied_num_string = '';
        $paied_sum_string = '';
        if($dates){
            foreach ($dates as $val){
                if(isset($data[$val])){
                    $order_num[] = $data[$val]['order_num'];  //下单笔数
                    $paied_num[] = $data[$val]['paied_num'];  //付款笔数
                    $paied_num_wx[] = $data[$val]['paied_num_wx'];  //微信付款笔数
                    $paied_num_jd[] = $data[$val]['paied_num_jd'];  //京东付款笔数
                    $paied_sum[] = $data[$val]['paied_sum'];  //付款金额
                } else {
                    $order_num[] = 0;  //下单笔数
                    $paied_num[] = 0;  //付款笔数
                    $paied_num_wx[] = 0;  //微信付款笔数
                    $paied_num_jd[] = 0;  //京东付款笔数
                    $paied_sum[] = 0;  //付款金额
                }
            }

            $order_num_string = implode(',', $order_num);
            $paied_num_string = implode(',', $paied_num);
            $paied_sum_string = implode(',', $paied_sum);

            $this->assign('dates', implode(',', $dates));
        }
        
        $this->assign('paied_num_wx', array_sum($paied_num_wx));
        $this->assign('paied_num_jd', array_sum($paied_num_jd));
        $this->assign('order_num_string', $order_num_string);
        $this->assign('paied_num_string', $paied_num_string);
        $this->assign('paied_sum_string', $paied_sum_string);
        $this->layout('sdata/order.phtml');
    }
    
    public function _get_time_string($start_created, $end_created) {
        
        $start  = strtotime($start_created);  
        $stop   = strtotime($end_created);  
        $extend = ($stop-$start)/86400;
        for ($i = 0; $i < $extend; $i++) {
            $date[] = '"'.date('m-d',$start + 86400 * $i).'"';
        }
        return $date;
    }
    
}
