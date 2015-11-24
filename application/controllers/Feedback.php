<?php

/**
 * @name GroupController
 * @desc 用户反馈控制器
 * @show
 * @author hph
 */
class FeedbackController extends Base {

    use Trait_Layout,
        Trait_Pagger;

    public $feedback;

    /**
     * 入口文件
     */
    public function init() {
        $this->initAdmin();
        $this->feedback = new FeedbackModel();
    }

    //首页列表
    public function indexAction() {
        die('audio index');
    }

    public function listAction() {
        $this->checkRole();
        $p = (int) $this->getRequest()->getParam('p', 1);
        $type = $this->getRequest()->getParam('type', '');
        
        $where = '';
        $pageUrl = '/feedback/list/p/{p}';        

        if(is_numeric($type)){
            $where.=" and f_status = ".$type;
            $pageUrl.='/type/'.$type;
        }

        $pagesize = 20;
        //读取列表
        $total = $this->feedback->getNumber($where);

        $this->renderPagger($p, $total, $pageUrl, $pagesize);

        $list = $this->feedback->getList($p, $pagesize,$where);
        
        $this->_view->alist = $list;
        $this->_view->type = $type;
        $this->layout('feedback/list.phtml');
    }
    
    public function upAction(){
        $this->checkRole('list');
        $st = $this->getRequest()->getParam('st');
        $fid = $this->getRequest()->getParam('fid');
        
        $res = $this->feedback->update(array('id'=>$fid,'status'=>$st));
        $return = array(
            "info" => '更新失败！',
            "status" => 0,
            "url" => "",
        );
        if($res>=0){
            $return['info'] = '更新排期成功！';
            $return['status'] = 1;
        }
        Tools::output($return);
    }

}
