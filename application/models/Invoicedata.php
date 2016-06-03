<?php
/**
 * @author why
 * @desc 商家信息
 */
class InvoicedataModel{
    
    use Trait_DB;
    
    public $tableName = 'invoicedata';
    public $tableNameData = 'data';
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

    /**
     * @param $time
     * @return mixed
     * @desc 查询统计数据按照月份
     */
    public function getData($time){
        try {
            $sql = 'SELECT * FROM '.$this->tableNameData." WHERE createtime LIKE '%$time%'";
            $stmt = $this->dbMaster->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $ex) {
            echo $ex->getMessage();
        }
    }

    /**
     * @param $params
     * @return string
     * @desc 添加统计数据
     */
    public function insertData($params){
        try {
            $sql = ' INSERT INTO '. $this->tableNameData . ' SET ' . $this->makeSet($params);
            $stmt = $this->dbMaster->prepare($sql);
            $stmt->execute();
            return $this->dbMaster->lastInsertId();
        } catch (Exception $exc) {
            echo $exc->getMessage();
        }
    }

    /**
     * @return array
     * @desc 查看最后一条时间
     */
    public function getTime(){
        try {
            $sql = 'SELECT createtime FROM '.$this->tableNameData.' ORDER BY createtime DESC LIMIT 1';
            $stmt = $this->dbMaster->prepare($sql);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $exc) {
            echo $exc->getMessage();
        }
    }

    /**
     * @param $year
     * @param $month
     * @return array|bool
     */
    public function getDataByTime($year, $month){
        try {
            $sql = 'SELECT `id`,`type` FROM '.$this->tableNameData." WHERE year(createtime) = $year AND month(createtime) = $month LIMIT 2";
            $stmt = $this->dbMaster->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $exc) {
            echo $exc->getMessage();
            return false;
        }
    }

    /**
     * @param $id
     * @param $params
     * @return bool|int
     * @desc 更新统计数据
     */
    public function updateData($id, $params){
        try {
            $sql = 'UPDATE `'.$this->tableNameData. '` SET '.$this->makeSet($params). ' WHERE id = :id';
            $stmt = $this->dbMaster->prepare($sql);
            $stmt->execute([':id' => $id]);
            return 1;
        } catch (Exception $exc) {
            echo $exc->getMessage();
            return false;
        }
    }
}

