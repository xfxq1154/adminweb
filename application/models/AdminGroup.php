<?php

/**
 * @name AdmingroupModel
 * @author hph
 * @desc 后台角色表
 */
class AdminGroupModel {

    use Trait_DB;

    use Trait_Redis;

    public $dbMaster, $dbSlave; //主从数据库 配置
    public $tableName = 'admin_group';
    public $adminLog;

    public function __construct() {
        $this->dbMaster = $this->getMasterDb('storecp');
        $this->adminLog = new AdminLogModel();
    }

    public function add($data) {
        if (empty($data) || !is_array($data))
            return false;
        $f = "";
        $v = "";
        $array = array();
        foreach ($data as $key => $value) {
            $f .= ",`g_" . $key . "`";
            $v .= ",:" . $key;
            $array[':' . $key] = $value;
        }

        $sql = " INSERT INTO `" . $this->tableName . "` (" . substr($f, 1) . ") "
                . "VALUES (" . substr($v, 1) . ") ";
        $stmt = $this->dbMaster->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
        $stmt->execute($array);

        return $this->dbMaster->lastInsertId();
    }

    public function getGroupById($id, $fields = '*') {
        $sql = "SELECT {$fields} FROM {$this->tableName} WHERE `g_id` = :id limit 1";
        $stmt = $this->dbMaster->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
        $stmt->execute(array(':id' => $id));
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? CustomArray::removeKeyPrefix($row, 'g_') : false;
    }

    public function getGroupList() {
        $sql = "SELECT * FROM {$this->tableName} WHERE 1";
        $stmt = $this->dbMaster->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
        $stmt->execute();
        $row = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $list = array();
        if (!$row)
            return $list;
        foreach ($row as $g) {
            $list[] = CustomArray::removeKeyPrefix($g, 'g_');
        }
        return $list;
    }

    public function updateGroup($id, $data) {
        if (empty($data) || !is_array($data) || empty($id))
            return false;
        $f = "";
        $array = array();
        foreach ($data as $key => $value) {
            $f .= ",`g_" . $key . "`= :" . $key;
            $array[':' . $key] = $value;
        }

        $sql = " UPDATE `" . $this->tableName . "`  SET " . substr($f, 1) . "  WHERE `g_id` = {$id}";
        $stmt = $this->dbMaster->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
        $stmt->execute($array);
        $adminuser = $_SESSION['a_user'];
        $log = array(
            'operator' => $adminuser['id'],
            'remark' => '后台修改用户角色权限：用户：' . $adminuser['user'] . ' 修改了后台用户角色[' . $id . ']信息：' . serialize($data),
        );
        $this->adminLog->add($log);
        return $stmt->rowCount();
    }

    public function del($gid) {
        if (empty($gid) || ($gid == '1'))
            return false;
        $sql = " delete  from  {$this->tableName}  where g_id = '{$gid}'  ";
        $stmt = $this->dbMaster->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
        $stmt->execute();
        $deleCount = $stmt->rowCount();

        //delete group user
        $AU = new AdminUserModel();
        $res = $AU->delByGroup($gid);
        $result = ($deleCount > 0 && ($res >= 0)) ? 1 : 0;

        $adminuser = $_SESSION['a_user'];
        $log = array(
            'operator' => $adminuser['id'],
            'remark' => '后台删除用户角色：用户：' . $adminuser['user'] . ' 删除了后台用户角色[' . $gid . ']信息：',
        );
        $this->adminLog->add($log);
        return $result;
    }

}
