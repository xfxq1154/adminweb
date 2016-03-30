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
    
    public $status = [
        1 => '未开发票',
        2 => '开票成功',
        3 => '开票失败',
        4 => '已发短信'
    ];
    
    public $state_name = [
        1 => '<span class="tag bg-green">未开发票</span>',
        2 => '<span class="tag bg-yellow">开票成功</span>',
        3 => '<span class="tag bg-blue">开票失败</span>',
        4 => '<span class="tag bg-bg-mix">已发短信</span>'
    ];
    
    public $host = ASSET_URL;

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
        $mobile = $this->getRequest()->get('mobile');
        $order_id = $this->getRequest()->get('order_id');
        $status = $this->getRequest()->get('status');
        
        $result = $this->invoice_mode->getList($page_no, 20, 1, $mobile, $order_id, $status);
        foreach ($result['data'] as &$values){
            if($values['invoice_url']){
                $values['invoice_url'] = $this->invoice_mode->getInvoice($values['invoice_url']);
            }
        }
        //查询开票信息
        $invoice_info = $this->invoice_data_model->getInfo();
        $invoice_info['host'] = $this->host;
        $this->renderPagger($page_no, $result['total_nums'], '/invoice/showlist/page_no/{p}/status/'.$status, 20);
        $this->assign('data', $result);
        $this->assign('mobile', $mobile);
        $this->assign('order_id', $order_id);
        $this->assign('invoice_info', $invoice_info);
        $this->assign('status', $this->status[$status]);
        $this->assign('state_name', $this->state_name);
        $this->layout('invoice/list.phtml');
    }
    
    /**
     * 发票预览列表
     */
    public function skuListAction(){
        $this->checkRole();
        $page_no = $this->getRequest()->get('page_no', 1);
        $mobile = $this->getRequest()->get('mobile');
        $order_id = $this->getRequest()->get('order_id');
        $status = $this->getRequest()->get('status');
        $result = $this->invoice_mode->getList($page_no, 20, 1, $mobile, $order_id, $status);
        $this->renderPagger($page_no, $result['total_nums'], '/invoice/skulist/page_no/{p}', 20);
        $this->assign('data', $result);
        $this->assign('info', array('mobile' => $mobile,'order_id' => $order_id));
        $this->layout('invoice/skulist.phtml');
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
            $invoices['project_name'] = $this->getRequest()->getPost('name');
            $invoices['barcode'] = $this->getRequest()->getPost('barcode');
            $invoices['tax_rate'] = $this->getRequest()->getPost('fpsl');
            $invoices['invoice_title'] = $this->getRequest()->getPost('title');
            
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
            $params['barcode'] = $this->getRequest()->getPost('barcode');
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
        $rs = $this->invoice_mode->updateSl($ordersing, $fpsl);
        if(!$rs){
            echo json_encode(array('msg' => '修改失败'));exit;
        }
        echo json_encode(array('msg' => '修改成功', 'status' => 2));exit;
    }
    
    /**
     * 上传
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
            $this->invoice_mode->insert($data);
        }
        echo json_encode(array('info' => '上传成功', 'code' => 1));exit;
    }
    
    
    /**
     * test
     */
    public function testAction(){
        $you = new YouZanOrderModel();
        $detail = new YouZanOrderDetailModel();
        $youzan_order_list = $you->getlist();
        foreach ($youzan_order_list as $value){
            $invoices['project_name'] = $value['y_title'];
            $invoices['order_id'] = substr($value['y_tid'], 0, -1);
            $invoices['invoice_title'] = '测试开发票';
            $invoices['buyer_phone'] = $value['y_receiver_mobile'];
            //添加数据
            $this->invoice_mode->insert($invoices);
            //更改数据
            $you->update($value['y_id'], substr($value['y_tid'], 0, -1));
            //更新detail数据
            $detail->update($value['o_id'],  substr($value['y_tid'], 0, -1));
        }
        exit;
    }
}

