<?php

/**
 * Created by PhpStorm.
 * User: wanghaiyang
 * Date: 16/3/30
 * Time: 下午7:53
 */
class SkuModel{

    use Trait_DB;

    public $tableName = 'sku';

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
     * @param $use_hax_next
     * @param $sku_id
     * @return mixed
     */
    public function getList($page_no, $page_size, $use_hax_next, $sku_id){
        $where = '1';

        if($sku_id){
            $where .= ' AND `sku_id` = :sku_id';
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
     * @param $ids
     * @param $fpsl
     * @return bool|int
     */
    public function updateSl($ids, $fpsl){
        if( !$ids || !$fpsl){
            return FALSE;
        }

        try {
            $sql = ' UPDATE `'. $this->tableName. "` SET `tax_tare` = :fpsl WHERE `id` IN ($ids) " ;
            $stmt = $this->dbMaster->prepare($sql);
            $stmt->execute(array(':fpsl' => $fpsl));
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