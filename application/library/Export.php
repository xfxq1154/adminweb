<?php

/**
 * @name Export
 * @author yanbo
 * @desc 数据导出类
 */
class Export {

    protected $separator = ',';           // 设置分隔符
    protected $delimiter = '"';           // 设置定界符

    private $fp;
    
    public function __construct() {
        $this->fp = fopen('php://output', 'a');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment;filename="'.date("YmdHis").'.csv"');
        header('Cache-Control: max-age=0');
    }
    
    /**
     * 设置title
     * @param array $data 要导出的数据，用于生成列
     * @param array $translate 列对应中文字段
     */
    public function setTitle($data, $translate = []) {
        $head_row = $data ? array_keys($data[0]) : $translate;
        //如果有中文翻译，将英文字段转换成中文
        foreach ($head_row as &$field){
            $field = isset($translate[$field]) ? $translate[$field] : $field;
        }
        $row_out = $this->_setCharset($head_row);
        echo $this->formatCSV($row_out);
    }
    
    /**
     * 输出数据
     * @param array $data
     */
    public function outPut($data) {

        foreach ($data as $row_in) {
            $row_out = $this->_setCharset($row_in);
            echo $this->formatCSV($row_out);
        }

    }
    
    /**
     * 设置数据编码，将utf-8转换为gbk
     * @param array $row
     * @return array
     */
    private function _setCharset($row) {
        foreach ($row as &$item){
            $item = iconv('UTF-8', 'UTF-8//GBK//TRANSLIT//IGNORE', $item);
        }
        return $row;
    }

    /**
     * 格式化为csv格式数据
     * @param array $data
     * @return string
     */
    private function formatCSV($data=array()){
        // 对数组每个元素进行转义
        $data = array_map(array($this,'escape'), $data);
        return implode("\t",$data)."\r\n";
    }


    /** 转义字符串
     * @param  String $str
     * @return String
     */
    private function escape($str){
        return str_replace($this->delimiter, $this->delimiter.$this->delimiter, $str);
    }
}
