<?php

/* 
 * ExpressController
 * @author yanbo
 * @desc 快递控制器
 */
class ExpressController extends Storebase{

    private $ex_status = array('0' => '已发货','1' => '已发货','2' => '在途中','3' => '已签收','4' => '疑难','5' => '已退签','6' => '派件中','7' => '退回中','8' => '已转投');
    private $ex_sub_status = array('-3' => '监控中止','-2' => '等待订阅','-1' => '订阅失败','0' => '订阅异常','1' => '订阅成功');


    public function init(){
        parent::init();
    }
    
    public function indexAction(){
        $order_id = $this->input_get_param('order_id');
        $excomsn = $this->input_get_param('excomsn');
        $exnum = $this->input_get_param('exnum');
        $state = $this->input_get_param('status');
        $sub_state = $this->input_get_param('sub_status');
        $start_created = $this->input_get_param('start_time');
        $end_created = $this->input_get_param('end_time');
        $page_no = $this->input_get_param('page_no');
        
        $size = 20;
        $params['order_id'] = $order_id;
        $params['excomsn'] = $excomsn;
        $params['exnum'] = $exnum;
        $params['state'] = $state;
        $params['sub_state'] = $sub_state;
        $params['start_created'] = $start_created;
        $params['end_created'] = $end_created;
        
        $params['page_no'] = $page_no;
        $params['page_size'] = $size;
        
        $result = $this->store_model->expressList($params);
        $data = $this->format_data_batch($result);
        $this->assign('list', $data['expresses']);
        $this->renderPagger($page_no, $result['total_nums'], "/store/express/index/?page_no={p}&start_time={$start_created}&end_time={$end_created}&order_id={$order_id}&exnum={$exnum}", $size);
        $this->assign('start_time', $start_created);
        $this->assign('end_time', $end_created);
        $this->assign('order_id', $order_id);
        $this->assign('exnum', $exnum);
        $this->layout('express/showlist.phtml');
    }

    /**
     * 格式化物流信息
     */
    public function tidy($data) {
        if (empty($data)) {
            return array();
        }
        $format_data = array(
            'order_id' => $data['order_id'],
            'exnum' => $data['exnum'],
            'excom' => $data['excom'],
            'excomsn' => $data['excom_sn'],
            'tocity' => $data['tocity'],
            'state' => $data['state'],
            'state_text' => $this->ex_status[$data['state']],
            'sub_state' => $data['sub_state'],
            'sub_state_text' => $this->ex_sub_status[$data['sub_state']],
            'sub_message' => $data['sub_message'],
            'shipping_time' => $data['shipping_time'],
            'callback_time' => $data['callback_time'],
        );

        return $format_data;
    }

    public function format_data_batch($datas) {
        if (empty($datas)) {
            return array();
        }
        foreach ($datas['expresses'] as &$data){
            $data = $this->tidy($data);
        }

        return $datas;
    }
}

