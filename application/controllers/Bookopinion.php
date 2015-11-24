<?php

/**
 * Created by PhpStorm.
 * User: momoko
 * Date: 15/11/23
 * Time: 21:42
 */
class BookopinionController extends Base
{

    use Trait_Layout, Trait_Pagger;

    const PAGE_SIZE = 20;

    /** @var BookOpinionModel*/
    protected $bookOpinion;

    /** @var VerseModel */
    protected $verse;

    public function init()
    {
        $this->initAdmin();
        $this->bookOpinion = new BookOpinionModel();
        $this->verse = new VerseModel();
    }

    public function indexAction()
    {
        $this->checkRole();
        $p = (int)$this->getRequest()->getParam('p', 1);
        $sort = $this->getRequest()->getParam('sort', null);
        $pageUrl = '/bookopinion/index/p/{p}';
        if ($sort) {
            $this->bookOpinion->where = ' ORDER BY o_create_date_time DESC';
            $pageUrl = '/bookopinion/index/p/{p}/sort/desc';
        }

        $total = $this->bookOpinion->getTotal();
        $this->renderPagger($p, $total, $pageUrl, self::PAGE_SIZE);
        $limit = ($p - 1) * self::PAGE_SIZE . ',' . self::PAGE_SIZE;

        $list = $this->bookOpinion->find($limit);

        $opinionId = Tools::arrayColumn($list, 'from_verse_id', 'id');

        $verse = $this->verse->findByIds($opinionId);
        $verse = Tools::arrayColumn($verse, null, 'id');

        if ($list) {
            foreach ($list as &$opinion) {
                if (array_key_exists($opinion['from_verse_id'], $verse)) {
                    $opinion['verse_source'] = $verse[$opinion['from_verse_id']]['source'];
                }
            }
        }

        $this->_view->list = $list;
        $this->layout('bookopinion/index.phtml');
    }
}