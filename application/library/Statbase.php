<?php

class Statbase extends Base {

    use Input,
        Trait_Api,
        Trait_Pagger,
        Trait_Layout;

    /**
     * @var StoreStatcenterModel
     */
    public $statcenter_model;
    /**
     * @var StoreShowcaseModel
     */
    public $showcase_model;
    public $showcase_list;

    public $showcase_id;
    public $start_created;
    public $end_created;

    public function init(){
        parent::initAdmin();
        $this->checkRole();

        $this->statcenter_model = new StoreStatcenterModel();
        $this->showcase_model = new StoreShowcaseModel();

        $default_start = date('Y-m-d', strtotime('-7 day'));
        $default_end = date('Y-m-d', strtotime('-1 day'));

        $this->start_created = $this->input_get_param('start_time', $default_start);
        $this->end_created = $this->input_get_param('end_time', $default_end);
        $this->showcase_id = $this->input_get_param('showcase_id');
    }

    public function setShowcaseList() {
        $showcase_list = $this->showcase_model->getlist(['page_no' => 1, 'page_size' => 100, 'block' => 0]);
        foreach ($showcase_list['showcases'] as $showcase){
            $this->showcase_list[$showcase['showcase_id']] = $showcase['name'];
        }
        $this->assign('showcase_list', $showcase_list['showcases']);
    }

    public function _get_time_string() {
        $start  = strtotime($this->start_created);
        $stop   = strtotime(Tools::format_date($this->end_created));
        $extend = ($stop-$start)/86400;
        $date = [];
        for ($i = 0; $i < $extend; $i++) {
            $date[] = '"'.date('m-d',$start + 86400 * $i).'"';
        }
        return $date;
    }

    public function _display($layout){
        $this->assign('showcase_id', $this->showcase_id);
        $this->assign('start_time', $this->start_created);
        $this->assign('end_time', $this->end_created);

        $this->layout($layout);
    }
}
