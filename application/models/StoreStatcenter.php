<?php

/**
 * @name StoreStatcenterModel
 * @desc 商城统计中心类
 */
class StoreStatcenterModel {

    const ORDER_OVERVIEW = 'api/order/overview';
    const ORDER_SKULIST = 'api/order/skulist';
    const PAGEDATA_OVERVIEW = 'api/pagedata/overview';
    const PAGEDATA_VIEWS = 'api/pagedata/getlist_by_date';
    const PAGEDATA_RANKLIST = 'api/pagedata/getlist_by_pageid';
    const PRODUCT_LIST = 'api/product/getlist_by_pageid';
    const CHANNEL_LIST = 'api/channel/getlist';
    const CHANNEL_LIST_DATE = 'api/channel/getlist_group_by_date';

    private $channel_names;

    public function productList($params) {
        return Sdata::request(self::PRODUCT_LIST, $params);
    }

    public function channelList($params) {
        $result = Sdata::request(self::CHANNEL_LIST, $params);
        return $this->format_channel_batch($result);
    }

    public function channelListGroupByDate($params) {
        $result = Sdata::request(self::CHANNEL_LIST_DATE, $params);
        return $this->format_channel_batch($result);
    }

    public function setSpmsName($datas){
        $spms = [];
        foreach ($datas as $val){
            $spms[] = $val['spm'];
        }

        $channel_model = new StoreChannelModel();
        $result = $channel_model->detail_mulit($spms);
        if (!$result){
            return false;
        }
        foreach ($result as $channel){
            $this->channel_names[$channel['spm']] = $channel['name'];
        }
    }

    public function format_channel_batch($datas){
        if (!$datas){
            return false;
        }
        $this->setSpmsName($datas['list']);

        $format_list = [];
        $total_pv = [];
        $total_uv = [];
        $paied_num = [];
        $paied_fee = [];
        foreach ($datas['list'] as $data){
            $total_pv[] = $data['pv'];
            $total_uv[] = $data['uv'];
            $paied_num[] = $data['trans_num'];
            $paied_fee[] = $data['trans_amount'];

            $format_list[] = $this->format_channel_struct($data);
        }

        return [
            'format_list'  => $format_list,
            'overview' => [
                'total_pv'     => array_sum($total_pv),
                'total_uv'     => array_sum($total_uv),
                'total_order'  => array_sum($paied_num),
                'total_amount' => array_sum($paied_fee),
            ],
            'total_nums'   => $datas['total_nums']
        ];
    }

    public function format_channel_struct($data){
        return [
            'date'          => $data['date'],
            'spm'           => $data['spm'],
            'name'          => isset($this->channel_names[$data['spm']]) ? $this->channel_names[$data['spm']] : '未知渠道',
            'pv'            => $data['pv'],
            'uv'            => $data['uv'],
            'rate'          => $data['uv'] ? round($data['trans_num'] / $data['uv'] * 100, 2) : '0.00',
            'trans_num'     => $data['trans_num'],
            'trans_amount'  => $data['trans_amount'],
        ];
    }

    public function orderOverview($params) {
        $result = Sdata::request(self::ORDER_OVERVIEW, $params);
        return $this->format_order_datas_batch($result);
    }

    public function skulist($params) {
        return Sdata::request(self::ORDER_SKULIST, $params);
    }

    public function pageOverview($params) {
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
        );

        return $format_data;
    }

    public function format_pages_batch($datas) {
        if (empty($datas)) {
            return array();
        }
        foreach ($datas['list'] as &$data){
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
            'total_nop' => $data['total_nop'],
            'total_num' => $data['total_num'],
            'total_amount' => $data['total_amount'],
            'trans_nop' => $data['trans_nop'],
            'trans_num' => $data['trans_num'],
            'trans_amount' => $data['trans_amount'],
            'odate' => $data['odate'],
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
