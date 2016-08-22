<?php

/**
 * ProductController
 * @author yanbo
 */
class ProductController extends Storebase {

    private $views;
    private $product_type = array(
        '0' => '实物',
        '1' => '卡券',
        '2' => '会员',
    );
    private $product_onsell = array(
        '1' => '上架',
        '2' => '下架',
    );

    public function init() {
        parent::init();
    }

    function indexAction() {
        $this->setShowcaseList();
        $showcase_id = $this->input_get_param('showcase_id');
        $type = $this->input_get_param('type');
        $name = $this->input_get_param('pname');
        $page_no = $this->input_get_param('page_no');

        $pname = $name ? $name : '';
        
        $page_size = 20;
        $params = [
            'kw' => $pname,
            'type' => $type,
            'showcase_id' => $showcase_id,
            'page_no' => $page_no,
            'page_size' => $page_size
        ];
        $result = $this->store_model->productList($params);
        $data = $this->format_data_batch($result);

        $this->renderPagger($page_no, $data['total_nums'], "/store/product/index?page_no={p}&pname={$pname}&type={$type}&showcase_id={$showcase_id}", $page_size);

        $this->assign('type', $type);
        $this->assign('pname', $pname);
        $this->assign("list", $data['products']);
        $this->assign('showcase_id', $showcase_id);
        $this->layout("product/showlist.phtml");
    }

    function infoAction() {
        $product_id = $this->input_get_param('id');
        $result = $this->store_model->productDetail($product_id);
        $detail = $this->format_data_struct($result);

        $this->assign("pinfo", $detail);
        $this->layout("product/detail.phtml");
    }

    /*
     * 格式化数据
     */

    public function tidy($product) {
        $alias = $product['product_alias'];
        $p['product_id'] = $product['product_id'];
        $p['product_alias'] = $product['product_alias'];
        $p['showcase_id'] = $product['showcase_id'];
        $p['showcase_name'] = $this->showcase_list[$product['showcase_id']];
        $p['outer_id'] = $product['outer_id'];
        $p['title'] = $product['title'];
        $p['intro'] = $product['intro'];
        $p['pv']    = isset($this->views[$alias]['pv']) ? $this->views[$alias]['pv'] : 0;
        $p['uv']    = isset($this->views[$alias]['uv']) ? $this->views[$alias]['uv'] : 0;
        $p['sold_num'] = $product['sold_num'];
        $p['quantity'] = $product['quantity'];
        $p['price'] = $product['price'];
        $p['post_fee'] = $product['post_fee'];
        $p['pic_path'] = $product['pic_path'];
        $p['item_imgs'] = $product['item_imgs'];
        $p['buy_quota'] = $product['buy_quota'];
        $p['buy_quota_name'] = ($product['buy_quota'] == 0) ? "无限制" : $product['buy_quota'];
        $p['onsell_name'] = $this->product_onsell[$product['onsell']];
        $p['onsell'] = $product['onsell'];
        $p['sellout'] = $product['sellout'];
        $p['presell'] = $product['presell']; //预售状态
        $p['type'] = $product['type'];
        $p['type_name'] = $this->product_type[$product['type']];
        $p['create_time'] = $product['create_time'];
        $p['update_time'] = $product['update_time'];
        $p['skus'] = $product['skus'];
        return $p;
    }

    public function format_data_struct($data) {
        if ($data === false) {
            return false;
        }
        if (empty($data)) {
            return array();
        }

        return $this->tidy($data);
    }

    public function format_data_batch($datas) {
        if ($datas === false) {
            return false;
        }
        if (empty($datas)) {
            return array();
        }
        $this->_set_views($datas['products']);

        foreach ($datas['products'] as &$data) {
            $data = $this->tidy($data);
        }
        return $datas;
    }

    /**
     * 批量设置页面浏览信息
     */
    public function _set_views($products){
        $pageids = [];
        foreach ($products as &$product){
            $pageids[] = $product['product_alias'];
        }
        $pageids = implode(',', $pageids);

        $datasum_model = new DatasumModel();
        $this->views = $datasum_model->pageview_bacth($datasum_model::PAGE_TYPE_PRODUCT, $pageids);
    }
}
