<?php

/**
 * Created by PhpStorm.
 * User: momoko
 * Date: 15/11/23
 * Time: 21:44
 */
class BookOpinionModel
{

    use Trait_DB;

    protected $dbMaster; //主从数据库 配置
    protected $dbSlave; //主从数据库 配置

    protected $tableName = 'a_book_opinion';

    public $where;

    public function __construct()
    {
        $this->dbMaster = $this->getDb('audio');
        $this->dbSlave = $this->getDb('audio');
        $this->where = '';
    }

    /**
     * 返回总数
     * @return int
     */
    public function getTotal()
    {
        try {
            $sql = 'SELECT COUNT(*) total FROM ' . $this->tableName .' LIMIT 1';
            $stmt = $this->dbSlave->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return $result['total'];

        } catch (PDOException $e) {
            Tools::error($e);
        }
    }

    /**
     * 查找列表
     * @param string $limit
     * @return array
     */
    public function find($limit = '')
    {
        try {
            $sql = 'SELECT * FROM ' . $this->tableName . ($this->where ? $this->where : '') . ($limit ? ' LIMIT ' . $limit : '');
            $stmt = $this->dbSlave->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $return = array();

            if ($result) {
                foreach ($result as $index => $value) {
                    $return[$index] = CustomArray::removeKeyPrefix($value, 'o_');
                }
            }

            return $return;

        } catch (PDOException $e) {
            Tools::error($e);
        }
    }
}