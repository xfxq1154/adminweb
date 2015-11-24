<?php

/**
 * AudioClassModel
 */
class UserBuyModel {

    use Trait_DB;

use Trait_Redis;

    public $dbMaster; //主从数据库 配置
    public $tableName = 'a_user_buy';
    public $adminLog;

    public function __construct() {
        $this->dbMaster = $this->getDb('audio');
        $this->adminLog = new AdminLogModel();
    }
    
    public function getTableName($uid) {
        return $this->tableName . '_' . substr(crc32($uid), -2);
    }

   
    /**
     * @desc 用户购买总数，状态为1的
     *
     * @param $userId
     * @return bool|int
     */
    public function getPurchased($userId) {

        if (!$userId) return false;

        $table = $this->getTableName($userId);
        try {
            $sql = 'SELECT SUM(b_price) total FROM `' . $table . '` WHERE b_uid = :userid AND b_pay_status = 1';
            $stmt = $this->dbMaster->prepare($sql);
            $stmt->execute([':userid' => $userId]);

            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $stmt->closeCursor();
            return isset($row['total']) ? $row['total'] : 0;

        } catch (PDOException $e) {
            Tools::error($e);
        }
    }
    
    
    /**
     * @desc 获取用户购买记录
     *
     * @param string $limit
     * @param array $condition required $condition['uid']
     * @param array $f 查询字段
     * @return array|bool
     */
    public function getList($limit = '', $condition = array(), $f = array()) {

        if (!isset($condition['uid']) || empty($condition['uid']) || !(int) $condition['uid']) return false;

        $fields = ' * ';
        if (is_array($f) && !empty($f)) {
            $fields = '';
            foreach ($f as $val) {
                $fields .= 'b_' . $val . ',';
            }
        }

        // condition
        $where = [];
        $whereStr = '';
        foreach ($condition as $key => $val) {
            $whereStr .= ' AND b_' . $key . ' = :' . $key;
            $where[':' . $key] = $val;
        }
        $fields = rtrim($fields, ',');

        $table = $this->getTableName($condition['uid']);
        try {
            $sql = 'SELECT ' . $fields . ' FROM `' . $table . '` WHERE 1 = 1 ' . $whereStr . ' ORDER BY b_utime DESC ' .
                    ($limit ? ' LIMIT ' . $limit : '');
            $stmt = $this->dbMaster->prepare($sql);
            $stmt->execute($where);

            $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $return = [];
            $stmt->closeCursor();
            if ($rs) {
                foreach ($rs as $key => $val) {
                    $return[$key] = CustomArray::removekeyPrefix($val, 'b_');
                }
            }

            return $return;
        } catch (PDOException $e) {
            Tools::error($e);
        }
    }
    
    
    public function getNumber($where='',$uid=0) {
        $table = $this->getTableName($uid);
        try {

            $sql = "  SELECT count(*) as num FROM `" . $table . "` where 1 {$where}    ";
            $stmt = $this->dbMaster->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row['num'];
        } catch (PDOException $e) {
            die($e->getMessage());
        }
    }

    
    
    
    
    

}
