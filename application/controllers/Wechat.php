<?php

/**
 * @name WechatController
 * @desc 微信列表控制器
 * @show
 * @author why
 */


class WechatController extends Base{
    
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
     * 七牛上传
     * @var $qiniu;
     */
    public $comment;
    /*
     * 七牛KEY
     */
    public $photokey;
    
    
    public $qiniu;
    
    /*
     * 后台日志
     */
    public $admin;
    
    /*
     * 初始化
     */
    public function init(){
        $this->initAdmin();
        Yaf_Loader::import(ROOT_PATH .'/application/library/phpExcel/reader.php');
        $this->list = $this->getApi('wechat');
        $this->img = $this->getApi('img');
        $this->comment = $this->getApi('message');
        $this->admin = new AdminLogModel();
        $this->qiniu = new QiniuModel();
    }
    
    /*
     * 微信列表页
     */
    public function indexAction(){
        $wecahtList = array('p'=>$_GET['p'],'showcount'=>10,'keyword'=>'');
        $url = 'api/adminapi/wechatlist';
        $postdata = array_merge($wecahtList,$this->getSignture());
        $rs = $this->list->post($url, $postdata);
        $jsondata = json_decode($rs, TRUE);
        if($jsondata['status'] =='ok'){
            $listdata = $jsondata['notice']['list'];
            $listpage = $jsondata['notice']['page'];
            $this->assign('data', $listdata);
            $this->assign('page', $listpage);
        }
        $this->layout('wechat/index.phtml');
    }
    
    /*
     * 回复管理
     */
    
    public function replayAction($id = 0){
        $url = "api/adminapi/selectwechatkeyword";
        $replayData = array('wechatid'=>$id,'showcount'=>10);
        $postdata = array_merge($replayData,  $this->getSignture());
        $rs = $this->list->post($url, $postdata);
        $jsonData = json_decode($rs, TRUE);
        if($jsonData['status'] == 'ok'){
            $newJson = $jsonData['notice']['list'];
            $page = $jsonData['notice']['page'];
            $this->assign('id', $id);
            $this->assign('data', $newJson);
            $this->assign('page', $page);
        }
        $this->layout('wechat/automaticReply.phtml');
    }
    
    /*
     * 上传
     */
    public function imgAction(){
        $user = $_SESSION['a_user'];
        $files = $this->getRequest()->getFiles('file');
        $rs = $this->qiniu->uploadFile($files['tmp_name']);
        $photoUrl = $this->qiniu->downloadfile($rs['key']);
        echo $photoUrl;
//        $user = $_SESSION['a_user'];
//        $files = $this->getRequest()->getFiles('file');
//        $params = [
//            'uid' => $user['id'],
//            'filedata'  => '@' . $files['tmp_name'],
//            'type'      => $files['type'],
//            'name'      => $files['name']
//        ];
//        // 上传接口
//        $url = API_SERVER_IMGURL . '/attachment/images/cate/avatar/type/apps';
//        $remoteRst = Curl::request($url, $params, 'post');
//        if ($remoteRst && is_array($remoteRst) && 'ok' == $remoteRst['status']) {
//            echo json_encode(['info' => '上传成功', 'status' => 1, 'data' => [
//                'savepath' => $remoteRst['data']['url']['url']]]);
//        } else {
//            echo json_encode(['info' => '上传失败', 'status' => 0]);
//        }
       exit;
    }
    
    
    /*
     * 修改回复管理
     */
    
    public function upReplyAction($wechatid = 0,$kid = 0){
        
        //修改信息
        if($this->getRequest()->isPost()){
            $url = "api/adminapi/editwechatkeyworddo";
            $upPostData = array_merge($this->getRequest()->getPost(),  $this->getSignture());
            $rs = $this->list->post($url, $upPostData);
            $rs = json_decode($rs, TRUE);
            
            
            if($rs['status'] == 'ok'){
                echo json_encode(['info' => '修改成功', 'status' => 1, 'url' => '/wechat/index']);
            } else {
                echo json_encode(['info' => '修改失败', 'status' => 0, 'url' => '/wechat/index']);
            }
            exit;
        }
        
        //列表
        
        $getUrl = 'api/adminapi/findwechatkeyword';
        $postdata = array('wechatid'=>$wechatid,'keywordid'=>$kid);
        $getPostData = array_merge($postdata,  $this->getSignture());
        $rs = $this->list->post($getUrl, $getPostData);
        $jsonData = json_decode($rs,TRUE);
        $notice = $jsonData['notice'];
        $this->assign('wid', $wechatid);
        $this->assign('kid', $kid);
        $this->assign('data', $notice);
        $this->layout('wechat/upReply.phtml');
    }
   
   /*
    * 微信菜单
    */
    public function menuAction($id = 0){
        $url = "api/adminapi/selectwechatmenu";
        $menuData = array('wechatid'=>$id);
        $postdata = array_merge($menuData,  $this->getSignture());
        $rs = $this->list->post($url, $postdata);
        $jsonData = json_decode($rs, TRUE);
        if($jsonData['status'] =='ok'){
            $notice = $jsonData['notice'];
            $this->assign('id', $id);
            $this->assign('data', $notice);
        }
        $this->layout('wechat/wechat_menu.phtml');
    }
    
    /*
     * 修改菜单
     */
    public function upMenuAction($id = 0, $wechatid = 0){
        if($this->getRequest()->isPost()){
            $upUrl = "api/adminapi/editwechatmenu";
            $postdata = array_merge($this->getRequest()->getPost(),  $this->getSignture());
            $rs = $this->list->post($upUrl, $postdata);
            
            $rs = json_decode($rs,TRUE);
            if($rs['status'] == 'ok'){
                echo json_encode(['info' => '修改成功', 'status' => 1, 'url' => '/wechat/index']);
            } else {
                echo json_encode(['info' => '修改失败，失败原因可能是'.$rs['notice'], 'status' => 0, 'url' => '/wechat/index']);
            }
            exit; 
        }
        $url = "api/adminapi/findwechatmenuinfo";
        $postdata = array_merge(array('menuid'=>$id,'wechatid'=>$wechatid),  $this->getSignture());
        $rs = $this->list->post($url, $postdata);
        $jsondata = json_decode($rs,TRUE);
        $notice = $jsondata['notice'];
        $this->assign('data', $notice); 
        $this->layout('wechat/upMenu.phtml');
    }
   
   /*
    *素材管理 
    */
    public function materialAction($id = 0){
        
        $url = "api/adminapi/selectwechatpubliclist";
        $data = array('wechatid'=>$id,'showcount'=>10);
        $postdata = array_merge($data,  $this->getSignture());
        $rs = $this->list->post($url, $postdata);
        $jsonData = json_decode($rs,TRUE);
        if($jsonData['status'] =='ok'){
            $list = $jsonData['notice']['list'];
            $listPage = $jsonData['notice']['page'];
            $this->assign('data', $list);
            $this->assign('listpage',$listPage);
        }
        $this->layout('wechat/wechat_material.phtml');
    }
   
   /*
    * 查看详情
    */
    public function getDetailsAction($id = 0){
        $url = "api/adminapi/wechatfindinfo";
        $wechatDetails = array('wechatid'=>$id);
        $postdata = array_merge($wechatDetails,  $this->getSignture());
        $rs = $this->list->post($url, $postdata);
        $jsonData = json_decode($rs, TRUE);
        $newJson = $jsonData['notice'];
        $this->assign('data', $newJson);
        $this->layout('wechat/getDetails.phtml');   
    }
    
    /*
     * 修改详情
     */
   public function updetailsAction($id = ''){
       
        if ($this->getRequest()->isPost()) {
            $upUrl = 'api/adminapi/wechateditdo';
            $upPostData = array_merge($this->getRequest()->getPost(),$this->getSignture());
            $rs = $this->list->post($upUrl, $upPostData);
            $rs = json_decode($rs, TRUE);
            if($rs['status'] == 'ok'){
                echo json_encode(['info' => '修改成功', 'status' => 1, 'url' => '/wechat/index']);
            } else {
                echo json_encode(['info' => '修改失败，失败原因可能是'.$rs['notice'], 'status' => 0, 'url' => '/wechat/index']);
            }
            exit; 
        }
        
        //列表
        
        $url = "api/adminapi/wechatfindinfo";
        $wechatDetails = array('wechatid'=>$id);
        $postdata = array_merge($wechatDetails,  $this->getSignture());
        $rs = $this->list->post($url, $postdata);
        $jsonData = json_decode($rs, TRUE);
        $newJson = $jsonData['notice'];
        $this->assign('id',$id);
        $this->assign('data', $newJson);
        $this->layout('wechat/updetails.phtml');
    }
    
    
    /*
     * 删除回复关键词
     */
    public function deleteAction($keywordid = 0, $wechatid = 0){
        if($this->getRequest()->isPost()){
            $url = "api/adminapi/delwechatkeyworddo";
            $postdata = array_merge(array('wechatid'=>$wechatid,'keywordid'=>$keywordid),$this->getSignture());
            $rs = $this->list->post($url, $postdata);
            $jsondata = json_decode($rs,TRUE);
            
            
            if($jsondata['status'] == 'ok'){
                echo json_encode(['info' =>'操作成功' ,'status' => 1 ,'url' =>'/wechat/replay']);
            } else {
                echo json_encode(['info' =>'操作失败' ,'status' => 0 ,'url' =>'/wechat/replay']);
            }
            exit;
        } 
        
    }
    
    /*
     * 短信群发
     */
    
    public function messageAction(){
        
        if($this->getRequest()->isPost()){
            if($this->getRequest()->getPost('message')){
                //请求短信接口
                $url = 'message/sms';
                $post_data = $this->getRequest()->getPost('message');
                $string = explode('-', $post_data);
                foreach ($string as $key=>$val){
                    $item[] = explode(',', $val);
                }
                foreach ($item as $ik=>$iv){
                    if(count($iv) >= 2){
                        $phone[] = $iv;
                    }
                }
                foreach ($phone as $pk=>$pv){
                    $data['phonenumber'] = $pv[1];
                    $data['message'] = $pv['2'];
                    $rs = $this->comment->request($url, $data, 'POST');
                }
                if($rs['status'] == 'ok'){
                    echo json_encode(['info' =>'操作成功' ,'status' => 1 ,'url' =>'/wechat/replay']);
                }else{
                    echo json_encode(['info' =>'发送失败，原因可能是'.$rs['notice'] ,'status' => 0 ,'url' =>'/wechat/replay']);
                }
//                $adminLog = 
//                $this->admin->add();
                exit;
                
            }
            $file = $this->getRequest()->getFiles('import');
            $dir = dirname(dirname(dirname(dirname(__FILE__))));
            $savePath = $dir.'/file/message/';
            if (!is_dir($savePath)) {
                mkdir($savePath, 0777);
            }
            $ext = explode(".", $file['name']);
            $ext = strtolower($ext[count($ext) - 1]);
            $saveName = date('YmdHis').uniqid().'.'.$ext;
            if(!@move_uploaded_file($file["tmp_name"], $savePath.'/'.$saveName)){
                echo '文件上传失败！';
            }else{
                $file_name = $savePath.'/'.$saveName;
            }
            $xls = new Spreadsheet_Excel_Reader(); 
            $xls->setOutputEncoding('utf-8');  //设置编码 
            $xls->read($file_name);  //解析文件 
            if($xls->sheets[0]['cells'][1]){
                $counts = count($xls->sheets[0]['cells'][2]);
                unset($xls->sheets[0]['cells'][1]);
                foreach ($xls->sheets[0]['cells'] as $key=>&$value){
                    if(is_int($key)){
                        for($i=0;$i<=$counts;$i++){
                            $str .= $value[$i].',';
                        }
                        $str .= "-"."\n";
                    }
                }
            }
        }
        $this->assign('data', $str);
        $this->layout('wechat/message.phtml');
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

