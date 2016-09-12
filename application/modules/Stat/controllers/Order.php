<?php

/**
 * OrderController
 * @author yanbo
 */
class OrderController extends Statbase {

    /** @var  StatOrderModel */
    private $order_model;
    /** @var  StatPageModel */
    private $page_model;
    /** @var  StatProductModel */
    private $product_model;

    public function init() {
        parent::init();

        $this->order_model = new StatOrderModel();
        $this->page_model = new StatPageModel();
        $this->product_model = new StatProductModel();
    }

    function dashboardAction() {
        $params['showcase_id'] = $this->showcase_id;
        $params['start_created'] = $this->start_created;
        $params['end_created'] = Tools::format_date($this->end_created);

        $res = $this->page_model->overview($params);
        $total_uv = ($res['total_uv']) ? : 0;

        $total_sold_num = $this->product_model->soldNum($params);

        $result = $this->order_model->overview($params);
        foreach ($result as $val){
            $key = '"'.date('m-d',strtotime($val['odate'])).'"';
            $chart_data[$key] = $val;
        }

        $order_peo = [];
        $order_num = [];
        $order_sum = [];
        $paied_peo = [];
        $paied_num = [];
        $paied_sum = [];
        $dates = $this->_get_time_string();
        if($dates){
            foreach ($dates as $val){
                if(isset($chart_data[$val])){
                    $order_peo[] = $chart_data[$val]['total_nop'];        //下单人数
                    $order_num[] = $chart_data[$val]['total_num'];        //下单笔数
                    $order_sum[] = $chart_data[$val]['total_amount'];     //下单金额
                    $paied_peo[] = $chart_data[$val]['trans_nop'];        //付款人数
                    $paied_num[] = $chart_data[$val]['trans_num'];        //付款笔数
                    $paied_sum[] = $chart_data[$val]['trans_amount'];     //付款金额
                } else {
                    $order_num[] = 0;  //下单笔数
                    $paied_num[] = 0;  //付款笔数
                    $paied_sum[] = 0;  //付款金额
                }
            }
            $order_num_string = implode(',', $order_num);
            $paied_num_string = implode(',', $paied_num);
            $paied_sum_string = implode(',', $paied_sum);


        }
        $order_peo_total = array_sum($order_peo);
        $order_num_total = array_sum($order_num);
        $order_sum_total = array_sum($order_sum);
        $paied_peo_total = array_sum($paied_peo);
        $paied_num_total = array_sum($paied_num);
        $paied_sum_total = array_sum($paied_sum);
        $paied_people_avg = ($paied_peo_total) ? round($paied_sum_total / $paied_peo_total, 2) : 0;  //客单价

        $this->assign('dates', implode(',', $dates));
        $this->assign('total_uv', $total_uv); //访客数
        $this->assign('total_sold_num', $total_sold_num); //总销量
        $this->assign('order_peo', $order_peo_total); //下单人数
        $this->assign('order_num', $order_num_total); //下单笔数
        $this->assign('order_sum', $order_sum_total); //下单金额
        $this->assign('paied_peo', $paied_peo_total); //付款人数
        $this->assign('paied_num', $paied_num_total); //付款笔数
        $this->assign('paied_sum', $paied_sum_total); //付款金额
        $this->assign('paied_people_avg', $paied_people_avg);

        $this->assign('order_num_string', $order_num_string);
        $this->assign('paied_num_string', $paied_num_string);
        $this->assign('paied_sum_string', $paied_sum_string);
        $this->_display('order/dashboard.phtml');
    }

    /*
     * 格式化数据
     */

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
