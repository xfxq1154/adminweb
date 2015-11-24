<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class YouzanController extends Base{
    
    use Trait_Api,
        Trait_Layout,
        Trait_Pagger;
    
    
    public $item;
    public $order,$you,$user;
    public $sku,$img,$tag;
    public $address;
    
    /*
     * 初始化
     */
    public function init(){
        $this->initAdmin();
        $this->item = new CommdityModel();
        $this->you = new YouzanModel();
        $this->order = new YouZanOrdersModel();
        $this->user = new YouZanUserModel();
        $this->sku = new YouZanSkuModel();
        $this->img = new YouZanImgsModel();
        $this->tag = new YouZanTagModel();
        $this->address = new YouZanAddressModel();
    }
    /*
     * 首页
     */
    public function indexAction(){
        if($_POST){
            $st = $this->getRequest()->getParam('st', '');
            if(empty($st)){
                $st = $_REQUEST['start_time'];
            }
            $st = urldecode($st);
            $et = $this->getRequest()->getParam('et','');
            if(empty($et)){
                $et = $_REQUEST['end_time'];
            }
            $et = urldecode($et);
            $size = 40;
            $p = (int) $this->getRequest()->getParam('p',1);
            $count = $this->user->getCount();
            $this->renderPagger($p, $count, "/youzan/index/p/{p}/st/{$st}/et/{$et}", $size);
            $rs = $this->user->getData($p, $size,$kw,$st,$et);
            $array = [
                'id'=>'id','user_id'=>'user_id','weixin_openid'=>'weixin_openid','avatar'=>'avatar',
                'follow_time'=>'follow_time','sex'=>'sex','province'=>'province','city'=>'city'
            ];
            foreach ($rs as $key=>$val){
                foreach ($array as $ak=>$av){
                    $content[$av] = $val[$ak];
                    $content['nick'] = $val['nick'];
                    $content['tags'] = unserialize($val['tags']);
                    $content['id'] = $val['id'];
                }
                $data[] = $content;
            }
        }
        if($_POST['val']){
            //导出excel
            header("Content-type:application/octet-stream");
            header("Accept-Ranges:bytes");
            header("Content-type:application/vnd.ms-excel");
            header("Content-Type:text/html; charset=gbk");
            header("Content-Disposition:attachment;filename=user.xls");
            header('Pragma:no-cache');
            header('Expires:0');
            $title = array('ID','用户ID','昵称','头像链接','关注时间','性别','所在省份','所在城市');
            echo iconv('utf-8', 'gbk', implode("\t", $title)),"\n";
            foreach ($data as $value){
                echo iconv('utf-8', 'gbk', implode("\t", $value)),"\n";
            }
            exit;
        }
        $this->assign('st',$st);
        $this->assign('et',$et);
        $this->assign('data', $data);
        $this->layout('youzan/youZanUser.phtml');
    }
    
}

