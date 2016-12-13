<?php

/**
 * ChannelController
 * @author yanbo
 */
class ChannelController extends Storebase {

    /** @var StoreChannelModel */
    private $channel_model;

    public function init() {
        parent::init();

        $this->channel_model = new StoreChannelModel();
    }
    
    /**
     * 渠道列表
     */
    public function showlistAction() {
        $name = $this->input_get_param('name');
        $spm = $this->input_get_param('spm');
        $desc = $this->input_get_param('desc');
        $ratio = $this->input_get_param('ratio');
        $unit = $this->input_get_param('unit');
        $page_no = $this->input_get_param('page_no');
        $page_size = 20;

        $params['name'] = $name;
        $params['desc'] = $desc;
        $params['spm'] = $spm;
        $params['ratio'] = $ratio;
        $params['unit'] = $unit;
        $params['page_no'] = $page_no;
        $params['page_size'] = $page_size;
        $channels = $this->channel_model->getlist($params);

        $this->assign("list", $channels['data']);
        $this->assign("search", $this->input_get());

        $this->renderPagger($page_no, $channels['total_nums'], '/store/channel/showlist?page_no={p}', $page_size);
        $this->layout("channel/showlist.phtml");
    }

    /**
     * 添加渠道
     */
    public function addAction() {
        if(!$_POST){
            $this->layout('channel/add.phtml');
        }

        $spm = $this->input_post_param('spm');
        $name = $this->input_post_param('name');
        $desc = $this->input_post_param('desc');
        $ratio = $this->input_post_param('ratio');
        $unit = $this->input_post_param('unit');
        $email = $this->input_post_param('email');

        $params = [
            'spm' => $spm,
            'name' => $name,
            'desc' => $desc,
            'ratio' => $ratio,
            'unit'  => $unit,
            'email' => $email,
        ];
        if(!$name){
            Tools::output(array('info' => '请填写渠道名称', 'status' => 1));
        }
        if(mb_strlen($ratio,'utf8') > 50){
            Tools::output(array('info' => '分成比例过长', 'status' => 1));
        }
        if(mb_strlen($unit,'utf8') > 50){
            Tools::output(array('info' => '结算单位过长', 'status' => 1));
        }
        $result = $this->channel_model->create($params);
        if($result === FALSE){
            Tools::output(array('info' => Sapi::getErrorMessage(), 'status' => 1));
        }

        Tools::output(array('info' => '创建成功', 'status' => 1, 'url'=>'/store/channel/showlist'));
    }

    /**
     * 编辑渠道
     */
    public function editAction() {
        $channel_id = $this->input_get_param('id');

        $channel = $this->channel_model->detail($channel_id);

        $this->assign("channel", $channel);
        $this->layout("channel/edit.phtml");
    }

    /**
     * 修改渠道信息
     */
    public function updateAction() {
        $channel_id = $this->input_post_param('id');
        $name = $this->input_post_param('name');
        $desc =$this->input_post_param('desc');
        $ratio = $this->input_post_param('ratio');
        $unit = $this->input_post_param('unit');
        $email =$this->input_post_param('email');

        $params = [
            'channel_id' => $channel_id,
            'name'       => $name,
            'desc'       => $desc,
            'ratio'      => $ratio,
            'unit'       => $unit,
            'email'      => $email
        ];
        if(!$name){
            Tools::output(array('info' => '请填写渠道名称', 'status' => 1));
        }
        if(mb_strlen($ratio,'utf8') > 50){
            Tools::output(array('info' => '分成比例过长', 'status' => 1));
        }
        if(mb_strlen($unit,'utf8') > 50){
            Tools::output(array('info' => '结算单位过长', 'status' => 1));
        }
        $result = $this->channel_model->update($params);
        if($result === FALSE){
            Tools::output(array('info' => Sapi::getErrorMessage(), 'status' => 1));
        }

        Tools::output(array('info' => '修改成功', 'status' => 1, 'url'=>'/store/channel/showlist'));
    }

    /**
     * 停用
     */
    public function disableAction() {
        $channel_id = $this->input_post_param('data');

        $result = $this->channel_model->disable($channel_id);
        if($result === FALSE){
            Tools::output(array('info' => Sapi::getErrorMessage(), 'status' => 1));
        }

        Tools::output(array('info' => '该渠道已停用', 'status' => 1, 'url'=>'/store/channel/showlist'));
    }

    /**
     * 恢复
     */
    public function enableAction() {
        $channel_id = $this->input_post_param('data');

        $result = $this->channel_model->enable($channel_id);
        if($result === FALSE){
            Tools::output(array('info' => Sapi::getErrorMessage(), 'status' => 1));
        }

        Tools::output(array('info' => '该渠道已恢复', 'status' => 1, 'url'=>'/store/channel/showlist'));
    }

    /**
     * 删除
     */
    public function deleteAction() {
        $channel_id = $this->input_post_param('data');

        $result = $this->channel_model->delete($channel_id);
        if($result === FALSE){
            Tools::output(array('info' => Sapi::getErrorMessage(), 'status' => 1));
        }

        Tools::output(array('info' => '删除成功', 'status' => 1, 'url'=>'/store/channel/showlist'));
    }
}
