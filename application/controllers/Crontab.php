<?php
/**
 * @author why
 * @desc 定时脚本任务 给已开发票的用户发短信
 */
class CrontabController extends Base{

    /**
     * @var InvoiceModel;
     */
    public $invoice_model;

    /**
     * @var InvoicedataModel;
     */
    public $invoice_data_model;

    /** @var Dzfp */
    public $dzfp;

    /** @var SkuModel  */
    public $sku_model;

    /** @var  KdtApiClient */
    public $youzan_api;

    /** @var  YouZanOrderModel */
    public $youzan_order_model;

    /** @var  CkdModel */
    public $ckd;

    public $errorMsg;

    const INVOICE_SUCCESS = 2;
    const INVOICE_FAIL = 3;
    const RED_INVOICE_SUCCESS = 4;

    public $app_id = KDT_APP_ID;
    public $app_secert = KDT_APP_SECERT;

    public function init(){
        Yaf_Loader::import(ROOT_PATH . '/application/library/youzan/KdtApiClient.php');
        $this->invoice_model = new InvoiceModel();
        $this->invoice_data_model = new InvoicedataModel();
        $this->ckd = new CkdModel();
        $this->dzfp = new Dzfp();
        $this->sku_model = new SkuModel();
        $this->youzan_order_model = new YouZanOrderModel();
        $this->youzan_api = new KdtApiClient($this->app_id, $this->app_secert);
    }

    /**
     * @name getPdfSendMessage
     * @desc 获取发票pdf文件，并且发送短信给用户
     * @frequency 每5分钟运行一次
     */
    public function getPdfSendMessageAction(){
        $datas = $this->invoice_model->getAll();
        //过滤空数组
        $invoice_data = array_filter($datas);
        if(!$invoice_data){
            exit;
        }
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
            //查询私密发票地址
            $invoice_path = $this->invoice_model->getInvoice($rs_oss['object']);
            if(!$invoice_path){
                continue;
            }
            //生成短网址
            $dwz_url = $this->invoice_model->dwz($invoice_path);
            if($dwz_url['errNum']){
                continue;
            }
            //将发票地址发送给用户
            $sms = new Sms();
            $message = '您好，您在罗辑思维所购产品的电子发票地址为:'.$dwz_url['urls'][0]['url_short'].'。地址有效期为30天，请尽快在电脑端查看。';
            $status = $sms->sendmsg($message, $value['buyer_phone']);
            if($status['status'] == 'ok'){
                $this->invoice_model->update($value['id'], array('invoice_url' => $dwz_url['urls'][0]['url_short'],'state' => 4));
            }else{
                $this->invoice_model->update($value['id'], array('invoice_url' => $dwz_url['urls'][0]['url_short'],'state' => 6));
            }
        }
        exit;
    }

    /**
     * @name createInvoice
     * @desc 开具电子发票
     * @frequency 每10分钟运行一次
     */
    public function createInvoiceAction(){
        $result = $this->invoice_model->getPendingInvoice();
        $datas = array_filter($result);
        if (!$datas) {
            exit;
        }
        foreach ($datas as $value){
            $order = $this->checkOrder($value['id'], $value['order_id'], $value['invoice_type']);
            if(!$order){
                continue;
            }
            $result_state = $this->checkState($value['id'], $order['status']);
            if(!$result_state){
                continue;
            }
            $order = $this->batchOrderDetail($order);
            $sku_id = implode(',', $order['skus']);
            $skus = $this->sku_model->getInfoBySkuId($sku_id);
            //1.1.3版本中删除此校验,如果有sku匹配不成功,则只开具匹配成功的
//            $count = $this->contrastSku($order['skus'], $skus, $value['id']);
            //将原有数据表的税率,合并到有赞订单中
            $skuarr = array();
            foreach ($skus as $sk_val){
                $skuarr[$sk_val['sku_id']] = $sk_val['tax_tare'];
            }
            $orders = $this->treatingSku($order, $skuarr);
            //判断是否有空的sl,如果有将该sku删除掉
            $detail = array();
            foreach ($orders['new_detail'] as $detailVal){
                if($detailVal['sl'] == null){
                    unset($detailVal);
                }
                $detail[] = $detailVal;
            }
            $orders['new_detail'] = array_filter($detail);
            $wasOver = $this->regroupSku($orders);
            $this->print_d($wasOver);exit;
            //判断发票类型 1 红票
            if ($value['invoice_type'] == 1){
                $this->redInvoice($wasOver, $value);
                continue;
            }
            $this->invoice($wasOver, $value);
            continue;
        }
        exit;
    }

    /**
     * @param $order
     * @return mixed
     * @desc 重新计算删除个别sku后的合计税额,合计金额
     */
    public function regroupSku($order){
        $hjse = '';
        $payment_fee = '';
        $one_tax = 0.00;    //税率为0.00的金额
        $two_tax = 0.00;    //税率为0.06的金额
        $three_tax = 0.00;    //税率为0.17的金额
        foreach ($order['new_detail'] as &$d_val){
            $hjse += $d_val['se'];
            $payment_fee += $d_val['payment'];

            if ($d_val['sl'] == 0.00) {
                $one_tax += $d_val['payment'];
            } elseif ($d_val['sl'] == 0.06) {
                $two_tax += $d_val['payment'];
            } else {
                $three_tax += $d_val['payment'];
            }
        }
        $order['hjse'] = $hjse;
        $order['payment_fee'] = $payment_fee;
        $order['one_tax'] = $one_tax;
        $order['two_tax'] = $two_tax;
        $order['three_tax'] = $three_tax;

        return $order;
    }

    /**
     * @param $id
     * @param $state
     * @return bool
     * @desc 验证状态
     */
    public function checkState($id,$state){
        $orderState = [
            'TRADE_NO_CREATE_PAY' => '没有创建支付交易',
            'WAIT_BUYER_PAY' => '等待买家付款',
            'WAIT_PAY_RETURN' => '等待支付确认',
            'WAIT_SELLER_SEND_GOODS' => '买家已付款',
            'WAIT_BUYER_CONFIRM_GOODS' => '卖家已发货',
            'TRADE_CLOSED' => '交易自动关闭',
            'TRADE_CLOSED_BY_USER' => '买家主动关闭交易'

        ];
        if ($state !== 'TRADE_BUYER_SIGNED'){
            $this->invoice_model->update($id, array('state_message' => '订单状态不符:'.$orderState[$state], 'state' => 3));
            return false;
        }
        return true;
    }

    /**
     * @param $id
     * @param $order_id
     * @param $invoice_type
     * @return array|bool|mixed
     * @desc 检查订单是否存在
     */
    public function checkOrder($id, $order_id, $invoice_type){
        if (strlen($order_id) > 24 && $invoice_type == 1) {
            $order_id = substr($order_id, 0, -3);
        }

        $order = $this->getYouzanOrderByTid($order_id);
        if(!$order){
            $this->invoice_model->update($id, array('state_message' => '订单查询失败', 'state' => 3));
            return false;
        }
        return $order;
    }

    /**
     * @param $id
     * @param $sku_id
     * @return bool|string
     * @desc 检验skuid
     */
    public function checkSkuId($id, $sku_id){
        if(!$sku_id){
            $this->invoice_model->update($id, array('state_message' => 'skuid不存在','state' => 3));
            return false;
        }
        return $sku_id;
    }

    /**
     * @param $order
     * @return mixed
     * @desc 重新组装订单数据
     */
    public function batchOrderDetail($order){
        //取订单详情中,sku_id或item_id 不为空的数据
        foreach ($order['order_detail']  as $o_val){
            //只有没发生过退款的sku才能开具发票 item_refund_state不存在 说明没发生退款
            if(isset($o_val['item_refund_state'])){
                if ($o_val['outer_sku_id'] || $o_val['outer_item_id']) {
                    if ($o_val['outer_sku_id']) {
                        $sku_id[]= '\''.$o_val['outer_sku_id'].'\'';
                    } else {
                        $sku_id[]= '\''.$o_val['outer_item_id'].'\'';
                    }
                    $order['sum_price'] += $o_val['payment'];
                    $new_detail[] = $o_val;
                }
            }
        }
        //销毁原详情
        unset($order['order_detail']);
        $order['new_detail'] = $new_detail;
        $order['skus'] = $sku_id;

        return $order;
    }

    /**
     * @param $skus1
     * @param $skus2
     * @param $id
     * @return bool
     * @desc 对比sku数量
     */
    public function contrastSku($skus1, $skus2, $id){
        //用于计算sku数量/sku总数不等于查询出的sku数量则说明该订单中有sku没有匹配到税率,跳出循环
        if(count($skus1) !== count($skus2)){
            $this->invoice_model->update($id, array('state_message' => '个别sku税率未匹配成功', 'state' => 3));
            return false;
        }
        return true;
    }

    /**
     * @param $order
     * @param $type
     * @param $skuRate
     * @desc 处理sku数据
     */
    public function treatingSku($order, $skuRate, $type = 0){
        //合并数据,并计算税额/如果有改订单有优惠劵则均摊优惠劵金额
        if($order['discount_fee'] !== '0.00'){
            //最终支付总金额
            $total_fee = $order['sum_price'];
            //优惠劵金额
            $discount = $order['discount_fee'];
            foreach ($order['new_detail'] as &$d_val){
                $payment = $d_val['payment'];
                //优惠劵的平摊计算公式为: (sku商品支付金额 / 订单支付总金额) * 优惠劵金额 = 平摊金额
                $mean_price = (round($payment / $total_fee, 6) * $discount);
                //sku商品支付金额 - 平摊金额 = 平摊后的支付金额
                $discount_payment = round($payment - $mean_price, 2);
                //组合数据
                $d_val['payment'] = $discount_payment;
                $d_val['sl'] = $type == 0 ? ($d_val['outer_sku_id'] ? $skuRate[$d_val['outer_sku_id']] : $skuRate[$d_val['outer_item_id']]) :$d_val['sl'];
                //平摊后的支付总价  / 数量 = 平摊商品单价
                $discount_price = round($discount_payment / $d_val['num'], 6);
                $d_val['se'] = round($discount_payment - ($discount_payment / (1 + $d_val['sl'])),2);
                $d_val['xmje'] = $discount_payment - $d_val['se'];
                //商品单价 = 商品单价 - 商品单价的税额
                $d_val['price'] = $discount_price - round($discount_price - ($discount_price / (1 + $d_val['sl'])),6);
                $order['hjse'] += $d_val['se'];
                $order['payment_fee'] += $discount_payment;
            }
        } else {
            foreach ($order['new_detail'] as &$d_val){
                $d_val['sl'] = $type == 0 ? ($d_val['outer_sku_id'] ? $skuRate[$d_val['outer_sku_id']] : $skuRate[$d_val['outer_item_id']]) : $d_val['sl'];
                $d_val['se'] = round($d_val['payment'] - ($d_val['payment'] / (1 + $d_val['sl'])),2); //税额 等于支付金额 减去支付金额除1+税率
                $d_val['xmje'] = $d_val['payment'] - $d_val['se'];
                if($type){
                    $d_val['price'] = $d_val['payment'] - $d_val['se'];
                }else{
                    //sku支付总价 / 数量 = 商品单价
                    $price = round($d_val['payment'] / $d_val['num'], 4);
                    //商品单价 = 商品单价 - 商品单价的税额
                    $d_val['price'] = $price - round($price - ($price / (1 + $d_val['sl'])),6); //商品单价 减去税额
                }
                $order['hjse'] += $d_val['se'];
                $order['payment_fee'] += $d_val['payment'];
            }
        }
        return $order;
    }

    /**
     * @param array $orders
     * @param array $value
     * @return bool
     * @desc 开具蓝字发票
     */
    public function invoice(array $orders, array $value){
        if(!$orders || !$value){
            return false;
        }
        $orders['xsf_mc'] = $value['seller_name'];
        $orders['xsf_dzdh'] = $value['seller_address'];
        $orders['kpr'] = $value['drawer'];
        $orders['type'] = 0;
        $orders['hjje'] = $orders['payment_fee'] - $orders['hjse'];
        $orders['invoice_title'] = $value['invoice_title'];
        $orders['count'] = count($orders['new_detail']);
        $orders['invoice_no'] = strtotime(date('Y-m-d H:i:s')).mt_rand(1000,9999);
        $orders['receiver_mobile'] = $value['buyer_phone'];
        $orders['payee'] = $value['payee'];
        $orders['review'] = $value['review'];
        
        //开发票
        $result = $this->dzfp->fpkj($orders, $orders['new_detail']);
        if(!$result){
            $file_data = [
                'state' => self::INVOICE_FAIL,
                'state_message' => $this->dzfp->getError()
            ];
            $this->invoice_model->update($value['id'], $file_data);
            return false;
        }
        $params = $this->setParameter($orders, $result);
        $this->invoice_model->update($value['id'], $params);
    }

    /**
     * @desc 统一设置参数
     * @param array $order
     * @param $result
     * @return array
     */
    public function setParameter(array $order, $result){
        $params = array();

        $params['invoice_type'] = $order['type'];
        $params['qr_code'] = $result['EWM'];
        $params['invoice_code'] = $result['FPDM'];
        $params['invoice_number'] = $result['FPHM'];
        $params['check_code'] = $result['JYM'];
        $params['jqbh'] = $result['JQBH'];
        $params['state'] = self::INVOICE_SUCCESS;
        $params['state_message'] = $result['DESC'];
        $params['seller_name'] = $order['xsf_mc'];
        $params['seller_address'] = $order['xsf_dzdh'];
        $params['drawer'] = $order['kpr'];
        $params['payment_fee'] = $order['payment'];
        $params['total_tax'] = $order['hjse'];
        $params['jshj'] = $order['payment_fee'];
        $params['invoice_time'] = $result['KPRQ'];
        $params['order_time'] = $order['created'];
        $params['total_fee'] = $order['hjje'];
        $params['invoice_no'] = $order['invoice_no'];
        $params['one_tax'] = $order['one_tax'];
        $params['two_tax'] = $order['two_tax'];
        $params['three_tax'] = $order['three_tax'];

        return $params;
    }

    /**
     * @desc 开具红票
     * @param array $order
     * @param array $invoice_info
     * @param int $type 1 正常红票 2脏数据红票冲印
     * @return bool
     */
    public function redInvoice(array $order, array $invoice_info, $type = 1)
    {
        $orders = array_filter($order);
        $invoice_info = array_filter($invoice_info);
        if (!$orders || !$invoice_info) {
            return false;
        }
        
        $orders['xsf_mc'] = $invoice_info['seller_name'];
        $orders['xsf_dzdh'] = $invoice_info['seller_address'];
        $orders['kpr'] = $invoice_info['drawer'];
        $orders['type'] = 1;
        $orders['count'] = count($order['new_detail']);
        $orders['hjje'] = $order['payment_fee'];
        $orders['hjse'] = $order['hjse'];
        $orders['payment_fee'] = $order['hjse'] + $order['payment_fee'];
        $orders['invoice_title'] = $invoice_info['invoice_title'];
        $orders['invoice_no'] = strtotime(date('Y-m-d H:i:s')).mt_rand(1000,9999);
        $orders['yfp_hm'] = $invoice_info['invoice_number'];
        $orders['yfp_dm'] = $invoice_info['invoice_code'];
        $orders['receiver_mobile'] = $invoice_info['buyer_phone'];

        $result = $this->dzfp->fpkj($orders, $orders['new_detail']);
        if(!$result) {
            $rs_data = [
                'state_message' => $this->dzfp->getError(),
                'state' => self::INVOICE_FAIL,
            ];
            if($type == 1){
                $this->invoice_model->update($invoice_info['id'], $rs_data);
                return false;
            }else{
                $this->invoice_model->updateDirtyData($invoice_info['id'], $rs_data);
                return false;
            }
        }
        //更新信息到数据库
        $params = array(
            'invoice_type' => 1,
            'state' => self::RED_INVOICE_SUCCESS,
            'state_message' => '红字发票开具成功'
        );
        if($type == 1){
            $this->invoice_model->update($invoice_info['id'], $params);
        }else{
            $this->invoice_model->updateDirtyData($invoice_info['id'], $params);
        }
    }

    /**
     * @desc 查询有赞订单
     * @param $tid
     * @return array|bool|mixed
     */
    public function getYouzanOrderByTid($tid){
        $url = 'kdt.trade.get';
        if(!$tid){
            return false;
        }
        $result = $this->youzan_api->get($url, array('tid' => $tid));
        if(!$result['response']){
            return false;
        }
        $order = $this->youzan_order_model->struct_order_data($result['response']);
        return $order;
    }

    /**
     * @desc 组合商品开票
     */
    public function createCkdInvoiceAction(){
        $result = $this->invoice_model->getCkdInvoice();
        $datas = array_filter($result);
        if(!$datas){
            exit;
        }
        foreach ($datas as $value){
            $order = $this->checkOrder($value['id'], $value['order_id'], $value['invoice_type']);
            if(!$order){
                continue;
            }
            $result_state = $this->checkState($value['id'], $order['status']);
            if(!$result_state){
                continue;
            }
            $order = $this->batchOrderDetail($order);
            $sku_id = implode(',', $order['skus']);
            $skus = $this->sku_model->getInfoBySkuId($sku_id);
            if(empty($skus)){
                $this->GroupProduct($sku_id, $order, $value);
                continue;
            }
            //不为空说明这订单是组合+单个商品订单
            $skuarr = array();
            foreach ($skus as $skv){
                $sku_array2[] = '\''.$skv['sku_id'].'\'';
                $skuarr[$skv['sku_id']] = $skv['tax_tare'];
            }
            //对比两个sku取出不不等的值
            $ckd_sku = array_diff($order['skus'], $sku_array2);
            foreach ($order['new_detail'] as $key => &$d_val){
                $d_val['sl'] = $d_val['outer_sku_id'] ? $skuarr[$d_val['outer_sku_id']] : $skuarr[$d_val['outer_item_id']];
                //未成功匹配到税率的删除
                if(empty($d_val['sl'])){
                    unset($order['new_detail'][$key]);
                }
            }
            //根据不等的值查询组合sku表
            if ($ckd_sku){
                $two_sku_id = implode(',', $ckd_sku);
                $this->GroupProduct($two_sku_id, $order, $value, 2);
                continue;
            }
        }
        exit;
    }

    /**
     * @param $sku_id
     * @param $order
     * @param $type 1该订单只有一件组合商品 2该订单为组合商品和单个商品订单
     * @return array|bool
     * @desc 查询组合商品父级sku编码
     */
    public function getCkdSku($sku_id, array $order ,$type){
        $ckdSkus = $this->ckd->getInfoBySkuId($sku_id);
        if(!$ckdSkus){
            $this->errorMsg = '未查询到组合商品编码';
            return false;
        }
        //拿到子级sku编码
        foreach ($ckdSkus as $ckdval){
            $ckd_sku_id_arr[] = '\''.$ckdval['kind_sku_id'].'\'';
        }
        $ckd_sku_id = implode(',', $ckd_sku_id_arr);
        //根据编码查询
        $ckd_data = $this->sku_model->getInfoBySkuId($ckd_sku_id);
        if(!$ckd_data){
            $this->errorMsg = '未查询到sku编码';
            return false;
        }

        //获取已经设置好的金额
        $money = $this->ckd->getMoney($ckd_sku_id);
        $sku_money = [];
        foreach ($money as $val){
            $sku_money[$val['kind_sku_id']] = $val['payment'];
        }
        //对应sku合并对应金额
        foreach ($ckd_data as &$cval){
            $cval['title'] = $cval['product_name'];
            $cval['sl'] = $cval['tax_tare'];
            $cval['payment'] = $sku_money[$cval['sku_id']];
            $cval['num'] = 1;
        }
        $retaArr = array();
        //单个组合商品
        if($type == 1){
            $order['new_detail'] = $ckd_data;
            $orders = $this->treatingSku($order,$retaArr,$type);
            return $orders;
        }else{
            //多个组合商品,需要合并其他订单数据
            $detail_all = array_merge($ckd_data, $order['new_detail']);
            $order['new_detail'] = $detail_all;
            $orders = $this->treatingSku($order, $retaArr, 1);
            return $orders;
        }
    }

    /**
     * @param $sku_id
     * @param $order
     * @param $invoice
     * @param $type
     * @return bool
     * @desc 单个组合商品开发票
     */
    public function GroupProduct($sku_id,$order, $invoice, $type = 1){
        $kind_order = $this->getCkdSku($sku_id,$order, $type);
        if(!$kind_order){
            $this->invoice_model->update($invoice['id'], ['state_message' => $this->getError(), 'state' => 3]);
            return false;
        }

        $wasOver = $this->regroupSku($kind_order);
        if ($invoice['invoice_type'] == 1){
            $this->redInvoice($wasOver, $invoice);
            return false;
        }
        $this->invoice($wasOver, $invoice);
    }

    /**
     * @return mixed
     * @desc 获取错误
     */
    public function getError(){
        return $this->errorMsg;
    }

    /**
     * @desc 数据统计 获取当天的开票数据
     * @return mixed
     */
    public function dataAction(){
        $time = date('Y-m-d', time());
        $invoices = $this->invoice_model->getSuccessInvoice($time);
        if(!$invoices){
            exit;
        }

        $count_one_data = [];
        $count_two_data = [];
        $count_thr_data = [];
        foreach ($invoices as $val) {
            if ($val['one_tax'] !== '0.00') {
                $count_one_data['se'] = '0.00';
                $count_one_data['tax'] = '0.00';
                $count_one_data['payment'] = $val['one_tax'];
                $count_one_data['updatetime'] = date('Y-m-d', time());
                $count_one_data['type'] = $val['invoice_type'] == 0 ? 1 : 2;
                $this->invoice_data_model->insertData($count_one_data);
            }
            if ($val['two_tax'] !== '0.00') {
                $sl = 0.06;
                $count_two_data['se'] = $val['two_tax'] * $sl;
                $count_two_data['tax'] = $sl;
                $count_two_data['payment'] = $val['two_tax'];
                $count_two_data['updatetime'] = date('Y-m-d', time());
                $count_two_data['type'] = $val['invoice_type'] == 0 ? 1 : 2;
                $this->invoice_data_model->insertData($count_two_data);
            }
            if ($val['three_tax'] !== '0.00') {
                $slq = 0.17;
                $count_thr_data['se'] = $val['three_tax'] * $slq;
                $count_thr_data['tax'] = $slq;
                $count_thr_data['payment'] = $val['three_tax'];
                $count_thr_data['updatetime'] = date('Y-m-d', time());
                $count_thr_data['type'] = $val['invoice_type'] == 0 ? 1 : 2;
                $this->invoice_data_model->insertData($count_thr_data);
            }
            $this->invoice_model->update($val['id'], ['cronta_sta' => 2]);
        }
        exit;
    }

    /**
     * @explain 历史数据开红票冲掉
     */
    public function repairDirtyDataAction()
    {
        $datas = $this->invoice_model->getRedInvoice();
        $invoices = array_filter($datas);
        if(!$invoices){
            exit;
        }
        foreach ($invoices as $value){
            $order = Fileds::$redOrder[$value['order_id']];
            $value['new_detail'] = $order;
            $params['xsf_mc'] = $value['seller_name'];
            $params['xsf_dzdh'] = $value['seller_address'];
            $params['kpr'] = $value['drawer'];
            $params['type'] = 1;
            $params['count'] = count($value['new_detail']);
            $params['hjje'] = $value['total_fee'];
            $params['hjse'] = $value['total_tax'];
            $params['payment_fee'] = $value['total_fee'] + $value['total_tax'];
            $params['invoice_title'] = $value['invoice_title'];
            $params['invoice_no'] = strtotime(date('Y-m-d H:i:s')).mt_rand(1000,9999);;
            $params['yfp_hm'] = $value['invoice_number'];
            $params['yfp_dm'] = $value['invoice_code'];
            $params['receiver_mobile'] = $value['buyer_phone'];
            $params['new_detail'] = $order;

            $result = $this->dzfp->fpkj($params, $params['new_detail']);
            if(!$result) {
                $rs_data = [
                    'state_message' => $this->dzfp->getError(),
                    'state' => self::INVOICE_FAIL,
                ];
                $this->invoice_model->update($value['id'], $rs_data);
                continue;
            }
            //更新信息到数据库
            $param = array(
                'invoice_type' => 1,
                'state' => self::RED_INVOICE_SUCCESS,
                'state_message' => '红字发票开具成功'
            );
            $this->invoice_model->update($value['id'], $param);
            continue;
        }
        exit;
    }

    public function checkYouZanOrderAction()
    {
        $payment_fee = 0;
        $data = $this->invoice_model->getYzO();
        foreach ($data as $value) {
            $order = $this->getYouzanOrderByTid($value['order_id']);
            $payment_fee += $order['payment'];
        }
        echo $payment_fee;exit;
    }

    /**
     * @explain 验证订单状态
     */
    public function checkInvoiceStateAction()
    {
        $data = $this->invoice_model->getCheckOrderList(1, 20, 1);
        if (empty($data['data']) || !$data) {
            exit;
        }
        $url = 'kdt.trade.get';
        $n = 0;
        $orderState = [
            'TRADE_NO_CREATE_PAY' => '没有创建支付交易',
            'WAIT_BUYER_PAY' => '等待买家付款',
            'WAIT_PAY_RETURN' => '等待支付确认',
            'TRADE_CLOSED' => '交易自动关闭',
            'TRADE_CLOSED_BY_USER' => '买家主动关闭交易'
        ];
        do{
            $n++;
            $result = $this->invoice_model->getCheckOrderList($n, 20, 1, null, 1);
            foreach ($result['data'] as $value) {
                $y_order = $this->youzan_api->get($url, array('tid' => $value['order_id']));
                $status = $y_order['response']['trade']['status'];
                if (in_array($status, array_flip($orderState))) {
                    $this->invoice_model->updateCheckOrder($value['id'], ['state_message' => $orderState[$status], 'state' => 2]);
                    continue;
                }
                if ($y_order['response']['trade']['feedback_num'] != 0) {
                    $this->invoice_model->updateCheckOrder($value['id'], ['state_message' => '有维权', 'state' => 2]);
                } else {
                    $this->invoice_model->updateCheckOrder($value['id'], ['state_message' => '无维权' ,'state' => 3]);
                }
            }
            if($result['has_next'] == 0){
                exit;
            }
        }
        while(1);
    }
}