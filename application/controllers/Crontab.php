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

    public $app_id = KDT_APP_ID;
    public $app_secert = KDT_APP_SECERT;

    public function init(){
        Yaf_Loader::import(ROOT_PATH . '/application/library/youzan/KdtApiClient.php');
        $this->invoice_model = new InvoiceModel();
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
                //更新发票信息
                $this->invoice_model->update($value['id'], array('invoice_url' => $dwz_url['urls'][0]['url_short'],'state' => 4));
                //将发票地址发送给用户
                $sms = new Sms();
                $message = '您好，您在罗辑思维所购产品的电子发票地址为:'.$dwz_url['urls'][0]['url_short'].'地址有效期为30天，请尽快在电脑端查看。';
                $sms->sendmsg($message, $value['buyer_phone']);
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
        $sku_id = '';
        //遍历数组
        if ($datas) {
            foreach ($datas as $value){
                $order = $this->getYouzanOrderByTid($value['order_id']);
                if(!$order){
                    $this->invoice_model->update($value['id'], array('state_message' => '订单查询失败', 'state' => 3));
                    continue;
                }
                
                //判断订单状态是否符合开票要求
                if ($order['status'] !== 'TRADE_BUYER_SIGNED'){
                    $this->invoice_model->update($value['id'], array('state_message' => '订单状态不符', 'state' => 3));
                    continue;
                }

                //取订单详情中,sku_id或item_id 不为空的数据
                foreach ($order['order_detail']  as $o_val){
                    //只有没发生过退款的sku才能开具发票 item_refund_state不存在 说明没发生退款
                    if(isset($o_val['item_refund_state'])){
                        if ($o_val['outer_sku_id'] || $o_val['outer_item_id']) {
                            if ($o_val['outer_sku_id']) {
                                $sku_id .= "'" . $o_val['outer_sku_id'] . "',";
                            } else {
                                $sku_id .= "'" . $o_val['outer_item_id'] . "',";
                            }
                            $order['sum_price'] += $o_val['payment'];
                            $new_detail[] = $o_val;
                        }
                    }
                }

                //销毁详情
                unset($order['order_detail']);

                if(!$sku_id){
                    $this->invoice_model->update($value['id'], array('state_message' => 'skuid不存在','state' => 3));
                    continue;
                }

                //根据有赞sku_id 查询sku表
                $skus = $this->sku_model->getInfoBySkuId(substr($sku_id, 0, -1));

                //用于计算sku数量/sku总数不等于查询出的sku数量则说明该订单中有sku没有匹配到税率,跳出循环
                $sku_array = explode(',', substr($sku_id, 0, -1));
                if(count($sku_array) !== count($skus)){
                    $this->invoice_model->update($value['id'], array('state_message' => '个别sku税率未匹配成功', 'state' => 3));
                    continue;
                }

                //将原有数据表的税率,合并到有赞订单中
                $skuarr = array();
                foreach ($skus as $sk_val){
                    $skuarr[$sk_val['sku_id']] = $sk_val['tax_tare'];
                }
                //合并数据,并计算税额/如果有改订单有优惠劵则均摊优惠劵金额
                if($order['discount_fee'] !== '0.00'){
                    //支付金额
                    $total_fee = $order['sum_price'];
                    //优惠劵金额
                    $discount = $order['discount_fee'];
                    foreach ($new_detail as &$d_val){
                        $payment = $d_val['payment'];
                        //优惠劵的平摊计算公式为: (sku商品支付金额 / 订单支付总金额) * 优惠劵金额 = 平摊金额
                        $mean_price = (round($payment / $total_fee, 6) * $discount);
                        //sku商品支付金额 - 平摊金额 = 平摊后的支付金额
                        $discount_payment = round($payment - $mean_price, 2);
                        //组合数据
                        $d_val['payment'] = $discount_payment;
                        $d_val['sl'] = $d_val['outer_sku_id'] ? $skuarr[$d_val['outer_sku_id']] : $skuarr[$d_val['outer_item_id']];
                        //平摊后的支付总价  / 数量 = 平摊商品单价
                        $discount_price = round($discount_payment / $d_val['num'], 6);
                        //商品单价 减去税额
                        $spdj = $discount_price - round($discount_price - ($discount_price / (1 + $d_val['sl'])),6);
                        $d_val['se'] = round($discount_payment - ($discount_payment / (1 + $d_val['sl'])),2); //税额 等于支付金额 减去支付金额除1+税率
                        $d_val['xmje'] = $discount_payment - $d_val['se'];
                        $d_val['price'] = $spdj;
                        $order['hjse'] += $d_val['se'];
                        $order['payment_fee'] += $discount_payment;
                    }
                } else {
                    foreach ($new_detail as &$d_val){
                        $d_val['sl'] = $d_val['outer_sku_id'] ? $skuarr[$d_val['outer_sku_id']] : $skuarr[$d_val['outer_item_id']];
                        $d_val['se'] = round($d_val['payment'] - ($d_val['payment'] / (1 + $d_val['sl'])),2); //税额 等于支付金额 减去支付金额除1+税率
                        $d_val['xmje'] = $d_val['payment'] - $d_val['se'];
                        $d_val['price'] = $d_val['price'] - round($d_val['price'] - ($d_val['price'] / (1 + $d_val['sl'])),6); //商品单价 减去税额
                        $order['hjse'] += $d_val['se'];
                        $order['payment_fee'] += $d_val['payment'];
                    }
                }
                //new order_detail
                $order['new_detail'] =  $new_detail;
                //判断发票类型 1 红票
                if ($value['invoice_type'] == 1){
                    $this->redInvoice($order, $value);
                    continue;
                }

                $order['xsf_mc'] = $value['seller_name'];
                $order['xsf_dzdh'] = $value['seller_address'];
                $order['kpr'] = $value['drawer'];
                $order['type'] = 0;
                $order['hjje'] = $order['payment_fee'] - $order['hjse'];
                $order['invoice_title'] = $value['invoice_title'];
                $order['count'] = count($order['new_detail']);
                $order['invoice_no'] = strtotime(date('Y-m-d H:i:s')).mt_rand(1000,9999);
                $order['receiver_mobile'] = $value['buyer_phone'];
                $order['payee'] = $value['payee'];
                $order['review'] = $value['review'];

                print_r($order);exit;
                //开发票
                $result = $this->dzfp->fpkj($order, $order['new_detail']);
                if(!$result){
                    $file_data = [
                        'state' => self::INVOICE_FAIL,
                        'state_message' => $this->dzfp->getError()
                    ];
                    $this->invoice_model->update($value['id'], $file_data);
                    continue;
                }
                $params = $this->setParameter($order, $result);
                $this->invoice_model->update($value['id'], $params);
            }
        }
        exit;
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

        return $params;
    }

    /**
     * @desc 开具红票
     * @param array $order
     * @param array $invoice_info
     * @return bool
     */
    public function redInvoice(array $order, array $invoice_info)
    {
        $orders = array_filter($order);
        $invoice_info = array_filter($invoice_info);
        if (!$orders && !$invoice_info) {
            return false;
        }

        $orders['xsf_mc'] = $invoice_info['seller_name'];
        $orders['xsf_dzdh'] = $invoice_info['seller_address'];
        $orders['kpr'] = $invoice_info['drawer'];
        $orders['type'] = 1;
        $orders['count'] = count($order['new_detail']);
        $orders['hjje'] = $invoice_info['total_fee'];
        $orders['hjse'] = $invoice_info['total_tax'];
        $orders['payment'] = $invoice_info['jshj'];
        $orders['invoice_title'] = $invoice_info['invoice_title'];
        $orders['invoice_no'] = $invoice_info['invoice_no'];
        $orders['yfp_hm'] = $invoice_info['invoice_number'];
        $orders['yfp_dm'] = $invoice_info['invoice_code'];
        $orders['receiver_mobile'] = $invoice_info['buyer_phone'];

        $result = $this->dzfp->fpkj($orders, $orders['new_detail']);
        if(!$result) {
            $rs_data = [
                'state_message' => $this->dzfp->getError(),
                'state' => self::INVOICE_FAIL,
            ];
            $this->invoice_model->update($invoice_info['id'], $rs_data);
            return false;
        }
        //更新信息到数据库
        $params = array(
            'original_invoice_code' => $invoice_info['invoice_number'],
            'original_invoice_number' => $invoice_info['invoice_code'],
            'invoice_type' => 1,
            'state' => self::INVOICE_SUCCESS,
            'state_message' => '红字发票开具成功'
        );
        $this->invoice_model->update($invoice_info['id'], $params);
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
        $sku_id = '';
        $sku_iid = '';
        if($datas){
            foreach ($datas as $value){
                $order = $this->getYouzanOrderByTid($value['order_id']);
                if(!$order){
                    $this->invoice_model->update($value['id'], array('state_message' => '订单查询失败', 'state' => 3));
                    continue;
                }

                //判断订单状态是否符合开票要求
//                if ($order['status'] !== 'TRADE_BUYER_SIGNED'){
//                    $this->invoice_model->update($value['id'], array('state_message' => '订单状态不符', 'state' => 3));
//                    continue;
//                }

                //取订单详情中,sku_id或item_id 不为空的数据
                foreach ($order['order_detail']  as $o_val){
                    //只有没发生过退款的sku才能开具发票 item_refund_state不存在 说明没发生退款
                    if(isset($o_val['item_refund_state'])){
                        if ($o_val['outer_sku_id'] || $o_val['outer_item_id']) {
                            if ($o_val['outer_sku_id']) {
                                $sku_id .= "'" . $o_val['outer_sku_id'] . "',";
                                $sku_iid .= $o_val['outer_sku_id'] .',';
                            } else {
                                $sku_id .= "'" . $o_val['outer_item_id'] . "',";
                                $sku_iid .= $o_val['outer_item_id'] .',';
                            }
                            $order['sum_price'] += $o_val['payment'];
                            $new_detail[] = $o_val;
                        }
                    }
                }


                unset($order['order_detail']);
                if(!$sku_id){
                    continue;
                }
                //根据有赞sku_id 查询sku表
                $skus = $this->sku_model->getInfoBySkuId(substr($sku_id, 0, -1));
                //如果skus 为空则说明这个订单只有一个组合商品,则根据组合商品skuid查询
                if(empty($skus)){
                    $kind_order = $this->getCkdSku($sku_id,$order);
                    if(!$kind_order){
                        $this->invoice_model->update($value['id'], ['state_message' => $this->getError(), 'state' => 3]);
                        continue;
                    }
                    //组合数据
                    $kind_order['xsf_mc'] = $value['seller_name'];
                    $kind_order['xsf_dzdh'] = $value['seller_address'];
                    $kind_order['kpr'] = $value['drawer'];
                    $kind_order['type'] = 0;
                    $kind_order['hjje'] = $kind_order['payment_fee'] - $kind_order['hjse'];
                    $kind_order['invoice_title'] = $value['invoice_title'];
                    $kind_order['invoice_no'] = strtotime(date('Y-m-d H:i:s')).mt_rand(1000,9999);
                    $kind_order['receiver_mobile'] = $value['buyer_phone'];
                    $kind_order['payee'] = $value['payee'];
                    $kind_order['review'] = $value['review'];

                    //开票
                    $result = $this->dzfp->fpkj($kind_order,$kind_order['new_detail']);
                    if(!$result){
                        $file_data = [
                            'state' => self::INVOICE_FAIL,
                            'state_message' => $this->dzfp->getError()
                        ];
                        $this->invoice_model->update($value['id'], $file_data);
                        continue;
                    }
                    $params = $this->setParameter($kind_order, $result);
                    $this->invoice_model->update($value['id'], $params);
                    continue;

                }
                //不为空说明这订单是组合+单个商品订单
                $skuarr = array();
                foreach ($skus as $skv){
                    $sku_array2[] = $skv['sku_id'];
                    $skuarr[$skv['sku_id']] = $skv['tax_tare'];
                }

                $sku_array = explode(',', substr($sku_iid, 0, -1));
                //对比两个sku取出不不等的值
                $ckd_sku = array_diff($sku_array,$sku_array2);

                foreach ($new_detail as $key => &$d_val){
                    $d_val['sl'] = $d_val['outer_sku_id'] ? $skuarr[$d_val['outer_sku_id']] : $skuarr[$d_val['outer_item_id']];
                    $d_val['se'] = round($d_val['payment'] - ($d_val['payment'] / (1 + $d_val['sl'])),2); //税额 等于支付金额 减去支付金额除1+税率
                    $d_val['xmje'] = $d_val['payment'] - $d_val['se'];
                    $d_val['price'] = $d_val['price'] - round($d_val['price'] - ($d_val['price'] / (1 + $d_val['sl'])),6); //商品单价 减去税额
                    $order['hjse'] += $d_val['se'];
                    $order['payment_fee'] += $d_val['payment'];
                    if(empty($d_val['sl'])){
                        unset($new_detail[$key]);
                    }
                }

                //根据不等的值查询组合sku表
                if ($ckd_sku){
                    $kind_order2 = $this->mergeSku($ckd_sku,$order,$new_detail);
                    if(!$kind_order2){
                        $this->invoice_model->update($value['id'], ['state_message' => $this->getError(), 'state' => 3]);
                        continue;
                    }
                    //组合数据
                    if($value['invoice_type'] == 1){
                        $kind_order2['yfp_dm'] = $value['invoice_code'];
                        $kind_order2['yfp_hm'] = $value['invoice_number'];
                        $params['original_invoice_code'] = $value['invoice_number'];
                        $params['original_invoice_number'] = $value['invoice_code'];
                        $params['state_message'] = '红字发票开具成功';
                    }
                    $kind_order2['xsf_mc'] = $value['seller_name'];
                    $kind_order2['xsf_dzdh'] = $value['seller_address'];
                    $kind_order2['kpr'] = $value['drawer'];
                    $kind_order2['type'] = $value['invoice_type'] == 0 ? 0 : 1; // 0 蓝字发票 1红字发票
                    $kind_order2['hjje'] = $kind_order2['payment_fee'] - $kind_order2['hjse'];
                    $kind_order2['invoice_title'] = $value['invoice_title'];
                    $kind_order2['invoice_no'] = strtotime(date('Y-m-d H:i:s')).mt_rand(1000,9999);
                    $kind_order2['receiver_mobile'] = $value['buyer_phone'];
                    $kind_order2['payee'] = $value['payee'];
                    $kind_order2['review'] = $value['review'];

                    //开发票
                    $result = $this->dzfp->fpkj($kind_order2, $kind_order2['new_detail']);
                    if(!$result){
                        $file_data = [
                            'state' => self::INVOICE_FAIL,
                            'state_message' => $this->dzfp->getError()
                        ];
                        $this->invoice_model->update($value['id'], $file_data);
                        continue;
                    }
                    $params = $this->setParameter($kind_order2, $result);
                    $this->invoice_model->update($value['id'], $params);
                    continue;
                }
            }
        }
        exit;
    }

    /**
     * @param $ckd_sku
     * @param $order
     * @param $new_detail
     * @return array|bool
     * @desc 遍历获取
     */
    public function mergeSku($ckd_sku, $order, $new_detail){
        $ckd_sku_id = '';
        foreach ($ckd_sku as $ckd_val){
            $ckd_sku_id .= '\''.$ckd_val.'\',';
            $ckd_data = $this->getCkdSku($ckd_sku_id, $order, $new_detail);
        }
        return $ckd_data;
    }

    /**
     * @param $sku_id
     * @param $order
     * @param $new_detail
     * @return array|bool
     * @desc 查询组合商品父级sku编码
     */
    public function getCkdSku($sku_id, $order,$new_detail){
        if(!$sku_id){
            return false;
        }

        $ckd_sku_id_str = '';
        $ckdSkus = $this->ckd->getInfoBySkuId(substr($sku_id, 0, -1));
        if(!$ckdSkus){
            $this->errorMsg = '未查询到组合商品编码';
            return false;
        }

        //拿到子级sku编码
        foreach ($ckdSkus as $ckdval){
            $ckd_sku_id_str .= '\''.$ckdval['kind_sku_id'].'\',';
        }
        //根据编码查询
        $ckd_data = $this->sku_model->getInfoBySkuId(substr($ckd_sku_id_str, 0, -1));
        if(!$ckd_data){
            $this->errorMsg = '未查询到sku编码';
            return false;
        }

        //获取已经设置好的金额
        $money = $this->ckd->getMoney(substr($ckd_sku_id_str, 0, -1));
        $sku_money = [];
        foreach ($money as $val){
            $sku_money[$val['kind_sku_id']] = $val['payment'];
        }

        //对应sku合并对应金额
        foreach ($ckd_data as &$cval){
            $cval['title'] = $cval['product_name'];
            $cval['sl'] = $cval['tax_tare'];
            $cval['payment'] = $sku_money[$cval['sku_id']];
            $cval['se'] = round($cval['payment'] - ($cval['payment'] / (1 + $cval['tax_tare'])),2); //税额 等于支付金额 减去支付金额除1+税率
            $cval['xmje'] = $cval['payment'] - $cval['se'];
            $cval['num'] = 1;
            $cval['price'] = $cval['payment'] - $cval['se'];
            $order['hjse'] += $cval['se'];
            $order['payment_fee'] += $cval['payment'];
        }
        $dataAll = array_merge($ckd_data, $new_detail);
        $order['count'] = count($dataAll);
        $order['new_detail'] = $dataAll;

        return $order;
    }

    /**
     * @return mixed
     * @desc 获取错误
     */
    public function getError(){
        return $this->errorMsg;
    }

}