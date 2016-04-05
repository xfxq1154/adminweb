<?php

/**
 * @name Export
 * @author yanbo
 * @desc 数据导出类
 */
class Export {

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
        foreach ($head_row as &$filed){
            $filed = isset($translate[$filed]) ? $translate[$filed] : $filed;
        }
        $row_out = $this->_setCharset($head_row);
        fputcsv($this->fp, $row_out);
    }
    
    /**
     * 输出数据
     * @param array $data
     */
    public function outPut($data) {
        foreach ($data as $row_in) {
            $row_out = $this->_setCharset($row_in);
            fputcsv($this->fp, $row_out);
        }
    }
    
    /**
     * 设置数据编码，将utf-8转换为gb18030
     * @param array $row
     * @return array
     */
    private function _setCharset($row) {
        $val_in = implode('|shzf|', $row);
        $val_out = iconv('UTF-8', 'GB18030//TRANSLIT', $val_in);
        return explode('|shzf|', $val_out);
    }
}
