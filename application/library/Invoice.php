<?php

/**
 * Created by PhpStorm.
 * User: wanghaiyang
 * Date: 16/3/30
 * Time: 下午8:22
 */
class Invoice
{

    public $dzfp;
    /**
     * @var YouZanOrderModel
     */
    public $youzan_order_model;

    /**
     * @var YouZanOrderDetailModel
     */
    public $youzan_order_detail;

    public function __construct() {
        $this->youzan_order_model = new YouZanOrderModel();
        $this->youzan_order_detail = new YouZanOrderDetailModel();
    }

    /**
     * @param $order_id
     * @return bool
     */
    public function getInfoById($order_id){
        $o_rs = $this->youzan_order_model->getInfo($order_id);
        if($o_rs === FALSE){
            return FALSE;
        }

        $detail_info = $this->youzan_order_detail->_getOrderDetail($order_id);
        if($detail_info == FALSE){
            return FALSE;
        }
        $o_rs['order_detail'] = $detail_info;
        $order_detail = $this->youzan_order_model->struct_order_data($o_rs);
        return $order_detail;
    }
}