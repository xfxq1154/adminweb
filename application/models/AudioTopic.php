<?php

/**
 * @name AudioTopicModel
 * @desc 音频排期model
 * @author hphui
 */
class AudioTopicModel {

    use Trait_DB;

    public $dbMaster;
    public $tableName = 'a_audio_topic';
    public $adminLog;

    /**
     * 搜索条件
     * @var array 
     */
    public $condition = array();

    /**
     * 查询条件
     * @var array 
     */
    public $where = array();

    public function __construct() {
        $this->dbMaster = $this->getDb('audio');
        $this->adminLog = new AdminLogModel();
        $this->audioTopicRecord = new AudioTopicRecordModel();
        $this->audio = new AudioModel();
    }

    public function add($data) {
        if (empty($data) || !is_array($data))
            return false;
        $f = "";
        $v = "";
        $array = array();
        foreach ($data as $key => $value) {
            $f .= ",`t_" . $key . "`";
            $v .= ",:" . $key;
            $array[':' . $key] = $value;
        }
        try {
            $sql = " INSERT INTO `" . $this->tableName . "` (" . substr($f, 1) . ") "
                    . "VALUES (" . substr($v, 1) . ") ";
            $stmt = $this->dbMaster->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
            $stmt->execute($array);
            $id = $this->dbMaster->lastInsertId();
            if ($id) {
                $adminuser = $_SESSION['a_user'];
                $log = array(
                    'operator' => $adminuser['id'],
                    'remark' => '添加音频排期：用户：' . $adminuser['user'] . ' 添加了一条音频排期：' . serialize($data),
                );
                $this->adminLog->add($log);

                //添加排期版本记录
                $recordArr = array('tid' => $id, 'audio' => $data['audio'], 'datetime' => date('Y-m-d H:i:s'), 'uid' => $adminuser['id'], 'user' => $adminuser['user']);
                $this->audioTopicRecord->add($recordArr);
                //修改音频 排期状态
                $this->audio->updateAudioStatus(array('id' => $data['audio'], 'schedule' => $id));
            }
            return $id;
        } catch (PDOException $e) {
            Tools::error($e);
        }
    }

    public function update($id, $data) {
        if (empty($data) || !is_array($data) || empty($id))
            return false;
        $f = "";
        $array = array();
        foreach ($data as $key => $value) {
            $f .= ",`t_" . $key . "`= :" . $key;
            $array[':' . $key] = $value;
        }
        try {
            if(($data['class'] ==  0) || ($data['class'] == 2)){
                if(!empty($data['audio'])){
                    $this->audio->updateAudioStatus(array('id' => $data['audio'], 'schedule' => 0),$id);
                }
                    
            }
            
            $sql = " UPDATE " . $this->tableName . "  SET " . substr($f, 1) . "  WHERE `t_id` = {$id}";
            $stmt = $this->dbMaster->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
            $stmt->execute($array);
            $res = $stmt->rowCount();
            $adminuser = $_SESSION['a_user'];

            $log = array(
                'operator' => $adminuser['id'],
                'remark' => '修改音频排期：用户：' . $adminuser['user'] . ' 修改了音频排期[' . $id . ']信息：' . serialize($data),
            );
            $this->adminLog->add($log);

            if ($res >= 0) {
                if(!empty($data['audio'])){
                    $recordArr = array('tid' => $id, 'audio' => $data['audio'], 'datetime' => date('Y-m-d H:i:s'), 'uid' => $adminuser['id'], 'user' => $adminuser['user']);
                    $this->audioTopicRecord->add($recordArr); 
                }
                

                //修改音频 排期状态(电子书除外)
                if(($data['class'] ==  0) || ($data['class'] == 2)){
                    if(!empty($data['audio'])){
                        $this->audio->updateAudioStatus(array('id' => $data['audio'], 'schedule' => $id));
                    }
                    
                }
                
            }
            $this->deleteCache($id);
            return $res;
        } catch (PDOException $e) {
            Tools::error($e);
        }
    }
    
    public function deleteCache($id) {
   
        //排期信息
        $topic = $this->getTopicById($id);
        if($topic){
            $cache = new CacheModel();
            $cache->removeTopicRedis($topic['datetime']);
            //CacheModel::removeTopicRedis($topic['datetime']);
        }

    }

    public function delete($id) {
        if (empty($id))
            return false;
        try {
            $sql = "DELETE FROM {$this->tableName} WHERE `t_id` = :id Limit 1";
            $stmt = $this->dbMaster->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
            $stmt->execute(array(':id' => $id));
            $row = $stmt->rowCount();
            if ($row > 0) {
                $adminuser = $_SESSION['a_user'];
                $log = array(
                    'operator' => $adminuser['id'],
                    'remark' => '删除音频排期：用户：' . $adminuser['user'] . ' 删除了一条音频排期：' . $id,
                );
                $this->adminLog->add($log);
            }
            return $row;
        } catch (PDOException $e) {
            Tools::error($e);
        }
    }

    public function getTopicById($id, $fields = '*') {
        if (empty($id))
            return array();
        try {
            $sql = "SELECT {$fields} FROM {$this->tableName} WHERE `t_id` = :id limit 1";
            $stmt = $this->dbMaster->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
            $stmt->execute(array(':id' => $id));
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $row ? CustomArray::removeKeyPrefix($row, 't_') : array();
        } catch (PDOException $e) {
            Tools::error($e);
        }
    }
    
    public function getTopicByEbook($id, $fields = '*') {
        if (empty($id))
            return array();
        try {
            $sql = "SELECT {$fields} FROM {$this->tableName} WHERE `t_class` = 1 and CONCAT(',',t_audio,',') LIKE '%,{$id},%' ";
            $stmt = $this->dbMaster->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
            $stmt->execute();
            $row = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $row ? $row: array();
        } catch (PDOException $e) {
            Tools::error($e);
        }
    }
    
    
    /**
     * 获取排期列表
     * @return 
     */
    public function getList($where, $limit = '20', $sort = array(), $fields = "*") {
        $this->where = $where;

        $w = '';
        foreach ($this->where as $key => $value) {
            $w .= " AND `t_" . $key . "` = :" . $key;
            $this->condition[':' . $key] = $value;
        }


        //处理搜索关键词
        $w2 = $this->getSearchCondition();

        $s = '';
        $oreder = '';
        if ($sort) {
            
        }
        $oreder = substr($oreder, 2);
        $oreder = $oreder ? $oreder : ' `t_datetime` DESC';

        
        $sql = "select t_datetime from " . $this->tableName . " WHERE 1 {$w} {$w2} group by t_datetime  ORDER BY {$oreder} LIMIT {$limit}";
        $stmt = $this->dbMaster->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
        $stmt->execute($this->condition);
        $array = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $list = array();
        if (!empty($array)) {
            $dateList = '';
            foreach ($array as $av) {
                $topicDate = $av['t_datetime'];
                $dateList.="'" . $topicDate . "',";
            }


            try {
                $dateList = substr($dateList, 0, -1);
                $sql2 = " select {$fields} from " . $this->tableName . " WHERE 1 {$w} {$w2} and t_datetime in (" . $dateList . ") order by `t_datetime` DESC  ";
                $stmt = $this->dbMaster->prepare($sql2, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
                $stmt->execute($this->condition);
                $array = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $stmt->closeCursor();
            } catch (PDOException $e) {
                Tools::error($e);
            }
            
            if ($array) {

                foreach ($array as $row) {
                    $tmp = CustomArray::removeKeyPrefix($row, 't_');
                    if (isset($tmp['icon'])) {
                        $tmp['icon'] = Tools::formatImg($tmp['icon']);
                    }
                    if (isset($tmp['audio_icon'])) {
                        $tmp['audio_icon'] = Tools::formatImg($tmp['audio_icon']);
                    }
                    if (isset($tmp['audio_banner'])) {
                        $tmp['audio_banner'] = Tools::formatImg($tmp['audio_banner']);
                    }
                    $list[] = $tmp;
                }
            }
        }


        return $list;
    }

    public function getListByAid($aid, $fields = '*') {
        if ($aid < 1)
            return false;
        $today = date('Y-m-d',time());
        $sql = "select {$fields} from " . $this->tableName . " WHERE  t_class != 1 and  t_datetime > '".$today."' and  concat(',',t_audio,',') not like '%," . $aid . ",%'    ";

        $stmt = $this->dbMaster->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
        $stmt->execute($this->condition);
        $array = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $list = array();
        if ($array) {

            foreach ($array as $row) {
                $tmp = CustomArray::removeKeyPrefix($row, 't_');
                if(isset($tmp['icon'])){
                    $tmp['icon'] = Tools::formatImg($tmp['icon']);
                }
                if(isset($tmp['audio_icon'])){
                    $tmp['audio_icon'] = Tools::formatImg($tmp['audio_icon']);
                }
                if(isset($tmp['audio_banner'])){
                    $tmp['audio_banner'] = Tools::formatImg($tmp['audio_banner']);
                }
                $list[] = $tmp;
            }
        }
        return $list;
    }

    /**
     * 获取查询条数
     * @param type $where
     * @return type
     */
    public function getNumber($where = array(), $whereStr='') {

        $this->where = $where;

        $w = "";
        //处理搜索关键词
        $w2 = $this->getSearchCondition();

        foreach ($this->where as $key => $value) {
            $w .= " AND `t_" . $key . "` = :" . $key;
            $this->condition[':' . $key] = $value;
        }

        //$sql = "SELECT COUNT(*) `number` FROM `" . $this->tableName . "` WHERE 1 = 1 {$w} {$w2} group by t_datetime ";
        $sql = " select count(distinct(t_datetime)) as number from `" . $this->tableName . "` where 1 = 1 {$w} {$w2}  ";
        if(!empty($whereStr)){
            $sql.=" and  ".$whereStr;
        }
        $stmt = $this->dbMaster->prepare($sql);
        $stmt->execute($this->condition);
        $array = $stmt->fetch(PDO::FETCH_ASSOC);
        return $array['number'];
    }

    /**
     * 获取搜索条件
     * @param array $array
     * @return string
     */
    private function getSearchCondition() {
        $w2 = '';
        $c = false;
        //关键词
        if ($this->where['keyword']) {
            $c = true;
            $w2 .= ($w2 == '' ? ' AND (' : ' OR ') . "`t_title` like :title";
            $this->condition[':title'] = '%' . $this->where['keyword'] . '%';
            unset($this->where['keyword']);
        }
        if ($c == true) {
            $w2 .= ')';
        }
        //ID筛选
        if ($this->where['id']) {
            $w2 .= " AND `t_id` IN ({$this->where['id']}) ";
            unset($this->where['id']);
        }

        return $w2;
    }

    public function getListByIds($aids, $fields = '*',$formatImg=true) {
        if (empty($aids))
            return false;
        try {
            $sql = 'SELECT ' . $fields . ' FROM ' . $this->tableName . ' where  t_id in(' . $aids . ')  ORDER BY t_id desc  ';
            $stmt = $this->dbMaster->prepare($sql);
            $stmt->execute();

            $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $return = [];

            if ($rs) {
                foreach ($rs as $key => $val) {
                    $tmp = CustomArray::removekeyPrefix($val, 't_');
                    if($formatImg){
                        if(isset($tmp['icon'])){
                        $tmp['icon'] = Tools::formatImg($tmp['icon']);
                        }
                        if(isset($tmp['audio_icon'])){
                            $tmp['audio_icon'] = Tools::formatImg($tmp['audio_icon']);
                        }
                        if(isset($tmp['audio_banner'])){
                            $tmp['audio_banner'] = Tools::formatImg($tmp['audio_banner']);
                        }
                    }
                    
                    $return[$key] = $tmp;
                }
            }

            return $return;
        } catch (PDOException $e) {
            die($e->getMessage());
        }
    }
    
    public function updateDurationByTid($tid){
        $tinfo = $this->getTopicById($tid);
        $aids  = $tinfo['audio'];
        $aList = $this->audio->getAudioById(explode(',',$aids),'*',$tid);
        if(!empty($aList)){
            $duration = 0;
            foreach($aList as $v){
                $duration+=$v['duration'];
            }
            
            if($duration>0){
                $res = $this->update($tid, array('duration'=>$duration));
            }
        }
        
        return $res;
    }

}
