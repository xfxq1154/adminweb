<?php

class AudioclassController extends Base{

    use Trait_Layout, Trait_Pagger;

    public $audio;

    public function init(){
        $this->initAdmin();
        $this->audio = new AudioClassModel();
    }

    // 列表
    public function classAction() {

        $this->checkRole();

        $p = (int) $this->getRequest()->getParam('p', 1);
        $size = 10;
        $limit = ($p - 1) * $size . ' , ' . $size;
        $count = $this->audio->getCount();
        $list = $this->audio->getAudioClassList($limit);

        $this->assign('list', $list);

        $this->renderPagger($p, $count, '/audioclass/class/p/{p}', $size);
        $this->layout('audio/class_class.phtml');
    }

    public function addAction() {

        $this->checkRole();

        if ($this->getRequest()->isPost()) {
            $data['name'] = $this->getRequest()->getPost('name');
            $data['order'] = $this->getRequest()->getPost('order');
            $data['icon'] = $this->getRequest()->getPost('icon');

            if ($this->audio->insert($data)) {
                echo json_encode(['info' => '添加成功', 'status' => 1, 'url' => '/audioclass/class']);
            } else {
                echo json_encode(['info' => '添加失败', 'status' => 0]);
            }
            exit;
        } else {
            $this->layout('audio/class_add.phtml');
        }


    }

    /**
     * icon 上传
     *
     * @return string  echo json data
     */
    public function uploadAction() {

        // $this->checkRole();
        $user = $_SESSION['a_user'];
        $files = $this->getRequest()->getFiles('file');
        $params = [
            'uid' => $user['id'],
            'filedata'  => '@' . $files['tmp_name'],
            'type'      => $files['type'],
            'name'      => $files['name']
        ];
        // 上传接口
        $url = API_SERVER_IMGURL . '/attachment/images/cate/audio/type/cover';
        $remoteRst = Curl::request($url, $params, 'post');

        if ($remoteRst && is_array($remoteRst) && 'ok' == $remoteRst['status']) {
            echo json_encode(['info' => '上传成功', 'status' => 1, 'data' => [
                'savepath' => $remoteRst['data']['url']['url']]]);
        } else {
            echo json_encode(['info' => '上传失败', 'status' => 0]);
        }
        exit;

    }

    public function editAction($id = 0) {
        $this->checkRole();
        if ($this->getRequest()->isPost()) {
            $data['id'] = $this->getRequest()->getPost('id');
            $data['name'] = $this->getRequest()->getPost('name');
            $data['icon'] = $this->getRequest()->getPost('icon');
            $data['status'] = $this->getRequest()->getPost('status');
            $data['order'] = $this->getRequest()->getPost('order');

            if (false !== $this->audio->update($data)) {
                echo json_encode(['info' => '编辑成功', 'status' => 1, 'url' => '/audioclass/class']);
            } else {
                echo json_encode(['info' => '编辑失败', 'status' => 0]);
            }
            exit;

        } else {

            $audio = $this->audio->findById($id);
            $this->assign('audio', $audio);
            $this->layout('audio/class_edit.phtml');
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
            if (false !== $this->audio->update(['id' => $id, 'status' => 0])) {
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