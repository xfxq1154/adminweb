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
        $showcase_id = $this->getRequest()->get('showcase_id', 10008);
        $sourceid = $this->getRequest()->get('sourceid');
        $title = $this->getRequest()->get('title');
        $nickname = $this->getRequest()->get('nickname');
        $start_time = $this->getRequest()->get('start_time');
        $end_time = $this->getRequest()->get('end_time');
        $page_no = (int) $this->getRequest()->getParam('page_no', 1);
        $page_size = 20;

        $oplogs_list = $this->oplogs_model->getlist($showcase_id, $sourceid, $title, $nickname, $start_time, $end_time, $page_no, $page_size);

        $this->assign('showcase_id', $showcase_id);
        $this->assign('params', $this->getRequest()->getQuery());
        $this->assign('showcase_list', $showcase_list['showcases']);
        $this->assign("list", $oplogs_list['oplogs']);
        $this->renderPagger($page_no, $oplogs_list['total_nums'], "/oplogs/showlist/showcase_id/$showcase_id/page_no/{p}", $page_size);
        $this->layout("platform/oplogs.phtml");
    }
}
