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
    const ERROR_PARAM_MISS = '必填参数缺失';

    function init() {
        $this->initAdmin();
        $this->checkRole();
        $this->cdkey_model = new CdkeyModel();
    }

    /**
     * 兑换码页面
     */
    public function indexAction() {
        $sku_outer_id = $this->getRequest()->get('sku_outer_id');
        $batch_number = $this->getRequest()->get('batch_number');
        $cid = $this->getRequest()->get('cid');
        $page_no      = (int)$this->getRequest()->get('p', 1);

        $params = [
            'sku_outer_id' => $sku_outer_id,
            'batch_number' => $batch_number,
            'cid'          => $cid,
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
     * 废除优惠券页面
     */
    public function nullifyTplAction() {
        $this->layout("platform/cdkey_nullify.phtml");
    }

    /**
     * 生成优惠券
     * @desc ajax请求,
     */
    public function execAction() {
        $sku_outer_id = $this->getRequest()->getPost('sku_outer_id');
        $count = $this->getRequest()->getPost('total');
        $type = $this->getRequest()->getPost('type');
        $contract_price = $this->getRequest()->getPost('price');
        $batch_number = $this->getRequest()->getPost('batch_number');
        $validity_time = $this->getRequest()->getPost('validity_time');
        $contract_id = $this->getRequest()->getPost('contract_id');


        if( !$sku_outer_id || !$validity_time || !$contract_price || !$contract_id) {
            $this->_outPut(self::ERROR_PARAM_MISS);
        }

        $count = abs($count) ? $count : 1000;

        $params = [
            'sku_outer_id'   => $sku_outer_id,
            'count'          => $count,
            'expire_at'      => $validity_time,
            'contract_price' => $contract_price,
            'batch_number'   => $batch_number,
            'type'           => $type,
            'contract_id'    => $contract_id,
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
        $this->cdkey_model->cdkeyLog('导出商品兑换码', $params);
        
        $export = new Export();
        $export->setTitle($result, Fields::$cdkey);
        $export->outPut($result);
        exit;
    }


    /**
     * 作废兑换码
     */
    public function nullifyAction() {
        $cdkey = $this->getRequest()->get('cdkey');
        $serial_num = $this->getRequest()->get('serial_num');
        $cid = $this->getRequest()->get('cid');

        if(!$cdkey && !$serial_num && !$cid) {
            $this->_outPut(self::ERROR_PARAM_MISS);
        }

        $params = [
            'cdkey'      => $cdkey,
            'serial_num' => $serial_num,
            'cid'        => $cid,
        ];

        $this->cdkey_model->cdkeyLog('作废兑换码', $params);

        $this->cdkey_model->nullify($params);
        $this->_outPut(Cdkey::getErrorMessage(), Cdkey::getErrorCode(), '/cdkey/index');
    }

    /**
     * 根据批次恢复
     */
    public function restoreAction() {
        $cid = $this->getRequest()->get('cid');
        if(!$cid) {
            $this->_outPut(self::ERROR_PARAM_MISS);
        }

        $params['cid'] = $cid;
        $this->cdkey_model->restore($params);
    }
}