<?php
class SoapController extends Base{
    
    use Trait_Api;
    
    public $dzfp;
    public $youzan_order_model;
    public $youzan_order_detail;
    public $invoice_model;
    
    const INVOICE_STATUS_SUCCESS = 2; //发票成功
    const INVOICE_STATUS_FAIL = 3;  //开票失败
    
    public $sms;

    public function init() {
        $this->initAdmin();
        $this->sms = $this->getApi('notification');
        $this->dzfp = new Dzfp();
        $this->invoice_model = new InvoiceModel();
        $this->youzan_order_model = new YouZanOrderModel();
        $this->youzan_order_detail = new YouZanOrderDetailModel();
    }
    
    /**
     * 开具发票
     */
    public function indexAction(){
//        $this->checkRole();
        
        $order_id = $this->getRequest()->getPost('order_id');
        $type = intval($this->getRequest()->getPost('type'));
        $fpsl = $this->getRequest()->getPost('fpsl');
        $xsf_mc = $this->getRequest()->getPost('xsf_mc');
        $xsf_dzdh = $this->getRequest()->getPost('address');
        $kpr = $this->getRequest()->getPost('kpr');
        $yfp_hm = $this->getRequest()->getPost('yfp_hm');
        $yfp_dm = $this->getRequest()->getPost('yfp_dm');
        $invoice_title = $this->getRequest()->getPost('title');
        $id = $this->getRequest()->getPost('id');
        
        //判断用户是不是批量开发票
        if(is_array($order_id)){
            $this->batch($order_id,$type,$xsf_mc,$xsf_dzdh,$kpr);
        }
        
        //查询订单详情
        $order = $this->getInfoById($order_id, $fpsl);
        if($order['status'] !== 'TRADE_BUYER_SIGNED'){
            Tools::output(array('msg' => '只有签收的商品才能开发票', 'status' => 3));
        }
        $order['xsf_mc'] = $xsf_mc;
        $order['xsf_dzdh'] = $xsf_dzdh;
        $order['kpr'] = $kpr;
        $order['type'] = $type;
        $order['hjje'] = $order['payment'] - $order['hjse'];
        $order['invoice_title'] = $invoice_title;
        $order['count'] = count($order['order_detail']);
        $order['invoice_no'] = strtotime(date('Y-m-d H:i:s'));
        $order['yfp_hm'] = $yfp_hm;
        $order['yfp_dm'] = $yfp_dm;
        
        //开发票
        $result = $this->dzfp->fpkj($order, $order['order_detail']);
        if(!$result){
            Tools::output(array('msg' => $this->dzfp->getError(), 'status' => 3));
        } 
        //将发票信息存到数据表
        $params['invoice_type'] = $order['type'];
        $params['qr_code'] = $result['EWM'];
        $params['invoice_code'] = $result['FPDM'];
        $params['invoice_number'] = $result['FPHM'];
        $params['check_code'] = $result['JYM'];
        $params['jqbh'] = $result['JQBH'];
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
        $params['original_invoice_code'] = $order['yfp_hm'];
        $params['original_invoice_number'] = $order['yfp_dm'];
        
        //将开票信息存储到数据表
        $this->invoice_model->update($id,$params);
        Tools::output(array('msg' => '电子发票开票成功', 'status' => 2));
    }
    
    /**
     * 查看发票(请求API)
     */
    public function getInvoiceAction(){
//        $this->checkRole();
        
        $_id = $this->getRequest()->get('id');
        $order_info = $this->invoice_model->getInfo($_id);
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
    
    /**
     * ca
     */
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
//        $this->checkRole();
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
    
    /**
     * 批量开发票
     */
    public function batch($orders,$type,$xsf_mc,$xsf_dzdh,$kpr){
        //遍历批量开票信息
        $orderdata = array_filter($orders);
        foreach ($orderdata as $value){
            $order = $this->getInfoById($value['order'], $value['sl']);
            if($order['status'] !== 'TRADE_BUYER_SIGNED'){
                continue;
            }
            $order['xsf_mc'] = $xsf_mc;
            $order['xsf_dzdh'] = $xsf_dzdh;
            $order['kpr'] = $kpr;
            $order['type'] = $type;
            $order['hjje'] = $order['payment'] - $order['hjse'];
            $order['invoice_title'] = $value['title'];
            $order['count'] = count($order['order_detail']);
            $order['invoice_no'] = strtotime(date('Y-m-d H:i:s'));
            //卡发票
            $result = $this->dzfp->fpkj($order, $order['order_detail']);
            if(!$result){
                continue;
            }
            $params['invoice_type'] = $order['type'];
            $params['qr_code'] = $result['EWM'];
            $params['invoice_code'] = $result['FPDM'];
            $params['invoice_number'] = $result['FPHM'];
            $params['check_code'] = $result['JYM'];
            $params['jqbh'] = $result['JQBH'];
            $params['state'] = self::INVOICE_STATUS_SUCCESS;
            $params['state_message'] = $result['DESC'];
            $params['seller_name'] = $xsf_mc;
            $params['seller_address'] = $xsf_dzdh;
            $params['drawer'] = $kpr;
            $params['payment_fee'] = $order['payment'];
            $params['total_tax'] = $order['hjse'];
            $params['tax_rate'] = $value['sl'];
            $params['jshj'] = $order['payment'];
            $params['invoice_time'] = $result['KPRQ'];
            $params['order_time'] = $order['created'];
            $params['total_fee'] = $order['hjje'];
            $params['invoice_no'] = $order['invoice_no'];

            $this->invoice_model->update($value['id'],$params);
        }
        echo json_encode(array('msg' => '批量开票成功', 'status' => 3));
        exit;
    }
   
}
