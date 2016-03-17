<?php
class SoapController extends Base{
    
    public $dzfp;
    public $order_model;
    public function init() {
        $this->dzfp = new Dzfp();
    }

    public function testAction(){
        $result = $this->dzfp->getpdf('111001571072','89278013', '03527779231551883440');
        if(!$result){
            echo $this->dzfp->getError();
            exit;
        }
        $pdf = base64_decode($result);
        header("Content-Type: application/pdf");
        echo $pdf;
        exit;
    }

    public function test2Action(){
        $result = $this->dzfp->fpcx('20160316131228');
        if(!$result){
            echo $this->dzfp->getError();
            exit;
        }
        var_dump($result);
        exit;
    }

    public function test3Action(){
        $src = "'1234567891'";
        $result = $this->dzfp->encryCfca($src);
        $r2 = $this->dzfp->deEncryCfca($result['encrypt'], $result['sign']);
        
        var_dump("原文:$src", $result,"解密结果:$r2");
        exit;
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
