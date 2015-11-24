<?php

/**
 * Audio Tag Model
 */
class AudioTagModel {

    use Trait_DB;

    public $dbMaster; //主从数据库 配置
    public $tableName = '`a_audio_tag`';
    public $adminLog;

    public function __construct() {
        $this->dbMaster = $this->getDb('audio');
        $this->adminLog = new AdminLogModel();
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

        $data = CustomArray::addKeyPrefix($data, 'at_');

        try {
            $sql = 'INSERT INTO ' . $this->tableName . ' SET ' . $this->makeSet($data);
            $stmt = $this->dbMaster->prepare($sql);
            $stmt->execute();

            $adminuser = $_SESSION['a_user'];
            $log = array(
                'operator' => $adminuser['id'],
                'remark' => '添加音频标签: ' . $adminuser['user'] . ' 添加了信息: ' . serialize($data),
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
            $sql = 'SELECT * FROM ' . $this->tableName . ' WHERE at_id = :id LIMIT 1';
            $stmt = $this->dbMaster->prepare($sql);
            $stmt->execute([':id' => $id]);

            $rs = $stmt->fetch(PDO::FETCH_ASSOC);

            return $rs ? CustomArray::removekeyPrefix($rs, 'at_') : false;
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
        $data = CustomArray::addKeyPrefix($data, 'at_');

        try {
            $sql = "UPDATE " . $this->tableName . ' SET ' . $this->makeSet($data) .
                    ' WHERE at_id = :id LIMIT 1';
            $stmt = $this->dbMaster->prepare($sql);
            $stmt->execute([':id' => $id]);

            $adminuser = $_SESSION['a_user'];
            $log = array(
                'operator' => $adminuser['id'],
                'remark' => '修改音频标签: ' . $adminuser['user'] . ' 修改了信息[' . $id . ']: ' . serialize($data),
            );

            $this->adminLog->add($log);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            die($e->getMessage());
        }
    }
    
    public function getTagByAid($aid,$limit = '20') {
        if(empty($aid)) return false;
        try {
            $sql = 'SELECT * FROM ' . $this->tableName . ' where at_aid= "'.$aid.'" ORDER BY at_id asc ' .
                    ($limit ? ' LIMIT ' . $limit : '');
            $stmt = $this->dbMaster->prepare($sql);
            $stmt->execute();

            $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $return = [];

            if ($rs) {
                foreach ($rs as $key => $val) {
                    $tids[] = $val['at_tid'];
                }
                
                if(!empty($tids) && is_array($tids)){
                    $tagMod = new TagModel();
                    $res = $tagMod->findByTids($tids);
                }
            }

            return $res;
        } catch (PDOException $e) {
            die($e->getMessage());
        }
    }
    public function delAudioTagByAid($aid) {
        if(empty($aid)) return false;
         try{
             $sql = ' delete FROM ' . $this->tableName . ' where at_aid= "'.$aid.'"  ';
             $stmt = $this->dbMaster->prepare($sql);
             $stmt->execute();
             return $stmt->rowCount();
         } catch (PDOException $e) {
            die($e->getMessage());
        }
    }

}
