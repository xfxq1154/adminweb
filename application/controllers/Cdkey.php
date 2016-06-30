<?php
/**
 * @name: cdkey.php
 * @time: 2016-06-20 下午2:25
 * @author: liuxuefeng
 * @desc: 商品兑换码
 */

class CdkeyController extends Base {
    use Trait_Layout,
        Trait_Pagger;
    /**
     * @var cdkeyModel
     */
    public $cdkey_model;

    const PAGE_SIZE = 20;

    function init() {
        $this->initAdmin();
        $this->checkRole();
        $this->cdkey_model = new CdkeyModel();
    }

    /**
     * 优惠券页面
     */
    public function indexAction() {
        $sku_outer_id = $this->getRequest()->get('sku_outer_id');
        $batch_number = $this->getRequest()->get('batch_number');
        $page_no      = (int)$this->getRequest()->get('p', 1);

        $params = [
            'sku_outer_id' => $sku_outer_id,
            'batch_number' => $batch_number,
            'page_no'      => $page_no,
        ];

        $result = $this->cdkey_model->getListOfCdkey($params);

        $this->renderPagger($page_no ,$result['total_nums'] , "/cdkey/index/p/{p}?sku_outer_id={$sku_outer_id}&batch_number={$batch_number}", self::PAGE_SIZE);
        $this->assign('list', $result['data']);
        $this->layout("platform/cdkey_list.phtml");
    }

    /**
     * 生成优惠券页面
     */
    public function addAction() {
        $this->layout("platform/cdkey_add.phtml");
    }

    /**
     * 生成优惠券
     * @desc ajax请求,
     */
    public function execAction() {
        $total = $_POST['total']; //合同数量
        $type =  $_POST['type'];  //类型
        $price = $_POST['price']; //价格
        $sku_outer_id = $_POST['sku_outer_id'];   //sku自编码
        $batch_number = $_POST['batch_number'];   //批次编号
        $validity_time = $_POST['validity_time']; //有效期


        if( !$sku_outer_id || !$validity_time || !$price || !$batch_number) {
            Tools::success('error', '缺少参数');
        }

        $total = abs($total) ? $total : 1000;

        $params = [
            'sku_outer_id'  => $sku_outer_id,
            'total'         => $total,
            'validity_time' => $validity_time,
            'price'         => $price,
            'batch_number'  => $batch_number,
            'type'          => $type
        ];
        $this->cdkey_model->addCdkey($params);

        $this->cdkey_model->cdkeyLog('生成商品兑换码', $params);

        if ($this->isLogin()) {
            $this->location('/cdkey/index');
        }
        exit;
    }

    /**
     * 生成CSV文件
     */
    public function exportAction() {
        $cid = $this->getRequest()->get('cid');

        $params = [
            'cid' => $cid
        ];

        $result = $this->cdkey_model->export($params);

        $this->cdkey_model->cdkeyLog('导出商品兑换码', $result);
        
        $export = new Export();
        $export->outPut($result);
        exit;
    }




}