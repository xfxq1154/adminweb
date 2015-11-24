<?php

/**
 * DataanalysisModule
 */

class DataanalysisModel {

    use Trait_DB;
    use Trait_Redis;

    public $dbMaster; //主从数据库 配置
    public $tableName;
    public $adminLog,$kdtApi;
    

    public function __construct() {
        $this->dbMaster = $this->getDb('youzan');
        $this->adminLog = new AdminLogModel();
    }
    
    /*
     * 查看方法
     */
    public function getData($tableName,$limit){
        
        $this->tableName = $tableName;
        if($limit){
            $where = 'limit '. $limit;
        }
        
        try {
            $sql = 'SELECT * FROM '. $this->tableName.' ORDER BY created DESC ' . $where;
            $stmt = $this->dbMaster->prepare($sql);
            $stmt->execute();
            
            $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $rs;
            
        } catch (PDOException $e) {
            echo $e->getMessage();
        }
            
    }
    
    /*
     *查询单个字段 
     */
    
    public function getOne($tableName,$where){
        
        $this->tableName = $tableName;
        
        $where = $where ? $where : '*'; 
        
        try{
            $sql = 'SELECT' .$where. 'FROM '. $this->tableName;
            $stmt = $this->dbMaster->prepare($sql);
            $stmt->execute();
            
            $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $rs;
            
        } catch (PDOException $e) {
            echo $e->getMessage();
        }
    }
    
    /*
     *查询粉丝数据表单个 
     */
    
    public function getDataRow($tableName,$limit){
        
        $this->tableName = $tableName;
        if($limit){
            $where .= 'limit '. $limit; 
        }
        
        try {
            $sql = 'SELECT * FROM '. $this->tableName.' ORDER BY follow_time DESC ' . $where;
            $stmt = $this->dbMaster->prepare($sql);
            $stmt->execute();
            
            $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $rs;
            
        } catch (PDOException $e) {
            echo $e->getMessage();
        }
    }
    
    /*
     * get count
     * 
     * @return mixed int or false
     */
    
    public function getCount($tableName){
        
        $this->tableName = $tableName;
        
        try {
            $sql = "SELECT COUNT(*) count FROM " . $this->tableName;
            $stmt = $this->dbMaster->prepare($sql);
            $stmt->execute();
            $rs = $stmt->fetch(PDO::FETCH_ASSOC);

            return $rs ? $rs['count'] : false;
        } catch (PDOException $e) {
            Tools::error($e);
        }
    }
    
    /*
     * 
     * 获取喜马拉雅数据列表
     */
    
    public function getHimalayaList($limit = '',$tableName){
        
        $this->tableName = $tableName;
        
        try {
            $sql = 'SELECT * FROM ' . $this->tableName .($limit ? ' LIMIT ' . $limit : '');
            $stmt = $this->dbMaster->prepare($sql);
            $stmt->execute();

            $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return $rs;
        } catch (PDOException $e) {
            die($e->getMessage());
        }
    }
}
