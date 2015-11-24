<?php

/**
 * TagssModel
 */
class TagModel {

    use Trait_DB;

    public $dbMaster; //主从数据库 配置
    public $tableName = '`a_tag`';
    public $adminLog;

    public function __construct() {
        $this->dbMaster = $this->getDb('audio');
        $this->adminLog = new AdminLogModel();
    }

    /**
     * 获取音频分类列表
     *
     * @param string limit
     * @return array result
     */
    public function searchTag($keyword,$limit = '20') {
        if(empty($keyword)) return false;
        try {
            $sql = 'SELECT * FROM ' . $this->tableName . ' where t_name like "%'.$keyword.'%" ORDER BY t_id desc ' ;
            $stmt = $this->dbMaster->prepare($sql);
            $stmt->execute();

            $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $return = [];

            if ($rs) {
                foreach ($rs as $key => $val) {
                    $return[$key] = CustomArray::removekeyPrefix($val, 't_');
                }
            }

            return $return;
        } catch (PDOException $e) {
            die($e->getMessage());
        }
    }

    /**
     * get count
     *
     * @return mixed int or false
     */
    public function getCount() {

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

        $data = CustomArray::addKeyPrefix($data, 't_');

        try {
            $sql = 'INSERT INTO ' . $this->tableName . ' SET ' . $this->makeSet($data);
            $stmt = $this->dbMaster->prepare($sql);
            $stmt->execute();

            $adminuser = $_SESSION['a_user'];
            $log = array(
                'operator' => $adminuser['id'],
                'remark' => '添加标签: ' . $adminuser['user'] . ' 添加了信息: ' . serialize($data),
            );

            $this->adminLog->add($log);

            return $this->dbMaster->lastInsertId();
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
            $sql = 'SELECT * FROM ' . $this->tableName . ' WHERE t_id = :id LIMIT 1';
            $stmt = $this->dbMaster->prepare($sql);
            $stmt->execute([':id' => $id]);

            $rs = $stmt->fetch(PDO::FETCH_ASSOC);

            return $rs ? CustomArray::removekeyPrefix($rs, 't_') : false;
        } catch (PDOException $e) {
            die($e->getMessage());
        }
    }
    
    public function addNewTags($tags){
        if(empty($tags) || !is_array($tags)) return false;
        $tid = array();
        foreach ($tags as $v){
            $re = $this->findByName($v);
            if($re){
                $tid[] = $re['id'];
            }else{
                $res = $this->insert(array('name'=>$v));
                $tid[] = $res;
            }
        }
        return $tid;
    }


    public function findByName($name) {
       if(empty($name)) return false;
        try {
            $sql = 'SELECT * FROM ' . $this->tableName . ' WHERE t_name = "'.$name.'" LIMIT 1';
            $stmt = $this->dbMaster->prepare($sql);
            $stmt->execute();

            $rs = $stmt->fetch(PDO::FETCH_ASSOC);

            return $rs ? CustomArray::removekeyPrefix($rs, 't_') : false;
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
        $data = CustomArray::addKeyPrefix($data, 't_');

        try {
            $sql = "UPDATE " . $this->tableName . ' SET ' . $this->makeSet($data) .
                    ' WHERE t_id = :id LIMIT 1';
            $stmt = $this->dbMaster->prepare($sql);
            $stmt->execute([':id' => $id]);

            $adminuser = $_SESSION['a_user'];
            $log = array(
                'operator' => $adminuser['id'],
                'remark' => '修改标签: ' . $adminuser['user'] . ' 修改了信息[' . $id . ']: ' . serialize($data),
            );

            $this->adminLog->add($log);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            die($e->getMessage());
        }
    }
    
    public function findByTids($tids,$limit = '20') {
        if(empty($tids) || !is_array($tids)) return false;
        $tids = implode(',', $tids);
        try {
            $sql = 'SELECT * FROM ' . $this->tableName . ' where t_id in('.$tids.')  ';
            $sql.= " ORDER BY FIND_IN_SET(`t_id`, '{$tids}')";
            $sql.=$limit ? ' LIMIT ' . $limit : '';
            $stmt = $this->dbMaster->prepare($sql);
            $stmt->execute();

            $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $return = [];

            if ($rs) {
                foreach ($rs as $key => $val) {
                    $return[$key] = CustomArray::removekeyPrefix($val, 't_');
                }
            }

            return $return;
        } catch (PDOException $e) {
            die($e->getMessage());
        }
    }
    

}
