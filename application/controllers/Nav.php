<?php

/**
 * @name NavController
 * @desc 后台菜单控制器
 * @show
 * @author hph
 */
Class NavController Extends Base {

    use Trait_Layout;

    public function init() {
        $this->initAdmin();
        $this->tags = new TagsModel();
    }

    /**
     * 菜单列表
     */
    public function indexAction() {
        $this->checkRole();
        $navlist = $this->adminNav->getNavList();
        $navlist = $this->adminNav->formatTableNav($navlist);
        //Tools::output($navlist, 'print_r');


        $tags = $this->tags->selectAll();
        $this->assign('tags', $tags);
        $this->assign('list', $navlist);
        $this->layout('user/nav.phtml');
    }

    /**
     * 添加菜单
     */
    public function addAction() {
        $this->checkRole();

        $id = $this->getRequest()->getParam('id', 0);
        //添加菜单
        if ($_POST) {
            //Tools::output($_POST, 'print_r');
            $errormsg = '';
            if (empty($_POST['name'])) {
                $errormsg .= "分类名字不能为空！\\n";
            }
            if (empty($_POST['icon'])) {
                $errormsg .= "分类图标不能为空！\\n";
            }
            if (empty($_POST['controller'])) {
                $errormsg .= "控制器不能为空！";
            }
            if (empty($_POST['action'])) {
                $errormsg .= "Action不能为空！\\n";
            }
            if (!empty($errormsg)) {
                $data = array(
                    "info" => $errormsg,
                    "status" => 0,
                    "url" => "",
                );
                Tools::output($data);
            }
            $id = $this->adminNav->add($_POST);
            if ($id > 0) {
                $data = array(
                    "info" => '分类添加成功！',
                    "status" => 1,
                    "url" => "/nav/index",
                );
                Tools::output($data);
            }
        }

        $navlist = $this->adminNav->getNavList();
        $navlist = $this->adminNav->formatTableNav($navlist);
        //Tools::output($navlist, 'print_r');
        $this->assign('id', $id);
        $this->assign('list', $navlist);
        $this->layout('user/nav_add.phtml');
    }

    /**
     * 修改菜单
     */
    public function editAction($id) {
        $this->checkRole();
        $id = intval($id);
        $data = array(
            "info" => '参数错误！',
            "status" => 0,
            "url" => "",
        );
        if (!($id > 0)) {
            Tools::output($data);
        }

        //保存修改
        if ($_POST) {
            //Tools::output($_POST, 'print_r');
            $p_id = (int) $_POST['id'];
            if ($id != $p_id) {
                $data['info'] = '数据不一致！';
                Tools::output($data);
            }
            unset($_POST['id']);
            $row = $this->adminNav->update($id, $_POST);
            if ($row >= 0) {
                $return['info'] = '修改菜单成功！';
                $return['status'] = 1;
                $return['url'] = '/nav/index';
                Tools::output($return);
            }
            $data['info'] = '修改菜单出现未知错误！，请稍后重试！';
            Tools::output($data);
        }



        $navInfo = $this->adminNav->getNavById($id);
        if (!$navInfo) {
            $data['info'] = "菜单信息不存在！";
            Tools::output($data);
        }
        $this->assign('navInfo', $navInfo);
        $navlist = $this->adminNav->getNavList();
        $navlist = $this->adminNav->formatTableNav($navlist);
        $this->assign('list', $navlist);
        $this->layout('user/nav_edit.phtml');
    }

    /*     * *
     * 删除菜单
     */

    public function deleteAction() {
        $this->checkRole();
        //Tools::output($_POST,'print_r');
        $return = array(
            "info" => '删除失败！',
            "status" => 0,
            "url" => "",
        );
        $id = (int) $_POST['data'];
        if (!($id > 0)) {
            $return['info'] = '参数错误！';
            Tools::output($return);
        }

        //判断有没有 子分类 如果存在子分类 则返回错误提示信息
        $hasChild = $this->adminNav->getChildById($id);
        if ($hasChild) {
            $return['info'] = '有子分类存在，不能被删除！请先删除子分类后在操作！';
            Tools::output($return);
        }
        $row = $this->adminNav->delete($id);
        if ($row > 0) {
            $return['info'] = '删除分类成功！';
            $return['status'] = 1;
            Tools::output($return);
        }
        Tools::output($return);
    }

    public function ajaxChangeTagAction() {

        $id = (int) $this->getRequest()->getPost('id');
        $tid = $this->getRequest()->getPost('tid');

        if (!$id) {
            echo json_encode(['status' => 0]);
            exit;
        }

        $this->adminNav->update($id, ['tid' => $tid]);
        echo json_encode(['status' => 1]);
        exit;
    }

}
