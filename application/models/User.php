<?php

/**
 * @name QiniuModel
 * @author hph
 * @desc 七牛
 */
class UserModel {

    use Trait_DB;

use Trait_Redis;

    public $dbMaster, $dbSlave; //主从数据库 配置
    public $AccessKey, $SecretKey;
    public $auth;

    public function __construct() {
        $this->dbMaster = $this->getMasterDb();
        $this->dbSlave = $this->getSlaveDb();
        $this->AccessKey = Yaf_Application::app()->getConfig()->safe->qiniu->accesskey;
        $this->SecretKey = Yaf_Application::app()->getConfig()->safe->qiniu->secretkey;
        //$this->auth = new Auth($this->AccessKey, $this->SecretKey);
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
            var_dump($iterms);
        }
    }

    public function filestat($bucket = 'luoji-jwl', $key = '帮我又一课科技有限公司.jpg') {
        $bucketMgr = New BucketManager($this->auth);

        list($ret, $err) = $bucketMgr->stat($bucket, $key);
        echo "\n====> stat result: \n";
        if ($err !== null) {
            var_dump($err);
        } else {
            var_dump($ret);
        }
    }

    public function pfop($bucket = 'luoji-jwl', $key = '2015-5-21成功.mp3', $format = 'amr/ab/128k') {

        $pfop = New PersistentFop($this->auth, $bucket);

        $fops = 'avthumb/' . $format;
        list($id, $err) = $pfop->execute($key, $fops);

        echo "\n====> pfop avthumb result: \n";
        if ($err != null) {
            var_dump($err);
        } else {
            echo "PersistentFop Id: $id";
        }
    }

}
