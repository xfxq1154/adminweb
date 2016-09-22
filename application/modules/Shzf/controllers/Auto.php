<?php
/**
 * @auth why
 * @explain 生活作风电子发票
 */
class AutoController extends Base
{
    /** @var  ShzfInvModel */
    private $shzfInvModel;
    /** @var  StoreModel */
    private $orderModel;
    /** @var SkuModel  */
    private $shzfSkuModel;
    /** @var ShzfDzfp */
    public $dzfp;
    public $error_msg = '';

    const INVOICE_SUCCESS = 2;
    const INVOICE_FAIL = 3;
    const RED_INVOICE_SUCCESS = 4;

    public function init(){
        $this->shzfInvModel = new ShzfInvModel();
        $this->orderModel = new StoreModel();
        $this->dzfp = new ShzfDzfp();
        $this->shzfSkuModel = new ShzfSkuModel();
    }

    /**
     * @name getPdfSendMessage
     * @desc 获取发票pdf文件，并且发送短信给用户
     * @frequency 每5分钟运行一次
     */
    public function SendMessageAction(){
        $datas = $this->shzfInvModel->getAll();
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
            $rs_oss = $this->shzfInvModel->ossUpload($pdf);
            if(!$rs_oss){
                continue;
            }
            //查询私密发票地址
            $invoice_path = $this->shzfInvModel->getInvoice($rs_oss['object']);
            if(!$invoice_path){
                continue;
            }
            //生成短网址
            $dwz_url = $this->shzfInvModel->dwz($invoice_path);
            if($dwz_url['errNum']){
                continue;
            }
            //将发票地址发送给用户
            $sms = new Sms();
            $message = '您好，您在罗辑思维所购产品的电子发票地址为:'.$dwz_url['urls'][0]['url_short'].'。地址有效期为30天，请尽快在电脑端查看。';
            $status = $sms->sendmsg($message, $value['buyer_phone']);
            if($status['status'] == 'ok'){
                $this->shzfInvModel->update($value['id'], array('invoice_url' => $dwz_url['urls'][0]['url_short'],'state' => 4));
            }else{
                $this->shzfInvModel->update($value['id'], array('invoice_url' => $dwz_url['urls'][0]['url_short'],'state' => 6));
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
        $result = $this->shzfInvModel->getPendingInvoice();
        $datas = array_filter($result);
        if (!$datas) {
            exit;
        }
        foreach ($datas as $value){
            if ($value['type'] == 1){
                $this->redInvoice($value);
                continue;
            }
            $order = $this->orderInfo($value['order_id']);
            if(!$order){
                continue;
            }
            $state = $this->checkState($value['id'], $order['state']);
            if(!$state){
                continue;
            }
            $orders = $this->batchDetail($order);
            $sku_id = implode(',', $orders['skus']);
            $skus = $this->shzfSkuModel->getInfoBySkuId($sku_id);
            //将原有数据表的税率,合并到有赞订单中
            $skuarr = array();
            foreach ($skus as $sk_val){
                $skuarr[$sk_val['sku_id']] = $sk_val['tax_tare'];
            }
            $orders = $this->treatingSku($order, $skuarr);
            //判断是否有空的sl,如果有将该sku删除掉
            $detail = array();
            foreach ($orders['order_detail'] as $val){
                if($val['sl'] == null){
                    unset($val);
                }
                $detail[] = $val;
            }
            $orders['order_detail'] = array_filter($detail);
            $wasOver = $this->regroupSku($orders);
            $this->invoice($wasOver, $value);
            continue;
        }
        exit;
    }

    /**
     * @param $info
     * @return bool
     * @explain 开红票
     */
    private function redInvoice($info)
    {
        $orders['xsf_mc']           = $info['seller_name'];
        $orders['xsf_dzdh']         = $info['seller_address'];
        $orders['kpr']              = $info['drawer'];
        $orders['type']             = $info['type'];
        $orders['count']            = 1;
        $orders['hjje']             = $info['total_fee'];
        $orders['hjse']             = $info['total_tax'];
        $orders['jshj']             = $info['jshj'];
        $orders['title']            = $info['title'];
        $orders['invoice_number']   = $info['invoice_number'];
        $orders['invoice_code']     = $info['invoice_code'];
        $orders['mobile']           = $info['buyer_phone'];
        $orders['order_id']         = $info['order_id'];
        $orders['create_time']      = $info['create_time'];
        $orders['serial_num']       = strtotime(date('Y-m-d H:i:s')).mt_rand(100000,999999);

        //设置不同税率开票金额
        $detail = $this->judgeInvTax($info);
        $result = $this->dzfp->fpkj($orders, $detail);
        if(!$result) {
            $this->shzfInvModel->update($info['id'], ['state_message' => $this->dzfp->getError(), 'state' => self::INVOICE_FAIL]);
            return false;
        }
        $this->shzfInvModel->update($info['id'], ['state' => self::RED_INVOICE_SUCCESS, 'state_message' => '红字发票开具成功']);
        return true;
    }

    /**
     * @param $info
     * @return array
     * @explain 设置红票税率
     */
    private function judgeInvTax($info)
    {
        $data = [];
        if ($info['one_fee'] != '0.00') {
            $data[] = [
                'title' => '红字发票',
                'num'   => 1,
                'price' => $info['one_fee'],
                'xmje'  => $info['one_fee'],
                'sl'    => '0.00',
                'se'    => '0'
            ];
        }

        if ($info['two_fee'] != '0.00') {
            $data[] = [
                'title' => '红字发票',
                'num'   => 1,
                'price' => $info['two_fee'] - $info['two_tax'],
                'xmje'  => $info['two_fee'] - $info['two_tax'],
                'sl'    => '0.06',
                'se'    => $info['two_tax']
            ];
        }
        if ($info['three_fee'] != '0.00') {
            $data[] = [
                'title' => '红字发票',
                'num'   => 1,
                'price' => $info['three_fee'] - $info['three_tax'],
                'xmje'  => $info['three_fee'] - $info['three_tax'],
                'sl'    => '0.17',
                'se'    => $info['three_tax']
            ];
        }

        return $data;
    }

    /**
     * @param $order
     * @return mixed
     * @desc 重新计算删除个别sku后的合计税额,合计金额
     */
    private function regroupSku($order){
        $hjse = '';
        $payment_fee = '';
        $one_tax = 0.00;    //税率为0.00的税额
        $two_tax = 0.00;    //税率为0.06的税额
        $three_tax = 0.00;    //税率为0.17的税额
        $one_fee = 0.00;    //税率为0.17的金额
        $two_fee = 0.00;    //税率为0.06的金额
        $three_fee = 0.00;    //税率为0.17的金额
        foreach ($order['order_detail'] as &$d_val){
            $hjse += $d_val['se'];
            $payment_fee += $d_val['pay_price'];
            if ($d_val['sl'] == '0.00') {
                $one_fee += $d_val['pay_price'];
                $one_tax += $d_val['se'];
            } elseif ($d_val['sl'] == '0.06') {
                $two_tax += $d_val['se'];
                $two_fee += $d_val['pay_price'];
            } else {
                $three_tax += $d_val['se'];
                $three_fee += $d_val['pay_price'];
            }
        }
        $order['hjse'] = $hjse;
        $order['jshj'] = $payment_fee;
        $order['hjje'] = $payment_fee - $hjse;
        $order['one_tax'] = $one_tax;
        $order['two_tax'] = $two_tax;
        $order['three_tax'] = $three_tax;
        $order['one_fee'] = $one_fee;
        $order['two_fee'] = $two_fee;
        $order['three_fee'] = $three_fee;

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
            'WAIT_BUYER_PAY' => '等待买家付款',
            'WAIT_SELLER_SEND_GOODS' => '买家已付款',
            'WAIT_BUYER_CONFIRM_GOODS' => '卖家已发货',
            'TRADE_BUYER_SIGNED' => '买家已签收',
            'TRADE_CLOSED' => '交易自动关闭',
            'TRADE_CLOSED_BY_USER' => '主动关闭交易'

        ];
        if ($state !== 'TRADE_BUYER_SIGNED'){
            $this->shzfInvModel->update($id, array('state_message' => '订单状态不符:'.$orderState[$state], 'state' => 3));
            return false;
        }
        return true;
    }

    /**
     * @param $order_id
     * @return array|bool
     */
    private function orderInfo($order_id)
    {
        $result = $this->orderModel->orderDetail($order_id);
        if (!$result) {
            $this->error_msg = '订单查询失败';
            return false;
        }
        return $this->format_order($result);
    }

    /**
     * @param $order
     * @return array
     */
    private function format_order($order)
    {
        $params = [
            'order_id'          => $order['order_id'],
            'discount_fee'      => $order['discount_fee'],
            'payment_fee'       => $order['payment_fee'],
            'create_time'       => $order['create_time'],
            'state'             => $order['state'],
            'type'              => $order['type']
        ];
        $params['order_detail'] = $this->format_batch_detail($order['order_detail']);

        return $params;
    }

    /**
     * @param $detail
     * @return array
     */
    private function format_batch_detail($detail)
    {
        $info = [];
        foreach ($detail as $val) {
            $item = [
                'sku_id'        => $val['sku_id'],
                'outer_sku_id'  => $val['outer_sku_id'],
                'title'         => $val['title'],
                'pay_price'     => $val['pay_price'],
                'num'           => $val['num']
            ];
            $info[] = $item;
        }

        return $info;
    }
    /**
     * @param $order
     * @return mixed
     */
    private function batchDetail($order){
        $sku_id = [];
        foreach ($order['order_detail']  as &$o_val){
            if ($o_val['outer_sku_id']) {
                $sku_id[] = '\''.$o_val['outer_sku_id'].'\'';
            }
            $order['sum_price'] += $o_val['pay_price'];
        }
        //销毁原详情
        $order['skus'] = $sku_id;
        return $order;
    }

    /**
     * @param $order
     * @param $skuRate
     * @desc 处理sku数据
     */
    public function treatingSku($order, $skuRate){
        foreach ($order['order_detail'] as &$d_val){
            $price = round($d_val['pay_price'] / $d_val['num'], 2); //价格(含税)
            $d_val['sl'] = $skuRate[$d_val['outer_sku_id']];
            $d_val['se'] = round($price - ($price / (1 + $d_val['sl'])),2); //税额 等于支付金额 减去支付金额除1+税率
            $d_val['xmje'] = $price - $d_val['se']; //支付金额 - 税额 = 项目金额
            $d_val['price'] = $d_val['xmje'];
            $order['hjse'] += $d_val['se'];
            $order['jshj'] += $price;
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
        $orders['xsf_mc']       = $value['seller_name'];
        $orders['xsf_dzdh']     = $value['seller_address'];
        $orders['kpr']          = $value['drawer'];
        $orders['type']         = $value['type'];
        $orders['title']        = $value['title'];
        $orders['count']        = count($orders['order_detail']);
        $orders['mobile']       = $value['buyer_phone'];
        $orders['payee']        = $value['payee'];
        $orders['review']       = $value['review'];
        $orders['serial_num']   = strtotime(date('Y-m-d H:i:s')).mt_rand(100000,999999);
        //开发票
        $result = $this->dzfp->fpkj($orders, $orders['order_detail']);
        if(!$result){
            $file_data = [
                'state' => self::INVOICE_FAIL,
                'state_message' => $this->dzfp->getError()
            ];
            $this->shzfInvModel->update($value['id'], $file_data);
            return false;
        }
        $params = $this->setParameter($orders, $result);
        $this->shzfInvModel->update($value['id'], $params);
    }

    /**
     * @desc 统一设置参数
     * @param array $order
     * @param $result
     * @return array
     */
    public function setParameter(array $order, $result){
        $params = array();
        $params['serial_number']    = $order['serial_num'];
        $params['type']             = $order['type'];
        $params['invoice_code']     = $result['FPDM'];
        $params['invoice_number']   = $result['FPHM'];
        $params['check_code']       = $result['JYM'];
        $params['state']            = self::INVOICE_SUCCESS;
        $params['state_message']    = $result['DESC'];
        $params['seller_name']      = $order['xsf_mc'];
        $params['seller_address']   = $order['xsf_dzdh'];
        $params['drawer']           = $order['kpr'];
        $params['jshj']             = $order['jshj'];
        $params['total_tax']        = $order['hjse'];
        $params['total_fee']        = $order['hjje'];
        $params['create_time']      = $result['KPRQ'];
        $params['success_time']     = $result['KPRQ'];
        $params['invoice_no']       = $order['invoice_no'];
        $params['one_tax']          = $order['one_tax'];
        $params['two_tax']          = $order['two_tax'];
        $params['three_tax']        = $order['three_tax'];
        $params['one_fee']          = $order['one_fee'];
        $params['two_fee']          = $order['two_fee'];
        $params['three_fee']        = $order['three_fee'];

        return $params;
    }

    /**
     * @return mixed
     * @desc 获取错误
     */
    public function getError(){
        return $this->error_msg;
    }
}