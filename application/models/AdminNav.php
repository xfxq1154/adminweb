<?php

/**
 * @name AdminnavModel
 * @author hph
 * @desc 后台菜单表
 */
class AdminNavModel {

    use Trait_DB;

    use Trait_Redis;

    public $dbMaster, $dbSlave; //主从数据库 配置
    public $tableName = 'admin_nav';
    public $group, $adminLog;

    public function __construct() {
        $this->dbMaster = $this->getMasterDb();
        $this->dbSlave = $this->getSlaveDb();
        $this->group = new AdminGroupModel();
        $this->adminLog = new AdminLogModel();
    }

    public function add($data) {
        if (empty($data) || !is_array($data))
            return false;
        $f = "";
        $v = "";
        $array = array();
        $data['order'] = $this->getMaxOrderByCid($data['cid']);
        foreach ($data as $key => $value) {
            $f .= ",`nav_" . $key . "`";
            $v .= ",:" . $key;
            $array[':' . $key] = $value;
        }

        $sql = " INSERT INTO `" . $this->tableName . "` (" . substr($f, 1) . ") "
                . "VALUES (" . substr($v, 1) . ") ";
        $stmt = $this->dbMaster->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
        $stmt->execute($array);
        $id = $this->dbMaster->lastInsertId();

        if ($id) {
            $adminuser = $_SESSION['a_user'];
            $log = array(
                'operator' => $adminuser['id'],
                'remark' => '添加菜单：用户：' . $adminuser['user'] . ' 添加了后台菜单：' . serialize($data),
            );
            $this->adminLog->add($log);
        }

        return $id;
    }

    public function update($id, $data) {
        if (empty($data) || !is_array($data) || empty($id))
            return false;
        $f = "";
        $array = array();
        foreach ($data as $key => $value) {
            $f .= ",`nav_" . $key . "`= :" . $key;
            $array[':' . $key] = $value;
        }

        $sql = " UPDATE " . $this->tableName . "  SET " . substr($f, 1) . "  WHERE `nav_id` = {$id}";
        $stmt = $this->dbMaster->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
        $stmt->execute($array);
        $adminuser = $_SESSION['a_user'];
        $log = array(
            'operator' => $adminuser['id'],
            'remark' => '修改菜单：用户：' . $adminuser['user'] . ' 修改了后台菜单[' . $id . ']信息：' . serialize($data),
        );
        $this->adminLog->add($log);
        return $stmt->rowCount();
    }

    public function delete($id) {
        if (empty($id))
            return false;
        $sql = "DELETE FROM {$this->tableName} WHERE `nav_id` = :id Limit 1";
        $stmt = $this->dbMaster->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
        $stmt->execute(array(':id' => $id));
        $row = $stmt->rowCount();
        if ($row > 0) {
            $adminuser = $_SESSION['a_user'];
            $log = array(
                'operator' => $adminuser['id'],
                'remark' => '删除菜单：用户：' . $adminuser['user'] . ' 删除了后台菜单：' . $id,
            );
            $this->adminLog->add($log);
        }
        return $stmt->rowCount();
    }

    public function getMaxOrderByCid($cid){
        $sql = "SELECT max(nav_order) `order` FROM {$this->tableName} WHERE `nav_cid` = :cid  limit 1";
        $stmt = $this->dbMaster->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
        $stmt->execute(array(':cid' => $cid));
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return 0;
        }
        return $row['order'] + 1;
    }

    public function getNavByRole($controller, $action) {
        $sql = "SELECT * FROM {$this->tableName} WHERE `nav_controller` = :controller AND `nav_action` = :action limit 1";
        $stmt = $this->dbMaster->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
        $stmt->execute(array(':controller' => $controller, ':action' => $action));
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return false;
        }
        return CustomArray::removeKeyPrefix($row, 'nav_');
    }

    public function getNavById($id) {
        $sql = "SELECT * FROM {$this->tableName} WHERE `nav_id` = :id  limit 1";
        $stmt = $this->dbMaster->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
        $stmt->execute(array(':id' => $id));
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return false;
        }
        return CustomArray::removeKeyPrefix($row, 'nav_');
    }

    public function getNavList($id = 0) {
        $list = array();
        $where = '1';

        if ($id > 0) {
            $g_info = $this->group->getGroupById($id);
            if (!$g_info)
                return $list;
            //nav menu
            $nav = $g_info['nav'];
            $menu = $g_info['menu'];
            if (empty($nav) || empty($menu))
                return $list;
            $where .= " and `nav_id` in ({$nav},{$menu}) ";
        }

        $sql = "SELECT * FROM {$this->tableName} WHERE {$where}  order by nav_cid,nav_order ";
        $stmt = $this->dbSlave->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
        $stmt->execute();
        $array = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if ($array) {
            foreach ($array as $row) {
                $list[] = CustomArray::removeKeyPrefix($row, 'nav_');
            }
        }
        return $list;
    }

    public function formatTableNav($list) {
        $_n = $_l = array();
        if (is_array($list)) {
            foreach ($list as $nav) {
                $_l[$nav['cid']][$nav['id']] = $nav;
            }
        }
        return $this->getTableList(0, $_l);
    }

    public function formatListNav($list) {
        $_n = $_l = array();
        if (is_array($list)) {
            foreach ($list as $nav) {

                $_l[$nav['cid']][$nav['id']] = array(
                    "id" => $nav['id'],
                    'name' => $nav['name'],
                    'cid' => $nav['cid']
                );
            }
        }
        return $this->getChildList(0, $_l);
    }

    public function getChildList($child = 0, $list, $depth = 1) {
        //static $_list;
        if (!$list[$child])
            return array();
        foreach ($list[$child] as $id => $navlist) {
            $navlist['depth'] = $depth;
            $navlist['menu'] = $this->getChildList($id, $list, $depth + 1);
            $_list[] = $navlist;
        }
        return $_list;
    }

    public function getTableList($child = 0, $list, $depth = 1) {
        static $_list;
        if (!$list[$child])
            return array();
        foreach ($list[$child] as $id => $navlist) {
            $navlist['depth'] = $depth;
            $navlist['depthstr'] = str_repeat("｜　　", $depth - 1);
            if ($depth > 1) {
                $navlist['depthstr'] .= '├  ';
            }
            $_list[] = $navlist;
            $this->getTableList($id, $list, $depth + 1);
        }
        return $_list;
    }

    /**
     * 获取 当前分类 下的子分类
     */
    public function getChildById($id) {
        $sql = "SELECT * FROM {$this->tableName} WHERE `nav_cid` = :id ";
        $stmt = $this->dbMaster->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
        $stmt->execute(array(':id' => $id));
        $array = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $list = array();
        if ($array) {
            foreach ($array as $row) {
                $list[] = CustomArray::removeKeyPrefix($row, 'nav_');
            }
        }
        return $list;
    }

    /**
     *
     */
    public function formatIconNav($list) {
        $_n = $_l = array();
        if (is_array($list)) {
            foreach ($list as $nav) {
                if ($nav['cid'] == 0) {
                    $_n[$nav['id']] = array(
                        "name" => $nav['name'],
                        "icon" => $nav['icon'],
                        "order" => $nav['id'],
                        "menu" => array()
                    );
                } else {
                    $_l[$nav['cid']][$nav['order']] = array(
                        "name" => $nav['name'],
                        "icon" => $nav['icon'],
                        "order" => $nav['id'],
                        "tid" => $nav['tid'],
                        "url" => '/' . $nav['controller'] . '/' . $nav['action']
                    );
                }
            }
        }
        $_list = array();
        foreach ($_n as $k => $v) {
            $v['menu'] = $_l[$k];
            $_list[] = $v;
        }
        return $_list;
    }

}
