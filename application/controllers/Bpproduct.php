<?php

/**
 * 商品
 * 
 * @author yanbo
 */
class BpProductController extends Base {

    use Trait_Layout,
        Trait_Pagger;

    public $product;

    public function init() {
        $this->initAdmin();
        $this->product = new BpProductsModel();
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
        
        $productsList = $this->product->getList($product_list);
        $list = $productsList['products'];
        $count = $productsList['total_nums'];
        
        $this->renderPagger($p, $count, "/bpproduct/index/p/{p}?t={$pname}&t={$t}&showcase_id={$showcase_id}", $page_size);
        $this->assign('pname', $pname);
        $this->assign("list", $list);
        $this->assign('showcase_id', $showcase_id);
        $this->layout("platform/product.phtml");
    }

    function editAction() {
        $this->checkRole();

        if (!$this->getrequest()->isPost()) {
            $product_id = $this->getrequest()->get('id');
            $product = $this->product->getInfoById($product_id);

            $this->assign("product", $product);
            $this->layout('platform/product_edit.phtml');
        } else {

            $params = $this->getrequest()->getPost();

            $result = $this->product->update($params);
            $msg = ($result === "") ? "修改成功" : "修改失败";
            $status = ($result === "") ? 1 : 0;
            echo json_encode(['info' => $msg, 'status' => $status]);
            exit;
        }
    }

    function deleteAction() {
        $this->checkRole();

//        $product_id = json_decode($this->getRequest()->getPost('data'), true)['id'];
//        $showcase_id = json_decode($this->getRequest()->getPost('data'), true)['sid'];
//        $result = $this->product->delete($product_id,$showcase_id);
        $msg = ($result === "") ? "删除成功" : "删除失败";
        $status = ($result === "") ? 1 : 0;
        echo json_encode(['info' => $msg, 'status' => $status]);
        exit;
    }

    function infoAction() {
        $this->checkRole();

        $product_id = $this->getrequest()->get('id');
        $info = $this->product->getInfoById($product_id);

        $this->assign("pinfo", $info);
        $this->layout("platform/product_info.phtml");
    }

}
