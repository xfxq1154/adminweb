<?php

/**
 * Created by PhpStorm.
 * User: momoko
 * Date: 15/11/17
 * Time: 16:25
 */
class RechargeController extends Base
{

    use Trait_Layout;
    use Trait_Pagger;

    /** @var AudioUsersModel */
    protected $audioUser;

    /** @var CoinModel */
    protected $coin;

    public function init()
    {
        $this->initAdmin();
        $this->audioUser = new AudioUsersModel();
        $this->coin = new CoinModel();
    }

    /**
     * 充值coin
     */
    public function addAction()
    {
        if ($this->getRequest()->isPost()) {

            $mobile = $this->getRequest()->getPost('mobile');
            $device = $this->getRequest()->getPost('device');
            $money = $this->getRequest()->getPost('money');

            $userInfo = $this->audioUser->getUserByMobile($mobile);

            $result = $this->coin->reChargeCoin(array(
                'device_type' => $device,
                'amount' => $money,
                'user_id' => $userInfo['id']
            ));


            if ($result) {
                echo json_encode(array(
                    'info' => '充值成功',
                    'status' => 1,
                    'url' => ''
                ));
            } else {
                echo json_encode(array(
                    'info' => '充值失败',
                    'status' => 1,
                ));
            }
            exit;

        } else {
            $this->layout('recharge/add.phtml');
        }
    }
}