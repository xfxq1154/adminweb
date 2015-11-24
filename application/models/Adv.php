<?php

/**
 * @name AdvModel
 * @desc 推荐广告位
 * @author hph
 */
class AdvModel {

    use Trait_DB,
        Trait_Redis;

    public $dbMaster, $dbSlave;
    public $adminLog;
    public $tableName = 'a_adv';

    public function __construct(){
        $this->dbMaster = $this->getDb('audio');
        $this->adminLog = new AdminLogModel();
    }

    public function add($data){
        if (empty($data) || !is_array($data))
            return false;
        $f = "";
        $v = "";
        $array = array();
        foreach ($data as $key => $value) {
            $f .= ",`a_" . $key . "`";
            $v .= ",:" . $key;
            $array[':' . $key] = $value;
        }

        $sql = " INSERT INTO " . $this->tableName . " (" . substr($f, 1) . ") "
                . "VALUES (" . substr($v, 1) . ") ";
        $stmt = $this->dbMaster->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
        $stmt->execute($array);
        $a_id = $this->dbMaster->lastInsertId();
        $adminuser = $_SESSION['a_user'];
        $log = array(
            'operator' => $adminuser['id'],
            'remark' => '后台添加用户：用户：' . $adminuser['user'] . ' 添加了广告内容[' . $a_id . ']' . serialize($data),
        );
        $this->adminLog->add($log);
        return $a_id;
    }

    public function update($id, $data){
        if (empty($data) || !is_array($data) || empty($id))
            return false;
        $f = "";
        $array = array();
        foreach ($data as $key => $value) {
            $f .= ",`a_" . $key . "`= :" . $key;
            $array[':' . $key] = $value;
        }

        $sql = " UPDATE " . $this->tableName . "  SET " . substr($f, 1) . "  WHERE `a_id` = {$id}";
        $stmt = $this->dbMaster->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
        $stmt->execute($array);
        $adminuser = $_SESSION['a_user'];
        $log = array(
            'operator' => $adminuser['id'],
            'remark' => '后台修改用户：用户：' . $adminuser['user'] . ' 修改了广告内容[' . $id . ']信息：' . serialize($data),
        );
        $this->adminLog->add($log);
        return $stmt->rowCount();
    }

    public function getAdvList($t=0, $fields = '*'){
        $where = '';
        $array = [];
        if($t > 0){
            $where .= ' and a_type = :t ';
            $array[':t'] = $t;
        }
        $sql = "SELECT {$fields} FROM {$this->tableName} WHERE 1 {$where} ORDER BY a_type,a_order";
        $stmt = $this->dbMaster->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
        $stmt->execute($array);
        $list = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $advlist = [];
        if($list){
            foreach ($list as $adv) {
                $tmp = CustomArray::removeKeyPrefix($adv, 'a_');
                if(isset($tmp['img'])){
                    $tmp['img'] = Tools::formatImg($tmp['img']);
                }
                $advlist[] = $tmp;
            }
        }
        return $advlist;
    }

    public function getAdvInfoById($id, $fields='*'){
        if(!$id) return false;

        try {

            $sql = 'SELECT ' . $fields . ' FROM ' . $this->tableName . ' WHERE a_id = :id LIMIT 1';
            $stmt = $this->dbMaster->prepare($sql);
            $stmt->execute([':id' => $id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $row ? CustomArray::removeKeyPrefix($row, 'a_') : false;

        } catch (PDOException $e) {
            die($e->getMessage());
        }
    }
}