<?php

/**
 * Created by PhpStorm.
 * User: momoko
 * Date: 15/11/13
 * Time: 12:51
 */
class VerseController extends Base
{


    use Trait_Layout;
    use Trait_Pagger;

    const PAGE_SIZE = 20;

    /** @var VerseModel */
    protected $verse;

    /** @var VerseRelationModel */
    protected $verseRelation;

    /** @var BookModel */
    protected $book;

    public function init()
    {
        $this->initAdmin();
        $this->verse = new VerseModel();
        $this->verseRelation = new VerseRelationModel();
        $this->book = new BookModel();
    }

    /**
     * 金句列表
     */
    public function indexAction()
    {
        $this->checkRole();
        $p = (int) $this->getRequest()->getParam('p', 1);
        $id = $this->getRequest()->getParam('id', null);

        $total = $this->verse->getTotal();
        $pageUrl = '/verse/index/p/{p}';
        $this->renderPagger($p, $total, $pageUrl, self::PAGE_SIZE);

        if ($id) {
            $this->verse->setWhere(array(
                'id' => $id
            ));
        }
        $limit = ($p - 1) * self::PAGE_SIZE . ',' . self::PAGE_SIZE;
        $list = $this->verse->find(null, '*', $limit);

        if ($list) {
            foreach ($list as &$value) {
                $relations = $this->verseRelation->findRelationsByVid($value['id'], 'r_object_id');

                if ($relations) {
                    $bookIds = array();
                    foreach ($relations as $val) {
                        $bookIds[] = $val['object_id'];
                    }

                    $relationBooks = $this->book->getBookById($bookIds, ' b_id, b_cover, b_operating_title ');
                    $value['relationBooks'] = $relationBooks;
                }
            }
        }

        $this->_view->list = $list;
        $this->layout('verse/index.phtml');
    }

    /**
     * 添加金句
     */
    public function addAction()
    {
        $this->checkRole();

        if ($this->getRequest()->isPost()) {

//            $bookId = $this->getRequest()->getPost('book_id');
//            $relation = array();
//
//            if ($bookId) {
//                $relation = explode(',', $bookId);
//            }

            $data = array(
                'source' => $this->getRequest()->getPost('source'),
                'content' => $this->getRequest()->getPost('content'),
                'state' => $this->getRequest()->getPost('state'),
            );

            if ($verseId = $this->verse->insert($data)) {

//                设置金句和电子书关联
//                if ($relation) {
//                    $addRelationData = $this->verseRelation->setRelationData($verseId, $relation);
//                    $this->verseRelation->insert($addRelationData);
//                }

                echo json_encode(array(
                    'info' => '添加成功',
                    'status' => 1,
                    'url' => '/verse/index'
                ));
            } else {
                echo json_encode(array(
                    'info' => '添加失败',
                    'status' => 0,
                ));
            }
            exit;
        } else {
            $this->layout('verse/add.phtml');
        }
    }

    /**
     * 编辑金句
     * @param int $id
     */
    public function editAction($id = 0)
    {
        if ($this->getRequest()->isPost()) {

            $id = (int)$this->getRequest()->getPost('id');

//            $bookId = $this->getRequest()->getPost('book_id');

            $updateData = array(
                'source' => $this->getRequest()->getPost('source'),
                'content' => $this->getRequest()->getPost('content'),
                'state' => $this->getRequest()->getPost('state'),
            );

            if ($this->verse->updateById($id, $updateData) !== false) {

//                $relation = $bookId ? explode(',', $bookId) : array();
//                $this->verseRelation->reSetRelation($relation, $id);

                echo json_encode(array(
                    'info' => '修改成功',
                    'status' => 1,
                    'url' => '/verse/index'
                ));
            } else {

                echo json_encode(array(
                    'info' => '修改失败',
                    'status' => 0,
                ));
            }
            exit;
        } else {

            $id = (int)$id;
            $verse = $this->verse->getById($id);
//            $relations = $this->verseRelation->findRelationsByVid($id, 'r_object_id');

//            if ($relations) {
//                $bookIds = array();
//                foreach ($relations as $val) {
//
//                    $bookIds[] = $val['object_id'];
//                }
//
//                $relationBooks = $this->book->getBookById($bookIds, ' b_id, b_cover, b_operating_title ');
//
//                $this->_view->relationBookIds = $bookIds;
//                $this->_view->relationBooks = $relationBooks;
//             }

            $this->_view->verse = $verse;
            $this->layout('verse/edit.phtml');
        }
    }

    public function ajaxVerseListAction()
    {
            $this->checkRole('index');
            $p          = (int) $this->getRequest()->get('p', 1);
            $title      = $this->getRequest()->get('search_title', '');
            $title      = urldecode($title);
            $pagesize   = 10;

            $where = ' AND   v_source LIKE "%' . $title . '%" ' ;


            $pageUrl    = '/verse/ajaxVerseList/p/{p}';

            $limit      = ($p - 1) * $pagesize . ',' . $pagesize;

            $this->verse->setWhere($where);
            $total      = $this->verse->getTotal();
            $this->verse->setWhere($where);
            $list       = $this->verse->find(1, '*', $limit);

            $this->renderPagger($p, $total, $pageUrl, $pagesize);
            $this->_view->list = $list;
            $this->_view->search_title = $title;

            $template = $this->getView()->render('verse/ajax_verse_list.phtml');

            echo json_encode(array(
                'template' => $template,
                'p' => $p
            ));
            exit;
    }
}