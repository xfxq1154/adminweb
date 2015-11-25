<?php

/**
 * BookModel
 */
class BookModel {

    use Trait_DB;

    public $dbMaster; //主从数据库 配置
    public $tableName = '`b_book`';
    public $adminLog;

    public function __construct() {
        $this->dbMaster = $this->getDb('audio');
        $this->adminLog = new AdminLogModel();
    }

    /**
     * 获取电子书列表
     *
     * @param string limit
     * @return array result
     */
    public function getList($limit = '',$where = '') {

        try {
            $sql = 'SELECT * FROM ' . $this->tableName . ' where 1  '.$where.' ORDER BY b_id DESC ' .
                    ($limit ? ' LIMIT ' . $limit : '');
            $stmt = $this->dbMaster->prepare($sql);
            $stmt->execute();

            $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $return = [];

            if ($rs) {
                foreach ($rs as $key => $val) {
                    $tmp = CustomArray::removekeyPrefix($val, 'b_');
                    if(isset($tmp['cover'])){
                        $tmp['cover'] = Tools::formatImg($tmp['cover']);
                    }
                    if(isset($tmp['banner'])){
                        $tmp['banner'] = Tools::formatImg($tmp['banner']);
                    }
                    $return[$key] = $tmp;
                }
            }

            return $return;
        } catch (PDOException $e) {
            die($e->getMessage());
        }
    }

    /**
     * get count
     *
     * @return mixed int or false
     */
    public function getCount($where = '') {

        try {
            $sql = "SELECT COUNT(*) count FROM " . $this->tableName." where 1  ".$where;
            $stmt = $this->dbMaster->prepare($sql);
            $stmt->execute();
            $rs = $stmt->fetch(PDO::FETCH_ASSOC);

            return $rs ? $rs['count'] : false;
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

        $book = CustomArray::addKeyPrefix($data['book'], 'b_');

        try {
            // 开启事务
            $this->dbMaster->beginTransaction();

            // 写入book
            $sql = 'INSERT INTO ' . $this->tableName . ' SET ' . $this->makeSet($book);
            $stmt = $this->dbMaster->prepare($sql);
            $stmt->execute();
            $bookLastInsertId = $this->dbMaster->lastInsertId();

            $data['bookInfo']['bid'] = $bookLastInsertId;
            $bookInfo = CustomArray::addKeyPrefix($data['bookInfo'], 'i_');

            // 写入bookinfo
            $sql = 'INSERT INTO `b_book_info`  SET ' . $this->makeSet($bookInfo);
            $stmt = $this->dbMaster->prepare($sql);
            $stmt->execute();

            $adminuser = $_SESSION['a_user'];
            $log = array(
                'operator' => $adminuser['id'],
                'remark' => '添加电子书: ' . $adminuser['user'] . ' 添加了信息: ' . serialize($data),
            );

            unset($data);
            $this->adminLog->add($log);

            $this->dbMaster->commit();

            return $bookLastInsertId;
        } catch (PDOException $e) {
            $this->dbMaster->rollBack();
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
            $sql = 'SELECT * FROM ' . $this->tableName . ' WHERE b_id = :id LIMIT 1';
            $stmt = $this->dbMaster->prepare($sql);
            $stmt->execute([':id' => $id]);

            $rs = $stmt->fetch(PDO::FETCH_ASSOC);

            return $rs ? CustomArray::removekeyPrefix($rs, 'b_') : false;
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

        if (!$data || !isset($data['book']['id']) || !isset($data['bookInfo']['bid'])) {
            return false;
        }

        $id = $data['book']['id'];
        $bid = $data['bookInfo']['bid'];
        unset($data['book']['id'], $data['bookInfo']['bid']);
        $book = CustomArray::addKeyPrefix($data['book'], 'b_');
        $bookInfo = CustomArray::addKeyPrefix($data['bookInfo'], 'i_');

        unset($data);

        try {

            $this->dbMaster->beginTransaction();

            // update book
            $sql = "UPDATE " . $this->tableName . ' SET ' . $this->makeSet($book) .
                    ' WHERE b_id = :id LIMIT 1';
            $stmt = $this->dbMaster->prepare($sql);
            $stmt->execute([':id' => $id]);

            // update bookinfo
            $sql = 'UPDATE `b_book_info` SET ' . $this->makeSet($bookInfo) .
                    ' WHERE i_bid = :bid LIMIT 1';
            $stmt = $this->dbMaster->prepare($sql);
            $stmt->execute([':bid' => $bid]);

            $adminuser = $_SESSION['a_user'];
            $log = array(
                'operator' => $adminuser['id'],
                'remark' => '修改电子书: ' . $adminuser['user'] . ' 修改了信息[' . $id . ']: ' . serialize($data),
            );

            $this->adminLog->add($log);
            $this->dbMaster->commit();
            $this->deleteCache($id);
            return true;
        } catch (PDOException $e) {

            $this->dbMaster->rollBack();
            die($e->getMessage());
        }
    }
    /**
     * 删除排期缓存
     * @param type $id
     */
    public function deleteCache($id) {
        
            
        if($id){
            //排期信息
            $audioTopic = new AudioTopicModel();
            $topic = $audioTopic->getTopicByEbook($id);
            if($topic){
                foreach ($topic as $info) {
                    $cache = new CacheModel();
                    $cache->removeTopicRedis($topic['t_datetime']);
                }
                
            }
        }
        
    }


    public function getBookById($id, $fields = '*',$status=1) {
        $list = array();
        if (!is_array($id) || empty($id))
            return $list;
        try {
            $fieldin = implode(',', $id);
            $sql = "SELECT {$fields} FROM {$this->tableName} WHERE `b_id` in ({$fieldin}) ";
            if($status > 0){
                $sql.=" and b_status = '".$status."'   ";
            }

            $sql.= " ORDER BY FIND_IN_SET(`b_id`, '{$fieldin}')";
            $stmt = $this->dbMaster->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
            $stmt->execute();

            $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if ($rs) {
                foreach ($rs as $val) {
                    $tmp = CustomArray::removeKeyPrefix($val, 'b_');
                    if(isset($tmp['cover'])){
                        $tmp['cover'] = Tools::formatImg($tmp['cover']);
                    }
                    if(isset($tmp['banner'])){
                        $tmp['banner'] = Tools::formatImg($tmp['banner']);
                    }
                    $list[] = $tmp;
                }
            }
            return $list;
        } catch (PDOException $e) {
            Tools::error($e);
        }
    }

    /**
     * 通过多个book id 获取记录
     * @param  string $ids string id
     * @return array      result
     */
    public function searchByBookIds($ids) {

        if (empty($ids)) {
            return false;
        }

        try {
            $sql = 'SELECT * FROM ' . $this->tableName . ' WHERE b_id IN ( ' . $ids . ' )';
            $stmt = $this->dbMaster->prepare($sql);
            $stmt->execute();

            $return = [];

            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if ($rows) {
                foreach ($rows as $index => $value) {
                    $tmp = CustomArray::removekeyPrefix($value, 'b_');
                    if(isset($tmp['cover'])){
                        $tmp['cover'] = Tools::formatImg($tmp['cover']);
                    }
                    if(isset($tmp['banner'])){
                        $tmp['banner'] = Tools::formatImg($tmp['banner']);
                    }
                    $return[$index] = $tmp;
                }
            }

            return $return;
        } catch (PDOException $e) {
            die($e->getMessage());
        }
    }

}
