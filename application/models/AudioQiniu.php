<?php

/**
 * AudioClassModel
 */
class AudioQiniuModel {

    use Trait_DB;

use Trait_Redis;

    public $dbMaster; //主从数据库 配置
    public $tableName = '`a_qiniu`';
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
    public function getAudioList() {

        try {
            $sql = 'SELECT * FROM ' . $this->tableName . ' ORDER BY q_id ASC desc ';
            $stmt = $this->dbMaster->prepare($sql);
            $stmt->execute();

            $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $return = [];

            if ($rs) {
                foreach ($rs as $key => $val) {
                    $return[$key] = CustomArray::removekeyPrefix($val, 'q_');
                }
            }

            return $return;
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
        $data = CustomArray::addKeyPrefix($data, 'q_');

        try {
            $sql = 'INSERT INTO ' . $this->tableName . ' SET ' . $this->makeSet($data);
            $stmt = $this->dbMaster->prepare($sql);
            $stmt->execute();


            $log = array(
                'operator' => $adminuser['id'],
                'remark' => '后台添加转换音频：用户：' . $adminuser['user'] . ' 添加了转换音频记录信息：' . serialize($data),
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
            $sql = 'SELECT * FROM ' . $this->tableName . ' WHERE q_id = :id LIMIT 1';
            $stmt = $this->dbMaster->prepare($sql);
            $stmt->execute([':id' => $id]);

            $rs = $stmt->fetch(PDO::FETCH_ASSOC);

            return $rs ? CustomArray::removekeyPrefix($rs, 'q_') : false;
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
        $data = CustomArray::addKeyPrefix($data, 'q_');

        try {
            $sql = "UPDATE " . $this->tableName . ' SET ' . $this->makeSet($data) .
                    ' WHERE q_id = :id LIMIT 1';
            $stmt = $this->dbMaster->prepare($sql);
            $stmt->execute([':id' => $id]);

            return $stmt->rowCount();
        } catch (PDOException $e) {
            die($e->getMessage());
        }
    }

}
