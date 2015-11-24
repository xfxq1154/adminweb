<?php

/**
 * @name AudioTopicModel
 * @desc 音频排期model
 * @author hphui
 */
class AudioTopicRecordModel {

    use Trait_DB;

    public $dbMaster;
    public $tableName = 'a_audio_topic_record';
    public $adminLog;

    /**
     * 搜索条件
     * @var array 
     */
    public $condition = array();

    /**
     * 查询条件
     * @var array 
     */
    public $where = array();

    public function __construct() {
        $this->dbMaster = $this->getDb('audio');
        $this->adminLog = new AdminLogModel();
    }

    public function add($data) {
        if (empty($data) || !is_array($data))
            return false;
        $f = "";
        $v = "";
        $array = array();
        foreach ($data as $key => $value) {
            $f .= ",`t_" . $key . "`";
            $v .= ",:" . $key;
            $array[':' . $key] = $value;
        }
        try {
            $sql = " INSERT INTO `" . $this->tableName . "` (" . substr($f, 1) . ") "
                    . "VALUES (" . substr($v, 1) . ") ";
            $stmt = $this->dbMaster->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
            $stmt->execute($array);
            $id = $this->dbMaster->lastInsertId();
            if ($id) {
                $adminuser = $_SESSION['a_user'];
                $log = array(
                    'operator' => $adminuser['id'],
                    'remark' => '添加音频排期：用户：' . $adminuser['user'] . ' 添加了一条音频排期：' . serialize($data),
                );
                $this->adminLog->add($log);
            }
            return $id;
        } catch (PDOException $e) {
            Tools::error($e);
        }
    }

    public function getRecordById($id, $fields = '*') {
        if (empty($id))
            return array();
        try {
            $sql = "SELECT {$fields} FROM {$this->tableName} WHERE `t_id` = :id limit 1";
            $stmt = $this->dbMaster->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
            $stmt->execute(array(':id' => $id));
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ? CustomArray::removeKeyPrefix($row, 't_') : array();
        } catch (PDOException $e) {
            Tools::error($e);
        }
    }

    public function getNumberByTid($tid, $fields = '*') {
        if (empty($tid))
            return array();
        try {
            $sql = "SELECT count(t_id) as num FROM {$this->tableName} WHERE `t_tid` = :id ";
            $stmt = $this->dbMaster->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
            $stmt->execute(array(':id' => $tid));
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row['num'];
        } catch (PDOException $e) {
            Tools::error($e);
        }
    }

    /**
     * 获取排期回滚历史记录
     * @return 
     */
    public function getList($tid, $limit = '10', $fields = "*") {

        try {
            $sql = "select {$fields} from " . $this->tableName . " WHERE 1 and t_tid=:tid  ORDER BY t_id desc LIMIT {$limit}";

            $stmt = $this->dbMaster->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
            $stmt->execute(array(':tid' => $tid));
            $array = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $stmt->closeCursor();

            $list = array();
            if ($array) {

                foreach ($array as $row) {
                    $list[] = CustomArray::removeKeyPrefix($row, 't_');
                }
            }
            return $list;
        } catch (PDOException $e) {
            Tools::error($e);
        }
    }

}
