<?php

class BookController extends Base
{

    use Trait_Layout, Trait_Pagger;
    public $bookClass, $ip, $qiniu, $bookInfo, $audioTopicbook;

    /** @var BookModel */
    protected $book;

    /** @var VerseRelationModel */
    protected $verseRelation;

    /** @var VerseModel */
    protected $verse;

    const PAGE_SIZE = 20;

    public function init()
    {
        $this->initAdmin();
        $this->book = new BookModel();
        $this->bookClass = new BookClassModel();
        $this->ip = new IpModel();
        $this->qiniu = new QiniuModel();
        $this->bookInfo = new BookInfoModel();
        $this->audioTopicBook = new AudioTopicBookModel();
        $this->verseRelation = new VerseRelationModel();
        $this->verse = new VerseModel();
    }

    public function listAction($id = 0)
    {
        $this->checkRole();
        $p = (int)$this->getRequest()->getParam('p', 1);
        $kwd = $this->getRequest()->getParam('kwd', '');
        $kwd = urldecode($kwd);
        $where = '';

        if ($id) {
            $where .= ' AND b_id = ' . $id;
        }

        $pageUrl = '/book/list/p/{p}';
        if (!empty($kwd)) {
            $where .= " and b_operating_title like '%" . $kwd . "%' ";
            $pageUrl .= '/kwd/' . $kwd;
        }

        $pagesize = 20;
        //读取列表
        $total = $this->book->getCount($where);
        $this->renderPagger($p, $total, $pageUrl, $pagesize);
        $limit = ($p - 1) * $pagesize . ',' . $pagesize;
        $list = $this->book->getList($limit, $where);
        $this->assign('list', $list);
        $this->_view->kwd = $kwd;
        $this->layout('book/book_list.phtml');
    }

    public function addAction()
    {
        $this->checkRole();
        if ($this->getRequest()->isPost()) {

            $book['title'] = $this->getRequest()->getPost('title');
            $book['style'] = $this->getRequest()->getPost('style');
            $book['type'] = $this->getRequest()->getPost('type');
            $book['class'] = $this->getRequest()->getPost('class');
            $book['sub_class'] = $this->getRequest()->getPost('sub_class');
            $book['author'] = $this->getRequest()->getPost('author');
            $book['cover'] = $this->getRequest()->getPost('cover');
            $book['banner'] = $this->getRequest()->getPost('banner');
            $book['count'] = $this->getRequest()->getPost('count');
            $book['price'] = $this->getRequest()->getPost('price');
            $book['status'] = $this->getRequest()->getPost('status');
            $book['ebook'] = $this->getRequest()->getPost('ebook');
            $book['ebook_size'] = $this->getRequest()->getPost('ebook_size');
            $book['operating_title'] = $this->getRequest()->getPost('operating_title');
            $book['integral'] = $this->getRequest()->getPost('integral');
            $book['other_share_summary'] = $this->getRequest()->getPost('other_share_summary');
            $book['other_content'] = $this->getRequest()->getPost('other_content');
            $book['book_filename'] = $this->getRequest()->getPost('book_filename');
            $book['other_data'] = serialize($this->getRequest()->getPost('other_data'));

            $bookInfo['source_name'] = $this->getRequest()->getPost('source_name');
            $bookInfo['source_version'] = $this->getRequest()->getPost('source_version');
            $bookInfo['source_volume'] = $this->getRequest()->getPost('source_volume');
            $bookInfo['source_author'] = $this->getRequest()->getPost('source_author');
            $bookInfo['source_compilation'] = $this->getRequest()->getPost('source_compilation');
            $bookInfo['source_press'] = $this->getRequest()->getPost('source_press');
            $bookInfo['source_publication_date'] = $this->getRequest()->getPost('source_publication_date');
            $bookInfo['source_ISBN'] = $this->getRequest()->getPost('source_ISBN');
            $bookInfo['source_folio'] = $this->getRequest()->getPost('source_folio');
            $bookInfo['ebook_author_title'] = $this->getRequest()->getPost('ebook_author_title');
            $bookInfo['ebook_type'] = $this->getRequest()->getPost('ebook_type');
            $bookInfo['ebook_compilation_date'] = $this->getRequest()->getPost('ebook_compilation_date');
            $bookInfo['wholebook_name'] = $this->getRequest()->getPost('wholebook_name');
            $bookInfo['wholebook_volume'] = $this->getRequest()->getPost('wholebook_volume');
            $bookInfo['wholebook_compilation'] = $this->getRequest()->getPost('wholebook_compilation');
            $bookInfo['wholebook_authorization'] = $this->getRequest()->getPost('wholebook_authorization');
            $bookInfo['wholebook_authorization_deadline'] = $this->getRequest()->getPost('wholebook_authorization_deadline');
            $bookInfo['other_share_title'] = $this->getRequest()->getPost('other_share_title');
            $bookInfo['other_sign'] = $this->getRequest()->getPost('other_sign');
            $bookInfo['other_related_book'] = $this->getRequest()->getPost('other_related_book');
            $bookInfo['version'] = $this->getRequest()->getPost('version');
            $bookInfo['remarks'] = $this->getRequest()->getPost('remarks');
            $bookInfo['other_recommend'] = $this->getRequest()->getPost('other_recommend');

//            关联金句
            $relationVerse = $this->getRequest()->getPost('verse_id');

            $source = $this->getRequest()->getPost('source');
            $content = $this->getRequest()->getPost('content');

            if ($relationVerse) {
                $relationVerse = explode(',', $relationVerse);
            }

            if ($lastInsertId = $this->book->insert(['book' => $book, 'bookInfo' => $bookInfo])) {

                $type = $book['type'] == 1 ? 3 : 2;
                if (isset($relationVerse)) {
                    foreach ($relationVerse as $vid) {

                        $this->verseRelation->insert(array(
                            'object_id' => $lastInsertId,
                            'vid' => $vid,
                            'type' => $type
                        ));
                    }
                }

//                手动填写的金句
                if ($source && $content) {

                    if ($source && $content) {
                        array_walk_recursive($source, function ($val, $key) use ($content, $type, $lastInsertId) {

                            $verseId = $this->verse->insert(array(
                                'source' => $val,
                                'content' => $content[$key],
                                'state' => 1
                            ));

                            if ($verseId) {
                                $this->verseRelation->insert(array(
                                    'vid' => $verseId,
                                    'type' => $type,
                                    'object_id' => $lastInsertId
                                ));
                            }

                        });
                    }
                }


                if (1 == $book['status']) {
                    // 如果电子书是上架状态。添加到audiotopic_book 表
                    $curdate = date('Y-m-d H:i:s', time());
                    $this->audioTopicBook->insert(['mixed_id' => $lastInsertId, 'type' => 2, 'date' => $curdate]);
                }



                echo json_encode(['info' => '添加成功', 'status' => 1, 'url' => '/book/list']);
            } else {
                echo json_encode(['info' => '添加失败', 'status' => 0]);
            }
            exit;
        } else {

            $this->assign('ipList', $this->ip->getList());
            $this->assign('classList', $this->bookClass->getListByPid());
            $this->layout('book/book_add.phtml');
        }
    }


    public function uploadToQNAction()
    {

        $files = $this->getRequest()->getFiles('file');

        $filename = mb_substr($files['name'], 0, strrpos($files['name'], '.'));

        $ret = $this->qiniu->uploadFile($files['tmp_name'], 2);

        if (isset($ret['key'])) {
            $fileProperty = $this->qiniu->filestat($ret['key']);
        }

        if ($ret && isset($fileProperty) && $fileProperty) {
            echo json_encode(['info' => '上传成功', 'status' => 1, 'data' => [
                'savepath' => $ret['key'], 'fsize' => $fileProperty['fsize'], 'filename' => $filename]]);
        } else {
            echo json_encode(['info' => '上传失败', 'status' => 0]);
        }
        exit;
    }

    // 通过PID获取分类
    public function ajaxClassAction()
    {

        $pid = $this->getRequest()->getPost('pid');

        $list = $this->bookClass->getListByPid($pid);

        echo json_encode($list);
        exit;
    }

    public function editAction($id = 0)
    {
        $this->checkRole();
        if ($this->getRequest()->isPost()) {
            $book['title'] = $this->getRequest()->getPost('title');
            $book['style'] = $this->getRequest()->getPost('style');
            $book['type'] = $this->getRequest()->getPost('type');
            $book['class'] = $this->getRequest()->getPost('class');
            $book['sub_class'] = $this->getRequest()->getPost('sub_class');
            $book['author'] = $this->getRequest()->getPost('author');
            $book['cover'] = $this->getRequest()->getPost('cover');
            $book['banner'] = $this->getRequest()->getPost('banner');
            $book['count'] = $this->getRequest()->getPost('count');
            $book['price'] = $this->getRequest()->getPost('price');
            $book['status'] = $this->getRequest()->getPost('status');
            $book['ebook'] = $this->getRequest()->getPost('ebook');
            $book['ebook_size'] = $this->getRequest()->getPost('ebook_size');
            $book['operating_title'] = $this->getRequest()->getPost('operating_title');
            $book['integral'] = $this->getRequest()->getPost('integral');
            $book['id'] = $this->getRequest()->getPost('id');
            $book['other_share_summary'] = $this->getRequest()->getPost('other_share_summary');
            $book['other_content'] = $this->getRequest()->getPost('other_content');
            $book['book_filename'] = $this->getRequest()->getPost('book_filename');
            $book['other_data'] = serialize($this->getRequest()->getPost('other_data'));

            $bookInfo['source_name'] = $this->getRequest()->getPost('source_name');
            $bookInfo['source_version'] = $this->getRequest()->getPost('source_version');
            $bookInfo['source_volume'] = $this->getRequest()->getPost('source_volume');
            $bookInfo['source_author'] = $this->getRequest()->getPost('source_author');
            $bookInfo['source_compilation'] = $this->getRequest()->getPost('source_compilation');
            $bookInfo['source_press'] = $this->getRequest()->getPost('source_press');
            $bookInfo['source_publication_date'] = $this->getRequest()->getPost('source_publication_date');
            $bookInfo['source_ISBN'] = $this->getRequest()->getPost('source_ISBN');
            $bookInfo['source_folio'] = $this->getRequest()->getPost('source_folio');
            $bookInfo['ebook_author_title'] = $this->getRequest()->getPost('ebook_author_title');
            $bookInfo['ebook_type'] = $this->getRequest()->getPost('ebook_type');
            $bookInfo['ebook_compilation_date'] = $this->getRequest()->getPost('ebook_compilation_date');
            $bookInfo['wholebook_name'] = $this->getRequest()->getPost('wholebook_name');
            $bookInfo['wholebook_volume'] = $this->getRequest()->getPost('wholebook_volume');
            $bookInfo['wholebook_compilation'] = $this->getRequest()->getPost('wholebook_compilation');
            $bookInfo['wholebook_authorization'] = $this->getRequest()->getPost('wholebook_authorization');
            $bookInfo['wholebook_authorization_deadline'] = $this->getRequest()->getPost('wholebook_authorization_deadline');
            $bookInfo['other_share_title'] = $this->getRequest()->getPost('other_share_title');
            $bookInfo['other_sign'] = $this->getRequest()->getPost('other_sign');
            $bookInfo['other_related_book'] = $this->getRequest()->getPost('other_related_book');
            $bookInfo['version'] = $this->getRequest()->getPost('version');
            $bookInfo['remarks'] = $this->getRequest()->getPost('remarks');
            $bookInfo['bid'] = $this->getRequest()->getPost('id');
            $bookInfo['other_recommend'] = $this->getRequest()->getPost('other_recommend');

            if ($this->book->update(['book' => $book, 'bookInfo' => $bookInfo])) {


                if (1 == $book['status']) {
                    $curdate = date('Y-m-d H:i:s', time());
                    $this->audioTopicBook->replace($book['id'], $curdate);
                } else {
                    $this->audioTopicBook->destory($book['id'], 2);
                }

                $verseId = $this->getRequest()->getPost('verse_id');
                $verseId = $verseId ? explode(',', $verseId) : array();
                $type = ($book['type'] == 1) ? 3 : 2;
                $this->verseRelation->reSetRelationFromBook($verseId, $book['id'], $type);


                echo json_encode(['info' => '编辑成功', 'status' => 1, 'url' => '/book/list']);
            } else {
                echo json_encode(['info' => '编辑失败', 'status' => 0]);
            }
            exit;
        } else {


            $book = $this->book->findById($id);
            $bookInfo = $this->bookInfo->searchByBookId($id);
            $ips = $this->ip->getList();
            $classes = $this->bookClass->getListByPid();
            $subClasses = $this->bookClass->getListByPid($book['class']);

            // 查询关联的电子书
            $books = $this->book->searchByBookIds($bookInfo['other_related_book']);
            $bookInfos = $this->bookInfo->searchByBookIds($bookInfo['other_related_book'],
                'i_ebook_compilation_date i_date, i_bid');

            if ($books && $bookInfos) {
                foreach ($books as &$value) {
                    $valid[] = $value['id'];
                    foreach ($bookInfos as $val) {
                        if ($value['id'] == $val['bid']) {
                            $value['date'] = $val['date'];
                        }
                    }
                }
            }

            unset($value);
            $versies = $this->verseRelation->findRelationByBookId($id, ' r_vid');

            if ($versies) {

                foreach ($versies as $value) {
                    $vids[] = $value['vid'];
                }

                $this->_view->versies = $this->verse->findByIds($vids, ' v_source, v_id');
                $this->_view->vids = $vids;
            }

            unset($bookInfos);
            $book['other_data'] = unserialize($book['other_data']);
            $this->assign('book', $book);
            $this->assign('books', $books);
            $this->assign('bookInfo', $bookInfo);
            $this->assign('ips', $ips);
            $this->assign('classes', $classes);
            $this->assign('subClasses', $subClasses);

            $this->layout('book/book_edit.phtml');
        }

    }

    /**
     * 异步获取电子书信息
     * @return string json string
     */
    public function ajaxGetBooksAction()
    {

        $ids = $this->getRequest()->getPost('ids');
        $ids = trim($ids, ',');

        $books = $this->book->searchByBookIds($ids);
        $bookInfos = $this->bookInfo->searchByBookIds($ids, 'i_ebook_compilation_date i_date, i_bid');

        // 获取有效的电子书ID
        $valid = [];
        if ($books && $bookInfos) {
            foreach ($books as &$value) {
                $valid[] = $value['id'];
                foreach ($bookInfos as $val) {
                    if ($value['id'] == $val['bid']) {
                        $value['date'] = $val['date'];
                    }
                }
            }
        }

        // 无效的电子书ID
        $invalid = array_diff(explode(',', $ids), $valid);
        unset($bookInfos);
        echo json_encode(['books' => $books ?: "", 'invalid' => implode(',', $invalid), 'valid' => implode(',', $valid)]);
        exit;
    }

    public function deleteAction()
    {


    }

    public function ajaxBookListAction()
    {
        $p = $this->getRequest()->get('p', 1);
        $title = $this->getRequest()->get('search_title', '');
        $pagesize = 10;

        $where = ' AND b_status = 1 ' . ($title != '' ? ' AND b_operating_title LIKE "%' . $title . '%" ' : '');

        $total = $this->book->getCount($where);
        $limit = ($p - 1) * $pagesize . ',' . $pagesize;
        $list = $this->book->getList($limit, $where);
        $pageUrl = '/book/ajaxbooklist/p/{p}';
        $this->renderPagger($p, $total, $pageUrl, $pagesize);

        $this->_view->list = $list;
        $template = $this->getView()->render('book/ajax_book_list.phtml');

        echo json_encode(array(
            'template' => $template,
            'p' => $p
        ));
        exit;
    }
}