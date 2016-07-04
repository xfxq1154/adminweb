<?php

/**
 * @name DatasumModel
 * @desc 数据统计操作类
 */
class DatasumModel {

    const ORDER_SKULIST   = 'api/order/skulist';
    const ORDER_TODAY   = 'api/order/today';

    const VIEW_GETLIST = 'api/view/getlist';

    const PAGE_TYPE_FEATURE = 1;
    const PAGE_TYPE_PRODUCT = 2;
    const PAGE_TYPE_MAGAZINE = 3;

    private $_error;


    /**
     * 批量获取页面访问量
     */
    public function pageview_bacth($type, $pageids) {
        $params['type'] = $type;
        $params['page_ids'] = $pageids;
        $result = Sdata::request(self::VIEW_GETLIST, $params);
        return $this->format_view_batch($result);
    }

    /**
     * 当天数据统计
     */
    public function today($showcase_id) {
        if (!$showcase_id){
            return false;
        }
        $params['showcase_id']  =  $showcase_id;
        $result = Sdata::request(self::ORDER_TODAY, $params);
        if (!$result){
            $this->_setError();
        }
        return $result;
    }

    /**
     * 销售数据排行列表
     */
    public function skulist($params) {
        if (!$params){
            return false;
        }
        $result = Sdata::request(self::ORDER_SKULIST, $params);
        return $this->format_data_batch($result);
    }

    
    /**
     * 格式化数据
     */
    public function tidy($data) {
        if (empty($data)) {
            return array();
        }
        $res_data = [
            'showcase_id' => intval($data['showcase_id']),
            'pid' => intval($data['pid']),
            'sku_id' => intval($data['sku_id']),
            'title' => $data['title'],
            'sku_value' => $data['sku_value'],
            'total_pay' => $data['total_pay'],
            'total_order' => $data['total_order']
        ];

        return $res_data;
    }

    public function format_data_batch($datas) {
        if ($datas === false) {
            $this->_setError();
            return false;
        }
        if (empty($datas)) {
            return array();
        }
        foreach ($datas['skulist'] as &$data) {
            $data = $this->tidy($data);
        }
        return $datas;
    }

    /**
     * 格式化
     */
    public function tidy_view($data) {
        $feature['pv']          = $data['pv'];
        $feature['uv']          = $data['uv'];
        $feature['share_pv']    = $data['share_pv'];
        $feature['share_uv']    = $data['share_uv'];
        return $feature;
    }

    public function format_view_batch($datas) {
        if ($datas === false) {
            $this->_setError();
            return false;
        }

        if (empty($datas)) {
            return array();
        }

        $res = [];
        foreach ($datas as $data) {
            $res[$data['page_id']] = $this->tidy_view($data);
        }
        
        return $res;
    }


    private function _setError(){
        $error_info = Sdata::getError();
        $code = $error_info['code'];
        switch ($code){
            case 10006:
                $this->_error = 10002;
                break;
            case 10007:
                $this->_error = 10001;
                break;
            default :
                $this->_error = 10000;
                break;
        }
    }
    
    public function getError(){
        return $this->_error;
    }
}
