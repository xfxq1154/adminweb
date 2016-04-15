<?php
/**
 * @author why
 * @desc 发票操作类
 * @name InvoiceController
 */
class InvoiceController extends Base{
    
    use Trait_Layout,Trait_Pagger;

    /** @var  InvoiceModel*/
    public $invoice_mode;

    /** @var SkuModel */
    public $sku_model;

    /** @var  InvoicedataModel */
    public $invoice_data_model;
    
    public $status = [
        1 => '未开发票',
        2 => '开票成功',
        3 => '开票失败',
        4 => '已发短信',
        5 => '开票中'
    ];
    
    public $state_name = [
        1 => '<span class="tag bg-green">未开发票</span>',
        2 => '<span class="tag bg-yellow">开票成功</span>',
        3 => '<span class="tag bg-blue">开票失败</span>',
        4 => '<span class="tag bg-bg-mix">已发短信</span>',
        5 => '<span class="tag bg-bg-blue">开票中</span>'
    ];
    
    public $host = ASSET_URL;

    public function init() {
        $this->initAdmin();
        Yaf_Loader::import(ROOT_PATH . '/application/library/phpExcel/reader.php');
        $this->sku_model = new SkuModel();
        $this->invoice_mode = new InvoiceModel();
        $this->invoice_data_model = new InvoicedataModel();
    }
    
    /**
     * 发票列表
     */
    public function showListAction(){
        $this->checkRole();
        $page_no = (int)$this->getRequest()->get('page_no', 1);
        $mobile = $this->getRequest()->get('mobile');
        $order_id = $this->getRequest()->get('order_id');
        $status = $this->getRequest()->get('status');
        $group_id= $this->getRequest()->get('group');
        
        $result = $this->invoice_mode->getList($page_no, 20, 1, $mobile, $order_id, $status, $group_id);

        //查询上传批次
        $group = $this->invoice_mode->getBatchGroup();
        $groups = array_filter($group);
        //查询开票信息
        $invoice_info = $this->invoice_data_model->getInfo();
        $invoice_info['host'] = $this->host;
        $invoice_info['mobile'] = $mobile;
        $invoice_info['order_id'] = $order_id;

        $this->renderPagger($page_no, $result['total_nums'], '/invoice/showlist/page_no/{p}?status/'.$status.'/group/'.$group_id, 20);
        $this->assign('data', $result);
        $this->assign('invoice_info', $invoice_info);
        $this->assign('status', $this->status[$status]);
        $this->assign('state_name', $this->state_name);
        $this->assign('group', $groups);
        $this->assign('group_val', $group_id);
        $this->layout('invoice/list.phtml');
    }
    
    /**
     * sku列表
     */
    public function skuListAction(){
        $this->checkRole();
        $page_no = $this->getRequest()->get('page_no', 1);
        $sku_id = $this->getRequest()->get('sku_id');
        $result = $this->sku_model->getList($page_no, 20, 1, $sku_id);
        $this->renderPagger($page_no, $result['total_nums'], '/invoice/skulist/page_no/{p}', 20);
        $this->assign('data', $result);
        $this->layout('invoice/skulist.phtml');
    }

    /**
     * @desc 添加sku 编码
     */
    public function addSkuAction(){
        $this->checkRole();

        if ($this->getRequest()->isPost()){
            $skus = array();
            $skus['sku_id'] = $this->getRequest()->getPost('sku_id');
            $skus['product_name'] = $this->getRequest()->getPost('product_name');
            $skus['tax_tare'] = $this->getRequest()->getPost('tax_tare');

            $result = $this->sku_model->insert($skus);
            if(!$result){
                Tools::output(array('info' => '添加失败', 'status' => 0));
            }
            Tools::output(array('info' => '添加成功', 'status' => 1));
        }
        $this->layout('invoice/add_sku.phtml');
    }
    
    /**
     * 添加发票信息
     */
    public function addInvoiceAction(){
        $this->checkRole();
        
        if ($this->getRequest()->isPost()){
            $invoices = array();
            $invoices['buyer_phone'] = $this->getRequest()->getPost('buyer_phone');
            $invoices['order_id'] = $this->getRequest()->getPost('order_id');
            $invoices['buyer_tax_id'] = $this->getRequest()->getPost('buyer_tax_id');
            $invoices['invoice_title'] = $this->getRequest()->getPost('title');
            $invoices['batch'] = strtotime(date('Ymd'));
            
            $result = $this->invoice_mode->insert($invoices);
            if(!$result){
                Tools::output(array('info' => '添加失败', 'status' => 0));
            }
            Tools::output(array('info' => '添加成功', 'status' => 1));
        }
        $this->layout('invoice/add_invoice.phtml');
    }
    
    /**
     * 修改发票信息
     */
    public function editAction(){
        $this->checkRole();
        
        $id = $this->getRequest()->get('id');
        if($this->getRequest()->isPost()){
            $params = array();
            $params['project_name'] = $this->getRequest()->getPost('project_name');
            $params['buyer_phone'] = $this->getRequest()->getPost('buyer_phone');
            $params['invoice_title'] = $this->getRequest()->getPost('invoice_title');
            $params['order_id'] = $this->getRequest()->getPost('order_id');
            $params['buyer_tax_id'] = $this->getRequest()->getPost('buyer_tax_id');
            $i_id = $this->getRequest()->getPost('i_id');
            //更新
            $result = $this->invoice_mode->update($i_id, $params);
            if(!$result){
                Tools::output(array('info' => '修改失败', 'status' => 0));
            }
            Tools::output(array('info' => '修改成功', 'status' => 1));
        }
        $i_info = $this->invoice_mode->getInfo($id);
        $this->assign('data', $i_info);
        $this->layout('invoice/edit.phtml');
    }
    
    /**
     * 设置销售方地址电话
     */
    public function setInvoiceAction(){
        $xsf_mc = $this->getRequest()->getPost('xsf_mc');
        $kpr = $this->getRequest()->getPost('kpr');
        $address = $this->getRequest()->getPost('address');
        $payee = $this->getRequest()->getPost('payee');
        $review = $this->getRequest()->getPost('review');
        
        if (!$xsf_mc || !$kpr){
            echo json_encode(array('msg' => '必传参数缺失', 'status' => 0));
        }
        $params = [
            'seller_address' => $address,
            'drawer' => $kpr,
            'seller_name' => $xsf_mc,
            'payee' => $payee,
            'review' => $review
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
        $this->checkRole();
        
        $order_id = $this->getRequest()->get('order_id');
        $id = $this->getRequest()->get('id');
        if(!$order_id || !$id){
            echo json_encode(array('msg' => '必传参数缺失' ,'status' => 3));exit;
        }
        $sms = new Sms();
        $invoice_info = $this->invoice_mode->getInfo($id);
        
        if(!$invoice_info){
            echo json_encode(array('msg' => '系统错误' ,'status' => 3));exit;
        }
        $phonenumber = $invoice_info['buyer_phone'];
        $message = '请在电脑端查看您的发票，地址:' .$invoice_info['invoice_url'];
        $result = $sms->sendmsg($message, $phonenumber);
        
        if($result['status'] == 'ok'){
            echo json_encode(array('msg' => '短信发送成功', 'status' => 2));exit;
        }
        echo json_encode(array('msg' => '短信发送失败', 'status' => 3));exit;
    }
    
    /**
     * 更新税率
     */
    public function updateSlAction(){
        $orders = $this->getRequest()->getPost('orderlist');
        $fpsl = $this->getRequest()->getPost('sl');
         //截取最后一个符号
        $ordersing = substr($orders,0, -1);
        
        //更新多个数据到数据表
        $rs = $this->sku_model->updateSl($ordersing, $fpsl);
        if(!$rs){
            echo json_encode(array('msg' => '修改失败'));exit;
        }
        echo json_encode(array('msg' => '修改成功', 'status' => 2));exit;
    }
    
    /**
     * 订单上传
     */
    public function uploadAction(){
        $files = $this->getRequest()->getFiles('file');
        
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
        foreach ($xls->sheets[0]['cells'] as $values){
            foreach (Fileds::$invoice as $k => $v){
                $data[$v] = $values[$k];
            }
            $data['batch'] = strtotime(date('Ymd')); //将时间戳当做批次号码
            $faliOrder = $this->invoice_mode->insert($data);
            if(!$faliOrder){
                continue;
            }
        }
        if($this->invoice_mode->getError()){
            echo json_encode(array('info' => '上传成功,个别失败', 'status' => 1, 'data' => $this->invoice_mode->getError()));exit;
        }
        echo json_encode(array('info' => '上传成功', 'code' => 1));exit;
    }

    /**
     *sku 编码上传
     */
    public function skuUploadAction(){
        $files = $this->getRequest()->getFiles('file');

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
        foreach ($xls->sheets[0]['cells'] as $values){
            foreach (Fileds::$sku as $k => $v){
                $data[$v] = $values[$k];
            }
            $failSku = $this->sku_model->insert($data);
            if(!$failSku){
                continue;
            }
        }
        if($this->sku_model->getError()){
            echo json_encode(array('info' => '上传成功,个别失败', 'status' => 1, 'data' => $this->sku_model->getError()));exit;
        }
        echo json_encode(array('info' => '上传成功', 'code' => 1));exit;
    }

    /**
     * @desc 下载样例
     * @param $type
     */
    public function exportAction($type = 1){
        if($type == 1){
             $data = array(
                    0 => array(
                        '1' => 'sku_id',
                        '2' => 'product_name',
                        '3' => 'tax_tare'
                    ),
                    1 => array(
                        '1' => 'LJATS14120001FKDTZ',
                        '2' => '死磕侠',
                        '3' => 0.06
                    )
                );
            $export = new Export();
            $export->outPut($data);
        }
        exit;
    }

}

