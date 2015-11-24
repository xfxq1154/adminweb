<?php

/**
 * AudioClassModel
 */
class FeedbackModel {

    use Trait_DB;

use Trait_Redis;

    public $dbMaster; //主从数据库 配置
    public $tableName = '`a_freeback`';
    public $adminLog;

    public function __construct() {
        $this->dbMaster = $this->getDb('audio');
        $this->adminLog = new AdminLogModel();
    }

    /**
     * 获取音频分类列表
     *
     * @return array result
     */
    public function getList($page = 1, $size = 20,$where = '') {
        $p = $page > 0 ? $page : 1;
        $limit = ($p - 1) * $size . ',' . $size;

        try {
            $sql = 'SELECT * FROM ' . $this->tableName . ' where 1  '.$where.' ORDER BY f_id desc  limit ' . $limit;
            $stmt = $this->dbMaster->prepare($sql);
            $stmt->execute();

            $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $return = [];

            if ($rs) {
                foreach ($rs as $key => $val) {
                    $return[$key] = CustomArray::removekeyPrefix($val, 'f_');
                }
            }

            return $return;
        } catch (PDOException $e) {
            die($e->getMessage());
        }
    }

    

    public function getNumber($where='') {
        try {

            $sql = "  SELECT count(*) as num FROM " . $this->tableName . " where 1 {$where}    ";
            $stmt = $this->dbMaster->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row['num'];
        } catch (PDOException $e) {
            die($e->getMessage());
        }
    }

    

    

    /**
     * 添加音频分类
     *
     * @param  array $data add data
     *
     * @return int       rowcount
     */
    public function insert($data) {

        if (!$data) {
            return false;
        }
        $adminuser = $_SESSION['a_user'];
        $data['operator_id'] = $adminuser['id'];
        $data['operator_name'] = $adminuser['name'];
        $data = CustomArray::addKeyPrefix($data, 'f_');

        try {
            $sql = 'INSERT INTO ' . $this->tableName . ' SET ' . $this->makeSet($data);
            $stmt = $this->dbMaster->prepare($sql);
            $stmt->execute();
            $newid = $this->dbMaster->lastInsertId();

            $log = array(
                'operator' => $adminuser['id'],
                'remark' => '后台添加音频：用户：' . $adminuser['user'] . ' 添加了音频标题为[' . $data['title'] . ']信息：' . serialize($data),
            );
            $this->adminLog->add($log);

            
            return $newid;
        } catch (PDOException $e) {
            die($e->getMessage());
        }
    }

    /**
     * find audio class by id
     *
     * @param  int $id class id
     *
     * @return mixed     array or false
     */
    public function findById($id) {

        try {
            $sql = 'SELECT * FROM ' . $this->tableName . ' WHERE f_id = :id LIMIT 1';
            $stmt = $this->dbMaster->prepare($sql);
            $stmt->execute([':id' => $id]);

            $rs = $stmt->fetch(PDO::FETCH_ASSOC);

            return $rs ? CustomArray::removekeyPrefix($rs, 'f_') : false;
        } catch (PDOException $e) {
            die($e->getMessage());
        }
    }

    /**
     * update audio class
     *
     * @param  array $data update data
     *
     * @return mixed       false or rowcount
     */
    public function update($data) {

        if (!$data || !isset($data['id'])) {
            return false;
        }

        $id = $data['id'];
        unset($data['id']);
        $data = CustomArray::addKeyPrefix($data, 'f_');

        try {
            $sql = "UPDATE " . $this->tableName . ' SET ' . $this->makeSet($data) .
                    ' WHERE f_id = :id LIMIT 1';
            $stmt = $this->dbMaster->prepare($sql);
            $stmt->execute([':id' => $id]);
            $res = $stmt->rowCount();
            
            return $res;
        } catch (PDOException $e) {
            die($e->getMessage());
        }
    }

    
    
    
    
    

}
