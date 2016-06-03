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

    /** @var  KdtApiClient */
    public $youzan_api;

    /** @var  CkdModel */
    public $ckd;

    /** @var Dzfp */
    public $dzfp;

    public $app_id = KDT_APP_ID;
    public $app_secert = KDT_APP_SECERT;
    
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
        5 => '<span class="tag bg-bg-blue">开票中</span>',
        6 => '<span class="tag bg-yellow">开票成功</span>'
    ];
    
    public $host = ASSET_URL;

    public function init() {
        $this->initAdmin();
        Yaf_Loader::import(ROOT_PATH . '/application/library/phpExcel/reader.php');
        Yaf_Loader::import(ROOT_PATH . '/application/library/youzan/KdtApiClient.php');
        $this->dzfp = new Dzfp();
        $this->ckd = new CkdModel();
        $this->sku_model = new SkuModel();
        $this->invoice_mode = new InvoiceModel();
        $this->invoice_data_model = new InvoicedataModel();
        $this->youzan_api = new KdtApiClient($this->app_id, $this->app_secert);
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
        $group_id = $this->getRequest()->get('group');
        $sku_type = $this->getRequest()->get('stype');
        $time = $this->getRequest()->get('time');
        
        $result = $this->invoice_mode->getList($page_no, 20, 1, $mobile, $order_id, $status, $group_id, $sku_type, $time);

        //查询上传批次
        $group = $this->invoice_mode->getBatchGroup();
        $groups = array_filter($group);
        //查询开票信息
        $invoice_info = $this->invoice_data_model->getInfo();
        $invoice_info['host'] = $this->host;
        $invoice_info['mobile'] = $mobile;
        $invoice_info['order_id'] = $order_id;
        $invoice_info['stype'] = $sku_type;
        $invoice_info['time'] = $time;

        $this->renderPagger($page_no, $result['total_nums'], '/invoice/showlist/page_no/{p}?status='.$status.'&group='.$group_id.'&stype='.$sku_type.'&time='.$time, 20);
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
     * @desc 删除发票订单
     */
    public function deleteAction(){
        $this->checkRole();
        $id = json_decode($this->getRequest()->get('data'),1)['id'];
        $result = $this->invoice_mode->delete($id);
        if(!$result){
            echo json_encode(['info' => '删除失败', 'status' => 0]);exit;
        }
        echo json_encode(['info' => '删除成功', 'status' => 1]);exit;
    }

    /**
     * @desc 删除sku编码
     */
    public function deleteSkuAction(){
        $this->checkRole();
        $id = json_decode($this->getRequest()->get('data'),1)['id'];
        $result = $this->sku_model->delete($id);
        if(!$result){
            echo json_encode(['info' => '删除失败', 'status' => 0]);exit;
        }
        echo json_encode(['info' => '删除成功', 'status' => 1]);exit;
    }

    /**
     * @desc 组合sku列表
     */
    public function ckdAction(){
        $this->checkRole();
        if($_POST){
            $id = $_POST['id'];
            $money = $_POST['money'];

            $result = $this->ckd->update($id, $money);
            if(!$result){
                echo json_encode(array('code' => 0));exit;
            }
            echo json_encode(array('code' => 1));exit;
        }
        $page_no = $this->getRequest()->get('page_no', 1);
        $sku_id = trim($this->getRequest()->get('sku_id'));
        $pid = trim($this->getRequest()->get('p_sku_id'));
        $result = $this->ckd->getList($page_no, 20, 1, $sku_id, $pid);
        $this->renderPagger($page_no, $result['total_nums'], '/invoice/ckd/page_no/{p}?pid='.$pid.'&sid='.$sku_id, 20);
        $this->assign('skus', array('sku_id' => $sku_id, 'p_sku_id' => $pid));
        $this->assign('data', $result);
        $this->layout('invoice/ckd.phtml');
    }

    /**
     * @desc 数据统计
     */
    public function dataListAction(){
        $this->checkRole();
        $stime = $this->getRequest()->get('time');
        if($stime){
            $time = date('Y-m', strtotime($stime));
        }else{
            $time = date('Y-m', time());
        }
        $data['list'] = $this->invoice_data_model->getData($time);
        //计算总和
        foreach ($data['list'] as $val) {
            if ($val['type'] == 1) {
                if ($val['tax'] == 0.00) {
                    $data['blue_inv_one_sl'] = $val['tax'];
                    $data['blue_inv_one_se'] += $val['se'];
                    $data['blue_inv_one_pay'] += $val['payment'];
                } else if ($val['tax'] == 0.06) {
                    $data['blue_inv_two_sl'] = $val['tax'];
                    $data['blue_inv_two_se'] += $val['se'];
                    $data['blue_inv_two_pay'] += $val['payment'];
                } else {
                    $data['blue_inv_thr_sl'] = $val['tax'];
                    $data['blue_inv_thr_se'] += $val['se'];
                    $data['blue_inv_thr_pay'] += $val['payment'];
                }
            } else{
                if ($val['tax'] == 0.00) {
                    $data['red_inv_one_sl'] = $val['tax'];
                    $data['red_inv_one_se'] += $val['se'];
                    $data['red_inv_one_pay'] += $val['payment'];
                } else if ($val['tax'] == 0.06) {
                    $data['red_inv_two_sl'] = $val['tax'];
                    $data['red_inv_two_se'] += $val['se'];
                    $data['red_inv_two_pay'] += $val['payment'];
                } else {
                    $data['red_inv_thr_sl'] = $val['tax'];
                    $data['red_inv_thr_se'] += $val['se'];
                    $data['red_inv_thr_pay'] += $val['payment'];
                }
            }
        }
        $this->assign('time', $time);
        $this->assign('list', $data);
        $this->layout('invoice/data_list.phtml');
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
     * @desc 修改手机号
     */
    public function updatePhoneAction(){
        $this->checkRole();

        $id = $this->getRequest()->get('id');
        if($this->getRequest()->isPost()){
            $params = array();
            $params['buyer_phone'] = $this->getRequest()->getPost('buyer_phone');
            $params['state'] = 2;
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
        $this->layout('invoice/upphone.phtml');
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
        $order_id = $this->getRequest()->getPost('id');
        $value = $this->invoice_mode->getInfo($order_id);
        $rs_pdf = $this->dzfp->getpdf($value['invoice_code'], $value['invoice_number'], $value['check_code']);
        if(!$rs_pdf){
            echo json_encode(array('msg' => 'PDF文件获取失败' ,'status' => 3));exit;
        }
        $pdf = base64_decode($rs_pdf);
        //将pdf文件上传到oss
        $rs_oss = $this->invoice_mode->ossUpload($pdf);
        if(!$rs_oss){
            echo json_encode(array('msg' => 'upload系统错误' ,'status' => 3));exit;
        }
        //查询私密发票地址
        $invoice_path = $this->invoice_mode->getInvoice($rs_oss['object']);
        if(!$invoice_path){
            echo json_encode(array('msg' => '发票获取失败' ,'status' => 3));exit;
        }

        //生成短网址
        $dwz_url = $this->invoice_mode->dwz($invoice_path);
        if($dwz_url['errNum']){
            echo json_encode(array('msg' => '系统错误' ,'status' => 3));exit;
        }

        //将发票地址发送给用户
        $sms = new Sms();
        $message = '您好，您在罗辑思维所购产品的电子发票地址为:'.$dwz_url['urls'][0]['url_short'].'。地址有效期为30天，请尽快在电脑端查看';
        $result = $sms->sendmsg($message, $value['buyer_phone']);
        if($result['status'] == 'ok'){
            //更新发票信息
//            $this->invoice_mode->update($value['id'], array('invoice_url' => $dwz_url['urls'][0]['url_short'],'state' => 4));
            echo json_encode(array('msg' => '短信发送成功', 'status' => 2));exit;
        }
//        $this->invoice_mode->update($value['id'], array('invoice_url' => $dwz_url['urls'][0]['url_short'],'state' => 6));
        echo json_encode(array('msg' => '短信发送失败', 'status' => 3));exit;
    }
    
    /**
     * 更新税率
     */
    public function updateSlAction(){
        $this->checkRole();
        $orders = $this->getRequest()->getPost('orderlist');
        $fpsl = $this->getRequest()->getPost('sl');
        if(!$orders){
            Tools::output(array('msg' => '请先勾选编码', 'status' => 2));
        }
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
        $this->checkRole();
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

        //判断是否是sku文件
        if(count($xls->sheets[0]['cells'][2]) < 4){
            Tools::output(array('info'=>'上传文件的内容不匹配','status' => 0));
        }
        foreach ($xls->sheets[0]['cells'] as $values){
            foreach (Fileds::$invoice as $k => $v){
                $data[$v] = trim($values[$k]);
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
        $this->checkRole();
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
        //判断是否是sku文件
        if(count($xls->sheets[0]['cells'][2]) > 3){
            Tools::output(array('info'=>'上传文件的内容不匹配','status' => 0));
        }
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
     * @desc 组合商品上传
     */
    public function ckdUploadAction(){
        $this->checkRole();
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

        if(!$xls->sheets[0]['cells'][1]){
            Tools::output(array('info'=> '系统错误','status' => 0));
        }

        unset($xls->sheets[0]['cells'][1]);
        //判断是否是sku文件
        if(count($xls->sheets[0]['cells'][2]) > 2){
            Tools::output(array('info'=>'上传文件的内容不匹配','status' => 0));
        }
        //将文件内容遍历循环存储到数据库
        foreach ($xls->sheets[0]['cells'] as $values){
            $sku_id = '';
            $sku_arr = explode(',', $values[1]);
            $parent_sku = $sku_arr[0];
            unset($sku_arr[0]);

            //获取sku串
            foreach ($sku_arr as $val){
                $sku_id .= '\''.$val.'\',';
            }
            $skus = $this->sku_model->getInfoBySkuId(substr($sku_id, 0, -1));

            foreach ($skus as $k => $val){
                $params['kind_sku_id'] = $val['sku_id'];
                $params['title'] = $val['product_name'];
                $params['tax_rate'] = $val['tax_tare'];
                $params['parent_sku_id'] = $parent_sku;

                $this->ckd->insert($params);
            }
        }
        echo json_encode(array('info' => '上传成功', 'code' => 1));exit;
    }

    /**
     * @desc 查询订单
     */
    public function checkPriceAction(){
        if($_POST){
            $order = $this->getRequest()->getPost('order');
            if(!$order){
                echo json_encode(array('info' => '上传成功', 'code' => 1));exit;
            }
            $url = 'kdt.trade.get';
            $skuList = array();
            $result = $this->youzan_api->get($url, array('tid' => $order));
            foreach ($result['response']['trade']['orders'] as $key=> $item) {
                $skuList[$key]['sku_id'] = $item['outer_sku_id'] ? $item['outer_sku_id'] : $item['outer_item_id'];
                $skuList[$key]['sku_name'] = $item['title'];
            }
            $this->assign('data', $skuList);
        }
        $this->layout('invoice/get_order.phtml');
    }

    public function stateAction()
    {
        $id = $this->getRequest()->get('id');
        $sta = $this->getRequest()->get('sta');
        $this->invoice_mode->update($id, array('state' => $sta));
        exit;
    }
}

