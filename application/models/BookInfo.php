<?php

class BookInfoModel {

    use Trait_DB;

    public $dbMaster; //主从数据库 配置
    public $tableName = '`b_book_info`';
    public $adminLog;

    public function __construct() {
        $this->dbMaster = $this->getDb('audio');
        $this->adminLog = new AdminLogModel();
    }

    /**
     * 通过bid查找记录
     * @param  integer $id book id
     * @return array     result
     */
    public function searchByBookId($id) {

        try {
            $sql = "SELECT * FROM " . $this->tableName . " WHERE i_bid = :id LIMIT 1";
            $stmt = $this->dbMaster->prepare($sql);
            $stmt->execute([':id' => $id]);

            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ? CustomArray::removekeyPrefix($row, 'i_') : false;
        } catch (PDOException $e) {
            die($e->getMessage());
        }

    }

    /**
     * 通过多个book id 获取记录
     * @param  string $ids string id
     * @return array      result
     */
    public function searchByBookIds($ids, $f = '*') {
        if (empty($ids)) {
            return false;
        }

        try {
            $sql = 'SELECT ' . $f . ' FROM ' . $this->tableName . ' WHERE i_bid IN (' . $ids . ')';
            $stmt = $this->dbMaster->prepare($sql);
            $stmt->execute();

            $return = [];

            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if ($rows) {
                foreach ($rows as $index => $value) {
                    $return[$index] = CustomArray::removekeyPrefix($value, 'i_');
                }
            }

            return $return;

        } catch (PDOException $e) {
            die($e->getMessage());
        }
    }
}