<?php
/**
 * @author why
 * Class SoapController
 */
class SoapController extends Base{
    
    use Trait_Api;

    /** @var  Dzfp */
    public $dzfp;

    /** @var InvoiceModel  */
    public $invoice_model;

    /** @var SkuModel */
    public $sku_model;

    /** @var 发票状态 */
    const INVOICE_STATUS_LODING = 5; //待开发票

    public function init() {
        $this->initAdmin();
        $this->dzfp = new Dzfp();
        $this->sku_model = new SkuModel();
        $this->invoice_model = new InvoiceModel();
    }
    
    /**
     * 开具发票
     */
    public function indexAction(){
        $this->checkRole();

        $order_id = $this->getRequest()->getPost('order_id');
        $type = intval($this->getRequest()->getPost('type'));
        $xsf_mc = $this->getRequest()->getPost('xsf_mc');
        $xsf_dzdh = $this->getRequest()->getPost('address');
        $kpr = $this->getRequest()->getPost('kpr');
        $id = $this->getRequest()->getPost('id');

        if(!$order_id){
            Tools::output(array('msg' => '请先选择订单', 'status' => 2));
        }
        
        //判断用户是不是批量开发票
        if(is_array($order_id)){
            $this->batch($order_id,$xsf_mc,$xsf_dzdh,$kpr,$type);
        }

        if($type == 1){
            $this->redInvoice($id,$xsf_mc, $xsf_dzdh, $kpr);
        }

        //单个发票开具
        $params = [
            'seller_address' => $xsf_dzdh,
            'seller_name' => $xsf_mc,
            'drawer' => $kpr,
            'state' => self::INVOICE_STATUS_LODING,
            'invoice_type' => $type,
        ];
        $this->invoice_model->update($id, $params);
        Tools::output(array('msg' => '开票申请已经提交,请稍后查看', 'status' => 2));
    }

    /**
     * @desc 补开红字发票
     * @param $id
     * @param $xsf_mc
     * @param $xsf_dzdh
     * @param $kpr
     */
    public function redInvoice($id, $xsf_mc, $xsf_dzdh, $kpr){
        if(!$id){
            echo json_encode(array('msg' => '参数缺失'));exit;
        }
        $invoice_info = $this->invoice_model->getInfo($id);
        if(!$invoice_info){
            echo json_encode(array('msg' => '系统错误'));exit;
        }
        if($invoice_info['state'] == 2){
            echo json_encode(array('msg' => '已经开具的红票,无法重新开具'));exit;
        }

        $params = array(
            'original_invoice_code' => $invoice_info['invoice_number'],
            'original_invoice_number' => $invoice_info['invoice_code'],
            'invoice_type' => 1,
            'state' => self::INVOICE_STATUS_LODING,
            'seller_address' => $xsf_dzdh,
            'seller_name' => $xsf_mc,
            'drawer' => $kpr
            );
        $this->invoice_model->update($invoice_info['id'], $params);
        Tools::output(array('msg' => '开票申请已经提交,请稍后查看', 'status' => 2));
    }

    /**
     * @desc 查看发票(请求API)
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
     * 测试ca加密
     */
    public function test3Action(){
        $src = "'1234567891'";
        $result = $this->dzfp->encrypt($src);
        $r2 = $this->dzfp->decrypt($result['encrypt'], $result['sign']);
        
        var_dump("原文:$src", $result,"解密结果:$r2");
        exit;
    }

    /**
     * @desc 批量开具蓝字发票
     * @param $orders
     * @param $xsf_mc
     * @param $xsf_dzdh
     * @param $kpr
     * @param $type
     */
    public function batch($orders,$xsf_mc, $xsf_dzdh, $kpr, $type){
        //遍历批量开票信息
        $orderdata = array_filter($orders);
        foreach ($orderdata as $value){
            $status = $this->invoice_model->getInfo($value['id']);
            //判断这张发票是不是待开状态
            if($status['state'] == 5){
                continue;
            }
            $params = [
                'seller_address' => $xsf_dzdh,
                'seller_name' => $xsf_mc,
                'drawer' => $kpr,
                'state' => self::INVOICE_STATUS_LODING,
                'invoice_type' => $type
            ];
            $this->invoice_model->update($value['id'],$params);
        }
        echo json_encode(array('msg' => '批量开票申请已经提交,请稍后查看', 'status' => 3));exit;
        
    }
}
