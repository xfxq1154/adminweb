<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 * 
 */
class CommdityModel {
    
    use Trait_DB;
    use Trait_Redis;
    
    public $tableName = '`onsela_commodity`';
    public $dbMaster;
    public $adminLog;
    
    /*
     * 初始化
     */
    public function __construct() {
        $this->dbMaster = $this->getDb('youzan');
        $this->adminLog = new AdminLogModel();
    }
    
    /*
     * 分页获取
     */
    public function getCommdity($page = 1, $size = 20 ,$keyword){
        $p = $page > 0 ? $page : 1;
        $limit = ($p - 1) * $size . ',' . $size;
        $where = ' WHERE 1  ';
        if(!empty($keyword)){
             $where .= ' and title like \'%'.$keyword.'%\' ';
        }
        
        try {
            $sql = "SELECT * FROM ".$this->tableName .$where. " ORDER BY id DESC limit " . $limit;
            $stmt = $this->dbMaster->prepare($sql);
            $stmt->execute();

            $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return $rs;
        } catch (PDOException $e) {
            die($e->getMessage());
        }
    }
    
    /*
     * 查询总数
     */
    public function getData(){
        
        try {
            $sql = "SELECT * FROM ".$this->tableName;
            $stmt = $this->dbMaster->prepare($sql);
            $stmt->execute();
            
            $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $rs;
            
        } catch (PDOException $e) {
            die($e->getMessage());
        }
    }
    
    /*
     * 获取商品总数
     */
    public function getCountCommdity(){
        try {

            $sql = "  SELECT count(*) as num FROM " . $this->tableName . "    ";
            $stmt = $this->dbMaster->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row['num'];
        } catch (PDOException $e) {
            die($e->getMessage());
        }
    }
    
    /*
     * 添加商品
     */
    
    public function insert($data){
        
        if(empty($data)) FALSE ;
        
        try {
            $sql = 'INSERT INTO ' . $this->tableName . ' SET ' . $this->makeSet($data);
            $stmt = $this->dbMaster->prepare($sql);
            $stmt->execute();

            return $this->dbMaster->lastInsertId();
        } catch (PDOException $e) {
            die($e->getMessage());
        }
    }
}

