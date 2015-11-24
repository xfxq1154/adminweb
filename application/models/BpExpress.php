<?php

/**
 * @name BpExpressModel
 * @desc 订单操作类
 */
class BpExpressModel {

    use Trait_Api;

    const EXPRESS_LIST = 'express/getlist';
    const EXPRESS_DETAIL = 'express/detail';
    const EXPRESS_UPDATE = 'express/update';
    const EXPRESS_DELETE = 'express/delete';

    private $ex_status = array('0' => '已发货','1' => '已发货','2' => '在途中','3' => '已签收','4' => '疑难','5' => '已退签','6' => '派件中','7' => '退回中','8' => '已转投');
    private $ex_sub_status = array('-3' => '监控中止','-2' => '等待订阅','-1' => '订阅失败','0' => '订阅异常','1' => '订阅成功');

    
    public function getList($params) {
        $result = $this->request(self::EXPRESS_LIST, $params);
        return $this->format_express_info_batch($result);
    }

    

    /**
     * 格式化物流信息
     */
    public function format_express_info_struct($data) {
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
    
    public function format_express_info_batch($datas) {
        if (empty($datas)) {
            return array();
        }
        foreach ($datas['expresses'] as &$data){
            $data = $this->format_express_info_struct($data);
        }

        return $datas;
    }


    private function request($uri, $params = array(), $requestMethod = 'GET', $jsonDecode = true, $headers = array(), $timeout = 10) {

        $sapi = $this->getApi('sapi');
        
        $params['sourceid'] = Yaf_Application::app()->getConfig()->api->sapi->source_id;
        $params['timestamp'] = time();
        
        $result = $sapi->request($uri, $params, $requestMethod);

        if (isset($result['status_code']) && $result['status_code'] == 0) {
            return isset($result['data']) ? $result['data'] : array();
        } else {
//            echo json_encode($result);
            return false;
        }
    }

}
