<?php
/**
 * @author why
 * @desc 发票操作类
 * @name InvoiceController
 */
class InvoiceController extends Base{
    
    use Trait_Layout,Trait_Pagger;
    
    public $invoice_mode;
    
    public $tax_rate = [
        '1' => 0,
        '2' => 0.06,
        '3' => 0.17
    ];

    public function init() {
        $this->initAdmin();
        Yaf_Loader::import(ROOT_PATH . '/application/library/phpExcel/reader.php');
        $this->invoice_mode = new InvoiceModel();
    }
    
    /**
     * 发票列表
     */
    public function showListAction(){
        $page_no = (int)$this->getRequest()->get('page_no', 1);
        $files = $this->getRequest()->getFiles('import');
        $mobile = $this->getRequest()->get('mobile');
        $order_id = $this->getRequest()->get('order_id');
        
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
        $address = '北京市朝阳区通惠河北路郎家园六号院朗园Vintage2号楼A座6层';
        $result = $this->invoice_mode->getList($page_no, 20, 1, $mobile, $order_id);
        $this->renderPagger($page_no, $result['total_nums'], '/invoice/showlist/page_no/{p}', 20);
        $this->assign('data', $result);
        $this->assign('mobile', $mobile);
        $this->assign('order_id', $order_id);
        $this->assign('seller_address', $address);
        $this->assign('tax_rate', $this->tax_rate);
        $this->layout('invoice/list.phtml');
    }
}

