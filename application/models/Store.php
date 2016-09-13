<?php

/**
 * @name BpExpressModel
 * @desc 订单操作类
 */
class StoreModel {

    const EXPRESS_LIST = 'express/getlist';
    const OPLOGS_LIST = 'oplogs/getlist';
    const ORDER_LIST = 'order/getlist';
    const ORDER_DETAIL = 'order/detail';
    const PRODUCT_LIST = 'product/getlist';
    const PRODUCT_DETAIL = 'product/detail';
    const TASK_LIST = 'task/getlist';
    const TASK_JOBLIST = 'task/joblist';
    const TASK_ADD = 'task/create';

    
    public function expressList($params) {
        return Sapi::request(self::EXPRESS_LIST, $params);
    }

    public function oplogsList($showcase_id, $sourceid, $title, $uri, $nickname, $start_time, $end_time, $page_no, $page_size) {
        $params['showcase_id'] = $showcase_id;
        $params['source_id'] = $sourceid;
        $params['title'] = $title;
        $params['uri'] = $uri;
        $params['nickname'] = $nickname;
        $params['start_time'] = $start_time;
        $params['end_time'] = $end_time;
        $params['page_no'] = $page_no;
        $params['page_size'] = $page_size;
        return Sapi::request(self::OPLOGS_LIST, $params);
    }

    public function orderList($params) {
        return Sapi::request(self::ORDER_LIST, $params);
    }

    public function orderDetail($order_id) {
        $params['order_id'] = $order_id;
        return Sapi::request(self::ORDER_DETAIL, $params);
    }
    public function productList($params) {
        return Sapi::request(self::PRODUCT_LIST, $params);
    }

    public function productDetail($product_id) {
        $params['product_id'] = $product_id;
        return Sapi::request(self::PRODUCT_DETAIL, $params);
    }

    public function taskList($params){
        return Sapi::request(self::TASK_LIST, $params);
    }

    public function jobList($params){
        return Sapi::request(self::TASK_JOBLIST, $params);
    }

    public function taskCreate($params){
        return Sapi::request(self::TASK_ADD, $params, 'POST');
    }

}
