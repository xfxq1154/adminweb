<?php

/**
 * @name CrondModel
 * @author hph
 * @desc 定时脚本
 */
class CrondModel {

    use Trait_DB,
        Trait_Api,
        Trait_Redis;
        

    public $qiniu; //七牛SDK
    public $dbMaster, $audioQiniu;
    public $pushApi;
    public $pushLog;

    public function __construct() {
        $this->dbMaster = $this->getDb('audio');
        $this->qiniu = new QiniuModel();
//        $this->audioQiniu = new AudioQiniuModel();
        $this->pushApi = $this->getApi('push');
        $this->pushLog = new PushLogModel();
        $this->audioQiniu = new AudioQiniuModel();
    }
    
    /**
     * 每天凌晨6:45同步当天的收费音频包到购买列表
     */
    public function rsyncTopicAudio() {
        
        $sql = "SELECT `t_id`, `t_audio`, `t_datetime` FROM `a_audio_topic` WHERE `t_datetime` = curdate() AND `t_class` = 0 and `t_status`=1 ";
        $stmt = $this->dbMaster->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
        $stmt->execute();
        $array = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        if($array){
            foreach ($array as $topic) {
                $tid = $topic['t_id'];
                $datetime = $topic['t_datetime'];
                $sql = "replace into `a_audiotopic_book` (`a_mixed_id`,`a_type`,`a_date`) values ('{$tid}','1','{$datetime}')";
                $stmt = $this->dbMaster->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
                $stmt->execute();
                $stmt->closeCursor();
                usleep(500);
                
            }
            
        }  
        
    }
    /**
     * 同步电子书到购买列表,根据当日上线的电子书 
     */
    public function rsyncTopicBook(){
        $sql = "SELECT `t_id`, `t_audio`, `t_datetime` FROM `a_audio_topic` WHERE `t_datetime` = curdate() AND `t_class` = 1 and `t_status`=1 ";
        $stmt = $this->dbMaster->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
        $stmt->execute();
        $array = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        if($array){
            foreach ($array as $topic) {
                $sql = "SELECT `b_id`,`b_status` FROM `b_book` WHERE `b_status` = 2 and `b_id` in ({$topic['t_audio']})";
                $stmt = $this->dbMaster->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
                $stmt->execute();
                $book_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $stmt->closeCursor();
                if($book_list){
                    foreach($book_list as $book){
                        $book_id = $book['b_id'];
                        $datetime = $topic['t_datetime'];
                        $sql = "replace into `a_audiotopic_book` (`a_mixed_id`,`a_type`,`a_date`) values ('{$book_id}','2','{$datetime}')";
                        $stmt = $this->dbMaster->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
                        $stmt->execute();
                        $stmt->closeCursor();
                        usleep(500);
                    }
                    $sql = "UPDATE `b_book` SET `b_status`=1  WHERE `b_status` = 2 and `b_id` in ({$topic['t_audio']})";
                    $stmt = $this->dbMaster->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
                    $stmt->execute();
                }
            }
        }
    }
    
    /**
     * 同步单条音频和电子书 到汇总表 便于收索
     */
    public function rsyncAudioEbook() {
       $this->rsyncTopicAudio();
       $this->rsyncTopicBook();
    }
    
    public function pushTopicMsg(){
        $sql = "SELECT `t_id`, `t_audio_title`, `t_audio_brife` FROM `a_audio_topic` WHERE `t_datetime` = curdate() AND `t_class` = 2 and `t_status`=1 limit 1";
        $stmt = $this->dbMaster->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
        $stmt->execute();
        $topic = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        $interface = '/notifications/pushToAllDevices';
        $push_params = [
            'sys_code' => '04',
            'title' => '您的今日音频已经准备好',
            'content' => '马上去得到！'
        ];
        if($topic){
            $push_params['title'] = $topic['t_audio_title']? $topic['t_audio_title'] : $push_params['title'];
            $push_params['content'] = $topic['t_audio_brife']? $topic['t_audio_brife'] : $push_params['content'];
        }
        $push_params['sign'] = $this->pushCreateSign($push_params);
        //var_dump($push_params);
        $return = $this->pushApi->post($interface, $push_params);
        $push_params['return'] = $return;
        $data = [
            'from' => 0,
            'to' => 0,
            'contents' => serialize($push_params)
        ];
        $this->pushLog->add($data);
    }
    /**
     * 生成 支付签名
     * @param type $data
     * @return boolean
     */
    public function pushCreateSign($data){
        if(empty($data) || !is_array($data)) return false;
        if( !(count($data) > 0) ) return false;
        
        $build = array();
        foreach ($data as $k => $v){
            if($v == '') continue;
            $build[$k] = $k .'=' . $v;
        }
        ksort($build);
        return md5(implode('&', $build).'&key=K1XOQ7AYW2UCO21SVA5UMK1YOETVAAQX');
    }
    
    
    
    public  function qiniuios(){
        $sql = "SELECT `a_id`, `a_mp3_url` FROM `a_audio` WHERE 1 ";
        $stmt = $this->dbMaster->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
        $stmt->execute();
        $audio_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        
        if($audio_list){
            foreach ($audio_list as $audio) {
                
                $fop = $this->qiniu->pfop($audio['a_mp3_url'], 'mp3/ab/64000/acodec/libmp3lame');
                if (empty($fop['error']) && !is_array($fop)) {
                    $next = (time() + 60);
                    $this->audioQiniu->insert(array('aid' => $audio['a_id'], 'nid' => $fop, 'next' => $next));
                }
                usleep(500);
            }
        }
        return;
    }
    
    

    /**
     * 七牛脚本入口
     */
    public function qiniu() {
        $list = $this->getpfoplist();

        if ($list) {

            foreach ($list as $row) {
                $this->getpfopstatus(CustomArray::removeKeyPrefix($row, 'q_'));
                usleep(5000);
            }
        }
        return;
    }

    public function getpfoplist() {
        //获取列表 
        $sql = 'select * from `a_qiniu` where `q_count` < 10 and `q_status` = 1 And `q_next` <= UNIX_TIMESTAMP()  ';
        $stmt = $this->dbMaster->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
        $stmt->execute();
        $array = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        return $array;
    }

    public function getpfopstatus($data) {

        $status = $this->qiniu->pfopStatusById($data['nid']);
        if ($status['error']) {
            //出现错误
            $nexttime = $data['count'] * 60 + 60;
            $sql = 'UPDATE `a_qiniu` SET `q_count` = `q_count` + 1,`q_next` = ' . $nexttime . ' + UNIX_TIMESTAMP() WHERE `q_id` = ' . $data['id'] . ' Limit 1';
            $stmt = $this->dbMaster->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
            $stmt->execute();
            $stmt->closeCursor();
            return;
        }
        $this->insertAvthumb($status);
        
        switch ($status['code']) {
            case 0:
                $items = $status['items'];
                if(!$items){ return; }
                foreach($items as $file){
                    $thumbKey = $file['key'] ;
                    $thumbCmd = $file['cmd'] ;
                    $filecode = $file['code'];
                    
                    if($filecode == 0){
                        $avinfo = $this->qiniu->getAvinfo($thumbKey);
                        //处理成功
                        $sql = 'UPDATE `a_qiniu` SET `q_count` = `q_count` + 1 , `q_status` = 0 WHERE `q_id` = ' . $data['id'] . ' Limit 1';
                        $stmt = $this->dbMaster->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
                        $stmt->execute();
                        $stmt->closeCursor();
                        if(strpos($thumbCmd, '/mp3/ab/48000/') !== false){
                            //48000
                            $sql = "UPDATE `a_audio` SET `a_low_url` = '" . $thumbKey . "' ,`a_low_size` = '".$avinfo['format']['size']."' WHERE `a_id`=" . $data['aid'] . ' Limit 1';
                            $stmt = $this->dbMaster->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
                            $stmt->execute();
                            $stmt->closeCursor();

                            $sql = "UPDATE `a_audio_mp3_record` SET `m_low` = '" . $thumbKey . "' ,`m_low_size` = '".$avinfo['format']['size']."'  WHERE `m_mp3key`='" . $status['inputKey'] . "'";
                            $stmt = $this->dbMaster->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
                            $stmt->execute();
                            $stmt->closeCursor();
                        }
                        if(strpos($thumbCmd, '/mp3/ab/64000/') !== false){
                            //64000 amr
                            $sql = "UPDATE `a_audio` SET `a_amr_url` = '" . $thumbKey . "' ,`a_thumb_size` = '".$avinfo['format']['size']."' WHERE `a_id`=" . $data['aid'] . ' Limit 1';
                            $stmt = $this->dbMaster->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
                            $stmt->execute();
                            $stmt->closeCursor();

                            $sql = "UPDATE `a_audio_mp3_record` SET `m_amr` = '" . $thumbKey . "' ,`m_thumb_size` = '".$avinfo['format']['size']."'  WHERE `m_mp3key`='" . $status['inputKey'] . "'";
                            $stmt = $this->dbMaster->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
                            $stmt->execute();
                            $stmt->closeCursor();


                        }
                        if(strpos($thumbCmd, '/mp3/ab/128000/') !== false){ 

                            //存在 128000

                            $sql = "UPDATE `a_audio` SET `a_ios_url` = '" . $thumbKey . "' ,`a_ios_size` = '".$avinfo['format']['size']."' WHERE `a_id`=" . $data['aid'] . ' Limit 1';
                            $stmt = $this->dbMaster->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
                            $stmt->execute();
                            $stmt->closeCursor();

                            $sql = "UPDATE `a_audio_mp3_record` SET `m_ios` = '" . $thumbKey . "' ,`m_ios_size` = '".$avinfo['format']['size']."'  WHERE `m_mp3key`='" . $status['inputKey'] . "'";
                            $stmt = $this->dbMaster->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
                            $stmt->execute();
                            $stmt->closeCursor();
                        }
                        
                    }
                }
                
                break;
            case 3: case 4:
                //处理失败
                $sql = 'UPDATE `a_qiniu` SET `q_count` = 10 WHERE `q_id` = ' . $data['id'] . ' Limit 1';
                $stmt = $this->dbMaster->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
                $stmt->execute();
                $stmt->closeCursor();


                break;
            case 1: case 2:
            default:
                $sql = 'UPDATE `a_qiniu` SET `q_count` = `q_count` + 1,`q_next` = 60 + UNIX_TIMESTAMP() WHERE `q_id` = ' . $data['id'] . ' Limit 1';
                $stmt = $this->dbMaster->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
                $stmt->execute();
                $stmt->closeCursor();
                break;
        }
    }

    public function insertAvthumb($data) {

        $sql = 'insert into `a_avthumb` (
                `a_inputbucket`,
                `a_inputkey`,
                `a_pid`,
                `a_code`,
                `a_pipeline`,
                `a_reqid`,
                `a_desc`,
                `a_cmd`,
                `a_items_key`
                
                ) values (
                
                :inputbucket,
                :inputkey,
                :pid,
                :code,
                :pipeline,
                :reqid,
                :desc,
                :cmd,
                :items_key
                )';
        $array = array(
            ':inputbucket' => (string)$data['inputBucket'],
            ':inputkey' => (string)$data['inputKey'],
            ':pid' => (string)$data['id'],
            ':code' => (int)$data['code'],
            ':pipeline' => (string)$data['pipeline'],
            ':reqid' => (string)$data['reqid'],
            ':desc' => (string)$data['desc'],
            ':cmd' => (string)$data['items'][0]['cmd'],
            ':items_key' => (string)($data['items'][0]['key'] ? $data['items'][0]['key'] : $data['items'][0]['error'])
        );
        //var_dump($sql,$array);
        $stmt = $this->dbMaster->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
        $stmt->execute($array);
        $stmt->closeCursor();
        return;
    }
    
    //更新音频用户表中 用户的手机号
    public function rsyncUserPhone(){
            $userMod = new AudioUsersModel();
            $page = 1;
            $size = 100;
            $where = " and  u_phone ='' ";
            
            do{ 
                    $userlist = $userMod->getUserList($page, $size, $where);
                    if(!$userlist){
                        break;
                    }
                    if($userlist){
                            foreach($userlist as $v){
                                    $uids[] = $v['uid'];
                            }
                        
                            if($uids && is_array($uids)){
                                    $ucapinfo = $userMod->getUCApiUserInfo($uids);
                                    
                                    if($ucapinfo && isset($ucapinfo) && is_array($ucapinfo)){
                                            foreach($uids as $v){
                                                    if(isset($ucapinfo[$v])){
                                                            $userid = $ucapinfo[$v]['id'];
                                                            $uphone = $ucapinfo[$v]['phone'];
                                                            if($userid && $uphone){
                                                                    $re = $userMod->update(array('uid'=>$userid,'phone'=>$uphone));
                                                                    if($re >= 0 ){
                                                                        $error = '';
                                                                    }else{
                                                                        $error = " update false user id :".$userid."\n";
                                                                    }
                                                                    

                                                            }
                                                    }
                                                        
                                                    
                                            }
                                    }
                            }    
                    }

                    
                    
                    $page++;
                
            }while (true);
            
    }


}
