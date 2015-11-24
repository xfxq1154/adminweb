<?php


class AudioTopicBookModel {

    use Trait_DB;

    public $dbMaster, $dbSlave; //主从数据库 配置
    public $tableName = 'a_audiotopic_book'; //数据表

    public function __construct() {
        $this->dbMaster = $this->getDb('audio');
    }

    public function insert($data) {

        if (!$data) return false;

        $data = CustomArray::addKeyPrefix($data, 'a_');

        try {
            $sql = 'INSERT INTO ' . $this->tableName . ' SET ' . $this->makeSet($data);
            $stmt = $this->dbMaster->prepare($sql);
            $stmt->execute();

            return $this->dbMaster->lastInsertId();
        } catch (PDOException $e) {
            Tools::error($e);
        }
    }

    /**
     * 删除记录
     *
     * @param      int  $mixedId  电子书id或是音频id
     * @param      int  $type     1 = 音频类型， 2 电子书类型
     *
     * @return     <type>
     */
    public function destory($mixedId, $type) {

        try {
            $sql = 'DELETE FROM ' . $this->tableName . ' WHERE a_mixed_id = :mixedid AND a_type = :type LIMIT 1';
            $stmt = $this->dbMaster->prepare($sql);
            $stmt->execute([':mixedid' => $mixedId, ':type' => $type]);

            return $stmt->rowCount();
        } catch (PDOException $e) {
            Tools::error($e);
        }
    }

    public function replace($mixedId, $date) {

        if (!$mixedId) return false;

        try {
            $sql = "replace into `a_audiotopic_book` (`a_mixed_id`,`a_type`,`a_date`) values ('{$mixedId}','2','{$date}')";
            $stmt = $this->dbMaster->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
            $stmt->execute();

            return $stmt->rowCount();
        } catch (PDOException $e) {
            Tools::error($e);
        }

    }
}