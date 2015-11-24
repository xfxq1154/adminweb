<?php

/**
 * AudioClassModel
 */
class CoinModel
{

    use Trait_DB,
        Trait_Api;
    private $coinApi;
    private $coinCode = 'IGET';
    private $coin_secret;

    use Trait_Redis;

    public $dbMaster; //主从数据库 配置
    public $tableName = '`u_charge`';
    public $adminLog;

    /** @var ChargeModel */
    protected $charge;

    public function __construct()
    {
        $this->dbMaster = $this->getDb('audio');
        $this->adminLog = new AdminLogModel();


        $this->coinApi = $this->getApi('coin');
        $this->coin_secret = API_COIN_SECRET;

        $this->charge = new ChargeModel();
    }


    /**
     * 获取用户的节操币余额，区分 设备ID  ANDROID，IOS
     * @param int $userID
     * @param strint $device
     */
    public function getUserCoin($userID, $device = 'ANDROID')
    {
        $interface = "api/accounts/{$this->coinCode}/{$device}/{$userID}/balance";

        $user_data = $this->coinApi->get($interface);

        $user_data = json_decode($user_data, true);

        if (isset($user_data['code']) && $user_data['code'] == 0) {
            return $user_data['detail'];
        } else if (isset($user_data['code']) && $user_data['code'] == 105) {
            $user_data['balance'] = 0;
            return $user_data;
        } else {
            return false;
        }

    }

    /**
     * 充值coin
     * @param array $data
     * @return bool
     */
    public function reChargeCoin(array $data = array())
    {
        if (empty($data) || !is_array($data)) {
            return false;
        }

        $data['sys_code'] = $this->coinCode;
        $data['sign'] = $this->create_Sign($data);

        $result = $this->coinApi->post('api/v1/create_admin_gift/', $data);
        $result = json_decode($result, true);

        try {

            if ($result['code'] != 0) {
                throw new LogicException($result['msg']);
            }

            $data['order'] = $result['detail'];
            $this->charge->insert($data);

            return true;
        } catch (LogicException $e) {
            Tools::error($e);
        }

    }


    public function create_Sign($data, $secret = API_COIN_SECRET)
    {
        if (empty($data) || !is_array($data))
            return false;
        if (!(count($data) > 0))
            return false;

        $build = array();
        foreach ($data as $k => $v) {
            if ($v === '')
                continue;
            $build[$k] = $k . '=' . $v;
        }
        ksort($build);
        return md5(implode('&', $build) . $secret);
    }


}
