<?php

/**
 * @name StatPageModel
 * @desc 页面统计结果查询类
 */
class StatPageModel {

    const PAGEDATA_OVERVIEW = 'api/pagedata/overview';
    const PAGEDATA_VIEWS = 'api/pagedata/getlist_by_date';
    const PAGEDATA_RANKLIST = 'api/pagedata/getlist_by_pageid';

    private $uris = [
        'product' => 'product/detail?alias=',
        'feature' => 'feature/index/alias/',
        'magazine' => 'magazine/detail?alias=',
    ];

    public function overview($params) {
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
            'page_url'     =>   STORE_H5_HOST . $this->uris[$data['page_type']].$data['page_id'],
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
