<?php

/**
 * @name StatOrderModel
 * @desc 订单统计结果查询类
 */
class StatOrderModel {

    const ORDER_OVERVIEW = 'api/order/overview';
    const ORDER_SKULIST = 'api/order/skulist';


    public function overview($params) {
        $result = Sdata::request(self::ORDER_OVERVIEW, $params);
        return $this->format_order_datas_batch($result);
    }

    public function skulist($params) {
        return Sdata::request(self::ORDER_SKULIST, $params);
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
