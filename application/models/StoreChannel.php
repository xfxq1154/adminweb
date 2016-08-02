<?php

/**
 * @name StoreChannelModel
 * @desc 渠道操作类
 */
class StoreChannelModel {

    const CHANNEL_LIST         = 'channel/getlist';
    const CHANNEL_DETAIL       = 'channel/detail';
    const CHANNEL_DETAILMULIT  = 'channel/detail_multi';
    const CHANNEL_CREATE       = 'channel/add';
    const CHANNEL_UPDATE       = 'channel/update';
    const CHANNEL_ENABLE       = 'channel/enable';
    const CHANNEL_DISABLE      = 'channel/disable';
    const CHANNEL_DELETE       = 'channel/delete';


    public function getlist($params) {
        return Sapi::request(self::CHANNEL_LIST, $params);
    }

    public function detail_mulit($spms) {
        $params['spms'] = implode(",", array_unique($spms));
        return Sapi::request(self::CHANNEL_DETAILMULIT, $params);
    }

    public function detail($channel_id) {
        $params['channel_id'] = $channel_id;
        return Sapi::request(self::CHANNEL_DETAIL, $params);
    }

    public function create($params) {
        return Sapi::request(self::CHANNEL_CREATE, $params, 'POST');
    }

    public function update($params) {
        return Sapi::request(self::CHANNEL_UPDATE, $params, 'POST');
    }

    public function enable($channel_id) {
        $params['channel_id'] = $channel_id;
        return Sapi::request(self::CHANNEL_ENABLE, $params, 'POST');
    }

    public function disable($channel_id) {
        $params['channel_id'] = $channel_id;
        return Sapi::request(self::CHANNEL_DISABLE, $params, 'POST');
    }

    public function delete($channel_id) {
        $params['channel_id'] = $channel_id;
        return Sapi::request(self::CHANNEL_DELETE, $params, 'POST');
    }
}
