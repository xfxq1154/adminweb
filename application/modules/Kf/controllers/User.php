<?php
/**
 * @name: User.php
 * @time: 2016-08-25 上午11:11
 * @author: liuxuefeng
 * @desc: 用户信息
 */

class UserController extends Kfbase {

    const USER_LIST = '/kf/user/list'; //用户列表
    const USER_ADD = '/kf/user/addtpl'; //添加用户页面
    const USER_MODIFY = '/kf/user/updatetpl'; //修改用户信息页面
    const DISABLE_STATUS = 2; //冻结状态
    const PAGE_SIZE = 20;


    public function init() {
        parent::init();
    }

    /**
     * 获取用户列表
     */
    public function listAction() {
        $page_no = (int)$this->getRequest()->get('p', 1);

        $params = [
            'page_no' => $page_no,
            'page_size' => self::PAGE_SIZE,
        ];
        $result = $this->kfadmin_model->getUserList($params);

        $this->renderPagger($page_no ,$result['total_nums'] , "/kf/user/list/p/{p}", self::PAGE_SIZE);
        $this->assign('list', $result['data']);
        $this->layout('user/list.phtml');

    }

    /**
     * 添加用户页面
     */
    public function addTplAction() {
        //获取身份列表
        $role_list = $this->kfadmin_model->getRoleList();

        $this->assign('role_list', $role_list);
        $this->layout('user/add.phtml');
    }

    /**
     * 添加账号
     */
    public function addUserAction() {
        $username = $this->getRequest()->getpost('username'); //账号
        $password = $this->getRequest()->getpost('password'); //密码
        $nickname = $this->getRequest()->getpost('nickname'); //密码

        if (!$username || !$password) {
            $this->_outPut('缺少必要参数');
        }

        $params = [
            'username' => $username,
            'password' => $password,
            'nickname' => $nickname,
        ];

        $this->kfadmin_model->addUser($params);
        $this->_outPut(Kfapi::getErrorMessage(), Kfapi::getErrorCode(), self::USER_LIST);
    }

    /**
     * 修改用户信息页面
     */
    public function editTplAction() {
        $user_id = $this->getRequest()->get('user_id');
        if(!$user_id) {
            $this->_outPut('缺少必要参数');
        }

        $params = [
            'user_id' => $user_id,
        ];
        //获取身份列表
        $user_info = $this->kfadmin_model->getUserInfo($params);

        $this->assign('userinfo', $user_info);
        $this->layout('user/edit.phtml');
    }

    /**
     * 确认修改
     */
    public function editAction() {
        $user_id  = $this->getRequest()->getPost('user_id');
        $password = $this->getRequest()->getPost('password');
        $nickname = $this->getRequest()->getPost('nickname');

        if(!$user_id || !$password) {
            $this->_outPut('缺少必要参数');
        }

        $params = [
            'user_id'  => $user_id,
            'password' => $password,
            'nickname' => $nickname
        ];
        $this->kfadmin_model->edit($params);
        $this->_outPut(Kfapi::getErrorMessage(), Kfapi::getErrorCode());
    }

    /**
     * 账号禁用
     */
    public function disableAction() {
        $data = json_decode($this->getRequest()->getPost('data'),true);

        if(empty($data)) {
            $this->_outPut('缺少必要参数');
        }

        $params = [
            'user_id' => $data['user_id'],
            'status'  => self::DISABLE_STATUS,
        ];
        $this->kfadmin_model->edit($params);
        $this->_outPut(Kfapi::getErrorMessage(), Kfapi::getErrorCode());
    }

    private function verPassword() {
        
    }
}