<?php
/**
 * @author why
 * @desc 有赞订单详情表
 */
class YouZanOrderDetailModel{
    
    use Trait_DB;
    
    public $dbMaster;
    public $dbSlave;
    
    public $tableName = 'y_youzan_order';
    
    public function __construct() {
        $this->dbMaster = $this->getMasterDb('youzan_order');
        $this->dbSlave = $this->getSlaveDb('youzan_order');
    }
    
    public function _getOrderDetail($order_id){
        $where = ' `o_trades_id` = :order_id ';
        $pdo_params[':order_id'] = $order_id;
        try {
            $sql = ' SELECT * FROM '.$this->tableName.' WHERE ' . $where;
            $stmt = $this->dbSlave->prepare($sql);
            $stmt->execute($pdo_params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $ex) {
            echo $ex->getMessage();
        }
    }
}

