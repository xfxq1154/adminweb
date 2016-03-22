<?php
class SoapController extends Base{
    
    public $dzfp;
    public $youzan_order_model;
    public $youzan_order_detail;
    
    const SL1 = 0.01;
    const SL2 = 0.17;
    const SL3 = 0;
    const JYM = '03527779231551883440';


    public function init() {
        $this->dzfp = new Dzfp();
        $this->youzan_order_model = new YouZanOrderModel();
        $this->youzan_order_detail = new YouZanOrderDetailModel();
    }
    
    
    /**
     * 开具发票
     */
    public function indexAction(){
        $order_id = $this->getRequest()->get('order_id');
        $type = intval($this->getRequest()->get('type'));
        $fpsl = $this->getRequest()->get('fpsl');
        $xsf_mc = $this->getRequest()->get('xsf_mc','北京四维造物信息科技有限公司');
        $xsf_dzdh = $this->getRequest()->get('xsf_dzdh');
        $kpr = $this->getRequest()->get('kpr','财务总监');
        //查询订单详情
        $order = $this->getInfoById($order_id);
//        if($order['status'] !== 'TRADE_BUYER_SIGNED'){
//            echo '未收货的订单不能开发票';exit;
//        }
        
        //格式化订单
        $order['xsf_mc'] = $xsf_mc;
        $order['xsf_dzdh'] = $xsf_dzdh;
        $order['kpr'] = $kpr;
        $order['type'] = $type == 1 ? 1 : 0;
        
        //格式订单详情
        $order_detail = $this->format_order_batch($info, $fpsl);
        $order_info['hjse'] = $order_detail['total_fpes'];
        $order_info['hjje'] = $order_info['payment_fee'] - $order_info['hjse'];
        
        //开发票
        $result = $this->dzfp->fpkj($order_info, $order_detail['order_detail']);
        var_dump($result);exit;
    }
    
    public function testAction(){
        $fp_dm = $this->getRequest()->get('fp_dm');
        $fp_hm = $this->getRequest()->get('fp_hm');
        $result = $this->dzfp->getpdf($fp_dm, $fp_hm, self::JYM);
//        $result = $this->dzfp->getpdf('111001571072','89278013', self::JYM);
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
    
    /**
     * 格式化订单详情
     */
    public function format_order_batch($datas, $fpsl){
        if($datas ===  FALSE){
            return FALSE;
        }
        if(empty($datas)){
            return array();
        }
        foreach ($datas['order_detail'] as &$data){
            $data = $this->detailTidy($data, $fpsl);
            $float = $data['se'];
            $int += ($float * 100);
        }
        $datas['total_fpes'] = $int /100;
        return $datas;
    }
    
    /**
     * 详情格式化
     */
    public function detailTidy($data, $fpsl){
        $order_info = [
            'title' => $data['title'],
            'num' => $data['num'],
            'shiped_num' => $data['shiped_num'],
            'price' => $data['price'],
            'pay_price' => $data['pay_price'] - round($data['pay_price'] - ($data['pay_price'] / (1 + $fpsl)),2),
            'sl' => $fpsl,
            'se' => round($data['pay_price'] - ($data['pay_price'] / (1 + $fpsl)),2)
            
        ];
        return $order_info;
    }
    
    /**
     * 查询有赞订单
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
