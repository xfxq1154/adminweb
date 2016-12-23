<?php

class ShzfSkuModel{

    use Trait_DB;
    public $tableName = 'shzf_sku';
    public $dbMaster;
    public $dbSlave;
    public $err_sku = '';

    /**
     * SkuModel constructor
     */
    public function __construct()
    {
        $this->dbMaster = $this->getMasterDb('storecp_invoice');
        $this->dbSlave = $this->getSlaveDb('storecp_invoice');
    }


    /**
     * @param $sku_id
     * @return array
     * @desc 根据多个skuid 查询编码
     */
    public function getInfoBySkuId($sku_id){
        try{
            $sql = ' SELECT * FROM `'.$this->tableName."` WHERE `sku_id` IN ($sku_id) ";
            $stmt = $this->dbSlave->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }catch (PDOException $ex){
            echo $ex->getMessage();
        }
    }

    /**
     * @param $params
     * @return mixed
     */
    public function insert($params){
        try {
            $sql = ' INSERT INTO '. $this->tableName . ' SET ' . $this->makeSet($params);
            $stmt = $this->dbMaster->prepare($sql);
            $stmt->execute();
            return $this->dbMaster->lastInsertId();
        } catch (Exception $exc) {
            $this->err_sku .= '<tr><td>'.$params['sku_id'].'</td><td>编码重复</td>></tr>';
            return false;
        }

    }

    /**
     * @param $page_no
     * @param $page_size
     * @param $sku_id
     * @param $sku_name
     * @return mixed
     */
    public function getList($page_no, $page_size, $sku_id = '', $sku_name = ''){
        $where = '1';

        if($sku_id){
            $where .= ' AND `sku_id` = :sku_id';
            $pdo_params[':sku_id'] = $sku_id;
        }
        if($sku_name){
            $where .= ' AND `product_name` LIKE :name';
            $pdo_params[':name'] = "%$sku_name%";
        }

        $start = ($page_no - 1) * $page_size;

        try {
            $sql = 'SELECT * FROM `'.$this->tableName. '` WHERE '. $where .' ORDER BY id DESC LIMIT '. $start .','.$page_size ;
            $stmt = $this->dbSlave->prepare($sql);
            $stmt->execute($pdo_params);
            $data['list'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $data['total_nums'] = $this->getCount($where,$pdo_params);
        } catch (Exception $ex) {
            echo $ex->getMessage();
        }
        return $data;
    }

    /**
     * @param $where
     * @param $pdo_params
     * @return int
     */
    public function getCount($where, $pdo_params) {
        try {
            $sql = "SELECT count(*) as num FROM " . $this->tableName . ' WHERE '. $where ;
            $stmt = $this->dbSlave->prepare($sql);
            $stmt->execute($pdo_params);
            $data = $stmt->fetch(PDO::FETCH_ASSOC);
            return $data['num'] ? : 0;
        } catch (Exception $ex) {
            echo $ex->getMessage();
            return false;
        }
    }

    /**
     * @param $ids
     * @param $fpsl
     * @param $label
     * @return bool|int
     */
    public function updateSl($ids, $fpsl, $label){
        if( !$ids && !$fpsl && !$label){
            return FALSE;
        }
        $val = " `tax_tare` = $fpsl ";
        if ($label){
            $val .= ", `label` = '$label' ";
        }
        try {
            $sql = " UPDATE " . $this->tableName . "  SET $val WHERE `id` IN ($ids) ";
            $stmt = $this->dbMaster->prepare($sql);
            $stmt->execute();
            return 1;
        } catch (Exception $ex) {
            echo $ex->getMessage();
        }
    }

    /**
     * @param $id
     * @return bool
     * @desc 删除sku编码
     */
    public function delete($id){
        try{
            $sql = ' DELETE FROM '.$this->tableName.' WHERE `id` = :id LIMIT 1 ';
            $stmt = $this->dbMaster->prepare($sql);
            return $stmt->execute([':id' => $id]);
        }catch (PDOException $ex){
            echo $ex->getMessage();
            return false;
        }
    }

    /**
     * @return array
     */
    public function getError(){
        if(isset($this->err_sku)){
            return $this->err_sku;
        }
        return array();
    }

}