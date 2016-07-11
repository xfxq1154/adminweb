<?php

class Storebase extends Base {

    use Input,
        Trait_Api,
        Trait_Pagger,
        Trait_Layout;

    /**
     * @var StoreModel
     */
    public $store_model;
    /**
     * @var StoreShowcaseModel
     */
    public $showcase_model;

    public function init(){
        parent::initAdmin();
        $this->checkRole();

        $this->store_model = new StoreModel();
        $this->showcase_model = new StoreShowcaseModel();
    }

    public function setShowcaseList() {
        $showcase_list = $this->showcase_model->getlist(['page_no' => 1, 'page_size' => 100, 'block' => 0]);
        $this->assign('showcase_list', $showcase_list['showcases']);
    }
}
