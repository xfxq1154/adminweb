<?php
/**
 * Description of DB
 * PDO数据库操作扩展类
 * @author ellis
 */
class PDO extends PDO {

    private $queryLogs = array();

    /**
     *
     * @var FileLog
     */
    private $fileLog = null;

    public function __construct($dsn, $username, $passwd, $options) {
        parent::__construct($dsn, $username, $passwd, $options);

        $this->fileLog = FileLog::getInstance();
    }

    /**
     * 获得所有查询日志
     * @return string
     */
    public function getQueryLogs() {
        return $this->queryLogs;
    }

    /**
     * 打印数据库查询日志
     * @return type
     */
    public function printQueryLogs() {
        $sqls = $this->getQueryLogs();
        echo '<pre>';
        print_r($sqls);
        echo '</pre>';
    }

    private function saveQueryLog($sql, $params = array()) {

        if (isset($params[0])) {
            foreach ($params as $k => $v) {
                $sql = str_replace('?', "'$v'", $sql);
            }
        } else {
            foreach ($params as $k => $v) {
                $sql = str_replace($k, "'$v'", $sql);
            }
        }

        $this->queryLogs[] = $sql;

        if ($this->fileLog != null) {
            //$this->fileLog->addLog($sql);
        }
    }

    /**
     * 查询单条记录
     * @param string $sql
     * @param array $params
     * @return array
     */
    function exec($sql) {
        $this->saveQueryLog($sql);

        return parent::exec($sql);
    }

    /**
     * 查询单条记录
     * @param string $sql
     * @param array $params
     * @return array
     */
    function find($sql, $params = array()) {
        $stmt = $this->makeSelectStatement($sql, $params);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * 查询全部记录
     * @param string $sql
     * @param array $params
     * @return array
     */
    function findAll($sql, $params = array()) {
        $stmt = $this->makeSelectStatement($sql, $params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * 获得单列结果
     * @param type $sql
     * @param type $params
     * @return type
     */
    function getColumn($sql, $params = array()) {
        $stmt = $this->makeSelectStatement($sql, $params);
        return $stmt->fetchColumn();
    }

    /**
     * 获得字段数组
     * 
     * @param array $records 结果集
     * @param string $fieldName
     * @return array
     */
    private function getFiledArray($records, $fieldName) {
        $result = array();

        if (empty($records) || empty($records[0])) {
            return array();
        }
        
        foreach ($records as $r) {
            $result[] = $r[$fieldName];
        }

        return $result;
    }

    /**
     * 获得字段数组
     * @param string $sql
     * @param string $fieldName
     * @param array $params
     * @return array
     */
    function getColumnArray($sql, $fieldName, $params = array()) {

        $result = $this->findAll($sql, $params);
        return $this->getFiledArray($result, $fieldName);
    }

    /**
     * 生成查询的预编译语句
     * @param string $sql
     * @param array $params
     * @return result
     */
    private function makeSelectStatement($sql, $params = array()) {
        $stmt = $this->prepare($sql);
        $stmt->execute($params);
        $this->saveQueryLog($sql, $params);
        return $stmt;
    }

    /**
     * 添加一行数据
     * @param string $tableName
     * @param array $array
     * @return int/bool id
     */
    public function add($tableName, $array) {    
        $fields = $this->getFields($tableName);
        $inserts = [];
        foreach ($array as $k => $v) {
            if (array_key_exists($k, $fields) && $fields[$k]['Key'] != 'PRI') {
                $inserts[$k] = "'$v'";
            }
        }
        $keys = implode(',', array_keys($inserts));
        $values = implode(',', array_values($inserts));
        $sql = "insert {$tableName} ({$keys}) values ({$values}); ";     
        $r = $this->exec($sql);
        if ($r) {
            return $this->lastInsertId();
        }
        return false;
    }

    /**
     * 更新一条记录
     * @param string $tableName
     * @param array $array
     */
    public function update($tableName, $array) {
        $fields = $this->getFields($tableName);
        $updates = [];
        foreach ($array as $k => $v) {
            if (array_key_exists($k, $fields) && $fields[$k]['Key'] != 'PRI') {
                $updates[] = "{$k} ='$v'";
            } elseif ($fields[$k]['Key'] == 'PRI') {
                $pk = $k;
            }
        }
        $pkV = $array[$pk];
        if (empty($pkV)) {
            return false;
        }
        $sets = implode(',', $updates);
        $sql = "update {$tableName} set $sets where {$pk} = {$pkV} limit 1";     
        $r = $this->exec($sql);
        if ($r) {
            return $pkV;
        }
        return false;
    }

    public function getFields($tableName) {
        $descs = $this->findAll('desc ' . $tableName);
        $fields = [];
        foreach ($descs as $d) {
            $fields[$d['Field']] = $d;
        }
        return $fields;
    }
}
