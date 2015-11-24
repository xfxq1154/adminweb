<?php

/**
 * Created by PhpStorm.
 * User: momoko
 * Date: 15/11/17
 * Time: 22:47
 */
class VerseRelationModel
{

    use Trait_DB;
    protected $dbMaster;
    protected $dbSlave;
    protected $tableName = 'a_verse_relation';
    protected $adminLog;

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

    /** @var BookModel */
    protected $book;

    public function __construct()
    {
        $this->dbMaster = $this->getDb('audio');
        $this->dbSlave = $this->getDb('audio');
        $this->adminLog = new AdminLogModel();
        $this->book = new BookModel();
    }

    /**
     * 写入记录，支持多条
     * @param array $data
     * @return bool|string
     */
    public function insert(array $data = array())
    {
        if (!is_array($data) || empty($data)) {
            return false;
        }

        try {

            $sql = 'INSERT ' . $this->tableName . ' (r_vid, r_type, r_object_id) VALUES  (:vid, :type, :object_id)';
            $stmt = $this->dbMaster->prepare($sql);

            if (is_array($data[0])) {
                foreach ($data as $value) {
                    $stmt->bindParam(':vid', $value['vid']);
                    $stmt->bindParam(':type', $value['type']);
                    $stmt->bindParam(':object_id', $value['object_id']);
                    $stmt->execute();
                }
            } else {

                $stmt->bindParam(':vid', $data['vid']);
                $stmt->bindParam(':type', $data['type']);
                $stmt->bindParam(':object_id', $data['object_id']);
                $stmt->execute();
            }

            return $this->dbMaster->lastInsertId();

        } catch (PDOException $e) {
            Tools::error($e);
        }
    }

    /**
     * 设置relation数据
     * @param int $verseId
     * @param array $relation
     * @return array|bool
     */
    public function setRelationData($verseId = 0, $relation = array())
    {

        if (!$verseId || !is_array($relation) || empty($relation)) {
            return false;
        }

        $bookInfo = $this->book->getBookById($relation, ' b_id, b_type ');

        $addRelationData = array();
        if ($bookInfo) {

            foreach ($bookInfo as $index => $value) {
                foreach ($relation as $id) {

                    if ($value['id'] == $id) {

                        $addRelationData[$index]['vid'] = $verseId;
                        $addRelationData[$index]['object_id'] = $id;
//                        如果电子书的type = 1(全文电子书)， $addRelationData[]['type'] = 3 否则是2
                        $addRelationData[$index]['type'] = ($value['type'] == 1 ? 3 : 2);
                    }
                }
            }
        }

        return $addRelationData;
    }

    /**
     * find 金句关联的电子书
     * @param int $vid
     * @param string $f
     * @return array|bool
     */
    public function findRelationsByVid($vid = 0, $f = '*')
    {

        if (!$vid) {
            return false;
        }

        try {
            $sql = 'SELECT ' . $f . ' FROM ' . $this->tableName . ' WHERE r_vid = :vid';

            $stmt = $this->dbSlave->prepare($sql);
            $stmt->execute(array(
                ':vid' => $vid
            ));

            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $return = array();

            if ($result) {
                foreach ($result as $index => $value) {
                    $return[$index] = CustomArray::removeKeyPrefix($value, 'r_');
                }
            }

            return $return;

        } catch (PDOException $e) {
            Tools::error($e);
        }
    }

    /**
     * 重新设置relation
     * @param array $relation
     * @param int $vid
     * @return bool
     */
    public function reSetRelation(array $relation = array(), $vid = 0)
    {
        if (!$vid || !is_array($relation)) {
            return false;
        }

        try {

            $this->dbMaster->beginTransaction();

            $sql = 'DELETE FROM ' . $this->tableName . ' WHERE r_vid = :vid AND (r_type = 2 OR r_type = 3)';
            $stmt = $this->dbMaster->prepare($sql);
            $stmt->execute(array(
                ':vid' => $vid
            ));

            if ($relation) {

                $addRelationData = $this->setRelationData($vid, $relation);
                $this->insert($addRelationData);
            }

            $this->dbMaster->commit();

            return true;
        } catch (PDOException $e) {
            $this->dbMaster->rollBack();
            Tools::error($e);
        }
    }

    public function findById($id, $fields = '*') {

        try {
            $sql = 'SELECT ' . $fields . ' FROM ' . $this->tableName . ' WHERE r_id = :id LIMIT 1';
            $stmt = $this->dbSlave->prepare($sql);
            $stmt->execute([':id' => $id]);

            $rs = $stmt->fetch(PDO::FETCH_ASSOC);
            $stmt->closeCursor();
            return $rs ? ['0'=>CustomArray::removekeyPrefix($rs, 'r_')] : false;
        } catch (PDOException $e) {
            Tools::error($e);
        }
    }

    /**
     * 获取列表
     * @return
     */
    public function getList($where=array(), $limit = '20', $sort = array(), $fields = "*", $strWhere = '') {
        $this->where = $where;

        $w = '';



        if ($this->where) {
            foreach ($this->where as $key => $value) {
                $w .= " AND `r_" . $key . "` = :" . $key;
                $this->condition[':' . $key] = $value;
            }
        }


        $s = '';
        $oreder = '';
        if ($sort) {

        }
        $oreder = substr($oreder, 2);
        $oreder = $oreder ? $oreder : ' `r_id` DESC';

        $sql = "select {$fields} from " . $this->tableName . " WHERE 1 {$w}  {$strWhere}  ORDER BY {$oreder} LIMIT {$limit}";
        $stmt = $this->dbSlave->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
        $stmt->execute($this->condition);
        $array = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $list = array();
        if ($array) {
            foreach ($array as $row) {
                $list[] = CustomArray::removeKeyPrefix($row, 'r_');
            }
        }
        return $list;
    }

    /**
     * 获取排期当天的推荐金句
     * @param type $topicid
     * @param type $type  type:1 排期  2 电子书干货 3 电子书全文
     * @param type $fields
     * @param type $order
     * @return type
     */
    public function getVerseByTid($topicid,$userid,$type=1,$fields='*',$order='desc'){
        if(empty($topicid)) return [];
        try {
            $sql = 'SELECT ' . $fields . ' FROM ' . $this->tableName . ' WHERE `r_object_id` = '.$topicid.'  and r_type = '.$type.'    order by r_id '.$order.' LIMIT 1 ';
            $stmt = $this->dbSlave->prepare($sql);
            $stmt->execute();

            $rs = $stmt->fetch(PDO::FETCH_ASSOC);
            $stmt->closeCursor();
            if($rs){
                $rs = CustomArray::removekeyPrefix($rs, 'r_');
                $verseMod = new VerseModel();

                $vinfo = $verseMod->getById($rs['vid']);
                //查看是否有关联电子书,有则查询金句关联的电子书，优先显示干货版
                $ebookListRelation = $this->getVerseByVid($rs['vid']);
                if($ebookListRelation){
                    $vinfo['book_id'] = ($ebookListRelation)?($ebookListRelation['object_id']):[];//金句关联的电子书id


                }
            }

            return $vinfo?['0'=>$vinfo]:[];
        } catch (PDOException $e) {
            Tools::error($e);
        }
    }

    //获取金句关联的电子书
    public function getVerseByVid($vid,$fields='*'){
        if(empty($vid)) return [];
        try {
            $sql = 'SELECT ' . $fields . ' FROM ' . $this->tableName . ' WHERE `r_vid` = '.$vid.'  and r_type > 1    order by r_type asc limit 1 ';
            $stmt = $this->dbSlave->prepare($sql);
            $stmt->execute();

            $rs = $stmt->fetch(PDO::FETCH_ASSOC);
            $stmt->closeCursor();


            return $rs?(CustomArray::removekeyPrefix($rs, 'r_')):[];
        } catch (PDOException $e) {
            Tools::error($e);
        }
    }


    /**
     * get list by ids string
     * $id Array
     */
    public function getListByIds($id, $fields = '*') {
        $list = array();
        if (empty($id))
            return $list;
        try {
            $fieldin = implode(',', $id);
            $sql = "SELECT {$fields} FROM {$this->tableName} WHERE `r_vid` in ({$fieldin}) and r_type > 1 order by r_type desc   ";


            $stmt = $this->dbSlave->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
            $stmt->execute();

            $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if ($rs) {
                foreach ($rs as $val) {
                    $tmp    = CustomArray::removeKeyPrefix($val, 'r_');
                    $list[$tmp['vid']] = $tmp;
                }
            }
            return $list;
        } catch (PDOException $e) {
            Tools::error($e);
        }
    }


    public function add($data,$check=true,$type=1)
    {
        if (empty($data))
        {
            return false;
        }
        
        if($check)
        {
                //查询此条金句是否已经关联过此排期
                $row = $this->find(array('object_id'=>$data['object_id'],'vid'=>$data['vid'],'type'=>$type));
                if($row>0){
                        return $row;
                }

                //查询此排期是否已经关联过其他排期,有则删除
                $row = $this->find(array('object_id'=>$data['object_id'],'type'=>$type));
                if($row>0){
                        $this->del($data['object_id'],$type);
                }
        }
        

        $data = CustomArray::addKeyPrefix($data, 'r_');
        $data = $this->makeInsert($data);

        try {
            $sql = 'INSERT ' . $this->tableName . ' ( ' . $data['keys'] . ' ) VALUES (' . $data['values'] . ')' ;
            $stmt = $this->dbMaster->prepare($sql);
            $stmt->execute();

            $lastInsertId = $this->dbMaster->lastInsertId();

            return $lastInsertId;
        } catch (PDOException $e) {
            Tools::error($e);
        }
        return true;
    }


    /**
     * 
     * @param type $data  :string  or array
     * @param type $fields
     * @param type $count: return number or array
     * @return type
     */
    public function find($data,$fields='*',$count=true) {

        try
        {
            $where = $this->setWhere($data);
            $sql = 'SELECT  '.$fields;
            if($count){
                    $sql = 'SELECT count(*) as num ';
            }
            
            $sql.= '  FROM ' . $this->tableName . ' WHERE 1 '.$where.'  LIMIT 1';
            $stmt = $this->dbMaster->prepare($sql);
            $stmt->execute();

            $rs = $stmt->fetch(PDO::FETCH_ASSOC);
            $stmt->closeCursor();
            
            
            if($count){
                return (int)$rs['num'];
            }else{
                return $rs?(CustomArray::removeKeyPrefix($rs, 'r_')):array();
            }
            
        }
        catch (PDOException $e)
        {
            Tools::error($e);
        }
    }

    public function del($tid,$type=1)
    {
        try
        {
            $sql = ' delete  FROM ' . $this->tableName . ' WHERE r_object_id = '.$tid.'  and r_type = '.$type.'  LIMIT 1';
            $stmt = $this->dbSlave->prepare($sql);
            $stmt->execute();

            $rs = $stmt->rowCount();
            $stmt->closeCursor();
            return $rs;
        }
        catch (Exception $e)
        {
            Tools::error($e);
        }
    }
    
    
    
    
    //同步排期记录中电子书关联的金句日期为当前排期的日期
    public function editTopicBookRelation($ebookid,$topicDate)
    {
            if(empty($ebookid) || empty($topicDate))
                    return false;
            $bookidArr  = explode(',', $ebookid);
            $date       = (int)date('Ymd',  strtotime($topicDate));
            if(count($bookidArr) > 0)
            {
                    
                    foreach($bookidArr as $v){
                            
                            //查询此条排期电子书是否关联过金句，有则修改日期，没有返回
                            $wh = ' AND r_object_id = '.$v.' AND r_type > 1 ';
                            $row = $this->find($wh,'*',false);
                            if(isset($row['id'])){
                                    $re = $this->update($date,$row['id']);
                            }
                            
                    }
            }
            return $re;
    }    
    
    public function update($date,$id)
    {
            if(empty($date) || empty($id)) return false;
            $sql = ' UPDATE  '.$this->tableName.' set r_date= '.$date.' WHERE r_id =  '.$id;
            $stmt = $this->dbSlave->prepare($sql);
            $stmt->execute();

            $rs = $stmt->rowCount();
            $stmt->closeCursor();
            return $rs;
    }

    public function setWhere($where)
    {
        if(empty($where))
            return '';

        $fv = "";
        if(is_array($where))
        {
            foreach ($where as $key => $value)
            {
                $fv .= " and `r_" . $key . "`='".$value."'";
            }
        }
        elseif(is_string($where))
        {
            $fv.=$where;
        }

        return $fv;
    }

    /**
     * 查找关联的金句
     * @param $bookId
     * @param string $f
     * @return array|bool
     */
    public function findRelationByBookId($bookId, $f = '*')
    {
        if (!$bookId) {
            return false;
        }

        try {
            $sql = 'SELECT ' . $f . ' FROM ' . $this->tableName . ' WHERE r_object_id = :book_id AND (r_type = 2 OR r_type = 3)';

            $stmt = $this->dbSlave->prepare($sql);
            $stmt->execute(array(
                ':book_id' => $bookId
            ));

            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $return = array();

            if ($result) {
                foreach ($result as $index => $value) {
                    $return[$index] = CustomArray::removeKeyPrefix($value, 'r_');
                }
            }

            return $return;

        } catch (PDOException $e) {
            Tools::error($e);
        }
    }

    /**
     * 重新设置电子书关联的金句
     * @param array $relation 关联的金句
     * @param int $bookId
     * @param int $type
     * @return bool
     */
    public function reSetRelationFromBook(array $relation = array(), $bookId = 0, $type = 0)
    {
        if (!$bookId || !is_array($relation) || !$type) {
            return false;
        }

        try {

            $this->dbMaster->beginTransaction();

            $sql = 'DELETE FROM ' . $this->tableName . ' WHERE r_object_id = :book_id AND (r_type = 2 OR r_type = 3) AND r_vid';
            $stmt = $this->dbMaster->prepare($sql);

            $stmt->execute(array(
                ':book_id' => $bookId,
            ));

            if ($relation) {
                $addData = array();
                foreach ($relation as $vid) {

                    $addData[] = array(
                        'vid' => $vid,
                        'type' => $type,
                        'object_id' => $bookId
                    );
                }
                $this->insert($addData);
            }

            $this->dbMaster->commit();

            return true;
        } catch (PDOException $e) {
            $this->dbMaster->rollBack();
            Tools::error($e);
        }
    }
}