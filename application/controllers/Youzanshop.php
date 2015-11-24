<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class YouZanShopController extends Base{
    
    use Trait_Layout,
        Trait_Pagger;
    
    public $commodity,$sku,$tag,$img,$orders,$address;
    public $shop;
    
    public function init(){
        $this->initAdmin();
        $this->sku = new YouZanSkuModel();
        $this->commodity = new CommdityModel();
        $this->shop = new YouZanShopModel();
        $this->tag = new YouZanTagModel();
        $this->img = new YouZanImgsModel();
        $this->orders = new YouZanOrderAllModel();
        $this->address = new YouZanAddressModel();
    }
    /*
     * 商品
     */
    public function indexAction(){
        if($this->getRequest()->isPost()){
            $rs = $this->commodity->getData();
            //导出excel
            header("Content-type:application/vnd.ms-excel");
            header("Content-Type:text/html; charset=gbk");
            header("Content-Disposition:attachment;filename=commodity.xls");
            header('Pragma:no-cache');
            header('Expires:0');
            $title = array('ID','商品别称','商品标题','商品分类的叶子类目id','商品推广栏目id','商品标签id串','商品描述','显示在“原价”一栏中的信息','商品货号','商品外部购买链接',
                            '限购','商品的发布时间','是否为虚拟商品','商品上架状态','商品是否锁定','是否为二手商品','商品定时上架','适合wap应用的商品url','分享出去的商品详情url',
                            '商品主图片地址','商品主图片','商品数量','商品销售量','商品价格','运费类型','是否是供货商商品');
            echo iconv('utf-8', 'gbk', implode("\t", $title)),"\n";
            exit;
        }
        $where = '';
        
        $kw = $this->getRequest()->getParam('t', '');
        if(empty($kw)){
            $kw = $_REQUEST['keyword'];
        }
        $kw = urldecode($kw);
        $size = 20;
        $p = (int) $this->getRequest()->getParam('p', 1);
        $count = $this->commodity->getCountCommdity();
        $this->renderPagger($p, $count, "/youzanshop/index/p/{p}/t/{$kw}", $size);
        $rs = $this->commodity->getCommdity($p, $size, $kw);
        $this->assign('data', $rs);
        $this->assign('keyword', $kw);
        $this->layout('youzan/commdity.phtml');
    }
    
    
    /*
     * 商品sku
     */
    public function skuAction(){
        
        $size = 40;
        $p = (int) $this->getRequest()->getParam('p',1);
        $count  = $this->sku->getCount();
        $this->renderPagger($p, $count, '/youzanshop/sku/p/{p}', $size);
        $rs = $this->sku->getData($p,$size,$kw);
        $this->assign('data', $rs);
        $this->layout('youzan/youZanSku.phtml');
    }
    
    /*
     * 商品标签
     */
    
    public function tagAction(){
        
        $size = 40;
        $p = (int) $this->getRequest()->getParam('p',1);
        $count = $this->tag->getCount();
        $this->renderPagger($p, $count, '/youzanshop/tag/p/{p}', $size);
        $rs = $this->tag->getData($p, $size,$kw);
        $this->assign('data', $rs);
        $this->layout('youzan/youZanTag.phtml');
    }
    
    /*
     * 商品图片
     */
    
    public function imgAction(){
        
        $size = 40;
        $p = (int) $this->getRequest()->getParam('p',1);
        $count = $this->img->getCount();
        $this->renderPagger($p, $count, '/youzanshop/img/p/{p}', $size);
        $rs = $this->img->getData($p, $size,$kw);
        $this->assign('data', $rs);
        $this->layout('youzan/youZanImg.phtml');
    }
    
    /*
     * 订单数据
     */
    
    public function ordersAction(){
        
        $size = 40;
        $p = (int) $this->getRequest()->getParam('p',1);
        $count = $this->orders->getCount();
        $this->renderPagger($p, $count,'/youzanshop/orders/p/{p}', $size);
        $rs = $this->orders->getData($p, $size,$kw);
        $this->assign('data', $rs);
        $this->layout('youzan/youZanOrders.phtml');
    }
    
    /*
     * 地址信息
     */
    public function addressAction(){
        
        $size = 40;
        $p = (int) $this->getRequest()->getParam('p',1);
        $count = $this->address->getCount();
        $this->renderPagger($p, $count, '/youzanshop/address/p/{p}', $size);
        $rs = $this->address->getData($p,$size,$kw);
        $this->assign('data', $rs);
        $this->layout('youzan/address.phtml');
    }
}