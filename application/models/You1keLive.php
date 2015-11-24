<?php

/** 
 * @name You1keliveModel
 * @author why
 * @desc 又一课课程列表模型
 */
class You1keLiveModel{
    
    use Trait_DB,
        Trait_Redis;
    
    public $dbMaster;
    public $tableName = '`classes_watchlog`';
    public $adminLog;
    
    public function __construct() {
        $this->dbMaster = $this->getDb('live');
        $this->adminLog = new AdminLogModel();
    }
    
    public function getCountLive($ctime,$etime){
        
        if(!$ctime ||!$etime){
            return FALSE;
        }
        
        $sql = "SELECT count(*) as nums,creation_time FROM " . $this->tableName . " WHERE creation_time >= '" . $ctime . "' AND " . " creation_time <= '". $etime . "' GROUP BY date(creation_time)";
        try {
            $stmt = $this->dbMaster->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $exc) {
            die($exc->getMessage());
        }
    }
}

