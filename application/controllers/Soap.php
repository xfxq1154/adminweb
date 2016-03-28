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
        $this->checkRole();
        
        $order_id = $this->getRequest()->getPost('order_id');
        $type = intval($this->getRequest()->getPost('type'));
        $fpsl = $this->getRequest()->getPost('fpsl');
        $xsf_mc = $this->getRequest()->getPost('xsf_mc');
        $xsf_dzdh = $this->getRequest()->getPost('address');
        $kpr = $this->getRequest()->getPost('kpr');
        $invoice_title = $this->getRequest()->getPost('title');
        $id = $this->getRequest()->getPost('id');
        $phone = $this->getRequest()->getPost('phone');
        
        //判断用户是不是批量开发票
        if(is_array($order_id)){
            $this->batch($order_id,$xsf_mc,$xsf_dzdh,$kpr);
        }
        
        if ($type == 1){
            $this->redInvoice($id);
        }
        if(!$order_id){
            Tools::output(array('msg' => '提交参数有误', 'status' => 3));
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
        $order['receiver_mobile'] = $phone;
        
        //开发票
        $result = $this->dzfp->fpkj($order, $order['order_detail']);
        if(!$result){
            Tools::output(array('msg' => $this->dzfp->getError(), 'status' => 3));
        }
        $params = $this->setParameter($order, $result, $fpsl);
        //将开票信息存储到数据表
        $this->invoice_model->update($id,$params);
        Tools::output(array('msg' => '电子发票开票成功', 'status' => 2));
    }
    
    /**
     * 开具红票
     */
    public function redInvoice($id){
        if(!$id){
            echo json_encode(array('mag' => '系统错误'));exit;
        }
        $invoice_info = $this->invoice_model->getInfo($id);
        if(!$invoice_info){
            echo json_encode(array('mag' => '系统错误'));exit;
        }
        //查询有赞订单详情
        $order['order_detail'] = $this->youzan_order_detail->_getOrderDetail($invoice_info['order_id']);
        //格式化订单详情
        $order = $this->youzan_order_model->struct_orderdetail_batch($order, $invoice_info['tax_rate']);
        
        //reset 
        $order['xsf_mc'] = $invoice_info['seller_name'];
        $order['xsf_dzdh'] = $invoice_info['seller_address'];
        $order['kpr'] = $invoice_info['drawer'];
        $order['type'] = 1;
        $order['count'] = count($order['order_detail']);
        $order['hjje'] = $invoice_info['total_fee'];
        $order['hjse'] = $invoice_info['total_tax'];
        $order['payment'] = $invoice_info['payment_fee'];
        $order['invoice_title'] = $invoice_info['invoice_title'];
        $order['invoice_no'] = $invoice_info['invoice_no'];
        $order['yfp_hm'] = $invoice_info['invoice_number'];
        $order['yfp_dm'] = $invoice_info['invoice_code'];
        $order['receiver_mobile'] = $invoice_info['buyer_phone'];
        
        //开具发票
        $result = $this->dzfp->fpkj($order, $order['order_detail']);
        if(!$result){
            Tools::output(array('msg' => $this->dzfp->getError(), 'status' => 3));
        }
        $params = array(
            'original_invoice_code' => $invoice_info['invoice_number'],
            'original_invoice_number' => $invoice_info['invoice_code'],
            'invoice_type' => 1
            );
        $this->invoice_model->update($invoice_info['id'], $params);
        Tools::output(array('msg' => '电子发票开票成功', 'status' => 2));
    }
    
    /**
     * 查看发票(请求API)
     */
    public function getInvoiceAction(){
        $this->checkRole();
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
    public function batch($orders,$xsf_mc,$xsf_dzdh,$kpr){
        //遍历批量开票信息
        $orderdata = array_filter($orders);
        foreach ($orderdata as $value){
            $order = $this->getInfoById($value['order'], $value['sl']);
            if($order['status'] !== 'TRADE_BUYER_SIGNED'){
                $this->invoice_model->update($value['id'],array('state' => self::INVOICE_STATUS_FAIL, 'state_message' => '订单状态不符'));
                continue;
            }
            $order['xsf_mc'] = $xsf_mc;
            $order['xsf_dzdh'] = $xsf_dzdh;
            $order['kpr'] = $kpr;
            $order['type'] = 0;
            $order['hjje'] = $order['payment'] - $order['hjse'];
            $order['invoice_title'] = $value['title'];
            $order['count'] = count($order['order_detail']);
            $order['invoice_no'] = strtotime(date('Y-m-d H:i:s'));
            $order['receiver_mobile'] = $value['phone'];
            //卡发票
            $result = $this->dzfp->fpkj($order, $order['order_detail']);
            if(!$result){
                continue;
            }
            //设置参数
            $params = $this->setParameter($order, $result, $value['sl']);
            //更新到数据表
            $this->invoice_model->update($value['id'],$params);
        }
        echo json_encode(array('msg' => '批量开票成功', 'status' => 3));exit;
        
    }
    
    /**
     *统一设置参数
     */
    public function setParameter(array $order, $result, $fpsl){
        $params = array();
        
        $params['invoice_type'] = $order['type'];
        $params['qr_code'] = $result['EWM'];
        $params['invoice_code'] = $result['FPDM'];
        $params['invoice_number'] = $result['FPHM'];
        $params['check_code'] = $result['JYM'];
        $params['jqbh'] = $result['JQBH'];
        $params['state'] = self::INVOICE_STATUS_SUCCESS;
        $params['state_message'] = $result['DESC'];
        $params['seller_name'] = $order['xsf_mc'];
        $params['seller_address'] = $order['xsf_dzdh'];
        $params['drawer'] = $order['kpr'];
        $params['payment_fee'] = $order['payment'];
        $params['total_tax'] = $order['hjse'];
        $params['tax_rate'] = $fpsl;
        $params['jshj'] = $order['payment'];
        $params['invoice_time'] = $result['KPRQ'];
        $params['order_time'] = $order['created'];
        $params['total_fee'] = $order['hjje'];
        $params['invoice_no'] = $order['invoice_no'];
        
        return $params;
    }
   
}
