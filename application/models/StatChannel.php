<?php

/**
 * @name StatChannelModel
 * @desc 渠道统计结果查询类
 */
class StatChannelModel {

    const CHANNEL_LIST = 'api/channel/getlist';
    const CHANNEL_LIST_DATE = 'api/channel/getlist_group_by_date';
    const CHANNEL_DETAIL = 'channel/detail_multi';

    private $channel_names;


    public function channelList($params) {
        $result = Sdata::request(self::CHANNEL_LIST, $params);
        return $this->format_channel_batch($result);
    }

    public function channelListGroupByDate($params) {
        $result = Sdata::request(self::CHANNEL_LIST_DATE, $params);
        return $this->format_channel_batch($result);
    }

    public function setSpmsName($datas){
        $spms = [];
        foreach ($datas as $val){
            $spms[] = $val['spm'];
        }

        $channel_model = new StoreChannelModel();
        $result = $channel_model->detail_mulit($spms);
        if (!$result){
            return false;
        }
        foreach ($result as $channel){
            $this->channel_names[$channel['spm']] = $channel['name'];
        }
    }

    public function format_channel_batch($datas){
        if (!$datas){
            return false;
        }
        $this->setSpmsName($datas['list']);

        $format_list = [];
        $total_pv = [];
        $total_uv = [];
        $paied_num = [];
        $paied_fee = [];
        foreach ($datas['list'] as $data){
            $total_pv[] = $data['pv'];
            $total_uv[] = $data['uv'];
            $paied_num[] = $data['trans_num'];
            $paied_fee[] = $data['trans_amount'];

            $format_list[] = $this->format_channel_struct($data);
        }

        return [
            'format_list'  => $format_list,
            'overview' => [
                'total_pv'     => array_sum($total_pv),
                'total_uv'     => array_sum($total_uv),
                'total_order'  => array_sum($paied_num),
                'total_amount' => array_sum($paied_fee),
            ],
            'total_nums'   => $datas['total_nums']
        ];
    }

    public function format_channel_struct($data){
        if(isset($this->channel_names[$data['spm']])){
            $params['spms'] = $data['spm'];
            $result = Sapi::request(self::CHANNEL_DETAIL, $params);
        }
        return [
            'date'          => $data['odate'],
            'spm'           => $data['spm'],
            'name'          => isset($this->channel_names[$data['spm']]) ? $this->channel_names[$data['spm']] : '未知渠道',
            'ratio'         => $result[0]['ratio'],
            'unit'          => $result[0]['unit'],
            'pv'            => $data['pv'],
            'uv'            => $data['uv'],
            'rate'          => $data['uv'] ? round($data['trans_num'] / $data['uv'] * 100, 2) : '0.00',
            'trans_num'     => $data['trans_num'],
            'trans_amount'  => $data['trans_amount'],
        ];
    }
}
