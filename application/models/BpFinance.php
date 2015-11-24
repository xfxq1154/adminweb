<?php

/**
 * @name OrderModel
 * @desc 订单操作类
 */
class BpFinanceModel {

    use Trait_Api;

    const ORDER_LIST = 'order/getlist';
    const ORDER_DETAIL = 'order/detail';
    const EXPRESS_DETAIL = 'express/detail';
    const SHOWCASE_NAME = 'showcase/detail';

    private $order_status = array(
        'WAIT_BUYER_PAY' => '等待买家付款',
        'WAIT_SELLER_SEND_GOODS' => '等待商家发货',
        'WAIT_BUYER_CONFIRM_GOODS' => '待买家确认收货',
        'TRADE_BUYER_SIGNED' => '买家已签收',
        'TRADE_CLOSED' => '退款完成',
        'TRADE_CLOSED_BY_USER' => '已关闭',
    );

    public function getList($params) {
        $result = $this->request(self::ORDER_LIST, $params);
        return $this->format_order_batch($result);
    }
    
    public function getExpress($params){
        $result = $this->request(self::EXPRESS_DETAIL, $params);
        return $this->format_express_struct($result);
    }

    public function getInfoById($order_id) {
        if (!$order_id) {
            //return false;
        }
        $params['order_id'] = $order_id;
        $result = $this->request(self::ORDER_DETAIL, $params);
        return $this->format_order_struct($result);
    }
    
    public function getShowcaseName($showcase_id){
        if(empty($showcase_id)){
            return FALSE;
        }
        $result = $this->request(self::SHOWCASE_NAME , $showcase_id);
        return $result['name']; 
    }
    
    /*
     * 格式化数据
     */
    public function tidy($order) {
        $o['oid'] = $order['oid'];
        $o['order_id'] = $order['order_id'];
        $o['total_fee'] = $order['total_fee'];
        $o['discount_fee'] = $order['discount_fee'];
        $o['payment_fee'] = $order['payment_fee'];
        $o['post_fee'] = $order['post_fee'];
        $o['showcase_id'] = $order['showcase_id'];
        $o['seller_id'] = $order['seller_id'];
        $o['showcase_id'] = $order['showcase_id'];
        $o['buyer_id'] = $order['buyer_id'];
        $o['receiver_province'] = $order['receiver_province'];
        $o['receiver_city'] = $order['receiver_city'];
        $o['receiver_district'] = $order['receiver_district'];
        $o['receiver_address'] = $order['receiver_address'];
        $o['receiver_zip'] = $order['receiver_zip'];
        $o['receiver_name'] = $order['receiver_name'];
        $o['receiver_mobile'] = $order['receiver_mobile'];
        $o['outer_tid'] = $order['outer_tid'];
        $o['state'] = $order['state'];
        $o['state_name'] = $this->order_status[$order['state']];
        $o['pay_type'] = $order['pay_type'];
        $o['pay_time'] = $order['pay_time'];
        $o['create_time'] = $order['create_time'];
        $o['update_time'] = $order['update_time'];
        $o['order_detail'] = $order['order_detail'];
        return $o;
    }
    
    /**
     * 格式化物流信息
     */
    public function tidy_express($data){
        $e['excom'] = $data[0]['excom'];
        $e['exnum'] = $data[0]['exnum'];
        $e['state'] = $data[0]['state'];
        return $e;
    }

    public function format_order_struct($data) {
        if ($data === false) {
            return false;
        }
        if (empty($data)) {
            return array();
        }

        return $this->tidy($data);
    }

    public function format_order_batch($datas) {
        if ($datas === false) {
            return false;
        }
        if (empty($datas)) {
            return array();
        }
        foreach ($datas['orders'] as &$data) {
            $data = $this->tidy($data);
        }
        return $datas;
    }
    
    /**
     * 初始化物流信息
     */
    public function format_express_struct($data){
        if($data === FALSE){
            return FALSE;
        }
        if(empty($data)){
            return array();
        }
        
        return $this->tidy_express($data);
    }
    
    /*
     * 遍历初始化物流信息
     */
    public function format_express_batch($datas){
        if($datas === FALSE){
            return FALSE;
        }
        if(empty($datas)){
            return array();
        }
        
        foreach ($datas['data'] as &$data){
            $data = $this->tidy_express($data);
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
