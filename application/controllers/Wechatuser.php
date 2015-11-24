<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 * @author:why
 * @purpose:微信用户列表
 */

class WechatuserController extends Base{
    
    use Trait_Layout;
    use Trait_Api;
    
    
    /* 
     * 钥匙串
     * @var $key;
     */
    public $key = "Gs8d62h@!K3g4d8H9C";
    
    /*
     * 微信列表
     * @var $list;
     */
    public $list; 
    
    /*
     * 图片接口
     */
    public $img; 
    
    /*
     * EXP:七牛
     */
    
    public $qiniu;
    
    /*
     * 初始化
     */
    public function init(){
        $this->initAdmin();
        $this->list = $this->getApi('wechat');
        $this->img = $this->getApi('img'); 
        $this->qiniu = new QiniuModel();
    }
    
    
    public function indexAction(){
        $url = "api/adminapi/selectwechatsuserlist";
        $postdata = array_merge(array('showcount'=>'20','p'=>'','url'=>'/wechatuser/index'),  $this->getSignture());
        $rs = $this->list->post($url, $postdata);
        $jsondata = json_decode($rs,TRUE);
        $list = $jsondata['notice']['list'];
        $listpage = $jsondata['page'];
        $this->assign('data', $list);
        $this->assign('listpage', $listpage);
        $this->layout('wechat/user.phtml');
    }
    
    
    /*
     * 客服消息日志
     */
    public function logAction(){
        $url = "api/adminapi/selectwechatserverlog";
        $postdata = array_merge(array('showcount'=>15),  $this->getSignture());
        $rs = $this->list->post($url,$postdata);
        
        
        $jsondata = json_decode($rs,TRUE);
        if($jsondata['status'] =='ok'){
            $list = $jsondata['notice']['list'];
            $listpage = $jsondata['notice']['page'];
            $this->assign('data', $list);
            $this->assign('listpage', $listpage);
        }
        $this->layout('wechat/log.phtml');
    }
    
    /*
     * 签名算法
     */
    public function getSignture(){
        $a = array();
        $a['timestamp'] = time();
        $a['nonce'] = rand(10000000, 99999999);
        $a['token'] = $this->key;    //$key 联系接口开发人员索取
        $tmpArr = array($a['token'], $a['timestamp'], $a['nonce']);
        sort($tmpArr, SORT_STRING);
        $tmpStr = implode( $tmpArr );
        $a['signature'] = sha1( $tmpStr );
        unset($a['token']);
        return $a;
    }
}
