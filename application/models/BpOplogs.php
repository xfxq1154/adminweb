<?php

/**
 * @name BpOplogsModel
 * @desc 系统操作日志类
 */
class BpOplogsModel {

    use Trait_Api;

    const OPLOGS_GETLIST = 'oplogs/getlist';
    
    public function __construct() {}


    /**
     * 日志列表
     */
    public function getlist($showcase_id, $sourceid, $title, $nickname, $start_time, $end_time, $page_no, $page_size) {
        $params['showcase_id'] = $showcase_id;
        $params['source_id'] = $sourceid;
        $params['title'] = $title;
        $params['nickname'] = $nickname;
        $params['start_time'] = $start_time;
        $params['end_time'] = $end_time;
        $params['page_no'] = $page_no;
        $params['page_size'] = $page_size;
        $result = Sapi::request(self::OPLOGS_GETLIST, $params);
        return $this->format_data_batch($result);
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
