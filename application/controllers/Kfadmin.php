<?php
/**
 * @name: Kfadmin.php
 * @time: 2016-07-01 上午11:36
 * @author: liuxuefeng
 * @desc:
 */

class KfadminController extends Base {
    use Trait_Layout,
        Trait_Pagger;
    /**
     * @var KfadminModel
     */
    public $kfadmin_model;
    
    const PAGE_SIZE = 20;
    const SUCCESS = 1; //执行成功
    const DISABLE_USER = 2;

    private $reback_msg = ['info' => '操作失败', 'status' => 0];

    function init() {
        $this->initAdmin();
        $this->checkRole();
        $this->kfadmin_model = new KfadminModel();
    }

    /**
     * 优惠券页面
     */
    public function indexAction() {
    }

    /**
     * 获取用户列表
     */
    public function getUserListAction() {
        $result = $this->kfadmin_model->getUserList();

        $this->assign('list', $result);
        $this->layout('kfadmin/user.phtml');

    }

    /**
     * 添加用户页面
     */
    public function addUserTplAction() {
        //获取身份列表
        $group_list = $this->kfadmin_model->getGroupList();

        $this->assign('group_list', $group_list);
        $this->layout('kfadmin/adduser.phtml');
    }

    /**
     * 添加账号
     */
    public function addUserAction()
    {
        $username = $this->getRequest()->getpost('username'); //账号
        $password = $this->getRequest()->getpost('password'); //密码
        $group = $this->getRequest()->getpost('group'); //身份

        if (!$username || !$password) {
            Tools::success('error', '缺少必要参数');
        }

        $params = [
            'username' => $username,
            'password' => $password,
            'auth'     => 1,
        ];

        $result = $this->kfadmin_model->addUser($params);
        if ($result) {
            $this->reback_msg = [
                'info'   => '添加成功',
                'status' => self::SUCCESS,
                'url'    => '/kfadmin/getuserlist'
            ];
        }
        $this->reback_msg();
    }

    /**
     * 账号禁用
     */
    public function disableUserAction()
    {
        $user_id = $_POST['user_id']; //账号

        if (!$user_id) {
            Tools::success('error', '缺少必要参数');
        }

        $params = [
            'user_id' => $user_id,
            'status'  => self::DISABLE_USER,
        ];

        $result = $this->kfadmin_model->disableUser($params);
        if ($result) {
            $this->reback_msg = [
                'info'   => '禁用成功',
                'status' => self::SUCCESS,
            ];
        }
        $this->reback_msg();
    }

    /**
     * 获取权限列表
     */
    public function getAuthListAction() {
        $result = $this->kfadmin_model->getAuthList();


        $this->assign('list', $result);
        $this->layout('kfadmin/auth.phtml');
    }

    /**
     * 获取身份列表
     */
    public function getGroupListAction() {
        $result = $this->kfadmin_model->getGroupList();

        $this->assign('list', $result);
        $this->layout('kfadmin/group.phtml');
    }
    

    /**
     * 添加权限
     */
    public function addAuthtplAction() {
        $id = $this->getRequest()->get('id');
        //获取父级
        $list = $this->kfadmin_model->getAuthParent();

        $this->assign('id', $id);
        $this->assign('list', $list);
        $this->layout('kfadmin/addauth.phtml');
    }

    /**
     * 修改权限
     */
    public function updateAuthTplAction() {
        $id = $this->getRequest()->get('id');

        $params['id'] = $id;
        $auth_info = $this->kfadmin_model->getAuthById($params);

        $list = $this->kfadmin_model->getAuthParent();

        $this->assign('id', $id);
        $this->assign('list', $list);
        $this->assign('info', $auth_info);
        $this->layout('kfadmin/updateauth.phtml');
    }

    /**
     * 添加权限
     */
    public function addAuthAction() {
        $id       = $this->getRequest()->getPost('id');
        $title_en = $this->getRequest()->getPost('title_en');
        $title_cn = $this->getRequest()->getPost('title_cn');

        if (!$title_en || !$title_cn) {
            Tools::success('error', '缺少必要参数');
        }

        $params = [
            'pid'      => ($id) ? $id : 0,
            'title_en' => $title_en,
            'title_cn' => $title_cn
        ];

        $result = $this->kfadmin_model->addAuth($params);


        if($result) {
            $this->reback_msg = [
                "info" => "添加成功",
                "status" => self::SUCCESS,
                "url" => "/kfadmin/getauthlist",
            ];
        }
        $this->reback_msg();
    }

    /**
     * 修改权限
     */
    public function updateAuthAction() {
        $id       = $this->getRequest()->getPost('id');
        $title_en = $this->getRequest()->getPost('title_en');
        $title_cn = $this->getRequest()->getPost('title_cn');

        if (!$id || !$title_en || !$title_cn) {
            Tools::success('error', '缺少必要参数');
        }

        $params = [
            'id'       => $id,
            'title_en' => $title_en,
            'title_cn' => $title_cn
        ];

        $result = $this->kfadmin_model->addAuth($params);

        /*else {
            $result = $this->kfadmin_model->updateAuth($params);
        }*/
        if($result) {
            $this->reback_msg = [
                "info" => "添加成功",
                "status" => self::SUCCESS,
                "url" => "/kfadmin/getauthlist",
            ];
        }
        $this->reback_msg();
    }

    /**
     * 删除权限
     */
    public function deleteAuthAction() {

    }


    /**
     * 添加身份
     */
    public function addGroupAction() {

    }

    /**
     * 修改身份
     */
    public function updateGroupAction() {

    }

    /**
     * 删除身份
     */
    public function deleteGroupAction() {

    }

    /**
     * 返回信息
     */
    private function reback_msg() {
        Tools::output($this->reback_msg);
    }
}