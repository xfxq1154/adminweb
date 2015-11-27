<?php

/**
 * Created by PhpStorm.
 * User: momoko
 * Date: 15/11/13
 * Time: 14:54
 */
class VerseModel
{
    use Trait_DB;
    protected $dbMaster;
    protected $dbSlave;
    protected $tableName = 'v_verse';
    protected $adminLog;
    public $whereStr;

    public function __construct()
    {
        $this->dbMaster = $this->getDb('audio');
        $this->dbSlave = $this->getDb('audio');
        $this->adminLog = new AdminLogModel();
    }

    /**
     * 添加金句
     * @param array $data
     * @return bool|string
     */
    public function insert(array $data = array())
    {
        if (empty($data)) {
            return false;
        }

        $data = CustomArray::addKeyPrefix($data, 'v_');
        $data = $this->makeInsert($data);

        try {
            $sql = 'INSERT ' . $this->tableName . ' ( ' . $data['keys'] . ' ) VALUES (' . $data['values'] . ')';
            $stmt = $this->dbMaster->prepare($sql);
            $stmt->execute();

            $lastInsertId = $this->dbMaster->lastInsertId();

            return $lastInsertId;
        } catch (PDOException $e) {
            Tools::error($e);
        }
    }

    /**
     * 查询金句总数
     * @param null $state 0|1
     * @return int
     */
    public function getTotal($state = null)
    {
        $where = is_null($state) ? '' : ' AND v_state = ' . $state;


        if ($this->whereStr) {
            $where .= $this->whereStr;
            $this->whereStr = '';
        }
        try {
            $sql = 'SELECT COUNT(*) total FROM ' . $this->tableName . ' WHERE 1 ' . $where . ' LIMIT 1';
            $stmt = $this->dbSlave->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return $result['total'];

        } catch (PDOException $e) {
            Tools::error($e);
        }
    }

    /**
     * 获取金句列表
     * @param null $state 0|1
     * @param string $f fields
     * @param string $limit
     * @return array
     */
    public function find($state = null, $f = '*', $limit = '')
    {

        $where = is_null($state) ? '' : ' AND v_state = ' . $state;
        if ($this->whereStr) {
            $where .= $this->whereStr;
            $this->whereStr = '';
        }
        try {
            $sql = 'SELECT ' . $f . ' FROM ' . $this->tableName . ' WHERE 1 ' . $where . ' ORDER BY v_id DESC ' . ($limit ? ' LIMIT ' . $limit : '');
            $stmt = $this->dbSlave->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $return = array();

            if ($result) {
                foreach ($result as $index => $value) {
                    $return[$index] = CustomArray::removeKeyPrefix($value, 'v_');
                }
            }


            return $return;

        } catch (PDOException $e) {
            Tools::error($e);
        }
    }

    /**
     * 查找金句
     * @param int $id
     * @param string $f
     * @return bool|array
     */
    public function getById($id = 0, $f = '*')
    {

        if (!$id) {
            return false;
        }

        try {
            $sql = 'SELECT ' . $f . ' FROM ' . $this->tableName . ' WHERE v_id = :id LIMIT 1';
            $stmt = $this->dbSlave->prepare($sql);
            $stmt->execute(array(
                ':id' => $id
            ));

            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            $result = CustomArray::removeKeyPrefix($result, 'v_');
            return $result;
        } catch (PDOException $e) {
            Tools::error($e);
        }
    }

    /**
     * 查找金句列表
     * @param null $ids
     * @param string $f
     * @return array|bool
     */
    public function findByIds($ids = null, $f = '*')
    {

        if (!$ids) {
            return false;
        }

        if (is_array($ids)) {
            $where = ' WHERE v_id IN (' . implode(',', $ids) . ') ';
        } else {
            $where = ' WHERE v_id IN (' . $ids . ') ';
        }

        try {
            $sql = 'SELECT ' . $f . ' FROM ' . $this->tableName . $where;
            $stmt = $this->dbSlave->prepare($sql);
            $stmt->execute();

            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $return = array();

            if ($result) {
                foreach ($result as $index => $value) {
                    $return[$index] = CustomArray::removeKeyPrefix($value, 'v_');
                }
            }

            return $return;
        } catch (PDOException $e) {
            Tools::error($e);
        }
    }

    /**
     * 修改金句
     * @param int $id
     * @param array $updateData
     * @return bool|int
     */
    public function updateById($id = 0, array $updateData = array())
    {
        if (!$id) {
            return false;
        }

        $updateData = CustomArray::addKeyPrefix($updateData, 'v_');
        $updateData = $this->makeSet($updateData);
        try {
            $sql = 'UPDATE ' . $this->tableName . ' SET ' . $updateData . ' WHERE v_id = :id LIMIT 1';
            $stmt = $this->dbMaster->prepare($sql);
            $stmt->execute(array(
                ':id' => $id
            ));

            return $stmt->rowCount();
        } catch (PDOException $e) {
            Tools::error($e);
        }
    }

    /**
     * 设置查询条件
     * @param type $data
     */
    public function setWhere($where)
    {
        if (empty($where))
            return false;

        $this->whereStr = '';
        $fv = "";
        if (is_array($where)) {
            foreach ($where as $key => $value) {
                $fv .= " and `v_" . $key . "`='" . $value . "'";
            }
        } elseif (is_string($where)) {
            $fv .= $where;
        }

        $this->whereStr = $fv;
    }

}