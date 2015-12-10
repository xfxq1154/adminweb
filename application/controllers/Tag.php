<?php

class TagController extends Base {

    use Trait_Layout, Trait_Pagger;
    
    public $tags;
    
    public function init() {
        $this->initAdmin();
        $this->tags = new TagsModel();
    }

    public function listAction() {
        $this->checkRole();

        $p = (int) $this->getRequest()->getParam('p', 1);
        $pagesize = 20;
        //读取列表
        $total = $this->tags->getCount();
        $this->renderPagger($p, $total, '/tag/list/p/{p}', $pagesize);
        $limit = ($p-1)*$pagesize.','.$pagesize;
        $tags = $this->tags->selectAll($limit);
        $this->assign('tags', $tags);
        $this->layout('tag/list.phtml');
    }

    public function addAction() {
        $this->checkRole();
        if ($this->getRequest()->isPost()) {

            if ($this->tags->insert($this->getRequest()->getPost())) {
                Tools::output(['info' => '添加标签成功', 'status' => 1, 'url' => '/tag/list']);
            } else {

                Tools::output(['info' => '添加标签失败', 'status' => 0]);
            }
            exit;
        } else {
            $this->layout('tag/add.phtml');
        }
    }
    
    public function deleteAction(){
        $this->checkRole();
        $id = json_decode($this->getRequest()->getPost('data'), true)['id'];
        
        if(empty($id)){
            return FALSE;
        }
        $rs = $this->tags->delete($id);
        
        if($rs){
            Tools::output(['info' => '删除成功', 'status' => 1, 'url' => '/tag/delete']);
        } else {
            Tools::output(['info' => '删除失败', 'status' => 0]);
        }
        exit;
    }
}