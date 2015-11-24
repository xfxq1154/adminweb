<?php

class TagsModel {

    use Trait_DB;

    public $dbMaster; //主从数据库 配置
    public $tableName = '`tags`';
    public $adminLog;

    public function __construct() {
        $this->dbMaster = $this->getMasterDb();
        $this->adminLog = new AdminLogModel();
    }

    public function selectAll($limit = '') {

        try {
            $sql = 'SELECT * FROM ' . $this->tableName . ($limit ? ' LIMIT ' . $limit : '');;
            $stmt = $this->dbMaster->prepare($sql);
            $stmt->execute();

            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return $rows;

        } catch (PDOException $e) {

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

        try {

            $sql = 'INSERT INTO ' . $this->tableName . ' SET ' . $this->makeSet($data);
            $stmt = $this->dbMaster->prepare($sql);
            $stmt->execute();

            $adminuser = $_SESSION['a_user'];
            $log = array(
                'operator' => $adminuser['id'],
                'remark' => '添加标签: ' . $adminuser['user'] . ' 添加了信息: ' . serialize($data),
            );

            $this->adminLog->add($log);

            return $this->dbMaster->lastInsertId();
        } catch (PDOException $e) {
            die($e->getMessage());
        }
    }
}