<?php

/**
 * @name Tools
 * @author ellis
 * @desc 工具函数类
 */
class Tools
{

    /**
     * URL重定向
     * @param type $url
     */
    static function redirect($url)
    {
        $url = $url ? $url : '/';
        header("Location: " . $url);
        exit;
    }

    static function formatImg($data)
    {
        if (strpos($data, 'http://') === false) {
            $data = IMG_HOST . '/' . $data;
        }
        return $data;
    }

    static function checkpower($power, $id)
    {

        if (strpos(',' . $power . ',', ',' . $id . ',') === false) {
            return false;
        }
        return true;
    }

    /**
     * 异常信息捕获
     * @param type $e
     */
    static function error($e)
    {
        $errormsg = '信息：' . $e->getMessage() . "\n";
        $errormsg .= '文件：' . str_replace(ROOT_PATH, '', $e->getFile()) . "\n";
        $errormsg .= '行号：' . $e->getLine() . "\n";
        $errormsg .= 'admincenter';
        $error = array(
            'msg' => '信息：' . $e->getMessage(),
            'file' => '文件：' . str_replace(ROOT_PATH, '', $e->getFile()),
            'line' => '行号：' . $e->getLine(),
        );
        self::success('error', $errormsg);
    }

    /**
     * 结果输出
     * @param type $t
     * @param type $code
     * @param type $notice
     * @return boolean
     */
    static function success($t, $code = '', $notice = '')
    {
        if ($t == '') return false;
        echo json_encode(array('status' => $t, 'code' => $code, 'data' => $notice));
        exit;
    }

    static function output($data, $format = 'json')
    {
        switch (strtolower($format)) {
            case 'json':
                self::output_json($data);
                break;
            case 'print_r':
                self::output_print_r($data);
                break;
            case 'xml':
                self::output_xml($data);
                break;
            default:
                self::output_print_r($data);
                break;
        }
    }

    static function output_json($data)
    {
        if (empty($data)) $data = array();
        echo json_encode($data);
        exit;
    }

    static function output_print_r($data)
    {
        echo '<pre>';
        var_dump($data);
        echo '</pre>';
        exit;
    }

    static function x($data)
    {
        self::output_print_r($data);
    }

    static function output_xml($data)
    {

    }


    /**
     * 结果输出
     * @param type $t
     * @param type $code
     * @param type $notice
     * @return boolean
     */
    static function formsuccess($t, $code = '', $notice = '')
    {
        if ($t == '') return false;
        echo json_encode(array('sta' => $t, 'code' => $code, 'data' => $notice));
        exit;
    }


    // 全角半角转换函数
    static function sbcToDbc($str)
    {
        $arr = array(
            //数字
            '０' => '0', '１' => '1', '２' => '2', '３' => '3', '４' => '4', '５' => '5', '６' => '6', '７' => '7', '８' => '8', '９' => '9',
            //大写字母
            'Ａ' => 'A', 'Ｂ' => 'B', 'Ｃ' => 'C', 'Ｄ' => 'D', 'Ｅ' => 'E', 'Ｆ' => 'F', 'Ｇ' => 'G', 'Ｈ' => 'H', 'Ｉ' => 'I', 'Ｊ' => 'J', 'Ｋ' => 'K', 'Ｌ' => 'L', 'Ｍ' => 'M', 'Ｎ' => 'N', 'Ｏ' => 'O', 'Ｐ' => 'P', 'Ｑ' => 'Q', 'Ｒ' => 'R', 'Ｓ' => 'S', 'Ｔ' => 'T', 'Ｕ' => 'U', 'Ｖ' => 'V', 'Ｗ' => 'W', 'Ｘ' => 'X', 'Ｙ' => 'Y', 'Ｚ' => 'Z',
            // 小写字母
            'ａ' => 'a', 'ｂ' => 'b', 'ｃ' => 'c', 'ｄ' => 'd', 'ｅ' => 'e', 'ｆ' => 'f', 'ｇ' => 'g', 'ｈ' => 'h', 'ｉ' => 'i', 'ｊ' => 'j', 'ｋ' => 'k', 'ｌ' => 'l', 'ｍ' => 'm', 'ｎ' => 'n', 'ｏ' => 'o', 'ｐ' => 'p', 'ｑ' => 'q', 'ｒ' => 'r', 'ｓ' => 's', 'ｔ' => 't', 'ｕ' => 'u', 'ｖ' => 'v', 'ｗ' => 'w', 'ｘ' => 'x', 'ｙ' => 'y', 'ｚ' => 'z',
            //括号
            '（' => '(', '）' => ')', '〔' => '[', '〕' => ']', '【' => '[', '】' => ']', '〖' => '[', '〗' => ']', '《' => ' < ', '》' => ' > ', '｛' => ' {', '｝' => '} ',
            //其它
            '％' => '%', '＋' => ' + ', '—' => '-', '－' => '-', '～' => '-', '．' => '.', '：' => ':', '。' => '.', '，' => ',', '、' => '\\', '；' => ':', '？' => '?', '！' => '!', '…' => '-', '‖' => '|', '“' => "\"", '”' => "\"", '‘' => '`', '’' => '`', '｜' => '|', '〃' => "\"", '　' => ' '
        );
        return strtr($str, $arr);
    }

    // 全角半角转换函数
    static function dbcToSbc($str)
    {
        $arr = array(
            //其它
            '\'' => '’',
            '"' => '“',
        );
        return strtr($str, $arr);
    }

    static function getCode()
    {
        $code = rand('100000', '999999');
        return $code;
    }


    static function getUrlDomain($url)
    {
        if (empty($url)) return false;
        if (strpos($url, '?')) {
            $res = explode('?', $url);
            $url = $res['0'];
        }
        $re = parse_url($url);
        if (!empty($re['host'])) {
            return $re['host'];
        } else {
            return Tools::getDomain($url);
        }
        return false;
    }

    static function getDomain($url)
    {
        preg_match("/^(http:\/\/)?([^\/]+)/i", $url, $matches);
        $host = $matches[2];    // 从主机名中取得后面两段
        preg_match("/[^\.\/]+\.[^\.\/]+$/", $host, $matches);
        return $matches[0];
    }

    static function refererDomain()
    {
        $referer = $_SERVER['HTTP_REFERER'];
        return Tools::getUrlDomain($referer);
    }

    static function currUrl()
    {
        $url = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        return $url;
    }

    //a 标签链接封装,链接添加踪参数
    static function link($url = '')
    {
        if (empty($url))
            return false;
        //add params
        if (!empty($_GET['spm'])) {
            $param = 'spm=' . $_GET['spm'];
            $url = ((strpos($url, '?')) !== false) ? $url . '&' : $url . '?';
            $url .= $param;
        }

        return $url;

    }

    //带跟踪参数的跳转
    static function to($url)
    {
        $newurl = Tools::link($url);
        Tools::redirect($newurl);
    }

    static function postSubmit($url, $para_temp, $formid = '', $method = 'post', $button_name = 'submit')
    {
        //待请求参数数组
        $para = $para_temp;
        if (empty($formid)) {
            $formid = 'formid_' . time();
        }

        $sHtml = "<form id='" . $formid . "' name='" . $formid . "' action='" . $url . "' method='" . $method . "'>";
        while (list ($key, $val) = each($para)) {
            $sHtml .= "<input type='hidden' name='" . $key . "' value='" . $val . "'/>";
        }

        //submit按钮控件请不要含有name属性
        $sHtml = $sHtml . "<input style='display:none;' type='submit' value='" . $button_name . "'></form>";

        $sHtml = $sHtml . "<script>document.forms['" . $formid . "'].submit();</script>";
        echo $sHtml;
        exit;
    }

    static function sub($str, $length = 10, $charset = 'UTF-8')
    {
        $str = strip_tags($str);
        return mb_substr($str, 0, $length, $charset);
    }

    /**
     * array_column PHP内置函数，> 5.5.0，现在只能自己写
     * @link http://php.net/manual/zh/function.array-diff-key.php
     * @param array $input
     * @param $columnKey
     * @param null $indexKey
     * @return array
     */
    static function arrayColumn(array $input, $columnKey, $indexKey = null)
    {
        $result = array();
        if (null === $indexKey) {
            if (null === $columnKey) {
                $result = array_values($input);
            } else {
                foreach ($input as $row) {
                    $result[] = $row[$columnKey];
                }
            }
        } else {
            if (null === $columnKey) {
                foreach ($input as $row) {
                    $result[$row[$indexKey]] = $row;
                }
            } else {
                foreach ($input as $row) {
                    $result[$row[$indexKey]] = $row[$columnKey];
                }
            }
        }
        return $result;
    }

}