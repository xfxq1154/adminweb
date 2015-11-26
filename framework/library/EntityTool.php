<?php
/**
 * 实体工具
 * @author ellis
 */
class EntityTool {

    use Trait_DB;

    /**
     * 快速建表，全部用varchar代替
     * @param type $tableName
     * @param type $entity
     */
    public function generateTable($tableName, $entity, $prefix) {

        $rc = new ReflectionClass($entity);

        $fields = [];
        
        $dbTypePattern = '/int|varchar|date|date|datetime|text|decimal|tinyint/';
        
        foreach ($rc->getProperties() as $p) {

            if ($p->getName() == 'id') {
                $fields[] = "`{$prefix}id` int(11) NOT NULL AUTO_INCREMENT";
                continue;
            }

            $types = [];
            preg_match("/@[a-z]+ (.*?) (.*)/", $p->getDocComment(), $types);

            $type = isset($types[1]) ? $types[1] : false;


            if ($type) {

                $comment = str_replace("\r", '', $types[2]);
          
                if (preg_match($dbTypePattern, $type)) {
                    $fields[] = "`{$prefix}{$p->getName()}` {$type} comment '{$comment}'";
                }
            }
        }



        $fieldsStr = implode(',', $fields);

        if (defined('ENVIRONMENT') && ENVIRONMENT == 'development') {
            $sql = "drop table {$tableName}";
            $this->getMasterDb()->exec($sql);
        }

        $sql = "CREATE TABLE `{$tableName}` (
            {$fieldsStr},
              PRIMARY KEY (`{$prefix}id`)
            ) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT";


        $this->getMasterDb()->exec($sql);
        
        return true;
    }

}
