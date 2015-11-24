<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 * @author:why
 */
class XimalayaController extends Base{
    
    use Trait_Layout,
        Trait_Pagger;
    
    public $xmly;
    public $followers;
    
    public function init(){
        $this->initAdmin();
        $this->followers = new XmlyFollowersModel();
        $this->xmly = new XimalayaModel();
    }
    
    /*
     * 喜马拉雅数据
     */
    public function indexAction(){
        
        if($this->getRequest()->isPost()){
            //获取文件
            $file = $this->getRequest()->getFiles('import');
            if($file['name'] !== ''){
                $dir = dirname(dirname(dirname(dirname(__FILE__))));
                $savePath = $dir.'/file/upload/';
                if (!is_dir($savePath)) {
                    mkdir($savePath, 0777);
                }
                $ext = explode(".", $file['name']);
                $ext = strtolower($ext[count($ext) - 1]);
                $saveName = date('YmdHis').uniqid().'.'.$ext;
                if(!@move_uploaded_file($file["tmp_name"], $savePath.'/'.$saveName)){
                    echo '上传失败'; 
                }else{
                    $file_name = $savePath.'/'.$saveName;
                }
                //上传
                $i = 0;
                $xls = new Spreadsheet_Excel_Reader(); 
                $xls->setOutputEncoding('utf-8');  //设置编码 
                $xls->read($file_name);  //解析文件 
                if($xls->sheets[0]['cells'][1]){
                    unset($xls->sheets[0]['cells'][1]);
                    $fields = array(
                        1 => 'music_id',2 => 'music_name',3 => 'anchor_id',4 => 'album_id',5 => 'album_name',
                        6 => 'upload_date',7 => 'music_often',8 => 'date',9 => 'message',10 => '`like`',
                        11 => 'total_player',12 => 'player_one',13 => 'player_two',14 => 'player_three',15 => 'player_four',
                        16 => 'player_add',17 => 'player_five',18 => 'album_time_zero',19 => 'album_time_one',20 => 'album_time_two',
                        21 => 'album_time_three',22 => 'album_time_four',23 => 'album_time_five',24 => 'album_time_six',25 => 'album_time_seven',
                        26 => 'album_time_eight',27 => 'album_time_nine',28 => 'album_time_ten',29 => 'album_time_eleven',30 => 'album_time_twelve',
                        31 => 'album_time_thirteen',32 => 'album_time_fourteen',33 => 'album_time_fifteen',34 => 'album_time_sixteen',35 => 'album_time_seventeen',
                        36 => 'album_time_eighteen',37 => 'album_time_nineteen',38 => 'album_time_twenty',39 => 'album_time_twenty_one',40 => 'album_time_twenty_two',
                        41 => 'album_time_twenty_three',42 => 'rorward_all',43 => 'rorward_qq',44 => 'rorward_qzone',45 => 'rorward_renren',
                        46 => 'rorward_qq_weibo',47 => 'rorward_sipn_weibo',48 => 'rorward_wechat',49 => 'rorward_wechat_friends'
                    );
                    foreach ($xls->sheets[0]['cells'] as $value){
                        foreach ($fields as $r => $f) {
                            $data[$f] = $value[$r];
                        }
                        $this->xmly->insert($data);
                        $i++;
                    }
                }
            }else{
                $notice = 1;
            }
        }
        $size = 20;
        $p = (int) $this->getRequest()->getParam('p', 1);
        $count = $this->xmly->getCount();
        $this->renderPagger($p, $count, "/ximalaya/index/p/{p}", $size);
        $rs = $this->xmly->getData($p, $size, $kw);
        $this->assign('data', $rs);
        $this->assign('i', $i);
        $this->assign('notice', $notice);
        $this->layout('ximalaya/ximalaya.phtml');
    }
    
    /*
     * 喜马拉雅粉丝数
     */
    
    public function followersAction(){
        
        if($this->getRequest()->isPost()){
            //获取文件
            $file = $this->getRequest()->getFiles('import');
            if($file['name'] !== ''){
                $dir = dirname(dirname(dirname(dirname(__FILE__))));
                $savePath = $dir.'/file/upload/';
                if (!is_dir($savePath)) {
                    mkdir($savePath, 0777);
                }
                $ext = explode(".", $file['name']);
                $ext = strtolower($ext[count($ext) - 1]);
                $saveName = date('YmdHis').uniqid().'.'.$ext;
                if(!@move_uploaded_file($file["tmp_name"], $savePath.'/'.$saveName)){
                    echo '上传失败'; 
                }else{
                    $file_name = $savePath.'/'.$saveName;
                }
                //上传
                $i = 0;
                $xls = new Spreadsheet_Excel_Reader(); 
                $xls->setOutputEncoding('utf-8');  //设置编码 
                $xls->read($file_name);  //解析文件 
                if($xls->sheets[0]['cells'][1]){
                    unset($xls->sheets[0]['cells'][1]);
                    $fields = array(
                        1 => 'date',2 => 'anchor_id',3 => 'num',4 => 'add_num',5 => 'del_num'
                    );
                    foreach ($xls->sheets[0]['cells'] as $value){
                        foreach ($fields as $r => $f) {
                            $data[$f] = $value[$r];
                        }
                        $this->followers->insert($data);
                        $i++;
                    }
                }
            }else{
                $notice = 1;
            }
        }
        $size = 20;
        $p = (int) $this->getRequest()->getParam('p', 1);
        $count = $this->followers->getCount();
        $this->renderPagger($p, $count, "/ximalaya/followers/p/{p}", $size);
        $rs = $this->followers->getData($p, $size, $kw);
        $this->assign('data', $rs);
        $this->assign('i', $i);
        $this->assign('notice', $notice);
        $this->layout('ximalaya/followers.phtml');
    }
}
