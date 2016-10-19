<?php
/**
 * @name: Pay.php
 * @time: 2016-10-13 下午15:00
 * @author: wzd
 * @desc:支付平台接口调用
 */

class PayModel {

    const ORDER_TRADE  = 'trades/orders/query/';  //支付订单信息查询

    public static $transaction_category = [ 'PAYMENT' => '支付交易', 'REFUND' => '退款交易'];
    public static $order_status = [
        'INIT' => '初始状态',
        'WAIT_BUYER_PAY' => '初始状态',
        'PAID' => '已支付',
        'PARTIAL_REFUNDED' => '部分退款',
        'REFUNDED' => '已退款',
        'CLOSED' => '已关闭',
    ];

    /**
     * 获取支付订单信息
     * @param array $params
     * @return array|bool
     */
    public function getOrderTrades($order_id) {
        $url = self::ORDER_TRADE.$order_id.'/';
        $result = Pay::request($url);

        return $result;
    }

    /**
     * 返回交易种类列表
     * @return array
     */
    public static function getTransctionCategory(){
        return self::$transaction_category;
    }

    /**
     * 返回订单状态列表
     * @return array
     */
    public static function getOrderStatus(){
        return self::$order_status;
    }

}