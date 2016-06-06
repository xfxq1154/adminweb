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
    public $showcase;

    public function init() {
        $this->initAdmin();

        $this->product_model = new BpProductsModel();
        $this->showcase = new BpShowcaseModel();
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

        $showlist = $this->showcase->getList(array('page_no'=>1,'page_size'=>100, 'block'=>0));
        foreach ($showlist['showcases'] as $key=>$val){
            $idlist[$key]['id'] = $val['showcase_id'];
            $idlist[$key]['name'] = $val['name'];
            $showcase[$val['showcase_id']] = $val['name'];
        }

        $this->renderPagger($p, $count, "/bpproduct/index/p/{p}?pname={$pname}&type={$t}&showcase_id={$showcase_id}", $page_size);

        $this->assign('pname', $pname);
        $this->assign("list", $list);
        $this->assign('showcase_id', $showcase_id);
        $this->assign('idlist', $idlist);
        $this->assign('name', $showcase);
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
