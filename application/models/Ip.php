<?php

class IpModel {

    use Trait_DB;

    public $dbMaster; //主从数据库 配置
    public $tableName = '`b_ip`';
    public $adminLog;

    public function __construct() {

        $this->dbMaster = $this->getDb('audio');
        $this->adminLog = new AdminLogModel();
    }

    public function getList($limit = '') {

        try {
            $sql = 'SELECT * FROM ' . $this->tableName . ' ORDER BY b_id ASC ' .
                    ($limit ? ' LIMIT ' . $limit : '');
            $stmt = $this->dbMaster->prepare($sql);
            $stmt->execute();

            $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $return = [];

            if ($rs) {
                foreach ($rs as $key => $val) {
                    $return[$key] = CustomArray::removekeyPrefix($val, 'b_');
                }
            }

            return $return;
        } catch (PDOException $e) {
            die($e->getMessage());
        }
    }

    public function getCount() {

        try {
            $sql = "SELECT COUNT(*) count FROM " . $this->tableName;
            $stmt = $this->dbMaster->prepare($sql);
            $stmt->execute();
            $rs = $stmt->fetch(PDO::FETCH_ASSOC);

            return $rs ? $rs['count'] : false;
        } catch (PDOException $e) {
            Tools::error($e);
        }
    }


    public function insert($data) {

        if (!$data) {
            return false;
        }

        $data = CustomArray::addKeyPrefix($data, 'b_');

        try {
            $sql = 'INSERT INTO ' . $this->tableName . ' SET ' . $this->makeSet($data);
            $stmt = $this->dbMaster->prepare($sql);
            $stmt->execute();

            $adminuser = $_SESSION['a_user'];
            $log = array(
                'operator' => $adminuser['id'],
                'remark' => '添加授权方: ' . $adminuser['user'] . ' 添加了信息: ' . serialize($data),
            );

            $this->adminLog->add($log);

            return $this->dbMaster->lastInsertId();
        } catch (PDOException $e) {
            die($e->getMessage());
        }
    }


    public function findById($id) {

        try {
            $sql = 'SELECT * FROM ' . $this->tableName . ' WHERE b_id = :id LIMIT 1';
            $stmt = $this->dbMaster->prepare($sql);
            $stmt->execute([':id' => $id]);

            $rs = $stmt->fetch(PDO::FETCH_ASSOC);

            return $rs ? CustomArray::removekeyPrefix($rs, 'b_') : false;
        } catch (PDOException $e) {
            die($e->getMessage());
        }
    }

    public function update($data) {

        if (!$data || !isset($data['id'])) {
            return false;
        }

        $id = $data['id'];
        unset($data['id']);
        $data = CustomArray::addKeyPrefix($data, 'b_');

        try {
            $sql = "UPDATE " . $this->tableName . ' SET ' . $this->makeSet($data) .
                    ' WHERE b_id = :id LIMIT 1';
            $stmt = $this->dbMaster->prepare($sql);
            $stmt->execute([':id' => $id]);

            $adminuser = $_SESSION['a_user'];
            $log = array(
                'operator' => $adminuser['id'],
                'remark' => '修改授权方: ' . $adminuser['user'] . ' 修改了信息[' . $id . ']: ' . serialize($data),
            );

            $this->adminLog->add($log);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            die($e->getMessage());
        }
    }


}