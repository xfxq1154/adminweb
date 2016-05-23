<?php
/**
 *@desc 公共变量
 */
class Fileds{
    
    //电子发票
    public static $invoice = [
        '1' => 'order_id',
        '2' => 'buyer_phone',
        '3' => 'project_name',
        '4' => 'invoice_title',
        '5' => 'buyer_tax_id',
        '6' => 'sku_type'
    ];

    //电子发票sku
    public static $sku = [
        '1' => 'sku_id',
        '2' => 'product_name',
        '3' => 'tax_tare'
    ];

    public static $order = [

        'E20160427120235027566012' => [
            '0' => [
                'title' => '《光荣与梦想》 传世经典  镇店之宝',
                'total_fee' => '6212',
                'sl' => '0.00',
                'se' => '0',
                'xmje' => '6212',
                'price' => '6212'
            ],
            '1' => [
                'title' => '《文艺复兴三杰》 你的私人美术馆^@^',
                'total_fee' => '347.17',
                'sl' => '0.06',
                'se' => '20.83',
                'xmje' => '347.17',
                'price' => '347.17'
            ]

        ]
    ];
    
}
