<?php

/**
 * 店铺操作记录
 *
 * @author yanbo
 */
class OplogsController extends Base {

    use Trait_Layout,
        Trait_Pagger;

    /**
     * @var BpShowcaseModel
     */
    public $showcase_model;

    /**
     * @var BpOplogsModel
     */
    public $oplogs_model;

    public function init() {
        $this->initAdmin();
        $this->checkRole();
        $this->showcase_model = new BpShowcaseModel();
        $this->oplogs_model = new BpOplogsModel();

    }

    /**
     * 操作记录列表
     */
    public function showlistAction() {
        $showcase_list = $this->showcase_model->getList(array('page_no'=>1,'page_size'=>100, 'block'=>0));
        $page_no = (int) $this->getRequest()->getParam('page_no', 1);
        $showcase_id = $this->getRequest()->get('showcase_id', 10008);
        $size = 20;

        $oplogs_list = $this->oplogs_model->getlist($showcase_id, $page_no, $size);

        $this->assign('showcase_id', $showcase_id);
        $this->assign('showcase_list', $showcase_list['showcases']);
        $this->assign("list", $oplogs_list['oplogs']);
        $this->renderPagger($page_no, $oplogs_list['total_nums'], "/bpshowcase/showlist/page_no/{page_no}", $size);
        $this->layout("platform/oplogs.phtml");
    }
}
