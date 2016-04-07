<?php
/**
 * @author why
 * @desc 有赞订单
 */
class YouZanOrderModel{
    
    use Trait_DB;
    
    public $dbMaster;
    public $dbSlave;
    
    public $tableName = 'y_youzan_trades';
    public $tableName2 = 'y_youzan_order';
    
    public function __construct() {
        $this->dbMaster = $this->getMasterDb('youzan_order');
        $this->dbSlave = $this->getSlaveDb('youzan_order');
    }

    /**
     * 订单详情
     */
    public function getInfo($order_id){
        $where = '';
        $pdo_params = [];
        $where .= ' y_tid = :order_id';
        $pdo_params[':order_id'] = $order_id;
        try {
            $sql = " SELECT * FROM " .$this->tableName . ' WHERE' . $where . ' LIMIT 1';
            $stmt = $this->dbSlave->prepare($sql);
            $stmt->execute($pdo_params);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $ex) {
            echo $ex->getMessage();
        }
    }
    
    /**
     * 修改订单号
     */
    public function update($id,$oid){
        try {
            $sql = " UPDATE " .$this->tableName. " SET `y_tid` = '$oid' WHERE y_id = $id ";
            $stmt = $this->dbMaster->prepare($sql);
            $stmt->execute();
        } catch (Exception $ex) {
            echo $ex->getMessage();
        }
    }
    
    /**
     * 获取订单(测试)
     */
    public function getlist(){
        $where = " ta.`y_status` = 'TRADE_BUYER_SIGNED' " ;
        try {
            $sql = " SELECT * FROM ".$this->tableName. ' ta LEFT JOIN '.$this->tableName2.' tb ON ta.y_tid = tb.o_trades_id  WHERE '.$where .' ORDER BY ta.y_id ASC  LIMIT 800,30';
            $stmt = $this->dbSlave->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $ex) {
            echo $ex->getMessage();
        }
    }
    
    /**
     * 格式化订单信息
     */
    public function struct_order_data($order) {
        //格式化基本信息
        $o = $this->tidyOrderInfo($order);
        //格式化订单详情
        $o = $this->struct_orderdetail_batch($o);
        return $o;
    }
    
    /*
     * 格式化订单基本信息
     */
    public function tidyOrderInfo($order){
        if (empty($order)) {
            return array();
        }

        $o['id'] = $order['y_id']; //id
        $o['price'] = floatval($order['y_price']);  //商品价格。精确到2位小数；单位：元。当一个trade对应多个order的时候，值为第一个交易明细中的商品的价格
        $o['title'] = $order['y_title'];  //交易标题，以首个商品标题作为此标题的值
//        $o['type'] = $order['y_type'];  //交易类型。取值范围：FIXED （一口价）GIFT （送礼）BULK_PURCHASE（来自分销商的采购）PRESENT （赠品领取）COD （货到付款）QRCODE（扫码商家二维码直接支付的交易）
        $o['discount_fee'] = $order['y_discount_fee'];  //交易优惠金额（不包含交易明细中的优惠金额）。单位：元，精确到分
        $o['status'] = $order['y_status'];  //交易状态 TRADE_NO_CREATE_PAY(没有创建) WAIT_BUYER_PAY (待付款) WAIT_PAY_RETURN (等待支付确认) WAIT_SELLER_SEND_GOODS (买家已付款) WAIT_BUYER_CONFIRM_GOODS(待收货) TRADE_BUYER_SIGNED (已签收) TRADE_CLOSED (用户退款成功，交易关闭) TRADE_CLOSED_BY_USER (付款以前交易关闭）
        $o['refund_state'] = $order['y_refund_state'];  //退款状态
        $o['total_fee'] = $order['y_total_fee'];  //商品总价（商品价格乘以数量的总金额）。单位：元，精确到分
        $o['refunded_fee'] = $order['y_refunded_fee'];  //交易完成后退款的金额。单位：元，精确到分
        $o['payment'] = floatval($order['y_payment']);  //实付金额。单位：元，精确到分
        $o['pay_time'] = $order['y_pay_time'];  //买家付款时间
        $o['pay_type'] = $order['y_pay_type'];  //支付类型WEIXIN (微信)ALIPAY (支付宝)BANKCARDPAY (银行卡)PEERPAY (代付)CODPAY (货到付款)BAIDUPAY (百度钱包)PRESENTTAKE (直接领取赠品)COUPONPAY（优惠券）BULKPURCHASE（来自分销商的采购）ECARD（有赞E卡通支付
        $o['consign_time'] = $order['y_consign_time'];  //卖家发货时间
        $o['sign_time'] = $order['y_sign_time'];  //买家签收时间
        $o['buyer_area'] = $order['y_buyer_area'];  //买家下单的地区
        $o['buyer_message'] = $order['y_buyer_message'];  //买家购买附言
        $o['adjust_fee'] = $order['y_adjust_fee'];  //卖家手工调整订单金额
        $o['tid'] = $order['y_tid'];  //交易编号
        $o['receiver_city'] = $order['y_receiver_city'];  //收货人的所在城市
        $o['receiver_district'] = $order['y_receiver_district'];  //收货人的所在地区
        $o['receiver_name'] = trim($order['y_receiver_name']);  //收货人的姓名
        $o['receiver_state'] = $order['y_receiver_state'];  //收货人的所在省份
        $o['receiver_address'] = $order['y_receiver_address'];  //收货人的详细地址
//        $o['receiver_mobile'] = $order['y_receiver_mobile'];  //收货人的手机号码
        $o['created'] = $order['y_created'];  //交易创建时间
        $o['update_time'] = $order['y_update_time'];  //交易更新时间。当交易的：状态改变、备注更改、星标更改 等情况下都会刷新更新时间
        $o['order_detail'] = $order['order_detail'];
        return $o;
    }
    
    /**
     * 批量格式化订单信息
     */
    public function struct_orderdetail_batch($datas) {
        if (empty($datas)) {
            return array();
        }

        foreach ($datas['order_detail'] as &$val) {
            $val = $this->struct_orderdetail_data($val);
        }
        return $datas;
    }
    
    /**
     * 格式化订单详情信息
     */
    public function struct_orderdetail_data($order_detail) {
        if (empty($order_detail)) {
            return array();
        }
        
        $data['oid'] = intval($order_detail['o_oid']);  //交易明细编号。该编号并不唯一，只用于区分交易内的多条明细记录
        $data['outer_sku_id'] = $order_detail['o_outer_sku_id'];  //商家编码（商家为Sku设置的外部编号）
        $data['outer_item_id'] = $order_detail['o_outer_item_id'];  //商品货号（商家为商品设置的外部编号）
        $data['title'] = $order_detail['o_title'];  //商品标题
        $data['price'] = floatval($order_detail['o_price']);  //商品价格。精确到2位小数；单位：元
        $data['total_fee'] = floatval($order_detail['o_total_fee']);  //应付金额（商品价格乘以数量的总金额）
        $data['payment'] = $order_detail['o_payment'] ; //实付金额。精确到2位小数，单位：元
        $data['sku_unique_code'] = $order_detail['o_sku_unique_code'];  //Sku在系统中的唯一编号，可以在开发者的系统中用作 Sku 的唯一ID，但不能用于调用接口
        $data['state_str'] = $order_detail['o_state_str'];  //商品状态
        $data['item_refund_state'] = $order_detail['o_item_refund_state'];  //商品退款状态
        $data['num_iid'] = $order_detail['o_num_iid'];  //商品数字编号
        $data['num'] = intval($order_detail['o_num']);  //商品购买数量
        $data['is_send'] = $order_detail['o_is_send'];  //交易明细编号
        $data['trades_id'] = $order_detail['o_trades_id'];  //交易明细编号
        $data['youzan_trades_id'] = $order_detail['o_youzan_trades_id'];  //youzan_trades 表id
        return $data;
    }
}

