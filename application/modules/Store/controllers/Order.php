<?php

/**
 * OrderController
 * @author yanbo
 */
class OrderController extends Storebase {

    private $order_status = array(
        'WAIT_BUYER_PAY' => '待付款',
        'WAIT_SELLER_SEND_GOODS' => '待发货',
        'WAIT_BUYER_CONFIRM_GOODS' => '已发货',
        'TRADE_BUYER_SIGNED' => '已签收',
        'TRADE_CLOSED' => '已退款',
        'TRADE_CLOSED_BY_USER' => '已关闭',
    );
    
    public function init() {
        parent::init();
    }

    function indexAction() {
        $this->setShowcaseList();
        $order_no = $this->input_get_param('order_no');
        $number = $this->input_get_param('number');
        $showcase_id = $this->input_get_param('showcase_id');
        $status = $this->input_get_param('status');
        $outer_tid = $this->input_get_param('outer_tid');
        $spm = $this->input_get_param('spm');
        $page_no = $this->input_get_param('page_no');

        $state     = $status ? $status : '';
        $mobile    = $number ? $number : '';
        $order_id  = $order_no ? $order_no : '';
        $outer_tid = $outer_tid ? $outer_tid : '';
        $spm       = $spm ? $spm : '';

        $page_size = 20;
        $order_list = [
            'page_no'     => $page_no,
            'page_size'   => $page_size,
            'mobile'      => $mobile,
            'order_id'    => $order_id,
            'status'      => $state,
            'showcase_id' => $showcase_id,
            'outer_tid'   => $outer_tid,
            'spm'         => $spm,
        ];

        $result = $this->store_model->orderList($order_list);
        $orderList = $this->format_order_batch($result);
        
        $this->renderPagger($page_no ,$orderList['total_nums'] , "/store/order/index?page_no={p}&number={$mobile}&order_no={$order_id}&status={$state}&showcase_id={$showcase_id}&outer_tid={$outer_tid}&spm={$spm}", $page_size);
        $this->assign('mobile', $mobile);
        $this->assign('status', $status);
        $this->assign('order_no', $order_id);
        $this->assign('spm', $spm);
        $this->assign('outer_tid', $outer_tid);
        $this->assign('showcase_id', $showcase_id);
        $this->assign("list", $orderList['orders']);
        $this->layout("order/showlist.phtml");
    }

    function infoAction() {
        $order_id = $this->input_get_param('id');
        $showcase_id = $this->input_get_param('showcase_id');

        $result = $this->store_model->orderDetail($order_id);
        $detail = $this->format_order_struct($result);

        $this->assign("oinfo", $detail);
        $this->layout("order/detail.phtml");
    }

    public function tidy($order) {
        $o['order_id'] = $order['order_id'];
        $o['total_fee'] = $order['total_fee'];
        $o['discount_fee'] = $order['discount_fee'];
        $o['payment_fee'] = $order['payment_fee'];
        $o['post_fee'] = $order['post_fee'];
        $o['showcase_id'] = $order['showcase_id'];
        $o['showcase_name'] = $this->showcase_list[$order['showcase_id']];
        $o['seller_id'] = $order['seller_id'];
        $o['showcase_id'] = $order['showcase_id'];
        $o['buyer_id'] = $order['buyer_id'];
        $o['receiver_province'] = $order['receiver_province'];
        $o['receiver_city'] = $order['receiver_city'];
        $o['receiver_district'] = $order['receiver_district'];
        $o['receiver_address'] = $order['receiver_address'];
        $o['invoice_title'] = $order['invoice_title'];
        $o['receiver_zip'] = $order['receiver_zip'];
        $o['receiver_name'] = $order['receiver_name'];
        $o['receiver_mobile'] = $order['receiver_mobile'];
        $o['outer_tid'] = $order['outer_tid'];
        $o['state'] = $order['state'];
        $o['state_name'] = $this->order_status[$order['state']];
        $o['pay_type'] = $order['pay_type'];
        $o['pay_time'] = $order['pay_time'];
        $o['create_time'] = $order['create_time'];
        $o['refund_time'] = $order['refund_time'];
        $o['shipping_time'] = $order['shipping_time'];
        $o['update_time'] = $order['update_time'];
        $o['order_detail'] = $order['order_detail'];
        $o['spm'] = $order['spm'];
        return $o;
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
}
