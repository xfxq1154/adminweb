<?php

/**
 * TaskController
 * @author yanbo
 */
class TaskController extends Storebase{

    public function init(){
        parent::init();
    }
    
    public function getlistAction(){
        $page_no = $this->input_get_param('page_no');
        $page_size = 20;
        
        $params = [
            'page_no'=> $page_no,
            'page_size'=> $page_size,
            'use_has_next'=> 0
        ];
        $result = $this->store_model->taskList($params);
        $task_list = $this->format_data_batch($result);
        $data = $task_list['tasks'];
        $count = $task_list['total_nums'];
        
        $this->renderPagger($page_no, $count, "/store/task/getlist?page_no={p}", $page_size);
        $this->assign('list', $data);
        $this->layout('task/showlist.phtml');
    }

    public function format_data_batch($datas) {
        if ($datas === false) {
            return false;
        }
        if (empty($datas)) {
            return array();
        }
        foreach ($datas['tasks'] as &$data) {
            $data = $this->tidy($data);
        }
        return $datas;
    }

    /*
     * 格式化数据
     */
    public function tidy($data) {
        $s['id'] = $data['id'];
        $s['queue_name'] = $data['queue_name'];
        $s['task_json'] = $data['task_json'];
        $s['memo'] = htmlspecialchars($data['memo']);
        $s['ctime'] = $data['ctime'];
        return $s;
    }
}
