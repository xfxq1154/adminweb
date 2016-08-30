<?php

/**
 * ProductController
 * @author yanbo
 */
class ProductController extends Statbase {

    /** @var  StatProductModel */
    private $product_model;
    /** @var  StatPageModel */
    private $page_model;

    public function init() {
        parent::init();

        $this->product_model = new StatProductModel();
        $this->page_model = new StatPageModel();
    }

    public function dashboardAction(){

        $params['showcase_id'] = $this->showcase_id;
        $params['orderby'] = 'total_pay';
        $params['start_created'] = $this->start_created;
        $params['end_created'] = Tools::format_date($this->end_created);
        $params['page_size'] = 10;
        $sell_top10 = $this->product_model->productList($params);
        if ($sell_top10){
            foreach ($sell_top10['list'] as $item){
                $ordertop[] = $item['trans_num'];
                $paytop[] = $item['trans_amount'];
            }
        }

        $params['showcase_id'] = $this->showcase_id;
        $params['type'] = 'product';
        $params['orderby'] = 'total_pv';
        $params['start_created'] = $this->start_created;
        $params['end_created'] = Tools::format_date($this->end_created);
        $params['page_size'] = 10;
        $productTop10 = $this->page_model->ranklist($params);
        if ($productTop10){
            $total_pv = 0;
            foreach ($productTop10['list'] as $product){
                $toppv[] = $product['total_pv'];
                $topuv[] = $product['total_uv'];

                $total_pv += $product['total_pv'];
            }
        }

        $this->assign('top10', $productTop10['list']);
        $this->assign('total_pv', $total_pv);
        $this->assign('toppv', $toppv);
        $this->assign('topuv', $topuv);

        $this->assign('sell_top10', $sell_top10['list']);
        $this->assign('ordertop', $ordertop);
        $this->assign('paytop', $paytop);

        $this->_display('product/dashboard.phtml');
    }

    public function detailAction(){
        $page_no  = $this->input_get_param('page_no', 1);

        $params['showcase_id'] = $this->showcase_id;
        $params['orderby'] = 'total_pv';
        $params['start_created'] = $this->start_created;
        $params['end_created'] = Tools::format_date($this->end_created);
        $params['page_no'] = $page_no;
        $params['page_size'] = 20;

        $result = $this->product_model->productList($params);

        $this->assign("list", $result['list']);
        $this->assign("search", $this->input_get());

        $query_string = http_build_query($this->input_get());
        $this->renderPagger($page_no, $result['total_nums'], "/stat/product/detail?$query_string&page_no={p}", $params['page_size']);

        $this->_display('product/detail.phtml');
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
