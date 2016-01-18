<?php
/**
 * DB Trait,数据库调用工具
 * @author ellis
 */
Trait Trait_DB {
    /**
     * @return PDO
     */
    public function getDb($name) {
        return DBFactory::factory($name);
    }
    
    /**
     * @return PDO
     */
    public function getMasterDb($dbname) {
        $dbname .= '_master';
        return $this->getDb($dbname);
    }

    /**
     * @return PDO
     */
    public function getSlaveDb($dbname) {
        $dbname .= '_slave';
        return $this->getDb($dbname);
    }

    /**
     * 将结果集归组
     * 
     * @param array $records 结果集
     * @param string $groupKey 需要归组的字段
     * @return array
     */
    public function groupResult($records, $groupKey) {
        $group = array();

        foreach ($records as $r) {
            $group[$groupKey][] = $r;
        }

        return $group;
    }

    /**
     * 提取结果集中的Key,以改key为键，如主键
     * 
     * @param array $records 结果集
     * @param string $key 
     * @return array
     */
    public function pickUpResultKey($records, $key) {
        $array = array();

        foreach ($records as $r) {
            $array[$key] = $r;
        }

        return $array;
    }

    /**
     * 生成set语句片段
     *
     * @param array $data 
     * @return string
     */
    public function makeSet($data) {



        foreach ($data as $k => $v) {
            $v = str_replace('\'', '\\\'', $v);
            $array[] = "{$k}='{$v}'";
        }

        return implode(',', $array);
    }

    /**
     * 生成insert语句片段
     *
     * @param array $data 
     * @return string
     */
    public function makeInsert($data) {

        $keys = [];
        $values = [];
        foreach ($data as $k => $v) {
            $keys[] = $k;

            $v = str_replace('\'', '\\\'', $v);

            $values[] = "'{$v}'";
        }

        $r = [];
        $r['keys'] = implode(',', $keys);
        $r['values'] = implode(',', $values);
        return $r;
    }


    /**
     * 同步数据
     * @param type $refIds
     * @param type $sourceData
     * @param type $uniqueId
     * @param type $structureTransFunc
     * @param type $dupSql
     * @param type $insertSql
     * @param type $updateSql
     * @param type $deleteSql
     * @param type $clearSql
     */
    public function sync($refIds, $sourceData, $uniqueId, $structureTransFunc, $dupSql, $insertSql, $updateSql, $deleteSql,$clearSql="") {
        $insert = 0;
        $delete = 0;
        $update = 0;
        $wrong = 0;

        $buff = [];

        $dup = [];

        //寻找重复
        foreach ($refIds as $id) {
            if (!in_array($id, $buff)) {
                $buff[] = $id;
            } else {

                if (!isset($dup[$id])) {
                    $dup[$id] = 0;
                }
                $dup[$id] ++;
            }
        }

        foreach ($dup as $id => $limit) {


            $dSql = $dupSql($id, $limit);

            $delete += $limit;
            $this->getMasterDb()->exec($dSql);
        }

        $refIds = $buff;


        foreach ($sourceData as $pt) {

            $targetDatas = $structureTransFunc($pt);

            if (empty($targetDatas)) {
                continue;
            }


            //是否多维的

            $keys = array_keys($targetDatas);
            if (!is_numeric($keys[0])) {
                $targetDatas = array(
                    $targetDatas);
            }

            foreach ($targetDatas as $targetData) {

                $uid = $uniqueId($targetData);
                if (!$uid) {
                    $wrong++;
                    continue;
                }
                
           

                if (in_array($uid, $refIds)) {

                    $mSql = $updateSql($targetData);
                    $update++;
                } else {
                    $mSql = $insertSql($targetData);

                    $insert++;
                }
                $this->getMasterDb()->exec($mSql);
                $mods[] = $uid;
            }
        }

    
        if (!empty($mods)) {

            $dSqls = $deleteSql($mods);

            if (!is_array($dSqls)) {
                $dSqls = array($dSqls);
            }
            foreach ($dSqls as $dSql) {
                $n = $this->getMasterDb()->exec($dSql);
                $delete+=$n;
            }
        }  elseif($clearSql != "") {
       
            $n = $this->getMasterDb()->exec($clearSql);
        
        }


        echo "total records " . count($sourceData) . PHP_EOL;
        echo "total update  " . $update . PHP_EOL;
        echo "total insert  " . $insert . PHP_EOL;
        echo "total delete  " . $delete . PHP_EOL;
        echo "total wrong data  " . $wrong . PHP_EOL;
    }

}
