<?php

/**
 * ChannelController
 * @author yanbo
 */
class ChannelController extends Statbase {

    /** @var StatChannelModel */
    private $channel_model;

    public function init() {
        parent::init();

        $this->channel_model = new StatChannelModel();
    }

    public function allAction() {
        $this->start_created = $this->input_get_param('start_time', date('Y-m-d', strtotime('-1 day')));
        $spm = $this->input_get_param('spm');
        $page_no = $this->input_get_param('page_no', 1);

        $params['spm'] = $spm;
        $params['page_no'] = $page_no;
        $params['start_created'] = $this->start_created;
        $params['end_created'] = Tools::format_date($this->end_created);

        $data = $this->channel_model->channelList($params);

        $this->assign('overview', $data['overview']); //付款金额
        $this->assign('spmlist', $data['format_list']); //访客数
        $this->assign('spm', $params['spm']);

        $this->renderPagger($page_no, $data['total_nums'], "/stat/channel/all?spm={$spm}&page_no={p}&start_time={$this->start_created}&end_created={$this->end_created}", 20);
        $this->_display('channel/all.phtml');
    }

    public function singleAction(){
        $spm = $this->input_get_param('spm');
        $page_no = $this->input_get_param('page_no', 1);
        $page_size = 100;

        $params['spm'] = $spm;
        $params['page_no'] = $page_no;
        $params['page_size'] = $page_size;
        $params['start_created'] = $this->start_created;
        $params['end_created'] = Tools::format_date($this->end_created);

        $data = $this->channel_model->channelListGroupByDate($params);

        if ($data['format_list']){
            foreach ($data['format_list'] as $val){
                $key = '"'.date('m-d',strtotime($val['date'])).'"';
                $chart_data[$key] = $val;
            }
        }

        //生成图表所需数据
        $dates = $this->_get_time_string();
        foreach ($dates as $val){
            $trans_num[] = (isset($chart_data[$val])) ? $chart_data[$val]['trans_num'] : 0;
            $trans_amount[] = (isset($chart_data[$val])) ? $chart_data[$val]['trans_amount'] : 0;
        }
        $this->assign('dates', $dates);
        $this->assign('trans_num', $trans_num);
        $this->assign('trans_amount', $trans_amount);

        $channel_model = new StoreChannelModel();
        $channel_info = current($channel_model->detail_mulit([$spm]));

        $this->assign('channel_name', $channel_info['name'] ? : '未知渠道'); //渠道名称
        $this->assign('overview', $data['overview']); //付款金额
        $this->assign('spmlist', $data['format_list']);
        $this->assign('spm', $spm);
        $this->renderPagger($page_no, $data['total_nums'], "/stat/channel/single?spm={$spm}&page_no={p}&start_time={$this->start_created}&end_created={$this->end_created}", $page_size);
        $this->_display('channel/single.phtml');
    }
}
