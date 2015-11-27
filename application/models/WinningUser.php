<?php

/**
 * WinningModel
 */
class WinningUserModel {

    use Trait_DB;

    public $dbMaster; //主从数据库 配置
    public $tableName = '`user`';
    public $adminLog;

    public function __construct() {
        $this->dbMaster = $this->getDb('operate');
        $this->adminLog = new AdminLogModel();
    }

    /**
     * 获取中奖用户列表
     *
     * @param string limit
     * @return array result
     */
    public function getList($limit = '',$where = '') {

        try {
            $sql = 'SELECT * FROM ' . $this->tableName . ' where 1  '.$where.' ORDER BY id DESC ' .
                    ($limit ? ' LIMIT ' . $limit : '');
            $stmt = $this->dbMaster->prepare($sql);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            die($e->getMessage());
        }
    }

    /**
     * get count
     *
     * @return mixed int or false
     */
    public function getCount($where = '') {

        try {
            $sql = "SELECT COUNT(*) count FROM " . $this->tableName." where 1  ".$where;
            $stmt = $this->dbMaster->prepare($sql);
            $stmt->execute();
            $rs = $stmt->fetch(PDO::FETCH_ASSOC);

            return $rs ? $rs['count'] : false;
        } catch (PDOException $e) {
            Tools::error($e);
        }
    }

}
