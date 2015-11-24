<?php

/**
 * AudioClassModel
 */
class AudioModel {

    use Trait_DB;

use Trait_Redis;

    public $dbMaster; //主从数据库 配置
    public $tableName = '`a_audio`';
    public $adminLog;

    public function __construct() {
        $this->dbMaster = $this->getDb('audio');
        $this->adminLog = new AdminLogModel();
        $this->audioclass = new AudioClassModel();
        $this->Audiocontent = new AudioContentModel();
    }

    /**
     * 获取音频分类列表
     *
     * @return array result
     */
    public function getAudioList($page = 1, $size = 20,$where = '') {
        $p = $page > 0 ? $page : 1;
        $limit = ($p - 1) * $size . ',' . $size;

        try {
            $sql = 'SELECT * FROM ' . $this->tableName . ' where 1  '.$where.' ORDER BY a_id desc  limit ' . $limit;
            $stmt = $this->dbMaster->prepare($sql);
            $stmt->execute();

            $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $return = [];

            if ($rs) {
                foreach ($rs as $key => $val) {
                    $return[$key] = CustomArray::removekeyPrefix($val, 'a_');
                }
            }

            return $return;
        } catch (PDOException $e) {
            die($e->getMessage());
        }
    }

    public function getListByIds($aids, $fields = '*') {
        if (empty($aids))
            return false;
        try {
            $sql = 'SELECT ' . $fields . ' FROM ' . $this->tableName . ' where 1 and  a_schedule = 0 and a_id in(' . $aids . ')  ORDER BY a_id desc  ';
            $stmt = $this->dbMaster->prepare($sql);
            $stmt->execute();

            $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $return = [];

            if ($rs) {
                foreach ($rs as $key => $val) {
                    $return[$key] = CustomArray::removekeyPrefix($val, 'a_');
                }
            }

            return $return;
        } catch (PDOException $e) {
            die($e->getMessage());
        }
    }

    public function getNumber($where='') {
        try {

            $sql = "  SELECT count(*) as num FROM " . $this->tableName . " where 1 {$where}    ";
            $stmt = $this->dbMaster->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row['num'];
        } catch (PDOException $e) {
            die($e->getMessage());
        }
    }

    public function getAudioById($id, $fields = '*', $tid = 0,$type=0) {
        $list = array();
        if (!is_array($id) || empty($id))
            return $list;
        try {
            $fieldin = implode(',', $id);
            $sql = "SELECT {$fields} FROM {$this->tableName} WHERE (`a_id` in ({$fieldin}) and `a_schedule` = 0) ";
            if ($tid > 0) {
                $sql = "SELECT {$fields} FROM {$this->tableName} WHERE (`a_id` in ({$fieldin}) and  `a_schedule`='{$tid}') ";
            }
            
            if($type > 0){
                $sql.= "  and a_type = '".$type."' ";
            }

            $sql.= " ORDER BY FIND_IN_SET(`a_id`, '{$fieldin}')";
            $stmt = $this->dbMaster->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
            $stmt->execute();

            $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if ($rs) {
                foreach ($rs as $val) {
                    $list[] = CustomArray::removeKeyPrefix($val, 'a_');
                }
            }
            return $list;
        } catch (PDOException $e) {
            Tools::error($e);
        }
    }

    public function getAudioByEdit($id, $fields = '*', $tid = 0,$type=0) {
        $list = array();
        if (!is_array($id) || empty($id))
            return $list;
        try {
            $fieldin = implode(',', $id);
            $sql = "SELECT {$fields} FROM {$this->tableName} WHERE (`a_id` in ({$fieldin}) and `a_schedule` = 0) ";
            if ($tid > 0) {
                $sql = "SELECT {$fields} FROM {$this->tableName} WHERE `a_id` in ({$fieldin}) and  `a_schedule` in (0,{$tid}) ";
            }
            
            if($type > 0){
                $sql.=" and a_type = '".$type."' ";
            }

            $sql.= " ORDER BY FIND_IN_SET(`a_id`, '{$fieldin}')";
            $stmt = $this->dbMaster->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
            $stmt->execute();

            $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if ($rs) {
                foreach ($rs as $val) {
                    $list[] = CustomArray::removeKeyPrefix($val, 'a_');
                }
            }
            return $list;
        } catch (PDOException $e) {
            Tools::error($e);
        }
    }

    /**
     * 添加音频分类
     *
     * @param  array $data add data
     *
     * @return int       rowcount
     */
    public function insert($data) {

        if (!$data) {
            return false;
        }
        $adminuser = $_SESSION['a_user'];
        $data['operator_id'] = $adminuser['id'];
        $data['operator_name'] = $adminuser['name'];
        $data = CustomArray::addKeyPrefix($data, 'a_');

        try {
            $sql = 'INSERT INTO ' . $this->tableName . ' SET ' . $this->makeSet($data);
            $stmt = $this->dbMaster->prepare($sql);
            $stmt->execute();
            $newid = $this->dbMaster->lastInsertId();

            $log = array(
                'operator' => $adminuser['id'],
                'remark' => '后台添加音频：用户：' . $adminuser['user'] . ' 添加了音频标题为[' . $data['title'] . ']信息：' . serialize($data),
            );
            $this->adminLog->add($log);

            if ($newid) {
                //音频内容表同步
                $this->Audiocontent->insert(array('id' => $newid, 'content' => $data['a_content'], 'sign' => $data['a_sign'], 'content_count' => $data['a_content_count']));

                //音频类别中的音频数加1
                $cid = $data['a_class_id'];
                if (!empty($cid) && is_numeric($cid)) {
                    $aclass = $this->audioclass->findById($cid);
                    $count = $aclass['count'] + 1;
                    $this->audioclass->update(array('id' => $cid, 'count' => $count));
                }
            }
            return $newid;
        } catch (PDOException $e) {
            die($e->getMessage());
        }
    }

    /**
     * find audio class by id
     *
     * @param  int $id class id
     *
     * @return mixed     array or false
     */
    public function findById($id) {

        try {
            $sql = 'SELECT * FROM ' . $this->tableName . ' WHERE a_id = :id LIMIT 1';
            $stmt = $this->dbMaster->prepare($sql);
            $stmt->execute([':id' => $id]);

            $rs = $stmt->fetch(PDO::FETCH_ASSOC);

            return $rs ? CustomArray::removekeyPrefix($rs, 'a_') : false;
        } catch (PDOException $e) {
            die($e->getMessage());
        }
    }

    /**
     * update audio class
     *
     * @param  array $data update data
     *
     * @return mixed       false or rowcount
     */
    public function update($data) {

        if (!$data || !isset($data['id'])) {
            return false;
        }

        $id = $data['id'];
        unset($data['id']);
        $data = CustomArray::addKeyPrefix($data, 'a_');

        try {
            $sql = "UPDATE " . $this->tableName . ' SET ' . $this->makeSet($data) .
                    ' WHERE a_id = :id LIMIT 1';
            $stmt = $this->dbMaster->prepare($sql);
            $stmt->execute([':id' => $id]);
            $res = $stmt->rowCount();
            if ($res > 0) {
                $contNum = mb_strlen(strip_tags($data['a_content']), 'UTF-8');
                $contArr = array('id' => $id, 'content' => $data['a_content'], 'content_count' => $contNum);
                if (!empty($data['a_sign'])) {
                    $contArr['sign'] = $data['a_sign'];
                }
                //add audio content
                $a_content = $this->Audiocontent->findById($id);
                if(!empty($a_content)){
                    $this->Audiocontent->update($contArr);
                }else{
                    $this->Audiocontent->insert($contArr);
                }
                
            }
            return $res;
        } catch (PDOException $e) {
            die($e->getMessage());
        }
    }

    /**
     * 有tid时，把状态都置为0，没有时按照给的音频id,批量修改排期id 字段
     * @param type $data 更新参数数组
     * @param type $tid 排期id
     * @return boolean
     */
    public function updateAudioStatus($data, $tid = 0) {
        if ($tid == 0) {
            if (!$data || !isset($data['id'])) {
                return false;
            }
        }
        $ids = $data['id'];
        unset($data['id']);
        $data = CustomArray::addKeyPrefix($data, 'a_');

        try {
            $sql = 'UPDATE ' . $this->tableName . ' SET ' . $this->makeSet($data) .
                    ' WHERE ';

            if ($tid > 0) {   //把当前排期的音频状态都置为0
                $sql.= ' a_schedule=' . $tid;
            } else {       // 音频排期
                $sql.=' a_id  in(' . $ids . ')  and a_schedule = 0 ';
            }

            $stmt = $this->dbMaster->prepare($sql);
            $stmt->execute();

            return $stmt->rowCount();
        } catch (PDOException $e) {
            die($e->getMessage());
        }
    }
    
    
    public function checkRetopic($id, $tid = 0,$fields = 'a_id,a_schedule') {
        $list = array();
        if (!is_array($id) || empty($id))
            return $list;
        try {
            
            $fieldin = implode(',', $id);
            
            $sql = "SELECT {$fields} FROM {$this->tableName} WHERE (`a_id` in ({$fieldin}) and `a_schedule` > 0) ";
            if($tid> 0){
                $sql.="  and  `a_schedule` != '{$tid}' ";
            }
            
            
            $sql.= " ORDER BY FIND_IN_SET(`a_id`, '{$fieldin}') ";
            $stmt = $this->dbMaster->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
            $stmt->execute();

            $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $badids = '';
            if ($rs) {
                foreach ($rs as $val) {
                    if(!empty($val['a_id'])){
                        $badids.=$val['a_id'].',';
                    }
                    
                }
            }
            
            return !empty($badids)?$badids:false;
        } catch (PDOException $e) {
            Tools::error($e);
        }
    }
    
    
    
    

}
