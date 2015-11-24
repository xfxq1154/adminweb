<?php

/**
 * AudioClassModel
 */
class ChargeModel
{

    use Trait_DB;

    use Trait_Redis;

    public $dbMaster; //主从数据库 配置
    public $tableName = '`u_charge`';
    public $adminLog;

    public function __construct()
    {
        $this->dbMaster = $this->getDb('audio');
        $this->dbSlave = $this->getDb('audio');
        $this->adminLog = new AdminLogModel();
    }

    /**
     * 获取音频分类列表
     *
     * @return array result
     */
    public function getList($page = 1, $size = 20, $where = '')
    {
        $p = $page > 0 ? $page : 1;
        $limit = ($p - 1) * $size . ',' . $size;

        try {
            $sql = 'SELECT * FROM ' . $this->tableName . ' where 1  ' . $where . ' ORDER BY c_id desc  limit ' . $limit;
            $stmt = $this->dbMaster->prepare($sql);
            $stmt->execute();

            $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $return = [];

            if ($rs) {
                foreach ($rs as $key => $val) {
                    $return[$key] = CustomArray::removekeyPrefix($val, 'c_');
                }
            }

            return $return;
        } catch (PDOException $e) {
            die($e->getMessage());
        }
    }


    public function getNumber($where = '')
    {
        try {

            $sql = "  SELECT count(*) as num FROM " . $this->tableName . " where 1 {$where}    ";
            $stmt = $this->dbMaster->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row['num'];
        } catch (PDOException $e) {
            die($e->getMessage());
        }
    }


    public function getTotalCoin($where = '')
    {
        try {

            $sql = "  SELECT sum(c_coin) as num FROM " . $this->tableName . " where  c_status = 1      ";
            if (!empty($where)) {
                $sql .= $where;
            }
            $stmt = $this->dbMaster->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row['num'];
        } catch (PDOException $e) {
            die($e->getMessage());
        }
    }


    /**
     * 写入充值记录
     * @param $data
     * @return bool|string
     */
    public function insert($data)
    {

        if (!$data) {
            return false;
        }

//        管理员信息
        $admin = $_SESSION['a_user'];

//        充值记录
        $addData = array(
            'uid' => $data['user_id'],
            'device' => $data['device_type'],
            'coin' => $data['amount'],
            'status' => 1,
            'paytype' => 'NOCHANNEL',
            'order' => $data['order']

        );

        $addData = CustomArray::addKeyPrefix($addData, 'c_');

        try {
            $sql = 'INSERT INTO ' . $this->tableName . ' SET ' . $this->makeSet($addData);
            $stmt = $this->dbMaster->prepare($sql);
            $stmt->execute();
            $chargeId = $this->dbMaster->lastInsertId();

            $log = array(
                'operator' => $admin['id'],
                'remark' => '后台添加充值：用户：' . $admin['user'] . '：充值 [' . $addData['c_coin'] . ']：信息：' . serialize($addData),
            );
            $this->adminLog->add($log);

            return $chargeId;
        } catch (PDOException $e) {
            Tools::error($e);
        }
    }

    /**
     * find audio class by id
     *
     * @param  int $id class id
     *
     * @return mixed     array or false
     */
    public function findById($id)
    {

        try {
            $sql = 'SELECT * FROM ' . $this->tableName . ' WHERE c_id = :id LIMIT 1';
            $stmt = $this->dbMaster->prepare($sql);
            $stmt->execute([':id' => $id]);

            $rs = $stmt->fetch(PDO::FETCH_ASSOC);

            return $rs ? CustomArray::removekeyPrefix($rs, 'c_') : false;
        } catch (PDOException $e) {
            die($e->getMessage());
        }
    }

    /**
     * update audio class
     *
     * @param  array $data update data
     *
     * @return mixed       false or rowcount
     */
    public function update($data)
    {
        if (!$data || !isset($data['id'])) {
            return false;
        }

        $id = $data['id'];
        unset($data['id']);
        $data = CustomArray::addKeyPrefix($data, 'c_');

        try {
            $sql = "UPDATE " . $this->tableName . ' SET ' . $this->makeSet($data) .
                ' WHERE c_id = :id LIMIT 1';
            $stmt = $this->dbMaster->prepare($sql);
            $stmt->execute([':id' => $id]);
            $res = $stmt->rowCount();

            return $res;
        } catch (PDOException $e) {
            die($e->getMessage());
        }
    }


}
