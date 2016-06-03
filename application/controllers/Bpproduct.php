<?php

/**
 * 商品
 * 
 * @author yanbo
 */
class BpProductController extends Base {

    use Trait_Layout,
        Trait_Pagger;

    /**
     * @var BpProductsModel
     */
    public $product_model;

    public function init() {
        $this->initAdmin();

        $this->product_model = new BpProductsModel();
    }

    function indexAction() {
        $this->checkRole();
        $p = (int) $this->getRequest()->get('p' ,1);
        $name = $this->getRequest()->get('pname');
        $type = $this->getRequest()->get('type');
        $showcase_id = $this->getRequest()->get('showcase_id');
        
        $pname = $name ? $name : '';
        $t = $type ? $type : '';
        
        $page_size = 20;
        $product_list = [
            'page_size' => $page_size,
            'page_no' => $p,
            'kw' => $pname,
            'type' => $t,
            'showcase_id' => $showcase_id
        ];
        
        $productsList = $this->product_model->getList($product_list);
        $list = $productsList['products'];
        $count = $productsList['total_nums'];
        
        $this->renderPagger($p, $count, "/bpproduct/index/p/{p}?t={$pname}&t={$t}&showcase_id={$showcase_id}", $page_size);
        $this->assign('pname', $pname);
        $this->assign("list", $list);
        $this->assign('showcase_id', $showcase_id);
        $this->layout("platform/product.phtml");
    }

    function infoAction() {
        $this->checkRole();

        $product_id = $this->getrequest()->get('id');
        $info = $this->product_model->getInfoById($product_id);

        $this->assign("pinfo", $info);
        $this->layout("platform/product_info.phtml");
    }

}
