<?php

/**
 * JoobpoolController
 * @author yanbo
 */
class JobpoolController extends Storebase {

    private $state = ['ready', 'delay', 'reserved', 'deleted'];

    public function init() {
        parent::init();
    }
    
    /**
     * 任务列表
     */
    public function showlistAction() {
        $topic = $this->input_get_param('topic', 'main');
        $page_no = $this->input_get_param('page_no');
        $page_size = 20;

        $params['topic'] = $topic;
        $params['page_no'] = $page_no;
        $params['page_size'] = $page_size;
        $datas = $this->store_model->jobList($params);

        $this->assign("list", $this->format_data_batch($datas['list']));
        $this->assign("topic", $topic);

        $this->renderPagger($page_no, $datas['total_nums'], '/store/jobpool/showlist?page_no={p}', $page_size);
        $this->layout("pool/showlist.phtml");
    }

    private function format_data_batch($datas){
        foreach ($datas as &$data){
            $data = $this->tidy($data);
        }

        return $datas;
    }

    private function tidy($data){
        return [
            'id'          => $data['id'],
            'topic'       => $data['topic'],
            'params'      => json_encode($data['params']),
            'state'       => $this->state[$data['state']],
            'delay'       => $data['delay'],
            'ctime'       => $data['ctime'],
            'update_time' => $data['update_time'],
        ];
    }
}
