<?php

/**
 * @name QiniuModel
 * @author hph
 * @desc 七牛
 */
Yaf_Loader::import(ROOT_PATH . '/application/library/qiniu/autoload.php');

use Qiniu\Auth;
use Qiniu\Storage\BucketManager;
use Qiniu\Processing\PersistentFop;
use Qiniu\Storage\UploadManager;

class QiniuModel {

    use Trait_DB;

use Trait_Redis;

    public $dbMaster, $dbSlave; //主从数据库 配置
    public $AccessKey, $SecretKey;
    public $auth, $bucket;

    public function __construct($bucket = 'luoji-jwl') {
        $this->AccessKey = Yaf_Application::app()->getConfig()->safe->qiniu->accesskey;
        $this->SecretKey = Yaf_Application::app()->getConfig()->safe->qiniu->secretkey;
        $this->auth = new Auth($this->AccessKey, $this->SecretKey);
        $this->bucket = $bucket ? $bucket : 'luoji-jwl';
    }

    /**
     * 获取管理凭证
     */
    public function listfiles($bucket = 'luoji-jwl') {

        $bucketMgr = New BucketManager($this->auth);
        $bucket = 'luoji-jwl';
        $prefix = '';

        list($iterms, $marker, $err) = $bucketMgr->listFiles($bucket);
        echo "\n====> List result: \n";
        if ($err !== null) {
            var_dump($err);
        } else {
            echo "Marker: $marker\n";
            echo 'iterms====>\n';
            
            if($iterms) {
                foreach($iterms as $file){
                    $this->delete($file['key']);
                    echo $file['key'] . "<br />";
                }
            }
            //Tools::output($iterms, 'print_r');
        }
    }
    
    public function delete($key){
        $bucketMgr = New BucketManager($this->auth);
        $bucket = 'luoji-jwl';
        $prefix = '';

        $bucketMgr->delete($this->bucket, $key);
        
    }

    public function filestat($key = '帮我又一课科技有限公司.jpg') {
        $bucketMgr = New BucketManager($this->auth);

        list($ret, $err) = $bucketMgr->stat($this->bucket, $key);
        if ($err !== null) {
            return $err->getResponse()->json();
        } else {
            return $ret;
        }
    }

    /**
     * 格式转换 接口
     * @param type $bucket
     * @param type $key
     * @param type $format
     */
    public function pfop($key = '2015-5-21成功.mp3', $format = 'mp3/ab/64000') {
        $pipeline = 'dedao';
        $pfop = New PersistentFop($this->auth, $this->bucket, $pipeline);
        $format = str_replace('/acodec/libmp3lame', '', $format);
        $skey = $this->randomkey(1,$format);
        $fops = 'avthumb/' . $format.'/acodec/libmp3lame|saveas/'. \Qiniu\entry($this->bucket, $skey);
        list($id, $err) = $pfop->execute($key, $fops);

        if ($err !== null) {
            return $err->getResponse()->json();
        } else {
            return $id;
        }
    }

    public function avconcat($key, $endkey = '2015-5-21成功.mp3') {
        $pfop = New PersistentFop($this->auth, $this->bucket);

        $fops = 'avconcat/2/format/ogg/' . \Qiniu\base64_urlSafeEncode($this->downloadfile($endkey));
        list($id, $err) = $pfop->execute($key, $fops);

        if ($err !== null) {
            return $err->getResponse()->json();
        } else {
            return $id;
        }
    }

    public function pfopStatusById($id) {

        list($status, $error) = PersistentFop::status($id);
        if ($error !== null) {
            return $error->getResponse()->json();
        } else {
            return $status;
        }
    }

    /**
     * 文件下载接口
     * @param type $key
     * @return type
     */
    public function downloadfile($key) {
        //var_dump($this->AccessKey, $this->SecretKey);
        $baseUrl = API_QINIU_DOMAIN . '/' . $key;
        $authUrl = $this->auth->privateDownloadUrl($baseUrl, 3600);
        return $authUrl;
    }

    public function getAvinfo($key) {
        $key .= '?avinfo';
        $url = $this->downloadfile($key);
        $res = Curl::get($url);
        return Qiniu\json_decode($res, 1);
    }
    /**
     * 
     * @param string $file
     * @param int $type 1 音频文件,2电子书
     * @return array
     */
    public function uploadFile($file, $type=1) {
        
        $token = $this->auth->uploadToken($this->bucket);
        $uploadMgr = new UploadManager();
        $key = $this->randomkey($type);
        list($ret, $err) = $uploadMgr->putFile($token, $key, $file);

        if ($err !== null) {
            return $err->getResponse()->json();
        } else {
            return $ret;
            //array(2) { ["hash"]=> string(28) "FkrWD4n8DD5TsarD62V6sU5Z0Ca4" ["key"]=> string(28) "FkrWD4n8DD5TsarD62V6sU5Z0Ca4" } 
        }
        
    }

    function IsQiniuCallback() {
        $authstr = $_SERVER['HTTP_AUTHORIZATION'];
        if (strpos($authstr, "QBox ") != 0) {
            return false;
        }
        $auth = explode(":", substr($authstr, 5));
        if (sizeof($auth) != 2 || $auth[0] != $this->AccessKey) {
            return false;
        }
        $data = "/callback.php\n" . file_get_contents('php://input');
        return URLSafeBase64Encode(hash_hmac('sha1', $data, $this->SecretKey, true)) == $auth[1];
    }
    
    function randomkey($type=1, $format=''){
        
        //  文件目录key  格式  年月/日/文件名
        //  文件名规则  年月日时分秒 + 6(微秒) + 4随机号 24位长度
        list($usec, $sec) = explode(" ", microtime());
        $usec = substr('000000'.intval($usec * 1000000),-6);
        $code = date("Ym/d/", $sec) . date("YmdHis", $sec). $usec .rand(1000, 9999);
        switch ($type) {
            case 3:
                $fileType = ['apk/','.apk'];
                break;
            case 2:
                $fileType = ['ebook/','.zip'];
                break;
            case 1:
            default:
                if($format){
                    $fileType = [$format .'/','.mp3']; 
                }else{
                    $fileType = ['mp3/','.mp3'];  
                }
                
                break;
        }
        return $fileType[0] . $code . $fileType[1];
        
    }

}
