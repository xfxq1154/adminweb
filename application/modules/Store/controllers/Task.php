<?php

/**
 * TaskController
 * @author yanbo
 */
class TaskController extends Storebase{

    use Trait_Redis;

    private $states = [
        0 => ['name' => 'ready', 'class' => 'bg-green'],
        1 => ['name' => 'delay', 'class' => 'bg-blue'],
        2 => ['name' => 'reserved', 'class' => 'bg-yellow'],
        3 => ['name' => 'deleted', 'class' => 'bg-gray'],
    ];

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
        $topic = $this->input_get_param('topic');
        $worker = $this->input_get_param('worker');
        $params = $this->input_get_param('params');
        $memo = $this->input_get_param('memo');
        $state = $this->input_get_param('state');
        $page_no = $this->input_get_param('page_no');
        $page_size = 20;
        
        $params = [
            'topic'=> $topic,
            'worker'=> $worker,
            'params'=> $params,
            'memo'=> $memo,
            'state'=> $state,
            'page_no'=> $page_no,
            'page_size'=> $page_size
        ];
        $result = $this->store_model->taskList($params);
        $task_list = $this->format_data_batch($result);

        $this->assign('list', $task_list['tasks']);
        $this->assign("search", $this->input_get());
        $query_string = http_build_query($this->input_get());
        $this->renderPagger($page_no, $task_list['total_nums'], "/store/task/getlist?$query_string&page_no={p}", $page_size);
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
        $s['state'] = $this->states[$data['state']]['name'];
        $s['state_bg'] = $this->states[$data['state']]['class'];
        $s['times'] = $data['times'];
        $s['timeuse'] = $data['timeuse'];
        $s['memo'] = htmlspecialchars($data['memo']);
        $s['ctime'] = $data['ctime'];
        return $s;
    }
}
