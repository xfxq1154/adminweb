<?php
/**
 * @author why
 * @desc 定时脚本任务 给已开发票的用户发短信
 */
class CrontabController extends Base{
    
    public $invoice_model;
    public $dzfp;
    
    public function init(){
        $this->invoice_model = new InvoiceModel();
        $this->dzfp = new Dzfp();
    }
    
    
    
    /**
     * @name getPdfSendMessage
     * @desc 获取发票pdf文件，并且发送短信给用户
     * @frequency 每小时运行一次
     */
    public function getPdfSendMessageAction(){
        
        $datas = $this->invoice_model->getAll();
        //过滤空数组
        $invoice_data = array_filter($datas);
        if($invoice_data){
            foreach ($datas as $value){
                //获取发票pdf文件
                $rs_pdf = $this->dzfp->getpdf($value['invoice_code'], $value['invoice_number'], $value['check_code']);
                if(!$rs_pdf){
                    continue;
                }
                $pdf = base64_decode($rs_pdf);
                //将pdf文件上传到oss
                $rs_oss = $this->invoice_model->ossUpload($pdf);
                if(!$rs_oss){
                    continue;
                }

                //更新发票信息
                $this->invoice_model->update($value['id'], array('invoice_url' => $rs_oss['object'],'state' => 4));
                //查询私密发票地址
                $invoice_path = $this->invoice_model->getInvoice($rs_oss['object']);
                if(!$invoice_path){
                    continue;
                }
                //将发票地址发送给用户
                $sms = new Sms();
                $message = '请在电脑端查看您的发票，地址:'.$invoice_path;
                $sms->sendmsg($message, $value['buyer_phone']);
            }
        }
        exit;
    }
}

