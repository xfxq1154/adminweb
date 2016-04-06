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

    const INVOICE_SUCCESS = 2;
    const INVOICE_FAIL = 3;

    public function init(){
        $this->invoice_model = new InvoiceModel();
        $this->dzfp = new Dzfp();
        $this->sku_model = new SkuModel();
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
                $message = '请在电脑端查看您的发票，地址:'.$dwz_url['urls'][0]['url_short'];
                $sms->sendmsg($message, $value['buyer_phone']);
            }
        }
        exit;
    }

    /**
     * @name createInvoice
     * @desc 开具电子发票
     * @frequency 每5分钟运行一次
     */
    public function createInvoiceAction(){
        $result = $this->invoice_model->getPendingInvoice();
        $datas = array_filter($result);
        $youzan = new Invoice();
        $sku_id = '';
        //遍历数组
        if ($datas) {
            foreach ($datas as $value){
                $order = $youzan->getInfoById($value['order_id']);
                if ($order['status'] !== 'TRADE_BUYER_SIGNED'){
                    $this->invoice_model->update($value['id'], array('state_message' => '订单状态不符'));
                    continue;
                }

                //取订单详情中,sku_id或item_id 不为空的数据
                foreach ($order['order_detail']  as $o_val){
                    if($o_val['outer_sku_id'] || $o_val['outer_item_id']) {
                        if ($o_val['outer_sku_id']) {
                            $sku_id .= "'" . $o_val['outer_sku_id'] . "',";
                        } else {
                            $sku_id .= "'" . $o_val['outer_item_id'] . "',";
                        }
                        $new_detail[] = $o_val;
                    }
                }

                //销毁详情
                unset($order['order_detail']);

                if(!$sku_id){
                    $this->invoice_model->update($value['id'], array('state_message' => 'skuid不存在'));
                    continue;
                }
                //根据有赞sku_id 查询sku表
                $skus = $this->sku_model->getInfoBySkuId(substr($sku_id, 0, -1));
                if(!$skus){
                    continue;
                }

                //将原有数据表的税率,合并到有赞订单中
                $skuarr = array();
                foreach ($skus as $sk_val){
                    $skuarr[$sk_val['sku_id']] = $sk_val['tax_tare'];
                }

                //合并数据,并计算税额
                foreach ($new_detail as &$d_val){
                    $d_val['sl'] = $d_val['outer_sku_id'] ? $skuarr[$d_val['outer_sku_id']] : $skuarr[$d_val['outer_item_id']];
                    $d_val['se'] = round($d_val['payment'] - ($d_val['payment'] / (1 + $d_val['sl'])),2); //税额 等于支付金额 减去支付金额除1+税率
                    $d_val['xmje'] = $d_val['payment'] - $d_val['se'];
                    $order['hjse'] += $d_val['se'];
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
                $order['hjje'] = $order['payment'] - $order['hjse'];
                $order['invoice_title'] = $value['invoice_title'];
                $order['count'] = count($order['new_detail']);
                $order['invoice_no'] = strtotime(date('Y-m-d H:i:s')).mt_rand(1000,9999);
                $order['receiver_mobile'] = $value['buyer_phone'];

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
        $params['jshj'] = $order['payment'];
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
        $orders['payment'] = $invoice_info['payment_fee'];
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
            'state' => self::INVOICE_SUCCESS
        );
        $this->invoice_model->update($invoice_info['id'], $params);
    }
}

    