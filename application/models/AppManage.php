<?php


class AppManageModel {

    use Trait_DB;

    private $dbMaster, $dbSlave, $tableName = 'a_app_package';

    public function __construct() {
        $this->dbMaster = $this->getDb('audio');
        $this->dbSlave = $this->getDb('audio');
    }


    public function getList($limit = '') {

        try {
            $sql = 'SELECT * FROM ' . $this->tableName . ' ORDER BY a_id DESC ' .
                    ($limit ? ' LIMIT ' . $limit : '');
            $stmt = $this->dbSlave->prepare($sql);
            $stmt->execute();

            $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $return = [];

            if ($rs) {
                foreach ($rs as $key => $val) {
                    $return[$key] = CustomArray::removekeyPrefix($val, 'a_');
                }
            }

            return $return;
        } catch (PDOException $e) {
            die($e->getMessage());
        }
    }

    /**
     * get count
     *
     * @return mixed int or false
     */
    public function getCount() {

        try {
            $sql = "SELECT COUNT(*) count FROM " . $this->tableName;
            $stmt = $this->dbSlave->prepare($sql);
            $stmt->execute();
            $rs = $stmt->fetch(PDO::FETCH_ASSOC);

            return $rs ? $rs['count'] : false;
        } catch (PDOException $e) {
            Tools::error($e);
        }
    }

    public function insert($data) {

        if (!$data || empty($data)) return false;

        $data = CustomArray::addKeyPrefix($data, 'a_');
        try {

            $sql = 'INSERT INTO ' . $this->tableName . ' SET ' . $this->makeSet($data);
            $stmt = $this->dbMaster->prepare($sql);
            $stmt->execute();
            return $this->dbMaster->lastInsertId();

        } catch (Exception $e) {
            Tools::error($e);
        }
    }

    public function findById($id) {

        if (!$id) return false;

        try {
            $sql = 'SELECT * FROM ' . $this->tableName . ' WHERE a_id = :id LIMIT 1';
            $stmt = $this->dbSlave->prepare($sql);
            $stmt->execute([':id' => $id]);

            $rs = $stmt->fetch(PDO::FETCH_ASSOC);

            return $rs ? CustomArray::removekeyPrefix($rs, 'a_') : false;
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
        $data = CustomArray::addKeyPrefix($data, 'a_');

        try {
            $sql = "UPDATE " . $this->tableName . ' SET ' . $this->makeSet($data) .
                    ' WHERE a_id = :id LIMIT 1';
            $stmt = $this->dbMaster->prepare($sql);
            $stmt->execute([':id' => $id]);

            return true;
        } catch (PDOException $e) {
            Tools::error($e);
        }
    }

}