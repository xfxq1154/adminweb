<?php

/**
 * @name GroupController
 * @desc 后台角色控制器
 * @show 
 * @author hph
 */
class GroupController extends Base {

    use Trait_Layout;

    /**
     * 入口文件
     */
    public function init() {
        $this->initAdmin();
    }

    /**
     * 角色列表
     */
    public function indexAction() {
        $this->checkRole();

        $this->assign('list', $this->adminGroup->getGroupList());

        $this->layout('user/group.phtml');
    }

    /**
     * 添加角色
     */
    public function addAction() {
        $this->checkRole();
        if ($_POST['code']) {
            $res = $this->adminGroup->add(array('name' => $_POST['code']));
            $data = array(
                "info" => "添加失败",
                "status" => 0,
                "url" => "/Group/add",
            );

            if ($res >= 0) {
                $data = array(
                    "info" => "添加成功",
                    "status" => 1,
                    "url" => "/Group/index",
                );
            }


            Tools::output($data);
        }

        $this->layout('user/group_add.phtml');
    }

    /**
     * 编辑角色
     * @param type $id
     */
    public function editAction($id) {
        $this->checkRole();

        if ($_POST) { //用户角色 权限编辑
            $group_id = $_POST['group_id'];
            $menulist = $_POST['menu_purview'];
            $sublist = $_POST['submenu_purview'];
            $group_name = $_POST['name'];

            $nav = $menu = array();
            if ($menulist) {
                foreach ($menulist as $_l) {
                    $_l = explode('-', $_l);
                    $nav[$_l[0]] = $_l[0];
                    $menu[$_l[1]] = $_l[1];
                }
            }
            if ($sublist) {
                foreach ($sublist as $_l) {
                    $_l = explode('-', $_l);
                    $nav[$_l[0]] = $_l[0];
                    $menu[$_l[1]] = $_l[1];
                    if (isset($_l[2])) {
                        $menu[$_l[2]] = $_l[2];
                    }
                }
            }
            $data = array(
                'nav' => implode(',', $nav),
                'menu' => implode(',', $menu)
            );
            if (!empty($group_name)) {
                $data['name'] = $group_name;
            }
            $rowCount = $this->adminGroup->updateGroup($group_id, $data);
            if ($rowCount >= 0) {
                $data = array(
                    "info" => "用户角色权限修改成功！",
                    "status" => 1,
                    "url" => "/group/index"
                );
                Tools::output($data);
            }
            $data = array(
                "info" => "用户角色权限修改出现位置错误，请重试！",
                "status" => 0,
                "url" => ""
            );
            Tools::output($data);
        }


        $id = intval($id);
        $ginfo = $this->adminGroup->getGroupById($id);

        $navlist = $this->adminNav->getNavList();
        $navlist = $this->adminNav->formatListNav($navlist);

        //Tools::output($navlist, 'print_r');

        $this->assign('ginfo', $ginfo);
        $this->assign('navlist', $navlist);


        $this->layout('user/group_edit.phtml');
    }

    /**
     * 删除角色
     */
    public function deleteAction() {
        $this->checkRole();
        if ($_POST['data']) {
            $gid = $_POST['data'];
            $data = array(
                "info" => "禁止删除",
                "status" => 0,
                "url" => "/group/index"
            );

            $res = $this->adminGroup->del($gid);

            switch ($res) {
                case -1:
                    $data['info'] = '禁止删除';
                    $data['status'] = 0;
                    break;
                case 0:
                    $data['info'] = '禁止删除';
                    $data['status'] = 0;
                    break;

                default :
                    $data['info'] = '删除成功';
                    $data['status'] = 1;
                    break;
            }
            Tools::output($data);
        }
    }

}
