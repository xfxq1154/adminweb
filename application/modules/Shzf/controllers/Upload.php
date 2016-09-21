<?php
/**
 * @auth why
 * @explain 生活作风电子发票
 */
class UploadController extends Base
{
    /** @var  ShzfInvModel */
    private $shzfInvModel;
    /** @var  ShzfSkuModel */
    private $shzfSkuModel;

    public function init() {
        $this->initAdmin();
        $this->shzfInvModel = new ShzfInvModel();
        $this->shzfSkuModel = new ShzfSkuModel();
        Yaf_Loader::import(ROOT_PATH . '/application/library/phpExcel/reader.php');
    }

    /**
     * @explain 订单上传
     */
    public function orderAction()
    {
        $files = $this->getRequest()->getFiles('file');
        $xls = new Spreadsheet_Excel_Reader();
        $xls->setOutputEncoding('utf-8');
        $xls->read($files['tmp_name']);

        //将文件内容遍历循环存储到数据库
        $data = array();
        if(!$xls->sheets[0]['cells'][1]){
            Tools::output(array('info'=> '系统错误','status' => 0));
        }
        unset($xls->sheets[0]['cells'][1]);
        //判断是否是sku文件
        if(count($xls->sheets[0]['cells'][2]) < 4){
            Tools::output(array('info'=>'上传文件的内容不匹配','status' => 0));
        }
        foreach ($xls->sheets[0]['cells'] as $values){
            foreach (Fileds::$shzf_inv as $k => $v){
                $data[$v] = trim($values[$k]);
            }
            $faliOrder = $this->shzfInvModel->insert($data);
            if(!$faliOrder){
                continue;
            }
        }
        if($this->shzfInvModel->getError()){
            echo json_encode(array('info' => '上传成功,个别失败', 'status' => 1, 'data' => $this->shzfInvModel->getError()));exit;
        }
        echo json_encode(array('info' => '上传成功', 'code' => 1));exit;
    }

    /**
     * @explain sku编码上传
     */
    public function uSkuAction()
    {
        $files = $this->getRequest()->getFiles('file');
        $xls = new Spreadsheet_Excel_Reader();
        $xls->setOutputEncoding('utf-8');
        $xls->read($files['tmp_name']);

        //将文件内容遍历循环存储到数据库
        $data = array();
        if(!$xls->sheets[0]['cells'][1]){
            Tools::output(array('info'=> '系统错误','status' => 0));
        }
        unset($xls->sheets[0]['cells'][1]);
        //判断是否是sku文件
        if(count($xls->sheets[0]['cells'][2]) > 3){
            Tools::output(array('info'=>'上传文件的内容不匹配','status' => 0));
        }
        foreach ($xls->sheets[0]['cells'] as $values){
            foreach (Fileds::$sku as $k => $v){
                $data[$v] = trim($values[$k]);
            }
            $faliOrder = $this->shzfSkuModel->insert($data);
            if(!$faliOrder){
                continue;
            }
        }
        if($this->shzfSkuModel->getError()){
            echo json_encode(array('info' => '上传成功,个别失败', 'status' => 1, 'data' => $this->shzfSkuModel->getError()));exit;
        }
        echo json_encode(array('info' => '上传成功', 'code' => 1));exit;
    }
}