<?php
/**
 * @author why
 * @desc 有赞订单
 */
class YouZanOrderModel{
    
    use Trait_DB;
    
    public $dbMaster;
    public $dbSlave;
    
    public $tableName = 'y_youzan_trades';
    
    public function __construct() {
        $this->dbMaster = $this->getMasterDb('youzan_order');
        $this->dbSlave = $this->getSlaveDb('youzan_order');
    }


    public function getInfo($order_id){
        $where = '';
        $pdo_params = [];
        $where .= ' y_tid = :order_id';
        $pdo_params[':order_id'] = $order_id;
        try {
            $sql = " SELECT * FROM " .$this->tableName . ' WHERE' . $where . ' LIMIT 1';
            $stmt = $this->dbSlave->prepare($sql);
            $stmt->execute($pdo_params);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $ex) {
            echo $ex->getMessage();
        }
    }
}

