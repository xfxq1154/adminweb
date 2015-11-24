<?php

class IpController extends Base {

    use Trait_Layout, Trait_Pagger;

    public function init() {
        $this->initAdmin();
        $this->ip = new IpModel();
    }

    public function listAction() {

        $this->checkRole();

        $p = (int) $this->getRequest()->getParam('p', 1);
        $size = 10;
        $limit = ($p - 1) * $size . ' , ' . $size;
        $count = $this->ip->getCount();
        $list = $this->ip->getList($limit);

        $this->assign('list', $list);

        $this->renderPagger($p, $count, '/ip/list/p/{p}', $size);

        $this->layout('ip/list.phtml');
    }

    public function addAction() {

        $this->checkRole();
        if ($this->getRequest()->isPost()) {

            if ($this->ip->insert($this->getRequest()->getPost())) {

                echo json_encode(['info' => '添加成功', 'status' => 1, 'url' => '/ip/list']);
            } else {
                echo json_encode(['info' => '添加失败', 'status' => 0]);
            }
            exit;
        } else {

            $this->layout('ip/add.phtml');
        }
    }

    public function editAction($id = 0) {

        $this->checkRole();
        if ($this->getRequest()->isPost()) {

            if (false !== $this->ip->update($this->getRequest()->getPost())) {
                echo json_encode(['info' => '编辑成功', 'status' => 1, 'url' => '/ip/list']);
            } else {
                echo json_encode(['info' => '编辑失败', 'status' => 0]);
            }
            exit;

        } else {

            $row = $this->ip->findById($id);
            $this->assign('row', $row);
            $this->layout('ip/edit.phtml');
        }
    }
}