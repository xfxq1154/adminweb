<?php

/**
 * Created by PhpStorm.
 * User: momoko
 * Date: 15/11/5
 * Time: 11:26
 */
class AudiousersController extends Base
{

    use Trait_Pagger, Trait_Layout;

    /** @var  AudioUsersModel*/
    protected $audioUsers;

    const PAGE_SIZE = 20;

    public function init()
    {
        $this->audioUsers = new AudioUsersModel();
    }

    public function indexAction()
    {

        $p = (int) $this->getRequest()->getParam('p', 1);
        $userid = $this->getRequest()->getParam('uid', '');
        $uphone = $this->getRequest()->getParam('phone', '');
        
        $pageUrl = '/audiousers/index/p/{p}';
        $allNum = $this->audioUsers->getCount();
        $hasPhoneNum = $this->audioUsers->getCount(' and u_phone != "" and u_phone > 0 ');
        $noPhoneNum  = ($allNum - $hasPhoneNum);
        
        
        
        
        $total = $this->audioUsers->getCount();
       
        
        $where = '';
        $wh = '';
        $condition = array();
        if(!empty($userid))
        {
              $where .= ' and u_uid = :uid ';
              $condition['bind'][':uid'] = $userid;
              $pageUrl .= '/uid/'.$userid;
              $wh.=" and u_uid = '".$userid."' ";
        }
        if(!empty($uphone))
        {
                $where .= ' and u_phone = :uphone ';
                $condition['bind'][':uphone'] = $uphone;
                $pageUrl .= '/phone/'.$uphone;
                $wh.=" and u_phone = '".$uphone."' ";
        }
        
        if(!empty($where))
        {
                $condition[0] = $where;
                $total = $this->audioUsers->getCount($wh);
        }
        
       
       
        $this->renderPagger($p, $total, $pageUrl, self::PAGE_SIZE);
        $limit = ($p - 1) * self::PAGE_SIZE . ',' . self::PAGE_SIZE;
        
        $condition['order by'] = 'u_id DESC';
        $condition['limit'] = $limit;
        
        $users = $this->audioUsers->getAudioUsers($condition);

        $uids = array();

        if ($users) {
            foreach ($users as $user) {
                $uids[] = $user['uid'];
            }
        }

        $userInfos = $this->audioUsers->getUCApiUserInfo($uids);

        if (count($uids) == 1) {
            $users[0]['nickname'] = $userInfos['nickname'];
            $users[0]['phone'] = $userInfos['phone'];
        } else {
            foreach ($users as &$user) {
                foreach ($userInfos as $userId => $userInfo) {
                    if ($user['uid'] == $userId) {
                        $user['nickname'] = $userInfo['nickname'];
                        $user['phone'] = $userInfo['phone'];
                    }
                }
            }
        }

        $this->_view->users = $users;
        $this->_view->total = $allNum;
        $this->_view->phoneNum = $hasPhoneNum;
        $this->_view->noPhoneNum = $noPhoneNum;
        $this->_view->userid = $userid;
        $this->_view->uphone = $uphone;
        $this->layout('audiousers/index.phtml');
    }
}