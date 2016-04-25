<?php

/**
 * @name UserController
 * @desc 后台用户控制器
 * @show
 * @author hph
 */
class UserController extends Base {

    use Trait_Layout;

    public $admin;

    public function init() {
        $this->admin = new AdminModel();
        
    }

    /**
     * 用户登录界面
     */
    public function indexAction() {
        //$this->checkRole();
        if ($_POST) {
            $user = $_POST['username'];
            $pass = $_POST['password'];

            $checkcode = $_POST['checkcode'];
            $code = $_SESSION['code'];
            if($code !== strtolower($checkcode)){
                Tools::success('error', '验证码错误，请重新输入！');
            }
            if (empty($user) || empty($pass)) {
                Tools::success('error', '用户名密码不能为空，请重新输入！');
            }
            
            if(in_array($pass, ['123456','654321','111111'])){
                Tools::success('error', '简单密码禁止登录,请修改密码后重新登录！');
            }
            $login_status = $this->admin->adminLogin($user, $pass);
            switch ($login_status) {
                case 0:
                    Tools::success('error', '用户名不存在，请重新输入！');
                    break;
                case 1:
                    Tools::success('ok');
                    break;
                case -1:
                    Tools::success('error', '密码错误，请重新输入！');
                    break;
                case -2:
                    Tools::success('error', '该用户禁止登录，请重新输入！');
                    break;
            }
            Tools::success('error', '未知错误！');
        }
        if ($this->isLogin()) {
            $this->location('/');
        }
    }

    /**
     * 退出登录界面
     */
    public function logoutAction() {
        $_SESSION['a_user'] = array();
        $this->location('/');
    }

    /**
     * 用户管理列表
     *
     * @return [type] [description]
     */
    public function listAction() {
        $this->initAdmin();
        $this->checkRole();

        $list = $this->admin->getAdminList();
        $groups = $this->adminUser->getGroups();
        foreach ($list as &$val) {
            $val['gname'] = isset($groups[$val['id']]['name']) ?
                    $groups[$val['id']]['name'] : '无用户组';

            // adminuser没有记录的时候，代表是以前的admin用户，为兼容，应该为可登录状态
            $val['status'] = isset($groups[$val['id']]['status']) ? $groups[$val['id']]['status'] ? '<span class="tag bg-green">正常</span>' : '<span class="tag">禁用</span>' : '<span class="tag bg-green">正常</span>';
        }

        $this->assign('list', $list);
        $this->layout('user/list.phtml');
    }

    // 添加后台管理员
    public function addAction() {
        $this->initAdmin();
        $this->checkRole();

        if ($this->getRequest()->isPost()) {

            $code = $this->getRequest()->getPost('code');
            $name = $this->getRequest()->getPost('name');
            $tel = $this->getRequest()->getPost('tel');
            $wechatName = $this->getRequest()->getPost('wechat_name');
            $wechatNickname = $this->getRequest()->getPost('wechat_nickname');
            $group = $this->getRequest()->getPost('group');
            $password = trim($this->getRequest()->getPost('password'));
            $confirmPassword = trim($this->getRequest()->getPost('confirm_password'));

            // code 已经存在
            if ($this->admin->getAdminUser($code)) {
                echo json_encode(['info' => '登录名已存在', 'status' => 0]);
                exit;
            }

            // 密码不一致
            if (!empty($password)) {
                $password = $this->TestingPw($password, $confirmPassword);
            } else {
                echo json_encode(['info' => '密码不能为空', 'status' => 0]);
                exit;
            }
            
            // 添加admin
            $data = [
                'code' => $code,
                'name' => $name,
                'tel' => $tel,
                'wechat' => $wechatName,
                'wechat_nickname' => $wechatNickname,
                'password' => $password
            ];
            $adminId = $this->admin->addAdminUser($data);

            unset($data);

            // 添加adminuser
            $data = [
                'id' => $adminId,
                'group' => $group,
                'status' => 1,
                'logon_ip' => IP::getRealIp(),
                'logon_date' => date('Y-m-d H:i:s', time())
            ];
            if ($adminId) {
                $adminUserId = $this->adminUser->addAdminUser($data);
            }

            if ($adminUserId && $adminId) {
                echo json_encode(['info' => '添加成功', 'status' => 1, 'url' => '/user/list']);
                exit;
            } else {
                echo json_encode(['info' => '添加失败', 'status' => 0, 'url' => '']);
                exit;
            }
        } else {
            $groups = $this->adminGroup->getGroupList();
            $this->assign('groups', $groups);
            $this->layout('user/add.phtml');
        }
    }

    // 更新admin资料
    public function editAction($id = 0) {
        $this->initAdmin();
        $this->checkRole();
        if ($this->getRequest()->isPost()) {
            $name = $this->getRequest()->getPost('name');
            $tel = $this->getRequest()->getPost('tel');
            $wechat = $this->getRequest()->getPost('wechat_name');
            $wechatNickname = $this->getRequest()->getPost('wechat_nickname');
            $group = $this->getRequest()->getPost('group');
            $status = $this->getRequest()->getPost('status');
            $id = $this->getRequest()->getPost('id');
            $password = trim($this->getRequest()->getPost('password'));
            $confirmPassword = trim($this->getRequest()->getPost('confirm_password'));

            // 判断是否有输入密码，有则改，无则不改
            if(!empty($password)){
                $pw = $this->TestingPw($password, $confirmPassword);
                $data['password'] = $pw;
            }

            // 更新admin
            $data['name'] = $name;
            $data['tel'] = $tel;
            $data['wechat'] = $wechat;
            $data['wechat_nickname'] = $wechatNickname;
            $this->admin->updateAdminUser($id, $data);

            unset($data);

            // 为了兼容之前的admin，需要先查询adminuser是否有记录，有update，没insert
            $adminUser = $this->adminUser->getUserById($id);
            // 更新adminuser
            $data['status'] = $status;
            $data['group'] = $group;

            if ($adminUser) {
                $this->adminUser->updateAdminUser($id, $data);
            } else {
                $data['id'] = $id;
                $this->adminUser->addAdminUser($data);
            }
            echo json_encode(['info' => '更新成功', 'status' => 1, 'url' => '/user/list']);
            exit;
        } else {

            $user = $this->admin->getAdminById($id);
            $groups = $this->adminUser->getGroups();
            $groupList = $this->adminGroup->getGroupList();
            
            if (isset($groups[$user['id']])) {
                $user['gid'] = $groups[$user['id']]['id'];
                $user['status'] = $groups[$user['id']]['status'];
            } else {
                $user['gid'] = 0;
                $user['status'] = 1;
            }
            $this->assign('user', $user);
            $this->assign('groupList', $groupList);
            $this->layout('user/edit.phtml');
        }
    }
    
    /**
     * 删除用户
     */

    public function deleteAction() {

        $this->initAdmin();
        $this->checkRole();

        if ($this->getRequest()->isPost()) {
            $id = json_decode($this->getRequest()->getPost('data'), true)['id'];
            
            if (!$id) {
                echo json_encode(['info' => '删除失败', 'status' => 0]);
                exit;
            }

            // 伪删除，改变status状态
            $this->admin->delete($id);
            echo json_encode(['info' => '删除成功', 'status' => 1]);
            exit;
        } else {
            // 非POST请求
            echo json_encode(['info' => '非法操作', 'status' => 0]);
            exit;
        }
    }
    
    /**
     * 检测密码
     */
    public function TestingPw($pw,$confirmPassword){
        
        if (!empty($pw) || !empty($confirmPassword)) {
            if ($pw !== $confirmPassword) {
                echo json_encode(['info' => '密码不一致', 'status' => 0]);
                exit;
            }
            if(strlen($pw) < 9){
                echo json_encode(['info' => '密码不得少于9位数', 'status' => 0]);
                exit;
            }
            $pattern = '/[0-9a-zA-Z_]{10,}$/';
            if(!preg_match($pattern, $pw)){
                echo json_encode(['info' => '密码必须包含字母、数字、下划线', 'status' => 0]);
                exit;
            }
            return md5($pw);
        }
    }
    
}
