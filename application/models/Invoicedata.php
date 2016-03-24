<?php
/**
 * @author why
 * @desc 商家信息
 */
class InvoicedataModel{
    
    use Trait_DB;
    
    public $tableName = 'invoicedata';
    public $dbMaster;
    public $dbSlave;
    
    public function __construct() {
        $this->dbMaster = $this->getMasterDb('storecp_invoice');
        $this->dbSlave = $this->getSlaveDb('storecp_invoice');
    }
    
    public function insert($params){
        try {
            $sql = ' INSERT INTO '. $this->tableName . ' SET ' . $this->makeSet($params);
            $stmt = $this->dbMaster->prepare($sql);
            $stmt->execute();
            return $this->dbMaster->lastInsertId();
        } catch (Exception $exc) {
            echo $exc->getMessage();
        }
    }
    
    /**
     * 只查询最后添加的开票信息
     */
    public function getInfo(){
        try {
            $sql = ' SELECT * FROM '. $this->tableName. ' ORDER BY id DESC LIMIT 1';
            $stmt = $this->dbMaster->prepare($sql);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $ex) {
            echo $ex->getMessage();
        }
    }
}

