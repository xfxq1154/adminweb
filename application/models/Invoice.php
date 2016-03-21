<?php
/**
 * @author why
 * @desc 发票模型
 */
class InvoiceModel{
    
    use Trait_DB;
    
    public $tableName = 'invoice';
    public $dbMaster;
    public $dbSlave;
    
    public function __construct() {
        $this->dbMaster = $this->getMasterDb('store_invoice');
        $this->dbSlave = $this->getSlaveDb('store_invoice');
    }
    
    public function getList($page_no, $page_size, $use_hax_next, $kw){
        
        $start = ($page_no - 1) * $page_size;
        
        try {
            $sql = 'SELECT * FROM `'.$this->tableName. '` LIMIT '. $start .','.$page_size;
            $stmt = $this->dbSlave->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $ex) {
            echo $ex->getMessage();
        }
    }
}

