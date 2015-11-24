<?php

/**
 * @name AdminLogModel
 * @author hph
 * @desc 后台日志表
 */
class AdminLogModel {

    use Trait_DB;

use Trait_Redis;

    public $dbMaster, $dbSlave; //主从数据库 配置
    public $tableName = 'admin_log';

    public function __construct() {
        $this->dbMaster = $this->getMasterDb();
        $this->dbSlave = $this->getSlaveDb();
    }

    public function add($data) {
        if (empty($data) || !is_array($data)) {
            return false;
        }
        $f = "";
        $v = "";
        $array = array();
        foreach ($data as $key => $value) {
            $f .= ",`al_" . $key . "`";
            $v .= ",:" . $key;
            $array[':' . $key] = $value;
        }

        $sql = " INSERT INTO `" . $this->tableName . "` (" . substr($f, 1) . ") "
                . "VALUES (" . substr($v, 1) . ") ";
        $stmt = $this->dbMaster->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
        $stmt->execute($array);

        return $this->dbMaster->lastInsertId();
    }

}
