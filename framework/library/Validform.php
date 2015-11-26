<?php
/**
 * 验证数据
 * @author ellis
 */
class Validform {
    
    /**
     * 验证邮箱
     * @param type $email
     * @return type
     */
    public static function checkEmail($email){   
        return ereg("^([a-zA-Z0-9_-])+@([a-zA-Z0-9_-])+(.[a-zA-Z0-9_-])+",$email);
    }
    
    /**
     * 验证手机号码
     * @param type $mobile
     * @return type
     */
    public static function checkMobile($mobile){   
        if(!preg_match("/^1[34578]\d{9}$/", $mobile)){
            return false;
        }
        return 1;
    }  
    
    /**
     * 检查链接合法性
     * @param type $url
     * @return boolean
     */
    public static function checkUrl($url = '') {
        if($url){
            if(!filter_var($url, FILTER_VALIDATE_URL)){
                return false;
            }
            return true;
        }
        return false;
    }
    
    /**
     * 判断人名
     * @param type $name
     * @return boolean
     */
    public static function checkTrueName($name) {
        $len = mb_strlen($name, 'utf-8');
        if($len <= 6 && $len >= 2){
            return true;
        }
        return false;
    }
    
    /**
     * 过滤特殊标签
     * @param string $content
     * @return string
     */
    public static function stripTags($content) {        
        $content = preg_replace( "@<script(.*?)</script>@is", "", $content );
        $content = preg_replace( "@<iframe(.*?)</iframe>@is", "", $content ); 
        $content = preg_replace( "@<style(.*?)</style>@is", "", $content ); 
        $content = preg_replace( "@<(.*?)>@is", "", $content ); 
        return strip_tags($content);
    }
}
