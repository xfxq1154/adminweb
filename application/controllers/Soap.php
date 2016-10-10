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
    /** @var  */
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
        $payee = $this->getRequest()->getPost('payee');
        $review = $this->getRequest()->getPost('review');
        $id = $this->getRequest()->getPost('id');
        if(!$order_id){
            Tools::output(array('msg' => '请先选择订单', 'status' => 2));
        }
        //判断用户是不是批量开发票
        if(is_array($order_id)){
            $this->batch($order_id,$xsf_mc,$xsf_dzdh,$kpr,$type, $payee, $review);
        }
        //单个发票开具
        $params = [
            'seller_address' => $xsf_dzdh,
            'seller_name' => $xsf_mc,
            'drawer' => $kpr,
            'state' => self::INVOICE_STATUS_LODING,
            'review' => $review,
            'payee' => $payee,
            'invoice_type' => $type
        ];
        $this->invoice_model->update($id, $params);
        Tools::output(array('msg' => '开票申请已经提交,请稍后查看', 'status' => 2));
    }

    /**
     * @explain 补开红字发票
     */
    public function redInvoiceAction(){
        $this->checkRole();
        $id = json_decode($this->getRequest()->get('data'), true)['id'];
        $invoice_info = $this->invoice_model->getInfo($id);
        if($invoice_info['state'] == 2){
            echo json_encode(array('info' => '已经开具的红票,无法重新开具'));exit;
        }
        $params = array(
            'original_invoice_code'     => $invoice_info['invoice_code'],
            'original_invoice_number'   => $invoice_info['invoice_number'],
            'original_check_code'       => $invoice_info['check_code'],
            'project_name'              => $invoice_info['project_name'],
            'seller_name'               => $invoice_info['seller_name'],
            'seller_address'            => $invoice_info['seller_address'],
            'invoice_title'             => $invoice_info['invoice_title'],
            'drawer'                    => $invoice_info['drawer'],
            'payee'                     => $invoice_info['payee'],
            'review'                    => $invoice_info['review'],
            'sku_type'                  => $invoice_info['sku_type'],
            'buyer_tax_id'              => $invoice_info['buyer_tax_id'],
            'order_id'                  => $invoice_info['order_id'].'RED',
            'payment_fee'               => $invoice_info['payment_fee'],
            'jshj'                      => $invoice_info['jshj'],
            'total_fee'                 => $invoice_info['total_fee'],
            'total_tax'                 => $invoice_info['total_tax'],
            'blue_invoice_id'           => $invoice_info['id'],
            'one_tax'                   => $invoice_info['one_tax'],
            'two_tax'                   => $invoice_info['two_tax'],
            'three_tax'                 => $invoice_info['three_tax'],
            'invoice_type'              => 1,
            'state'                     => self::INVOICE_STATUS_LODING
        );
        $this->invoice_model->insert($params);
        Tools::output(array('info' => '开票申请已经提交,请稍后查看', 'status' => 2));
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
     * @desc 批量开票
     * @param $orders
     * @param $xsf_mc
     * @param $xsf_dzdh
     * @param $kpr
     * @param $type
     * @param string $payee
     * @param string $review
     */
    public function batch($orders,$xsf_mc, $xsf_dzdh, $kpr, $type,$payee = '', $review = ''){
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
                'invoice_type' => $type,
                'payee' => $payee,
                'review' => $review
            ];
            $this->invoice_model->update($value['id'],$params);
        }
        echo json_encode(array('msg' => '批量开票申请已经提交,请稍后查看', 'status' => 3));exit;
        
    }
}
