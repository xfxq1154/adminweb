<?php

/**
 * Created by PhpStorm.
 * User: wanghaiyang
 * Date: 16/4/19
 * Time: 下午7:47
 * @desc 组合sku
 */
class CkdModel
{
    use Trait_DB;

    public $tableName = 'ckd';

    public $dbMaster;
    public $dbSlave;

    public $err_sku;

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
     */
    public function getInfoBySkuId($sku_id){
        try{
            $sql = ' SELECT * FROM `'.$this->tableName."` WHERE `parent_sku_id` IN ($sku_id) ";
            $stmt = $this->dbSlave->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }catch (PDOException $ex){
            echo $ex->getMessage();
        }
    }

    /**
     * @param $skuid
     * @return array|bool
     * @desc 根据指定skuid 获取金额
     */
    public function getMoney($skuid){
        try{
            $sql = ' SELECT kind_sku_id,payment FROM `'.$this->tableName."` WHERE `kind_sku_id` IN ($skuid) ";
            $stmt = $this->dbSlave->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }catch (PDOException $ex){
            echo $ex->getMessage();
            return false;
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
     * @param $use_hax_next
     * @param $sku_id
     * @return mixed
     */
    public function getList($page_no, $page_size, $use_hax_next, $sku_id = ''){
        $where = '1';

        if($sku_id){
            $where .= ' AND `kind_sku_id` = :sku_id';
            $pdo_params[':sku_id'] = $sku_id;
        }

        $start = ($page_no - 1) * $page_size;

        try {
            $sql = 'SELECT * FROM `'.$this->tableName. '` WHERE '. $where .' ORDER BY id DESC LIMIT '. $start .','.$page_size ;
            $stmt = $this->dbSlave->prepare($sql);
            $stmt->execute($pdo_params);
            $data['data'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $data['total_nums'] = $this->getCount($where,$pdo_params);
        } catch (Exception $ex) {
            echo $ex->getMessage();
        }

        if ($use_hax_next) {
            $data['has_next'] = count($data['data']) < $page_size ? 0 : 1;
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
            Output::jsonStr(Error::ERROR_DB_EXCEPTION, $ex->getMessage());
        }
    }

    /**
     * @param $id
     * @param $money
     * @return bool|int
     */
    public function update($id, $money){
        if( !$id || !$money){
            return FALSE;
        }

        try {
            $sql = ' UPDATE `'. $this->tableName. "` SET `payment` = :payment WHERE `id` = :id " ;
            $stmt = $this->dbMaster->prepare($sql);
            $stmt->execute(array(':payment' => $money,':id' => $id));
            return 1;
        } catch (Exception $ex) {
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