<?php


class AppmanageController extends Base {

    private $appManage, $qiniu;

    use Trait_Layout, Trait_Pagger;
    public function init() {
        $this->initAdmin();
        $this->appManage = new AppManageModel();
        // 公开资源bucket
        $this->qiniu = new QiniuModel('luoji-shzf-wechat');
    }

    public function listAction() {
        $this->checkRole();

        $p = (int) $this->getRequest()->getParam('p', 1);
        $pagesize = 20;
        //读取列表
        $total = $this->appManage->getCount();

        $this->renderPagger($p, $total, '/appmanage/list/p/{p}', $pagesize);
        $limit = ($p-1)*$pagesize.','.$pagesize;
        $list = $this->appManage->getList($limit);
        $this->assign('list', $list);

        $this->layout("appmanage/list.phtml");
    }

    public function addAction() {
        $this->checkRole();

        if ($this->getRequest()->isPost()) {

            $postData = $this->getRequest()->getPost();
            $postData['create_time'] = date('Y-m-d H:i:s', time());

            if ($this->appManage->insert($postData)) {

                echo json_encode(['info' => '添加成功', 'status' => 1, 'url' => '/appmanage/list']);
            } else {

                echo json_encode(['info' => '添加失败', 'status' => 0]);
            }

            exit;
        } else {

            $this->layout('appmanage/add.phtml');
        }
    }

    public function editAction($id = 0) {
        $this->checkRole();

        if ($this->getRequest()->isPost()) {

            $postData = $this->getRequest()->getPost();

            if ($this->appManage->update($postData)) {
                echo json_encode(['info' => '编辑成功', 'status' => 1, 'url' => '/appmanage/list']);
            } else {
                echo json_encode(['info' => '编辑失败', 'status' => 0]);
            }
            exit;
        } else {

            $packageInfo = $this->appManage->findById($id);
            $this->assign('packageInfo', $packageInfo);
            $this->layout('appmanage/edit.phtml');
        }
    }

    public function uploadToQNAction() {

        $files = $this->getRequest()->getFiles('file');

        $ret = $this->qiniu->uploadFile($files['tmp_name'],3);

        if ($ret) {
            echo json_encode(['info' => '上传成功', 'status' => 1, 'data' => [
                'savepath' => $ret['key']]]);
        } else {
            echo json_encode(['info' => '上传失败', 'status' => 0]);
        }
        exit;
    }

}