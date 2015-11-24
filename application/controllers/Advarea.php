<?php

/**
 * @name AdvareaController
 * @desc 广告为管理
 * @author hph
 */
class AdvareaController extends Base{

    use Trait_Layout;

    public $adv;

    public function init(){
        $this->initAdmin();
        $this->adv = new AdvModel();
    }

    public function indexAction(){
        $this->checkRole();
        $advlist = $this->adv->getAdvList();
        $this->assign('adv', $advlist);

        $this->layout('adv/list.phtml');
    }

    public function addAction(){
        $this->checkRole();
        if($_POST){
            $a_id = $this->adv->add($_POST);
            if ($a_id > 0) {
                $data = array(
                    "info" => '推荐位广告添加成功！',
                    "status" => 1,
                    "url" => "/advarea/index",
                );
                Tools::output($data);
                exit;
            } else {
                $data = array(
                    "info" => '推荐位广告添失败！',
                    "status" => 0,
                );
                Tools::output($data);
                exit;
            }
        } else {
            $this->layout('adv/add.phtml');
        }
    }

    public function editAction($id = 0){

        $this->checkRole();

        if ($this->getRequest()->isPost()) {
            $id = $this->getRequest()->getPost('id');
            $data = $this->getRequest()->getPost();
            unset($data['id']);

            if ($this->adv->update($id, $data) !== false) {

                Tools::output(['info' => '编辑推荐广告位成功', 'status' => 1, 'url' => '/advarea/index']);
                exit;
            } else {
                Tools::output(['info' => '编辑推荐广告位失败', 'status' => 0]);
                exit;
            }
        } else {
            $adv = $this->adv->getAdvInfoById((int) $id);

            $this->assign('adv', $adv);
            $this->layout('adv/edit.phtml');
        }
    }
}
