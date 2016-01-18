<?php

/**
 * @name AdminModel
 * @author hph
 * @desc 后台管理
 */
class AdminModel {

    use Trait_DB,
        Trait_Redis;

    public $dbMaster, $dbSlave; //主从数据库 配置
    public $tableName = '`admin`'; //数据表
    public $adminLog;
    public $adminUser;

    public function __construct() {
        $this->dbMaster = $this->getMasterDb('storecp');
        $this->dbSlave = $this->getSlaveDb('storecp');
        $this->adminLog = new AdminLogModel();
        $this->adminUser = new AdminUserModel();
    }

    /**
     * 后台管理人员登录
     * @param type $user
     * @param type $passwd
     */
    public function adminLogin($user, $passwd) {

        $userinfo = $this->getAdminUser($user);

        if (!$userinfo) {
            $log = array(
                'operator' => '',
                'remark' => '后台登录：查询用户：' . $user . ' 不存',
            );
            $this->adminLog->add($log);
            return 0; //用户不存在
        }

        $userpass = $userinfo['password'];
        if (md5($passwd) != $userpass) {
            $log = array(
                'operator' => '',
                'remark' => '后台登录：用户：' . $user . ' 密码错误，尝试密码：' . $passwd,
            );
            $this->adminLog->add($log);
            return -1; //密码错误
        }
        $uid = $userinfo['id'];

        $adminInfo = $this->adminUser->getUserById($uid);

        if ($adminInfo['status'] != '1') {
            $log = array(
                'operator' => '',
                'remark' => '后台登录：用户：' . $user . ' 禁止登录',
            );
            $this->adminLog->add($log);
            return -2; //禁止用户登录
        }

        //处理登陆后session
        $data = array(
            'id' => $userinfo['id'],
            'user' => $userinfo['code'],
            'name' => $userinfo['name'],
            'group' => $adminInfo['group'],
            'logon_ip' => $adminInfo['logon_ip'],
            'logon_date' => $adminInfo['logon_date']
        );
        $_SESSION['a_user'] = $data;


        $this->adminUser->Login($data);
//        //本次登录信息
//        $ip = IP::getRealIp();
//        $date = date('Y-m-d H:i:s', time());
//        $this->updateAdminUser($userinfo['id'],array('logon_ip' => $ip,'logon_date' => $date));
//        $log = array(
//            'operator' => $userinfo['id'],
//            'remark'  => '后台登录：用户：'.$user.' 登录成功',
//         );
//         $this->adminLog->add($log);

        return 1;
    }

    /**
     * 获取后台管理人员信息
     * @param type $user
     * @param type $fields
     */
    public function getAdminUser($user, $fields = '*') {
        $sql = "SELECT {$fields} "
                . "FROM {$this->tableName} "
                . "WHERE `admin_code` = :user limit 1";
        $stmt = $this->dbSlave->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
        $stmt->execute(array(':user' => $user));
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? CustomArray::removeKeyPrefix($row, 'admin_') : false;
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
            $f .= ",`admin_" . $key . "`";
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
            'remark' => '后台添加用户：用户：' . $adminuser['user'] . ' 添加了后台用户[' . $a_id . ']' . serialize($data),
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
            $f .= ",`admin_" . $key . "`= :" . $key;
            $array[':' . $key] = $value;
        }

        $sql = " UPDATE " . $this->tableName . "  SET " . substr($f, 1) . "  WHERE `admin_id` = {$id}";
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
     * 查询admin用户
     * @return array        result
     */
    public function getAdminList() {
        try {

            $sql = 'SELECT admin_id, admin_code, admin_name, admin_wechat, admin_tel,
                admin_wechat_nickname FROM ' . $this->tableName;
            $stmt = $this->dbMaster->prepare($sql);
            $stmt->execute();
            $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $return = [];

            if ($rs) {
                foreach ($rs as $k => $v) {
                    $return[$k] = CustomArray::removeKeyPrefix($v, 'admin_');
                }
            }

            return $return;
        } catch (PDOException $e) {
            echo $e->getMessage();
            exit;
        }
    }

    /**
     * 获取一条admin用户
     *
     * @param  int $id 用户的id
     *
     * @return mixed     array or false
     */
    public function getAdminById($id) {
        if (!$id) {
            return false;
        }

        try {

            $sql = 'SELECT * FROM ' . $this->tableName . ' WHERE admin_id = :id LIMIT 1';
            $stmt = $this->dbMaster->prepare($sql);
            $stmt->execute([':id' => $id]);

            return CustomArray::removeKeyPrefix($stmt->fetch(PDO::FETCH_ASSOC), 'admin_');
        } catch (PDOException $e) {
            die($e->getMessage());
        }
    }

}
