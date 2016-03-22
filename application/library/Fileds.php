<?php
/**
 *@desc 公共变量
 */
class Fileds{
    
    //电子发票
    public static $invoice = [
        '1' => 'barcode',
        '2' => 'project_name'
    ];
    
    public function order($order){
        $o['id'] = $order['y_id']; //id
        $o['num_iid'] = $order['y_num_iid'];  //商品购买数量
        $o['num'] = $order['y_num'];  //商品数字编号。当一个trade对应多个order的时候，值为第一个交易明细中的商品的编号
        $o['price'] = floatval($order['y_price']);  //商品价格。精确到2位小数；单位：元。当一个trade对应多个order的时候，值为第一个交易明细中的商品的价格
        $o['title'] = $order['y_title'];  //交易标题，以首个商品标题作为此标题的值
        $o['type'] = $order['y_type'];  //交易类型。取值范围：FIXED （一口价）GIFT （送礼）BULK_PURCHASE（来自分销商的采购）PRESENT （赠品领取）COD （货到付款）QRCODE（扫码商家二维码直接支付的交易） 
        $o['discount_fee'] = $order['y_discount_fee'];  //交易优惠金额（不包含交易明细中的优惠金额）。单位：元，精确到分
        $o['status'] = $order['y_status'];  //交易状态 TRADE_NO_CREATE_PAY(没有创建) WAIT_BUYER_PAY (待付款) WAIT_PAY_RETURN (等待支付确认) WAIT_SELLER_SEND_GOODS (买家已付款) WAIT_BUYER_CONFIRM_GOODS(待收货) TRADE_BUYER_SIGNED (已签收) TRADE_CLOSED (用户退款成功，交易关闭) TRADE_CLOSED_BY_USER (付款以前交易关闭）
        $o['refund_state'] = $order['y_refund_state'];  //退款状态
        $o['shipping_type'] = $order['y_shipping_type'];  //创建交易时的物流方式。取值范围：express（快递），fetch（到店自提）
        $o['post_fee'] = $order['y_post_fee'];  //运费。单位：元，精确到分
        $o['total_fee'] = $order['y_total_fee'];  //商品总价（商品价格乘以数量的总金额）。单位：元，精确到分
        $o['refunded_fee'] = $order['y_refunded_fee'];  //交易完成后退款的金额。单位：元，精确到分
        $o['payment'] = $order['y_payment'];  //实付金额。单位：元，精确到分
        $o['created'] = $order['y_created'];  //交易创建时间
        $o['update_time'] = $order['y_update_time'];  //交易更新时间。当交易的：状态改变、备注更改、星标更改 等情况下都会刷新更新时间
        $o['pay_time'] = $order['y_pay_time'];  //买家付款时间
        $o['pay_type'] = $order['y_pay_type'];  //支付类型WEIXIN (微信)ALIPAY (支付宝)BANKCARDPAY (银行卡)PEERPAY (代付)CODPAY (货到付款)BAIDUPAY (百度钱包)PRESENTTAKE (直接领取赠品)COUPONPAY（优惠券）BULKPURCHASE（来自分销商的采购）ECARD（有赞E卡通支付
        $o['consign_time'] = $order['y_consign_time'];  //卖家发货时间
        $o['sign_time'] = $order['y_sign_time'];  //买家签收时间
        $o['buyer_area'] = $order['y_buyer_area'];  //买家下单的地区
        $o['buyer_message'] = $order['y_buyer_message'];  //买家购买附言
        $o['seller_flag'] = $order['y_seller_flag'];  //卖家备注星标，取值范围 1、2、3、4、5；如果为0，表示没有备注星标
        $o['adjust_fee'] = $order['y_adjust_fee'];  //卖家手工调整订单金额
        $o['weixin_user_id'] = $order['y_weixin_user_id'];  //微信粉丝ID
        $o['trade_memo'] = trim($order['y_trade_memo']);  //卖家对该交易的备注
        $o['buy_way_str'] = $order['y_buy_way_str'];  //交易编号
        $o['pf_buy_way_str'] = $order['y_pf_buy_way_str'];  //交易编号
        $o['buyer_nick'] = trim($order['y_buyer_nick']);  //买家昵称
        $o['tid'] = $order['y_tid'];  //交易编号
        $o['buyer_type'] = $order['y_buyer_type'];  //买家类型，取值范围：0 为未知，1 为微信粉丝，2 为微博粉丝
        $o['buyer_id'] = $order['y_buyer_id'];  //买家ID，当 buyer_type 为 1 时，buyer_id 的值等于 weixin_user_id 的值
        $o['receiver_city'] = $order['y_receiver_city'];  //收货人的所在城市
        $o['receiver_district'] = $order['y_receiver_district'];  //	收货人的所在地区
        $o['receiver_name'] = trim($order['y_receiver_name']);  //收货人的姓名
        $o['receiver_state'] = $order['y_receiver_state'];  //收货人的所在省份
        $o['receiver_address'] = $order['y_receiver_address'];  //收货人的详细地址
        $o['receiver_zip'] = $order['y_receiver_zip'];  //收货人的邮编
        $o['receiver_mobile'] = $order['y_receiver_mobile'];  //收货人的手机号码
        $o['feedback'] = $order['y_feedback'];  //0 无维权，1 顾客发起维权，2 顾客拒绝商家的处理结果3 顾客接受商家的处理结果，9 商家正在处理,101 维权处理中,110 维权结束
        $o['outer_tid'] = $order['y_outer_tid'];  //外部交易编号
        $o['relation_type'] = $order['y_relation_type'];  //订单类型: source：采购单，fenxiao：分销单 空串为普通订单
        $o['relations'] = $order['y_relations'];  //订单类型为source时,为供应商订单号列表订单类型为fenxiao时,为分销伤订单号列表订单类型返回空时,列表返回空
    }
    
    public function order_detail($order){
        $data['o_oid'] = intval($order_detail['o_oid']);  //交易明细编号。该编号并不唯一，只用于区分交易内的多条明细记录
        $data['o_outer_sku_id'] = $order_detail['o_outer_sku_id'];  //商家编码（商家为Sku设置的外部编号）
        $data['o_outer_item_id'] = intval($order_detail['o_outer_item_id']);  //商品货号（商家为商品设置的外部编号）
        $data['o_title'] = $order_detail['o_title'];  //商品标题
        $data['o_price'] = floatval($order_detail['o_price']);  //商品价格。精确到2位小数；单位：元
        $data['o_total_fee'] = floatval($order_detail['o_total_fee']);  //应付金额（商品价格乘以数量的总金额）
        $data['o_payment'] = floatval($order_detail['o_payment']);  //实付金额。精确到2位小数，单位：元
        $data['o_sku_id'] = $order_detail['o_sku_id'];  //Sku的ID，sku_id 在系统里并不是唯一的，结合商品ID一起使用才是唯一的。
        $data['o_sku_unique_code'] = $order_detail['o_sku_unique_code'];  //Sku在系统中的唯一编号，可以在开发者的系统中用作 Sku 的唯一ID，但不能用于调用接口
        $data['o_sku_properties_name'] = $order_detail['o_sku_properties_name'];  //SKU的值，即：商品的规格。如：机身颜色:黑色;手机套餐:官方标配
        $data['o_item_type'] = $order_detail['o_item_type'];  //商品类型。0：普通商品；10：分销商品
        $data['o_state_str'] = $order_detail['o_state_str'];  //商品状态
        $data['o_item_refund_state'] = $order_detail['o_item_refund_state'];  //商品退款状态
        $data['o_num_iid'] = $order_detail['o_num_iid'];  //商品数字编号
        $data['o_num'] = intval($order_detail['o_num']);  //商品购买数量
        $data['o_is_send'] = $order_detail['o_is_send'];  //交易明细编号
        $data['o_trades_id'] = $order_detail['o_trades_id'];  //交易明细编号
        $data['o_youzan_trades_id'] = $order_detail['o_youzan_trades_id'];  //youzan_trades 表id
    }
}
