<?php

/**
 * @name StatProductModel
 * @desc 商品统计结果查询类
 */
class StatProductModel {

    const PRODUCT_LIST = 'api/product/getlist_by_pageid';

    public function productList($params) {
        return Sdata::request(self::PRODUCT_LIST, $params);
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
            'page_url'     =>   STORE_H5_HOST . 'product/detail?alias='. $data['page_id'],
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
}
