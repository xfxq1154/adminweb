<?php

/**
 * @name TestModel
 * @author why
 * @desc 测试用户管理
 */
class TestModel {

    use Trait_DB;

    use Trait_Redis;

    public $dbMaster, $dbSlave; //主从数据库 配置
    public $tableName = '`user`'; //数据表
    public $tableName_b = '`user_bind`'; //数据表
    public $adminLog;

    public function __construct() {
        $this->dbMaster = $this->getMasterDb('passport');
        $this->dbSlave = $this->getSlaveDb('passport');
        
    }

    public function getList($page_no, $page_size,$openid){
        $start = ($page_no - 1) * $page_size;
        $where = '1';
        if($openid){
            $where .= " b.b_openid = $openid ";
        }
        $sql = " SELECT a.*,b.b_uid,b_openid FROM $this->tableName a LEFT JOIN $this->tableName_b b ON a.u_id = b.b_uid WHERE $where LIMIT $start, $page_size ";
        $stmt = $this->dbSlave->prepare($sql);
        $stmt->execute();
        return $this->format_test_batch($stmt->fetchAll(PDO::FETCH_ASSOC)); 
    }
    
    /**
     * 格式化
     */
    public function tidy_test($user_info) {
        if(!$user_info){
            return array();
        }
        $user['id'] = $user_info['u_id'];
        $user['phone'] = $user_info['u_phone'];
        $user['nickname'] = $user_info['u_nickname'];
        $user['source'] = $user_info['u_source'];
        $user['regtime'] = $user_info['u_regtime'];
        $user['openid'] = $user_info['b_openid'];
        return $user;
    }
    
    /*
     * 格式化数据
     */
    public function tidy($showcase) {
        $s['showcase_id'] = $showcase['showcase_id'];
        $s['user_id'] = $showcase['user_id'];
        $s['name'] = $showcase['name'];
        $s['nickname'] = $showcase['nickname'];
        $s['signature'] = $showcase['signature'];
        $s['alias'] = $showcase['alias'];
        $s['logo'] = $showcase['logo'];
        $s['intro'] = $showcase['intro'];
        $s['status_person'] = $showcase['status_person'];
        $s['status_com'] = $showcase['status_com'];
        $s['status_com_name'] = $this->showcase_status[$showcase['status_com']];
        $s['block'] = $showcase['block'];
        $s['ctime'] = $showcase['ctime'];
        return $s;
    }

    public function format_showcase_struct($data) {
        if ($data === false) {
            return false;
        }
        if (empty($data)) {
            return array();
        }

        return $this->tidy($data);
    }

    public function format_test_batch($datas) {
        if ($datas === false) {
            return false;
        }
        if (empty($datas)) {
            return array();
        }
        foreach ($datas as &$data) {
            $data = $this->tidy_test($data);
        }
        return $datas;
    }
    
    private function _setError(){
        $error_info = Sapi::getError();
        $code = $error_info['code'];
        switch ($code){
            case 10006:
                $this->_error = 10002;
                break;
            case 10007:
                $this->_error = 10001;
                break;
            case 40001:
                $this->_error = 40001;
                break;
            case 40002:
                $this->_error = 40002;
                break;
            case 40003:
                $this->_error = 40003;
                break;
            case 40004:
                $this->_error = 40004;
                break;
            default :
                $this->_error = 10000;
                break;
        }
    }
    
    public function getError(){
        return $this->_error;
    }

}
