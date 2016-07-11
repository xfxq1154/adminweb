<?php

/**
 * 店铺操作记录
 *
 * @author yanbo
 */
class OplogsController extends Storebase {


    public function init() {
        parent::init();

    }

    /**
     * 操作记录列表
     */
    public function showlistAction() {
        $showcase_list = $this->showcase_model->getlist(array('page_no'=>1,'page_size'=>100, 'block'=>0));
        $this->assign('showcase_list', $showcase_list['showcases']);

        $showcase_id = $this->input_get_param('showcase_id', 10008);
        $sourceid = $this->input_get_param('sourceid');
        $title = $this->input_get_param('title');
        $uri = $this->input_get_param('uri');
        $nickname = $this->input_get_param('nickname');
        $start_time = $this->input_get_param('start_time');
        $end_time = $this->input_get_param('end_time');
        $page_no = $this->input_get_param('page_no', 1);
        $page_size = 20;

        $result = $this->store_model->oplogsList($showcase_id, $sourceid, $title, $uri, $nickname, $start_time, $end_time, $page_no, $page_size);

        $oplogs_list = $this->format_data_batch($result);
        $this->assign('showcase_id', $showcase_id);
        $this->assign('params', $this->getRequest()->getQuery());
        $this->assign("list", $oplogs_list['oplogs']);
        $this->renderPagger($page_no, $oplogs_list['total_nums'], "/store/oplogs/showlist?showcase_id=$showcase_id&page_no={p}?sourceid=$sourceid&title=$title&uri=$uri&nickname=$nickname&start_time=$start_time&end_time=$end_time", $page_size);
        $this->layout("oplogs/showlist.phtml");
    }

    /*
     * 格式化数据
     */
    public function tidy($showcase) {
        $output = json_decode($showcase['output'], 1);

        $s['opid'] = $showcase['id'];
        $s['showcase_id'] = $showcase['showcase_id'];
        $s['user_id'] = $showcase['user_id'];
        $s['nickname'] = $showcase['nickname'];
        $s['title'] = $showcase['title'];
        $s['uri'] = $showcase['uri'];
        $s['input'] = $showcase['input'];
        $s['result'] = ($output) ? $output['status_msg'] : htmlspecialchars($showcase['output']);
        $s['source_id'] = $showcase['source_id'];
        $s['optime'] = $showcase['optime'];
        return $s;
    }

    public function format_data_batch($datas) {
        if ($datas === false) {
            return false;
        }
        if (empty($datas)) {
            return array();
        }
        foreach ($datas['oplogs'] as &$data) {
            $data = $this->tidy($data);
        }
        return $datas;
    }
}
