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
        $this->dbMaster = $this->getMasterDb('storecp_invoice');
        $this->dbSlave = $this->getSlaveDb('storecp_invoice');
    }
    
    public function getList($page_no, $page_size, $use_hax_next, $kw){
        
        $start = ($page_no - 1) * $page_size;
        
        try {
            $sql = 'SELECT * FROM `'.$this->tableName. '` LIMIT '. $start .','.$page_size;
            $stmt = $this->dbSlave->prepare($sql);
            $stmt->execute();
            $data['data'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $data['total_nums'] = $this->getCount();
        } catch (Exception $ex) {
            echo $ex->getMessage();
        }
        
        if ($use_hax_next) {
            $data['has_next'] = count($data['data']) < $page_size ? 0 : 1;
        }
        return $data;
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
     * 获取总数
     */
    public function getCount() {
        try {
            $sql = "SELECT count(*) as num FROM " . $this->tableName ;
            $stmt = $this->dbSlave->prepare($sql);
            $stmt->execute();
            $data = $stmt->fetch(PDO::FETCH_ASSOC);
            return $data['num'] ? : 0;
        } catch (Exception $ex) {
            Output::jsonStr(Error::ERROR_DB_EXCEPTION, $ex->getMessage());
        }
    }
    
    /**
     * 获取详情
     */
    public function getInfo($order_id){
        $where = ' `order_id` =  :order_id';
        $pdo_params[':order_id'] = $order_id;
        try {
            $sql = ' SELECT * FROM ' . $this->tableName. ' WHERE ' .$where;
            $stmt = $this->dbSlave->prepare($sql);
            $stmt->execute($pdo_params);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $ex) {
            echo $ex->getMessage();
        }
    }
    
    /**
     * 更新开票信息
     */
    public function update($order_id, $params){
        if(!$order_id || !$params){
            return array();
        }
        $f = '';
        $array = array(':order_id' => $order_id);
        foreach ($params as $key => $value) {
            //不传递则跳过
            if ($value === null) {
                continue;
            }
            $f .= ",`" . $key . "` = :$key";
            $array[':' . $key] = $value;
        }
        $sql = "UPDATE `" . $this->tableName . "` SET " . substr($f, 1) . " WHERE `order_id` = :order_id LIMIT 1";
        try {
            $stmt = $this->dbMaster->prepare($sql);
            $stmt->execute($array);
        } catch (Exception $ex) {
            Output::jsonStr(Error::ERROR_DB_EXCEPTION, $ex->getMessage());
        }
    }
}

