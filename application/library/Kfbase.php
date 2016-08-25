<?php

class Kfbase extends Base {

    use Input,
        Trait_Api,
        Trait_Pagger,
        Trait_Layout;

    /**
     * @var KfadminModel
     */
    public $kfadmin_model;

    const SUCCESS = 1; //执行成功

    public function init(){
        parent::initAdmin();
        $this->checkRole();

        $this->kfadmin_model = new KfadminModel();
    }

    /**
     * 返回信息
     */
    public function _outPut($info = "", $status = 0, $url = "") {
        $reback_msg = [
            'info'   => $info,
            'status' => $status,
            'url'    => $url
        ];
        Tools::output($reback_msg);
    }
}
