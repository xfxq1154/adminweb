<?php
/**
 * 数据转换类
 * @author ellis
 */
class Convert {

    /**
     * 解析config数组为对象
     * @param array $config
     */
    public static function configs2Object($config) {


        $configObj = new stdClass();

        foreach ($config as $key => $v) {
            self::config2Object($key, 0, $v, $configObj);
        }
        
        return $configObj;
    }

    /**
     * 转换单个配置到对象
     * @param type $configKey
     * @param type $i
     * @param type $v
     * @param type $configObj
     * @return type
     */
    public static function config2Object($configKey, $i, $v, &$configObj) {

        $keys = explode('.', $configKey);



        $key = $keys[$i];
    
        $vars = get_object_vars($configObj);

        if (!isset($vars[$key]) ) {

            $obj = new stdClass();
            $configObj->$key = $obj;
         
        } 
        
     
    

        if (!isset($keys[$i + 1])) {

            $configObj->$key = $v;
            return;
        }
         
        self::config2Object($configKey, ++$i, $v, $configObj->$key);
    }

}
