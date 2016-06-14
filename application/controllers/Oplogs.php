<?php

/**
 * 店铺操作记录
 *
 * @author yanbo
 */
class OplogsController extends Base {

    use Trait_Layout,
        Trait_Pagger;

    public $showcase;

    const ADMIN = '0'; //店长

    public function init() {
        $this->initAdmin();
        $this->checkRole();
        $this->showcase = new BpShowcaseModel();
    }

    /**
     * 操作记录列表
     */
    public function indexAction() {
        $t = (int) $this->getRequest()->get('t');
        $p = (int) $this->getRequest()->getParam('p', 1);
        $block = $this->getRequest()->get('block');
        $kw = $this->getRequest()->get('kw');
        $nickname = $this->getRequest()->get('nickname');
        $showcase_id = $this->getRequest()->get('showcase_id');
        $size = 20;

        $params = array();

        switch ($t) {
            case 1:
                $params['status_person'] = 1;
                break;
            case 2:
                $params['status_com'] = 1;
                break;
            case 3:
                $params['status_com'] = 3;
                break;
            case 11:
                $params['block'] = 1;
                break;
        }
        $params['page_no'] = $p;
        $params['page_size'] = $size;
        $params['block'] = $block > 0 ? $block : '0';
        $params['kw'] = $kw;
        $params['nickname'] = $nickname;
        $params['showcase_id'] = $showcase_id;

        $showcasesList = $this->showcase->getList($params);
        $showlist = $this->showcase->getList(array('page_no'=>$p,'page_size'=>$size));
        $idlist = array();
        foreach ($showlist['showcases'] as $val){
            $idlist[] = $val['showcase_id'];
        }
        $this->assign("list", $showcasesList['showcases']);
        $this->assign('kw', $kw);
        $this->assign('nickname', $nickname);
        $this->assign('id', $showcase_id);
        $this->assign('idlist', $idlist);
        $this->renderPagger($p, $showcasesList['total_nums'], '/bpshowcase/index/p/{p}/t/'.$t, $size);
        $this->layout("platform/oplogs.phtml");
    }
}
