<?php

/**
 * @name TestModel
 * @author why
 * @desc 测试用户管理
 */
class TestModel {

    use Trait_DB;

    public $dbMaster;
    public $user_table = 'user'; //数据表
    public $user_bind_table = 'user_bind'; //数据表
    public $user_info_table = 'user_info'; //数据表
    
    public function __construct() {
        $this->dbMaster = $this->getMasterDb('passport');
    }
    
    public function getUserBind($openids, $offset=0, $count=20){
        try {
            $start = $offset * $count;
            $sql = "SELECT `b_uid`, `b_nickname`, `b_type`, `b_openid`, `b_time` FROM `".$this->user_bind_table."` WHERE `b_openid` in ({$openids}) ORDER BY `b_id` DESC LIMIT {$start},{$count}";
            $stmt = $this->dbMaster->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $ex) {
           die($ex->getMessage());
        }
    }
    
    public function getUser($mobiles, $offset=0, $count=20){
        try {
            $start = $offset * $count;
            $sql = "SELECT `u_id`, `u_nickname`, `u_phone`, `u_source`, `u_regtime` FROM `".$this->user_table."` WHERE `u_phone` in ({$mobiles}) ORDER BY `u_id` DESC LIMIT {$start},{$count}";
            $stmt = $this->dbMaster->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $ex) {
           die($ex->getMessage());
        }
    }
    
    public function delete($uid){
        try {
            $sql = 'DELETE FROM ' . $this->user_table . ' WHERE `u_id`=:id limit 1';
            $stmt = $this->dbMaster->prepare($sql);
            $stmt->execute(array(':id' => $uid));
            
            $sql = 'DELETE FROM ' . $this->user_info_table . ' WHERE `i_uid`=:id limit 1';
            $stmt = $this->dbMaster->prepare($sql);
            $stmt->execute(array(':id' => $uid));
            
            $sql = 'DELETE FROM ' . $this->user_bind_table . ' WHERE `b_uid`=:id limit 1';
            $stmt = $this->dbMaster->prepare($sql);
            $res = $stmt->execute(array(':id' => $uid));
            return $res;
        } catch (Exception $ex) {
           die($ex->getMessage());
        }
    }
    

}
