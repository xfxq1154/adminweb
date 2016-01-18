<?php

/**
 * @name ProductsModel
 * @desc 商品操作类
 */
class BpProductsModel {

    use Trait_Api;
    
    const PRODUCT_LIST = 'product/getlist';
    const PRODUCT_DETAIL = 'product/detail';
    const PRODUCT_UPDATE = 'product/update';
    const PRODUCT_DELETE = 'product/delete';

    private $product_type = array(
        '0' => '实物',
        '1' => '卡券',
        '2' => '会员',
    );
    private $product_onsell = array(
        '1' => '上架',
        '2' => '下架',
    );
    private $_error = null;

    public function __construct() {
        
    }

    public function getList($params) {
        $result = $this->request(self::PRODUCT_LIST, $params);
        return $this->format_order_batch($result);
    }

    public function getInfoById($product_id) {
        if (!$product_id) {
            //return false;
        }
        $params['product_id'] = $product_id;
        $result = $this->request(self::PRODUCT_DETAIL, $params);
        return $this->format_order_struct($result);
    }

    public function update($params) {
        return $this->request(self::PRODUCT_UPDATE, $params, "POST");
    }

    public function delete($product_id,$showcase_id) {
        $params['product_id'] = $product_id;
        $params['showcase_id'] = $showcase_id;
        return $this->request(self::PRODUCT_DELETE, $params, "POST");
    }

    /*
     * 格式化数据
     */

    public function tidy($product) {
        $p['product_id'] = $product['product_id'];
        $p['showcase_id'] = $product['showcase_id'];
        $p['outer_id'] = $product['outer_id'];
        $p['title'] = $product['title'];
        $p['intro'] = $product['intro'];
        $p['description'] = $product['description'];
        $p['sold_num'] = $product['sold_num'];
        $p['quantity'] = $product['quantity'];
        $p['price'] = $product['price'];
        $p['post_fee'] = $product['post_fee'];
        $p['pic_path'] = $product['pic_path'];
        $p['item_imgs'] = $product['item_imgs'];
        $p['buy_quota'] = $product['buy_quota'];
        $p['buy_quota_name'] = ($product[buy_quota] == 0) ? "无限制" : $product['buy_quota'];
        $p['onsell_name'] = $this->product_onsell[$product['onsell']];
        $p['onsell'] = $product['onsell'];
        $p['presell'] = $product['presell']; //预售状态
        $p['type'] = $product['type'];
        $p['type_name'] = $this->product_type[$product['type']];
        $p['create_time'] = $product['create_time'];
        $p['update_time'] = $product['update_time'];
        $p['skus'] = $product['skus'];
        return $p;
    }

    public function format_order_struct($data) {
        if ($data === false) {
            return false;
        }
        if (empty($data)) {
            return array();
        }

        return $this->tidy($data);
    }

    public function format_order_batch($datas) {
        if ($datas === false) {
            return false;
        }
        if (empty($datas)) {
            return array();
        }
        foreach ($datas['products'] as &$data) {
            $data = $this->tidy($data);
        }
        return $datas;
    }

    private function request($uri, $params = array(), $requestMethod = 'GET', $jsonDecode = true, $headers = array(), $timeout = 10) {

        $sapi = $this->getApi('sapi');

        $params['sourceid'] = Yaf_Application::app()->getConfig()->api->sapi->source_id;
        $params['timestamp'] = time();

        $result = $sapi->request($uri, $params, $requestMethod);

        if (isset($result['status_code']) && $result['status_code'] == 0) {
            return isset($result['data']) ? $result['data'] : array();
        } else {
            return false;
        }
    }

}
