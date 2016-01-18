<?php

/**
 * @name AdminUserModel
 * @author hph
 * @desc 后台管理
 */
class AdminUserModel {

    use Trait_DB;

use Trait_Redis;

    public $dbMaster, $dbSlave; //主从数据库 配置
    public $tableName = '`admin_user`'; //数据表
    public $adminLog;

    public function __construct() {
        $this->dbMaster = $this->getMasterDb('storecp');
        $this->dbSlave = &$this->dbMaster;
        $this->adminLog = new AdminLogModel();
    }

    /**
     * 后台管理人员登录
     * @param int $id
     */
    public function Login($userInfo) {

        //本次登录信息
        $ip = IP::getRealIp();
        $date = date('Y-m-d H:i:s', time());

        $this->updateAdminUser($userInfo['id'], array('logon_ip' => $ip, 'logon_date' => $date));
        $log = array(
            'operator' => $userInfo['id'],
            'remark' => '后台登录：用户：' . $userInfo['id'] . '-' . $userInfo['user'] . ' 登录成功',
        );
        $this->adminLog->add($log);

        return $userinfo;
    }

    /**
     * 获取后台管理人员信息
     * @param type $user
     * @param type $fields
     */
    public function getUserById($id, $fields = '*') {
        $sql = "SELECT {$fields} FROM {$this->tableName} WHERE `a_id` = :id limit 1";
        $stmt = $this->dbMaster->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
        $stmt->execute(array(':id' => $id));
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            $row = array(
                'id' => $id,
                'group' => 0,
                'status' => 1,
                'logon_ip' => IP::getRealIp(),
                'logon_date' => date('Y-m-d H:i:s', time()),
            );
            $this->addAdminUser($row);
            return $row;
        }
        return CustomArray::removeKeyPrefix($row, 'a_');
    }

    /**
     * 添加后台管理人员
     * @param Array $data
     */
    public function addAdminUser($data) {
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
            'remark' => '自动关联用户：后台用户[' . $a_id . ']' . serialize($data),
        );
        $this->adminLog->add($log);
        return $a_id;
    }

    public function updateAdminUser($id, $data) {
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
            'remark' => '后台修改用户：用户：' . $adminuser['user'] . ' 修改了后台用户[' . $id . ']信息：' . serialize($data),
        );
        $this->adminLog->add($log);
        return $stmt->rowCount();
    }

    /**
     * 获取所有用户组，返回格式化格式
     *
     * @return array result
     */
    public function getGroups() {

        try {
            $sql = "SELECT g.g_id, g.g_name, u.a_id a_uid, u.a_status FROM " . $this->tableName .
                    " u INNER JOIN admin_group g ON (u.a_group = g.g_id)";

            $stmt = $this->dbMaster->prepare($sql);
            $stmt->execute();
            $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $return = [];

            if ($rs) {
                foreach ($rs as $val) {
                    $return[$val['a_uid']] = CustomArray::removeKeyPrefix($val, 'g_');
                }
            }

            return $return;
        } catch (PDOException $e) {
            die($e->getMessage());
        }
    }

    public function delByGroup($gid) {
        if (empty($gid))
            return -1;
        $sql = " update  " . $this->tableName . "  set `a_group` = 0  and `a_status` = 0 where  `a_group` = '{$gid}'  ";
        $stmt = $this->dbMaster->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
        $stmt->execute();
        return $stmt->rowCount();
    }

}
