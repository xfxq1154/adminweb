<?php
class SoapController extends Base{
    
    public $dzfp;
    public $youzan_order_model;
    public $youzan_order_detail;
    public $invoice_model;
    
    const INVOICE_STATUS_SUCCESS = 2; //发票成功
    const INVOICE_STATUS_FAIL = 3;  //开票失败

    public function init() {
        $this->dzfp = new Dzfp();
        $this->invoice_model = new InvoiceModel();
        $this->youzan_order_model = new YouZanOrderModel();
        $this->youzan_order_detail = new YouZanOrderDetailModel();
    }
    
    
    /**
     * 开具发票
     */
    public function indexAction(){
        
        echo json_encode([
            'status' => 2,
            'hello' => 'WORD',
            'post' => $_POST,
        ]);
        exit;
        
        $order_id = $this->getRequest()->get('order_id');
        $type = intval($this->getRequest()->get('type'));
        $fpsl = $this->getRequest()->get('fpsl', 0);
        $xsf_mc = $this->getRequest()->get('xsf_mc','测试');
        $xsf_dzdh = $this->getRequest()->get('xsf_dzdh', '北京市朝阳区通惠河北路朗园Vintage2号楼A座七层');
        $kpr = $this->getRequest()->get('kpr','财务总监');
        //查询订单详情
        $order = $this->getInfoById($order_id, $fpsl);
        if($order['status'] !== 'TRADE_BUYER_SIGNED'){
            echo '未收货的订单不能开发票';exit;
        }
        $invoice = $this->invoice_model->getInfo($order_id);
        $order['xsf_mc'] = $xsf_mc;
        $order['xsf_dzdh'] = $xsf_dzdh;
        $order['kpr'] = $kpr;
        $order['type'] = $type == 1 ? 1 : 0;
        $order['hjje'] = $order['payment'] - $order['hjse'];
        $order['invoice_title'] = $invoice['invoice_title'];
        $order['count'] = count($order['order_detail']);
        $order['invoice_no'] = strtotime(date('Y-m-d H:i:s'));
        
        //开发票
        $result = $this->dzfp->fpkj($order, $order['order_detail']);
        if(!$result){
            $this->dzfp->getError();
            Tools::output(array('info' => $this->dzfp->getError(), 'status' => 0));
        } else {
            //将发票信息存到数据表
            $params['invoice_type'] = $order['type'];
            $params['qr_code'] = $result['EWM'];
            $params['invoice_code'] = $result['FPDM'];
            $params['invoice_number'] = $result['FPHM'];
            $params['check_code'] = $result['JYM'];
            $params['JQBH'] = $result['JQBH'];
            $params['state'] = self::INVOICE_STATUS_SUCCESS;
            $params['state_message'] = $result['DESC'];
            $params['seller_name'] = $xsf_mc;
            $params['seller_address'] = $xsf_dzdh;
            $params['drawer'] = $kpr;
            $params['payment_fee'] = $order['payment'];
            $params['total_tax'] = $order['hjse'];
            $params['tax_rate'] = $fpsl;
            $params['jshj'] = $order['payment'];
            $params['invoice_time'] = $result['KPRQ'];
            $params['order_time'] = $order['created'];
            $params['total_fee'] = $order['hjje'];
            $params['invoice_no'] = $order['invoice_no'];
        }
        $this->invoice_model->update($order_id,$params);
        Tools::output(array('info' => '电子发票开具成功', 'status' => 1));
    }
    
    /**
     * 查看发票
     */
    public function getInvoiceAction(){
        $order_id = $this->getRequest()->get('order_id');
        $order_info = $this->invoice_model->getInfo($order_id);
        $fp_dm = $order_info['invoice_code'];
        $fp_hm = $order_info['invoice_number'];
        $jym = $order_info['check_code'];
        $result = $this->dzfp->getpdf($fp_dm, $fp_hm, $jym);
        if(!$result){
            echo $this->dzfp->getError();
            exit;
        }
        $pdf = base64_decode($result);
        header("Content-Type: application/pdf");
        echo $pdf;
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
     * 查询有赞订单
     */
    public function getInfoById($order_id, $fpsl){
        $o_rs = $this->youzan_order_model->getInfo($order_id);
        if($o_rs === FALSE){
            return FALSE;
        }
        $detail_info = $this->youzan_order_detail->_getOrderDetail($order_id);
        if($detail_info == FALSE){
            return FALSE;
        }
        $o_rs['order_detail'] = $detail_info;
        $order_detail = $this->youzan_order_model->struct_order_data($o_rs, $fpsl);
        return $order_detail;
    }
   
}
