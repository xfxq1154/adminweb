<?php
/**
 * @author why
 * @desc 发票操作类
 * @name InvoiceController
 */
class InvoiceController extends Base{
    
    use Trait_Layout,Trait_Pagger;
    
    public $invoice_mode;
    public $invoice_data_model;
    
    public $tax_rate = [
        '1' => 0,
        '2' => 0.06,
        '3' => 0.17
    ];
    
    public $status = [
        1 => '未开发票',
        2 => '开票成功',
        3 => '开票失败'
    ];

    public function init() {
        $this->initAdmin();
        Yaf_Loader::import(ROOT_PATH . '/application/library/phpExcel/reader.php');
        $this->invoice_mode = new InvoiceModel();
        $this->invoice_data_model = new InvoicedataModel();
    }
    
    /**
     * 发票列表
     */
    public function showListAction(){
        $this->checkRole();
        
        $page_no = (int)$this->getRequest()->get('page_no', 1);
        $files = $this->getRequest()->getFiles('import');
        $mobile = $this->getRequest()->get('mobile');
        $order_id = $this->getRequest()->get('order_id');
        $status = $this->getRequest()->get('status');
        
        //导入
        if($this->getRequest()->isPost()){
            if(!$files){
                Tools::output(array('info'=> '请先选择上传文件','status' => 0));
            }
            if($files['error']){
                Tools::output(array('info'=> '上传失败','status' => 0));
            }
            if($files['size'] / 1024 > 1024){
                Tools::output(array('info'=>'上传文件不得大于1MB','status' => 0));
            }
            $xls = new Spreadsheet_Excel_Reader();
            $xls->setOutputEncoding('utf-8');
            $xls->read($files['tmp_name']);

            //将文件内容遍历循环存储到数据库
            $data = array();
            if(!$xls->sheets[0]['cells'][1]){
                Tools::output(array('info'=> '系统错误','status' => 0));
            }
            unset($xls->sheets[0]['cells'][1]);
            foreach ($xls->sheets[0]['cells'] as $key => $values){
                foreach (Fileds::$invoice as $k => $v){
                    $data[$v] = $values[$k];
                }
                $this->invoice_mode->insert($data);
            }
        }
        
        $result = $this->invoice_mode->getList($page_no, 20, 1, $mobile, $order_id, $status);
        //查询开票信息
        $invoice_info = $this->invoice_data_model->getInfo();
        $this->renderPagger($page_no, $result['total_nums'], '/invoice/showlist/page_no/{p}/status/'.$status, 20);
        $this->assign('data', $result);
        $this->assign('mobile', $mobile);
        $this->assign('order_id', $order_id);
        $this->assign('invoice_info', $invoice_info);
        $this->assign('tax_rate', $this->tax_rate);
        $this->assign('status', $this->status[$status]);
        $this->layout('invoice/list.phtml');
    }
    
    /**
     * 设置销售方地址电话
     */
    public function setInvoiceAction(){
        $xsf_mc = $this->getRequest()->getPost('xsf_mc');
        $kpr = $this->getRequest()->getPost('kpr');
        $address = $this->getRequest()->getPost('address');
        
        
        if (!$xsf_mc || !$kpr){
            echo json_encode(array('msg' => '必传参数缺失', 'status' => 0));
        }
        $params = [
            'seller_address' => $address,
            'drawer' => $kpr,
            'seller_name' => $xsf_mc
        ];
        
        $insertId = $this->invoice_data_model->insert($params);
        if(!$insertId){
            echo json_encode(array('msg' => '添加失败', 'status' => 3));exit;
        }
        echo json_encode(array('msg' => '添加成功', 'status' => 2));exit;
    }
    
    /**
     * 重新发送短信
     */
    public function repeatMessageAction(){
        $order_id = $this->getRequest()->get('order_id');
        if(!$order_id){
            echo json_encode(array('msg' => '订单号缺失' ,'status' => 3));exit;
        }
        $sms = new Sms();
        $invoice_info = $this->invoice_mode->getInfo($order_id);
        if(!$invoice_info){
            echo json_encode(array('msg' => '系统错误' ,'status' => 3));exit;
        }
        $phonenumber = $invoice_info['buyer_phone'];
        $message = $invoice_info['invoice_url'];
        $result = $sms->sendmsg($message, $phonenumber);
        if($result['status'] == 'ok'){
            echo json_encode(array('msg' => '短信发送成功', 'status' => 2));exit;
        }
        echo json_encode(array('msg' => '短信发送失败', 'status' => 3));exit;
    }
}

