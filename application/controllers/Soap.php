<?php
class SoapController extends Base{
    
    public $dzfp;
    public $order_model;
    public function init() {
        $this->dzfp = new Dzfp();
        $this->order_model = new BpOrderModel();
    }

    public function indexAction(){
        $order_id = $this->getRequest()->get('order_id');
        $info = $this->order_model->getInfoById($order_id);
//        $order_info = $this->format_order_struct($info);
        echo "<pre>";
        print_r($info);exit;
        $result = $this->dzfp->fpkj();
        var_dump($result);exit;
    }
    
    /**
     * 格式化订单
     */
    public function format_order_struct($data){
        if(empty($data)){
            return FALSE;
        }
        if($data === FALSE){
            return FALSE;
        }
        $this->tidy($data);
    }
    
    /**
     * 订单格式化
     */
    public function tidy($data){
        $order_info = [
            'total_fee' => $data['total_fee'],
            'order_id' => $data['payment_fee'],
            'order_id' => $data['order_id'],
            'order_id' => $data['order_id'],
            'order_id' => $data['order_id'],
            'order_id' => $data['order_id'],
            'order_id' => $data['order_id'],
            'order_id' => $data['order_id'],
            'order_id' => $data['order_id'],
            'order_id' => $data['order_id'],
            'order_id' => $data['order_id'],
            'order_id' => $data['order_id'],
            'order_id' => $data['order_id'],
            'order_id' => $data['order_id'],
            'order_id' => $data['order_id'],
            'order_id' => $data['order_id'],
            
        ];
    }
   
}
