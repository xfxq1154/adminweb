<?php

/**
 * @author why
 * @name worksModel
 * @desc 用户作品列表
 */
class WorksModel{
    
    use Trait_DB;
    
    public $dbMaster;
    public $tableName = '`poster`';
    
    public function __construct() {
        $this->dbMaster = $this->getDb('operate');
    }
    
    /**
     * 查看列表
     */
    public function getList($limit = '',$where = ''){
        
        try {
            $sql = 'SELECT * FROM ' . $this->tableName . ' where 1  '.$where.' ORDER BY id DESC ' .
                    ($limit ? ' LIMIT ' . $limit : '');
            $stmt = $this->dbMaster->prepare($sql);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            die($e->getMessage());
        }
    }
    
    /**
     * get count
     *
     * @return mixed int or false
     */
    public function getCount($where = '') {

        try {
            $sql = "SELECT COUNT(*) count FROM " . $this->tableName." where 1  ".$where;
            $stmt = $this->dbMaster->prepare($sql);
            $stmt->execute();
            $rs = $stmt->fetch(PDO::FETCH_ASSOC);

            return $rs ? $rs['count'] : false;
        } catch (PDOException $e) {
            Tools::error($e);
        }
    }
    
    /**
     * 修改操作
     */
    public function update($word_id,$where){
        if(empty($word_id)){
            return FALSE;
        }
        try {
            $sql = "UPDATE " .$this->tableName." SET `delete` = $where WHERE id = $word_id LIMIT 1";
            $stmt = $this->dbMaster->prepare($sql);
            return $stmt->execute();
        } catch (Exception $exc) {
            return FALSE;
        }
    }

}

