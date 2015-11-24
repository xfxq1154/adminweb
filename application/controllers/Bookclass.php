<?php

class BookclassController extends Base {

    use Trait_Layout, Trait_Pagger;

    public $bookClass;
    public function init(){
        $this->initAdmin();
        $this->bookClass = new BookClassModel();
    }

    // 列表
    public function listAction() {

        $this->checkRole();

        $list = $this->bookClass->getList();

        foreach ($list as &$value) {
            $value['cid'] = $value['pid'];
        }


        $this->assign('list', $this->adminNav->formatTableNav($list));
        $this->layout('book/class_list.phtml');
    }

    public function addAction($id = 0) {

        $this->checkRole();

        if ($this->getRequest()->isPost()) {
            $data['name'] = $this->getRequest()->getPost('name');
            $data['order'] = $this->getRequest()->getPost('order');
            $data['icon'] = $this->getRequest()->getPost('icon');
            $data['pid'] = $this->getRequest()->getPost('pid');

            if ($this->bookClass->insert($data)) {
                echo json_encode(['info' => '添加成功', 'status' => 1, 'url' => '/bookclass/list']);
            } else {
                echo json_encode(['info' => '添加失败', 'status' => 0]);
            }
            exit;
        } else {

            $categories = $this->bookClass->getList();

            // 坑爹的formatTableNav，一定要用cid作为父级啊
            foreach ($categories as &$val) {
                $val['cid'] = $val['pid'];
            }

            $this->assign('list', $this->adminNav->formatTableNav($categories));
            $this->assign('id', $id);
            $this->layout('book/class_add.phtml');
        }

    }

    public function editAction($id = 0) {
        $this->checkRole();
        if ($this->getRequest()->isPost()) {
            $data['id'] = $this->getRequest()->getPost('id');
            $data['name'] = $this->getRequest()->getPost('name');
            $data['icon'] = $this->getRequest()->getPost('icon');
            $data['status'] = $this->getRequest()->getPost('status');
            $data['order'] = $this->getRequest()->getPost('order');

            if (false !== $this->bookClass->update($data)) {
                echo json_encode(['info' => '编辑成功', 'status' => 1, 'url' => '/bookclass/list']);
            } else {
                echo json_encode(['info' => '编辑失败', 'status' => 0]);
            }
            exit;

        } else {

            $audio = $this->bookClass->findById($id);
            $this->assign('audio', $audio);
            $this->layout('book/class_edit.phtml');
        }
    }

    public function deleteAction() {

        $this->checkRole();
        if ($this->getRequest()->isPost()) {
            $id = json_decode($this->getRequest()->getPost('data'), true)['id'];

            if (!$id) {
                echo json_encode(['info' => '删除失败', 'status' => 0]);
                exit;
            }

            // 伪删除，改变status状态
            if (false !== $this->bookClass->update(['id' => $id, 'status' => 0])) {
                echo json_encode(['info' => '删除成功', 'status' => 1]);
            } else {
                echo json_encode(['info' => '删除失败', 'status' => 0]);
            }
            exit;

        } else {
            // 非POST请求
            echo json_encode(['info' => '非法操作', 'status' => 0]);
            exit;
        }
    }
}