<?php

/**
 * TaskController
 * @author yanbo
 */
class TaskController extends Storebase{

    use Trait_Redis;

    public function init(){
        parent::init();
    }

    public function createAction(){
        if(!$_POST){
            $this->layout('task/create.phtml');
        }

        $topic = $this->input_post_param('topic');
        $body = $this->input_post_param('body');

        $result = $this->store_model->taskCreate($topic, $body);
        if($result === FALSE){
            Tools::output(array('info' => Sapi::getErrorMessage(), 'status' => 1));
        }

        Tools::output(array('info' => '添加成功', 'status' => 1, 'url'=>'/store/task/getlist'));
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
        $this->layout('task/readylist.phtml');
    }

    public function delayListAction(){
        $topic = $this->input_get_param('name', 'async');
        $started = $this->input_get_param('started', time());
        $ended = $this->input_get_param('ended', strtotime('+1 days'));

        $masterRedis = $this->getMasterRedis();
        $store_dealy_async_jobs = $masterRedis->zRangeByScore('store:task:delay:async', $started, $ended);

        $jobs = [];
        foreach ($store_dealy_async_jobs as $jobid){
            $time = $masterRedis->zScore('store:task:delay:'.$topic, $jobid);
            $jobs[] = [
                'time'    => date('Y-m-d H:i:s', $time),
                'body'    => $masterRedis->hGet("store:job:$jobid", 'params'),
                'remain'  => $time - time()
            ];
        }

        $this->assign('jobs', $jobs);
        $this->layout('task/delaylist.phtml');
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
        $s['topic'] = $data['topic'];
        $s['jobid'] = $data['jobid'];
        $s['worker'] = $data['worker'];
        $s['params'] = $data['params'];
        $s['state'] = $data['state'];
        $s['times'] = $data['times'];
        $s['timeuse'] = $data['timeuse'];
        $s['memo'] = htmlspecialchars($data['memo']);
        $s['ctime'] = $data['ctime'];
        return $s;
    }
}
