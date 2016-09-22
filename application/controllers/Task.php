<?php
/**
 * @name：TaskController
 * @author:yanbo
 * @desc:大平台队列监控
 */

class TaskController extends Base{
    
    use Trait_Pagger;
    use Trait_Layout;
    
    public $task;
    public $sourceid = SAPI_SOURCE_ID; //请求sapi接口必传参数
    
    public function init(){
        $this->initAdmin();
        $this->task = new TaskModel();
    }
    
    public function getlistAction(){
        $this->checkLogin();
        $p = $this->getRequest()->get('p',1);
        
        $page_size = 20;
        
        $task_list = [
            'page_no'=> $p,
            'page_size'=> $page_size,
            'use_has_next'=>0
        ];
        
        $res = $this->task->getList($task_list);
        $data = $res['tasks'];
        $count = $res['total_nums'];
        
        $this->renderPagger($p, $count, '/task/getlist/p/{p}', $page_size);
        $this->assign('list', $data);
        $this->layout('platform/task_list.phtml');
    }
}
