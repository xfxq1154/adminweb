<?php

/**
 * WinningModel
 */
class WinningUserModel {

    use Trait_DB;

    public $dbMaster; //主从数据库 配置
    public $tableName = '`user`';
    public $tableNmae_rank = '`ranking`';
    public $tableName_poster = '`poster`';
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
    public function total() {
        $sql = "SELECT a.*,b.name,b.nickname,b.phone FROM $this->tableName_poster a left join $this->tableName b on a.openid = b.openid WHERE `delete` = 1 ORDER BY uv DESC LIMIT 5";
        
        try {
            $stmt = $this->dbMaster->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            die($e->getMessage());
        }
    }
    
    /**
     * ranking 中奖用户
     */
    public function isWinning() {
        $sql = "SELECT a.*,b.name,b.nickname,b.phone FROM $this->tableNmae_rank a left join $this->tableName b on a.openid = b.openid WHERE rank >= 1 AND rank <= 3 AND update_time <> DATE_FORMAT(NOW(),'%Y-%m-%d')";
        
        try {
            $stmt = $this->dbMaster->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $exc) {
            return FALSE;
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
