<?php

/**
 * @name WelcomeController
 * @author why
 * @desc 欢迎页控制器
 */
class WelcomeController extends Base{
    
    use Trait_Layout;
    
    public $sdata_model;
    
    public function init(){
        $this->initAdmin();
        $this->sdata_model = new SdataModel();
    }
    
    /**
     * 首页
     */
    public function indexAction(){
        $this->checkLogin();
        $this->checkRole();
        
        if($_POST){
            $time1 = $this->getRequest()->getPost('time1');
            $time2 = $this->getRequest()->getPost('time2');
            $time3 = $this->getRequest()->getPost('time3');
            if ($time1) {
                $params['start_created'] = $time1;
                $params['end_created'] = date('Y-m-d', strtotime($time1.'+1 day'));
                $result1 = $this->sdata_model->getList($params);
            }
            if ($time2) {
                $params['start_created'] = $time2;
                $params['end_created'] = date('Y-m-d', strtotime($time2.'+6 day'));
                $result = $this->sdata_model->getList($params);
                foreach ($result as $val){
                    $data['order_num'] += $val['order_num'];
                    $data['order_sum'] += $val['order_sum'];
                    $data['order_people'] += $val['order_people'];
                    $data['paied_num'] += $val['paied_num'];
                    $data['paied_num_wx'] += $val['paied_num_wx'];
                    $data['paied_num_jd'] += $val['paied_num_jd'];
                    $data['paied_sum'] += $val['paied_sum'];
                    $data['paied_people'] += $val['paied_people'];
                    $data['paied_people_repeat'] += $val['paied_people_repeat'];
                    $data['paied_people_sum'] += $val['paied_people_sum'];
                    $data['paied_people_num'] += $val['paied_people_num'];
                }
            }
            if ($time3) {
                $params['start_created'] = $time3;
                $params['end_created'] = date('Y-m-d', strtotime($time3.'+29 day'));
                $result = $this->sdata_model->getList($params);
                foreach ($result as $val){
                    $data3['order_num'] += $val['order_num'];
                    $data3['order_sum'] += $val['order_sum'];
                    $data3['order_people'] += $val['order_people'];
                    $data3['paied_num'] += $val['paied_num'];
                    $data3['paied_num_wx'] += $val['paied_num_wx'];
                    $data3['paied_num_jd'] += $val['paied_num_jd'];
                    $data3['paied_sum'] += $val['paied_sum'];
                    $data3['paied_people'] += $val['paied_people'];
                    $data3['paied_people_repeat'] += $val['paied_people_repeat'];
                    $data3['paied_people_sum'] += $val['paied_people_sum'];
                    $data3['paied_people_num'] += $val['paied_people_num'];
                }
            }
            
            $pra['ta1'] = $result1;
            $pra['ta2'] = $data;
            $pra['ta3'] = $data3;
            echo json_encode($pra);exit;
        } else {
            $params['start_created'] = date('Y-m-d', time());
            $params['end_created'] = date('Y-m-d', strtotime($time1.'+1 day'));
            $result = $this->sdata_model->getList($params);
        }
        $this->assign('data', $result);
        $this->assign('time', $time1 ? $time1 : date('Y-m-d', time()));
        $this->layout('welcome/index.phtml');
    }
}
