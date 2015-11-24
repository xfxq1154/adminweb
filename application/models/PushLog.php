<?php

/**
 * @name PushLogModel
 * @author hph
 * @desc 推送日志 
 */
class PushLogModel {
   
    use Trait_DB;
    
    public $dbMaster;
    public $tableName = 'u_push_log';

    
    public function __construct(){
        $this->dbMaster = $this->getDb('audio');
    }
    
    public function add($data) {
        if (empty($data) || !is_array($data)) {
            return false;
        }
        $f = "";
        $v = "";
        $array = array();
        foreach ($data as $key => $value) {
            $f .= ",`p_" . $key . "`";
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