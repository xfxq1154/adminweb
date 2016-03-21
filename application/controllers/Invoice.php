<?php
/**
 * @author why
 * @desc 发票操作类
 * @name InvoiceController
 */
class InvoiceController extends Base{
    
    use Trait_Layout;
    public $invoice_mode;


    public function init() {
        $this->initAdmin();
        $this->invoice_mode = new InvoiceModel();
    }
    
    /**
     * 发票列表
     */
    public function showListAction(){
        $page_no = (int)$this->getRequest()->get('page_no', 1);
        $page_size = (int)$this->getRequest()->get('page_size', 20);
        $use_hax_next = $this->getRequest()->get('use_hax_next', 1);
        $kw = $this->getRequest()->get('kw');
        
        $result = $this->invoice_mode->getList($page_no, $page_size, $use_hax_next, $kw);
        
        $this->assign('data', $result);
        $this->layout('invoice/list.phtml');
    }
}

