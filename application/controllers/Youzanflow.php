<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class YouzanflowController extends Base{
    
    use Trait_Api,
        Trait_Layout,
        Trait_Pagger;
    
    public $youzan;
    public $menu,$page,$maxpage,$homepage,$shoplower,$hourpv;
    
    public function init(){
        $this->initAdmin();
        $this->menu = new YouZanFlowModel();
        $this->youzan = new YouzanModel();
        $this->page = new YouZanPageModel();
        $this->maxpage = new YouZanMaxPageModel();
        $this->homepage = new YouZanHomeShopModel();
        $this->shoplower = new YouZanShopLowerModel();
        $this->hourpv = new HourPvModel();
    }
    
    public function indexAction(){
        
        if($this->getRequest()->isPost()){
            $file = $this->getRequest()->getFiles('import');
            if(!empty($file['name'])){
                $dir = dirname(dirname(dirname(dirname(__FILE__)))); 
                $savePath = $dir.'/file/upload/';
                if (!is_dir($savePath)) {
                    mkdir($savePath, 0777);
                }
                $ext = explode(".", $file['name']);
                $ext = strtolower($ext[count($ext) - 1]);
                $saveName = date('YmdHis').uniqid().'.'.$ext;
                if(!@move_uploaded_file($file["tmp_name"], $savePath.'/'.$saveName)){
                    echo '上传失败'; 
                }else{
                    $file_name = $savePath.'/'.$saveName;
                }
                $i = 0;
                $xls = new Spreadsheet_Excel_Reader(); 
                $xls->setOutputEncoding('utf-8');  //设置编码 
                $xls->read($file_name);  //解析文件 
                if($xls->sheets[0]['cells'][1]){
                    unset($xls->sheets[0]['cells'][1]);
                    $fields = array(
                        1 => 'order_no',2 => 'express',3 => 'express_no',4 => 'vip_name',5 => 'sex',
                        6 => 'buyers_provinces',7 => 'buyers_city',8 => 'is_followers',9 => 'amount_payable',10 => '`paid_amount`',
                        11 => 'sum_money',12 => 'payment_amount',13 => 'channel',14 => 'status',15 => 'suk',
                        16 => 'merchant_code_suk',17 => 'merchant_code_sp',18 => 'rights_message',19 => 'consignee_name',20 => 'consignee_provinces',
                        21 => 'consignee_city',22 => 'consignee_area',23 => 'consignee_address',24 => 'postalcode',25 => 'shipping',
                        26 => 'since_address',27 => 'appointment',28 => 'appointment_phone',29 => 'appointment_date',30 => 'contact_number',
                        date(31) => 'create_order_time',  date(32) => 'time_end',33 => 'title',34 => 'price',35 => 'order_message',
                        36 => 'sum',37 => 'shop_no',38 => 'shop_name',39 => 'merchant_message',40 => 'order_comment',
                        41 => 'order_code',42 => 'serial_number',43 => 'star',44 => 'comment'
                            );
                    foreach ($xls->sheets[0]['cells'] as $value){
                        foreach ($fields as $r => $f) {
                            $data[$f] = $value[$r];
                        }
                        $this->youzan->insert($data);
                        $i++;
                    }
                }
            }else{
                $notice = 1;
            }
        }
        $size = 20;
        $p = (int) $this->getRequest()->getParam('p', 1);
        $count = $this->youzan->getCount();
        $this->renderPagger($p, $count, "/youzanflow/index/p/{p}", $size);
        $rs = $this->youzan->getData($p, $size, $kw);
        $this->assign('data', $rs);
        $this->assign('i', $i);
        $this->assign('notice', $notice);
        $this->layout('youzan/youzanList.phtml');
    }
    
    /*
     * 全站总流量
     */
    public function menuAction(){
        //导入数据
        if(isset($_POST['import']) && $_POST['import'] == 'import'){
            $file = $this->getRequest()->getFiles('import');
            if(!empty($file['name'])){
                $dir = dirname(dirname(dirname(dirname(__FILE__)))); 
                $savePath = $dir.'/file/upload/';
                if (!is_dir($savePath)) {
                    mkdir($savePath, 0777);
                }
                $ext = explode(".", $file['name']);
                $ext = strtolower($ext[count($ext) - 1]);
                $saveName = date('YmdHis').uniqid().'.'.$ext;
                if(!@move_uploaded_file($file["tmp_name"], $savePath.'/'.$saveName)){
                    echo '上传失败'; 
                }else{
                    $file_name = $savePath.'/'.$saveName;
                }
                $i = 0;
                $xls = new Spreadsheet_Excel_Reader(); 
                $xls->setOutputEncoding('utf-8');  //设置编码 
                $xls->read($file_name);  //解析文件 
                if($xls->sheets[0]['cells'][1]){
                    unset($xls->sheets[0]['cells'][1]);
                    $fields = array(
                        1=>'date',2=>'url',3=>'title',4=>'pv',5=>'uv'
                    );
                    foreach ($xls->sheets[0]['cells'] as $value){
                        foreach ($fields as $r => $f) {
                            $data[$f] = $value[$r];
                        }
                        $this->menu->insert($data);
                        $i++;
                    }
                }
            }else{
                $notice = 1;
            }
        }
        //分页查询
        $size = 20;
        $p = (int) $this->getRequest()->getParam('p' , 1);
        $t = $this->getRequest()->getParam('t' ,'');
        $t1 = $this->getRequest()->getParam('t1' ,'');
        
        if(empty($t)){
            $t = $_REQUEST['start_time'];
        }
        if(empty($t1)){
            $t1 = $_REQUEST['end_time'];
        }
        
        $t = urldecode($t);
        $t1 = urldecode($t1);
        
        $count = $this->menu->getCount($kw,$t,$t1);
        $this->renderPagger($p, $count, "/youzanflow/menu/p/{p}/t/{$t}/t1/{$t1}", $size);
        $rs = $this->menu->getData($p, $size, $kw,$t,$t1);
        //导出数据
        if(isset($_POST['export']) && $_POST['export'] == 'export'){
            header("Content-type:application/octet-stream");
            header("Accept-Ranges:bytes");
            header("Content-type:application/vnd.ms-excel");
            header("Content-Type:text/html; charset=gbk");
            header("Content-Disposition:attachment;filename=export_flow.xls");
            header('Pragma:no-cache');
            header('Expires:0');
            $title = array('ID','日期','URL','标题','PV','UV');
            echo iconv('utf-8', 'gbk', implode("\t", $title)),"\n";
            foreach ($rs as $value){
                echo iconv('utf-8', 'gbk', implode("\t", $value)),"\n";
            }
            exit;
        }
        $this->assign('data', $rs);
        $this->assign('time_s', $t);
        $this->assign('time_e', $t1);
        $this->assign('i', $i);
        $this->assign('notice', $notice);
        $this->layout('youzan/menu.phtml');
    }
    
    /*
     * 每日最高流量页面
     */
    public function flowpageAction(){
        //导入数据
        if(isset($_POST['import']) && $_POST['import'] == 'import'){
            $file = $this->getRequest()->getFiles('import');
            if(!empty($file['name'])){
                $dir = dirname(dirname(dirname(dirname(__FILE__)))); 
                $savePath = $dir.'/file/upload/';
                if (!is_dir($savePath)) {
                    mkdir($savePath, 0777);
                }
                $ext = explode(".", $file['name']);
                $ext = strtolower($ext[count($ext) - 1]);
                $saveName = date('YmdHis').uniqid().'.'.$ext;
                if(!@move_uploaded_file($file["tmp_name"], $savePath.'/'.$saveName)){
                    echo '上传失败'; 
                }else{
                    $file_name = $savePath.'/'.$saveName;
                }
                $i = 0;
                $xls = new Spreadsheet_Excel_Reader(); 
                $xls->setOutputEncoding('utf-8');  //设置编码 
                $xls->read($file_name);  //解析文件 
                if($xls->sheets[0]['cells'][1]){
                    unset($xls->sheets[0]['cells'][1]);
                    $fields = array(
                        1=>'date',2=>'title',3=>'url',4=>'pv',5=>'uv',6=>'share_pv',7=>'share_uv',8=>'to_the_store_pv',9=>'to_the_store_uv'
                    );
                    foreach ($xls->sheets[0]['cells'] as $value){
                        foreach ($fields as $r => $f) {
                            $data[$f] = $value[$r];
                        }
                        $this->page->insert($data);
                        $i++;
                    }
                }
            }else{
                $notice = 1;
            }
        }
        //分页查询
        $where  = '';
        
        $size = 20;
        $p = (int) $this->getRequest()->getParam('p', 1);
        $t = $this->getRequest()->getParam('t','');
        $t1 = $this->getRequest()->getParam('t1','');
        
        $time_start = $t ? '' : $_REQUEST['start_time'];
        $time_end = $t1 ? '' : $_REQUEST['end_time'];
        
        $t = urldecode($time_start);
        $t1 = urldecode($time_end);
        
        $count = $this->page->getCount();
        $this->renderPagger($p, $count, "/youzanflow/flowpage/p/{p}/t/{$t}/t1/{$t1}", $size);
        $rs = $this->page->getData($p, $size, $kw,$t,$t1);
        //导出数据
        if(isset($_POST['export']) && $_POST['export'] == 'export'){
            header("Content-type:application/octet-stream");
            header("Accept-Ranges:bytes");
            header("Content-type:application/vnd.ms-excel");
            header("Content-Type:text/html; charset=gbk");
            header("Content-Disposition:attachment;filename=export_flow.xls");
            header('Pragma:no-cache');
            header('Expires:0');
            $title = array('ID','日期','URL','标题','PV','UV','外部分享PV','外部分享UV','到店PV','到店UV');
            echo iconv('utf-8', 'gbk', implode("\t", $title)),"\n";
            foreach ($rs as $value){
                echo iconv('utf-8', 'gbk', implode("\t", $value)),"\n";
            }
            exit;
        }
        $this->assign('time_s', $t);
        $this->assign('time_e', $t1);
        $this->assign('data', $rs);
        $this->assign('i', $i);
        $this->assign('notice', $notice);
        $this->layout('youzan/flowPage.phtml');
    }
    
    
    /*
     * 每日流量最高下级
     */
    
    public function maxpageAction(){
        
        if($this->getRequest()->isPost()){
            $file = $this->getRequest()->getFiles('import');
            if(!empty($file['name'])){
                $dir = dirname(dirname(dirname(dirname(__FILE__)))); 
                $savePath = $dir.'/file/upload/';
                if (!is_dir($savePath)) {
                    mkdir($savePath, 0777);
                }
                $ext = explode(".", $file['name']);
                $ext = strtolower($ext[count($ext) - 1]);
                $saveName = date('YmdHis').uniqid().'.'.$ext;
                if(!@move_uploaded_file($file["tmp_name"], $savePath.'/'.$saveName)){
                    echo '上传失败'; 
                }else{
                    $file_name = $savePath.'/'.$saveName;
                }
                $i = 0;
                $xls = new Spreadsheet_Excel_Reader(); 
                $xls->setOutputEncoding('utf-8');  //设置编码 
                $xls->read($file_name);  //解析文件 
                if($xls->sheets[0]['cells'][1]){
                    unset($xls->sheets[0]['cells'][1]);
                    $fields = array(
                        1=>'date',2=>'title',3=>'url',4=>'pv',5=>'uv'
                    );
                    foreach ($xls->sheets[0]['cells'] as $value){
                        foreach ($fields as $r => $f) {
                            $data[$f] = $value[$r];
                        }
                        $this->maxpage->insert($data);
                        $i++;
                    }
                }
            }else{
                $notice = 1;
            }
        }
        $size = 20;
        $p = (int) $this->getRequest()->getParam('p', 1);
        $count = $this->maxpage->getCount();
        $this->renderPagger($p, $count, "/youzanflow/maxpage/p/{p}", $size);
        $rs = $this->maxpage->getData($p, $size, $kw);
        $this->assign('data', $rs);
        $this->assign('i', $i);
        $this->assign('notice', $notice);
        $this->layout('youzan/maxpage.phtml');
    }
    
    /*
     * 每日店铺主页
     */
    
    public function homeshopAction(){
        
        if($this->getRequest()->isPost()){
            $file = $this->getRequest()->getFiles('import');
            if(!empty($file['name'])){
                $dir = dirname(dirname(dirname(dirname(__FILE__)))); 
                $savePath = $dir.'/file/upload/';
                if (!is_dir($savePath)) {
                    mkdir($savePath, 0777);
                }
                $ext = explode(".", $file['name']);
                $ext = strtolower($ext[count($ext) - 1]);
                $saveName = date('YmdHis').uniqid().'.'.$ext;
                if(!@move_uploaded_file($file["tmp_name"], $savePath.'/'.$saveName)){
                    echo '上传失败'; 
                }else{
                    $file_name = $savePath.'/'.$saveName;
                }
                $i = 0;
                $xls = new Spreadsheet_Excel_Reader(); 
                $xls->setOutputEncoding('utf-8');  //设置编码 
                $xls->read($file_name);  //解析文件 
                if($xls->sheets[0]['cells'][1]){
                    unset($xls->sheets[0]['cells'][1]);
                    $fields = array(
                        1=>'date',2=>'pv',3=>'uv',4=>'share_pv',5=>'share_uv',6=>'shop_pv',7=>'shop_uv'
                    );
                    foreach ($xls->sheets[0]['cells'] as $value){
                        foreach ($fields as $r => $f) {
                            $data[$f] = $value[$r];
                        }
                        $this->homepage->insert($data);
                        $i++;
                    }
                }
            }else{
                $notice = 1;
            }
        }
        $size = 20;
        $p = (int) $this->getRequest()->getParam('p', 1);
        $count = $this->homepage->getCount();
        $this->renderPagger($p, $count, "/youzanflow/homeshop/p/{p}", $size);
        $rs = $this->homepage->getData($p, $size, $kw);
        $this->assign('data', $rs);
        $this->assign('i', $i);
        $this->assign('notice', $notice);
        $this->layout('youzan/homeshop.phtml');
    }
    
    /*
     * 每日店铺主页下级
     */
    public function shoplowerAction(){
        
        if($this->getRequest()->isPost()){
            $file = $this->getRequest()->getFiles('import');
            if(!empty($file['name'])){
                $dir = dirname(dirname(dirname(dirname(__FILE__)))); 
                $savePath = $dir.'/file/upload/';
                if (!is_dir($savePath)) {
                    mkdir($savePath, 0777);
                }
                $ext = explode(".", $file['name']);
                $ext = strtolower($ext[count($ext) - 1]);
                $saveName = date('YmdHis').uniqid().'.'.$ext;
                if(!@move_uploaded_file($file["tmp_name"], $savePath.'/'.$saveName)){
                    echo '上传失败'; 
                }else{
                    $file_name = $savePath.'/'.$saveName;
                }
                $i = 0;
                $xls = new Spreadsheet_Excel_Reader(); 
                $xls->setOutputEncoding('utf-8');  //设置编码 
                $xls->read($file_name);  //解析文件 
                if($xls->sheets[0]['cells'][1]){
                    unset($xls->sheets[0]['cells'][1]);
                    $fields = array(
                        1=>'date',2=>'title',3=>'url',4=>'pv',5=>'uv'
                    );
                    foreach ($xls->sheets[0]['cells'] as $value){
                        foreach ($fields as $r => $f) {
                            $data[$f] = $value[$r];
                        }
                        $this->shoplower->insert($data);
                        $i++;
                    }
                }
            }else{
                $notice = 1;
            }
        }
        $size = 20;
        $p = (int) $this->getRequest()->getParam('p', 1);
        $count = $this->shoplower->getCount();
        $this->renderPagger($p, $count, "/youzanflow/shoplower/p/{p}", $size);
        $rs = $this->shoplower->getData($p, $size, $kw);
        $this->assign('data', $rs);
        $this->assign('i', $i);
        $this->assign('notice', $notice);
        $this->layout('youzan/shoplower.phtml');
    }
    
    /*
     * 小时pv
     */
    public function hourpvAction(){
        
        if($this->getRequest()->isPost()){
            $file = $this->getRequest()->getFiles('import');
            if(!empty($file['name'])){
                $dir = dirname(dirname(dirname(dirname(__FILE__)))); 
                $savePath = $dir.'/file/upload/';
                if (!is_dir($savePath)) {
                    mkdir($savePath, 0777);
                }
                $ext = explode(".", $file['name']);
                $ext = strtolower($ext[count($ext) - 1]);
                $saveName = date('YmdHis').uniqid().'.'.$ext;
                if(!@move_uploaded_file($file["tmp_name"], $savePath.'/'.$saveName)){
                    echo '上传失败'; 
                }else{
                    $file_name = $savePath.'/'.$saveName;
                }
                $i = 0;
                $xls = new Spreadsheet_Excel_Reader(); 
                $xls->setOutputEncoding('utf-8');  //设置编码 
                $xls->read($file_name);  //解析文件 
                if($xls->sheets[0]['cells'][1]){
                    unset($xls->sheets[0]['cells'][1]);
                    $fields = array(
                        1=>'date',2=>'hour',3=>'pv',4=>'uv'
                    );
                    foreach ($xls->sheets[0]['cells'] as $value){
                        foreach ($fields as $r => $f) {
                            $data[$f] = $value[$r];
                        }
                        $this->hourpv->insert($data);
                        $i++;
                    }
                }
            }else{
                $notice = 1;
            }
        }
        $size = 20;
        $p = (int) $this->getRequest()->getParam('p', 1);
        $count = $this->hourpv->getCount();
        $this->renderPagger($p, $count, "/youzanflow/hourpv/p/{p}", $size);
        $rs = $this->hourpv->getData($p, $size, $kw);
        $this->assign('data', $rs);
        $this->assign('i', $i);
        $this->assign('notice', $notice);
        $this->layout('youzan/hourpv.phtml');
    }
}