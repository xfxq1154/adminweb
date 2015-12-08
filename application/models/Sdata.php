<?php

/**
 * @name SdataModel
 * @desc 数据统计
 */
class SdataModel {

    use Trait_Api;

    const ORDER_DETAIL = 'result/order_detail';


    public function getList($params) {
        $result = Sdata::request(self::ORDER_DETAIL, $params);
        return $this->format_order_datas_batch($result);
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
