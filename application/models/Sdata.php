<?php

/**
 * @name SdataModel
 * @desc 数据统计
 */
class SdataModel {

    const ORDER_OVERVIEW = 'api/order/overview';
    const ORDER_SKULIST = 'api/order/skulist';
    const PAGEDATA_OVERVIEW = 'api/pagedata/overview';
    const PAGEDATA_VIEWS = 'api/pagedata/getlist_by_date';
    const PAGEDATA_RANKLIST = 'api/pagedata/getlist_by_pageid';


    public function orderOverview($params) {
        $result = Sdata::request(self::ORDER_OVERVIEW, $params);
        return $this->format_order_datas_batch($result);
    }

    public function skulist($params) {
        return Sdata::request(self::ORDER_SKULIST, $params);
    }

    public function PageOverview($params) {
        return Sdata::request(self::PAGEDATA_OVERVIEW, $params);
    }

    public function ranklist($params) {
        $result = Sdata::request(self::PAGEDATA_RANKLIST, $params);
        return $this->format_pages_batch($result);
    }

    public function views($params) {
        return Sdata::request(self::PAGEDATA_VIEWS, $params);
    }


    /**
     * 格式化
     */
    public function format_pages_struct($data) {
        if (empty($data)) {
            return array();
        }
        $format_data = array(
            'showcase_id'  =>   $data['showcase_id'],
            'page_id'      =>   $data['page_id'],
            'page_title'   =>   $data['page_title'],
            'total_pv'     =>   intval($data['total_pv']),
            'total_uv'     =>   intval($data['total_uv']),
            'share_pv'     =>   intval($data['share_pv']),
            'share_uv'     =>   intval($data['share_uv']),
            'avg_stay'     =>   doubleval($data['avg_stay']),
        );

        return $format_data;
    }

    public function format_pages_batch($datas) {
        if (empty($datas)) {
            return array();
        }
        foreach ($datas as &$data){
            $data = $this->format_pages_struct($data);
        }

        return $datas;
    }


    /**
     * 格式化物流信息
     */
    public function format_order_datas_struct($data) {
        if (empty($data)) {
            return array();
        }
        $format_data = array(
            'showcase_id' => $data['showcase_id'],
            'order_num' => $data['order_num'],
            'order_sum' => $data['order_sum'],
            'order_people' => $data['order_people'],
            'paied_num' => $data['paied_num'],
            'paied_num_wx' => $data['paied_num_wx'],
            'paied_num_jd' => $data['paied_num_jd'],
            'paied_sum' => $data['paied_sum'],
            'paied_people' => $data['paied_people'],
            'paied_people_repeat' => $data['paied_people_repeat'],
            'paied_people_sum' => $data['paied_people_sum'],
            'paied_people_num' => $data['paied_people_num'],
            'date' => $data['date'],
        );

        return $format_data;
    }
    
    public function format_order_datas_batch($datas) {
        if (empty($datas)) {
            return array();
        }
        foreach ($datas as &$data){
            $data = $this->format_order_datas_struct($data);
        }

        return $datas;
    }

}
