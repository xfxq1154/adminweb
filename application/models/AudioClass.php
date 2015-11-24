<?php

/**
 * AudioClassModel
 */
class AudioClassModel {

    use Trait_DB;

    public $dbMaster; //主从数据库 配置
    public $tableName = '`a_class`';
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
    public function getAudioClassList($limit = '') {

        try {
            $sql = 'SELECT * FROM ' . $this->tableName . ' ORDER BY c_order ASC, c_id ASC ' .
                    ($limit ? ' LIMIT ' . $limit : '');
            $stmt = $this->dbMaster->prepare($sql);
            $stmt->execute();

            $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $return = [];

            if ($rs) {
                foreach ($rs as $key => $val) {
                    $tmp = CustomArray::removekeyPrefix($val, 'c_');
                    if(isset($tmp['icon'])){
                        $tmp['icon'] = Tools::formatImg($tmp['icon']);
                    }
                    $return[$key] = $tmp;
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

        $data = CustomArray::addKeyPrefix($data, 'c_');

        try {
            $sql = 'INSERT INTO ' . $this->tableName . ' SET ' . $this->makeSet($data);
            $stmt = $this->dbMaster->prepare($sql);
            $stmt->execute();

            $adminuser = $_SESSION['a_user'];
            $log = array(
                'operator' => $adminuser['id'],
                'remark' => '添加音频分类: ' . $adminuser['user'] . ' 添加了信息: ' . serialize($data),
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
            $sql = 'SELECT * FROM ' . $this->tableName . ' WHERE c_id = :id LIMIT 1';
            $stmt = $this->dbMaster->prepare($sql);
            $stmt->execute([':id' => $id]);

            $rs = $stmt->fetch(PDO::FETCH_ASSOC);

            return $rs ? CustomArray::removekeyPrefix($rs, 'c_') : false;
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
        $data = CustomArray::addKeyPrefix($data, 'c_');

        try {
            $sql = "UPDATE " . $this->tableName . ' SET ' . $this->makeSet($data) .
                    ' WHERE c_id = :id LIMIT 1';
            $stmt = $this->dbMaster->prepare($sql);
            $stmt->execute([':id' => $id]);

            $adminuser = $_SESSION['a_user'];
            $log = array(
                'operator' => $adminuser['id'],
                'remark' => '修改音频分类: ' . $adminuser['user'] . ' 修改了信息[' . $id . ']: ' . serialize($data),
            );

            $this->adminLog->add($log);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            die($e->getMessage());
        }
    }

}
