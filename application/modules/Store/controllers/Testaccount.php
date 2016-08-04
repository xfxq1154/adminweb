<?php

/**
 * @name TestAccountController
 * @desc 测试号管理
 */
class TestAccountController extends Base{

    use Trait_Layout,
        Trait_Pagger;

    /** @var  TestModel */
    public $test_model;

    public function init(){
        $this->test_model = new TestModel();
    }

    /*
     * openid查询测试账号
     */
    public function indexAction(){
        $user_bind = array();
        $newOpenids = array();

        //测试账户
        $testAccount = array(
            'openid' => array(
                'o0CwHuDKBsSdj2-4AVUQ5E4S15Kw',
                'o0CwHuFM9hIPc80AY1RyEGEx_tbc',
                'oYFNNwQhBVx0JWZZ-eg6yU42lI-Y'
            ),
            'mobile' => array(
                '13811035046',
                '18500202512',
                '18610109182'
            )
        );

        if (empty($testAccount['openid'])) {
            $this->assign('data', $user_bind);
            $this->layout('testaccount/index.phtml');
        }
        $openids = "'".implode("','", $testAccount['openid'])."'";
        $user_bind = $this->test_model->getUserBind($openids);
        if (!empty($user_bind)) {
            foreach ($user_bind as $k=>$v) {
                $user_bind[$k]['status'] = 0;
                $newOpenids[] = $v['b_openid'];
            }
        }
        $openid = array_diff($testAccount['openid'], $newOpenids);
        if (!empty($openid)) {
            foreach ($openid as $v) {
                $user_bind[] = array(
                    'b_uid'=>'',
                    'b_nickname'=>'',
                    'b_type'=>'',
                    'b_time'=>'',
                    'b_openid'=>$v,
                    'status'=>1
                );
            }
        }
        $this->assign('data', $user_bind);
        $this->layout('testaccount/index.phtml');
    }

    /*
     * openid查询测试账号
     */
    public function mobileAction(){
        $user = array();
        $newMobiles = array();
        if (empty($GLOBALS['testAccount']['mobile'])) {
            $this->assign('data', $user);
            $this->layout('test/mobile.phtml');
        }
        $mobiles = "'".implode("','", $GLOBALS['testAccount']['mobile'])."'";
        $user = $this->test_model->getUser($mobiles);
        if (!empty($user)) {
            foreach ($user as $k=>$v) {
                $user[$k]['status'] = 0;
                $newMobiles[] = $v['u_phone'];
            }
        }
        $mobile = array_diff($GLOBALS['testAccount']['mobile'], $newMobiles);
        if (!empty($mobile)) {
            foreach ($mobile as $v) {
                $user[] = array(
                    'u_id'=>'',
                    'u_nickname'=>'',
                    'u_source'=>'',
                    'u_regtime'=>'',
                    'u_phone'=>$v,
                    'status'=>1
                );
            }
        }
        $this->assign('data', $user);
        $this->layout('test/mobile.phtml');
    }

    /*
     * 删除测试账号
     */
    public function deleteAction(){
        if ($this->getRequest()->isPost()) {
            $uid = json_decode($this->getRequest()->getPost('data'), true)['uid'];
            $openid = json_decode($this->getRequest()->getPost('data'), true)['openid'];
            $mobile = json_decode($this->getRequest()->getPost('data'), true)['mobile'];
        } else {
            Tools::output(array('info'=>'非法操作','status'=>0));
        }

        if (empty($openid) && empty($mobile)) {
            Tools::output(array('info'=>'参数非法1','status'=>0));
        }

        if (!empty($openid)) {
            if (!in_array($openid, $GLOBALS['testAccount']['openid'])) {
                Tools::output(array('info'=>'参数非法2','status'=>0));
            }
        }

        if (!empty($mobile)) {
            if (!in_array($mobile, $GLOBALS['testAccount']['mobile'])) {
                Tools::output(array('info'=>'参数非法3','status'=>0));
            }
        }

        $res = $this->test_model->delete($uid);
        if($res){
            Tools::output(array('info'=>'删除成功','status'=>1));
        }

        Tools::output(array('info'=>'删除失败','status'=>0));
    }
}

