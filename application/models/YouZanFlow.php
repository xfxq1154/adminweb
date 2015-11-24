<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
Yaf_Loader::import(ROOT_PATH . '/application/library/phpExcel/reader.php');
class YouZanFlowModel{
    
    use Trait_DB,
        Trait_Redis;
    
    public $dbMaster;
    public $adminLog;
    public $tableName = '`youzan_menu`';
    
    function __construct(){
        $this->dbMaster = $this->getDb('youzan');
        $this->adminLog = new AdminLogModel();
    }
    
    /*
     * 添加方法
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
    
    /*
     * 查询
     */
    
    public function getData($page = 1, $size = 20 ,$keyword,$time,$time_e){
        
        $p = $page > 0 ? $page : 1;
        $limit = ($p - 1) * $size . ',' . $size;
        $where = ' WHERE 1  ';
        if(!empty($keyword)){
             $where .= ' and title like \'%'.$keyword.'%\' ';
        }
        if(!empty($time)){
            $where .= " and date >= '$time' and date <= '$time_e' ";
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
    public function getCount($keyword,$time,$time_e){
        
        $where = ' WHERE 1  ';
        if(!empty($keyword)){
             $where .= ' and title like \'%'.$keyword.'%\' ';
        }
        if(!empty($time)){
            $where .= " and date >= '$time' and date <= '$time_e' ";
        }
        
        try {
            $sql = "SELECT COUNT(*) count FROM " . $this->tableName .$where;
            $stmt = $this->dbMaster->prepare($sql);
            $stmt->execute();
            $rs = $stmt->fetch(PDO::FETCH_ASSOC);

            return $rs ? $rs['count'] : false;
        } catch (PDOException $e) {
            Tools::error($e);
        }
    }
}
