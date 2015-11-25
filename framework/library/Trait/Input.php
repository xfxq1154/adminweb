<?php
/**
 * 输入工具
 *
 * @author ellis
 */
trait Trait_Input {

    /**
     * 解析input name="xx[]"的参数,转化成二维数组
     * @example
     * 表单：
     * input name=a[] 1 input name=b[] 3 input name=c[] 5
     * input name=a[] 2 input name=b[] 4 input name=c[] 6
     * 转化结果:
     * array
     *    array a=1  b=3 c=5
     *    array a=2  b=4 c=6
     *    
     * @param array $params ($_POST|$_GET|$_REQUEST)
     * @return array 
     */
    public function parseGroupParams($params) {
        $result = array();
        $a = array();

        $max = 0;

        foreach ($params as $k => $p) {
            if (is_array($p)) {
                $a[$k] = $p;
                if (count($p) > $max) {
                    $max = count($p);
                }
            }
        }

        $keys = array_keys($a);

        for ($i = 0; $i < $max; $i++) {
            foreach ($keys as $k) {
                $result[$i][$k] = $a[$k][$i];
            }
        }

        return $result;
    }

}
