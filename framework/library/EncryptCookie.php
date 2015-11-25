<?php
/**
 * 加密cookie 用于加密的数据，以便能将敏感信息保存到cookie中 *
 ** @author ellis
 */
class EncryptCookie {

    use Trait_Encrypt;
    private $cookieKey = 'c';
    
    public function __construct() {
        
    }

    /**
     * 设置cookie,只能设置本域的根目录
     * @param type $name
     * @param type $value
     * @param type $expire
     */
    public function setCookie($name, $value, $expire = 86400) {
        $expire += time();
        $cookies = $this->getCookies();

        if (is_array($value)) {
            $value = json_encode($value);
        }
        $cookies[$name] = $value;
        $encodedStr = $this->getDes()->encrypt(json_encode($cookies));
        setcookie($this->cookieKey, $encodedStr, $expire, '/');
    }

    /**
     * 获得所有cookie
     * @return array
     */
    public function getCookies() {
        $co = $_COOKIE[$this->cookieKey];
        return json_decode($this->getDes()->decrypt($co), true);
    }

    /**
     * 获得cookie
     * @param type $name
     * @param type $value
     * @param type $expire
     */
    public function getCookie($name = null) {
        $cookies = $this->getCookies();
        
        if(!isset($cookies[$name]))
        {
            return false;
        }
        
        if (json_decode($cookies[$name])) {
            return json_decode($cookies[$name],true);
        }

        return $cookies[$name];
    }

    /**
     * 删除cookie
     * @param type $name
     */
    public function delCookie($name) {
        return $this->setCookie($name, null, 0);
    }

}
