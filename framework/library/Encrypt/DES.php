<?php

/**
 * RSA算法类 
 * @author ellis
 */
class Encrypt_DES {

    private $key = "";
    private $iv = "";

    /**
     * 
     * @param string $key 加密key
     * @param string $iv 初始化向量
     */
    function __construct($key, $iv = '') {

        $this->key = base64_encode($key);
        if($iv == ''){
            $iv = substr($key, 0, 8);
        }
        $this->iv = base64_encode($iv);
    }

    /**
     * 加密 
     * @param <type> $value 
     * @return <type> 
     */
    public function encrypt($value) {
        $td = mcrypt_module_open(MCRYPT_3DES, '', MCRYPT_MODE_CBC, '');
        $iv = base64_decode($this->iv);
        $value = $this->paddingPKCS7($value);
        $key = base64_decode($this->key);
        mcrypt_generic_init($td, $key, $iv);
        $ret = base64_encode(mcrypt_generic($td, $value));
        mcrypt_generic_deinit($td);
        mcrypt_module_close($td);
        return $ret;
    }

    /**
     * 解密 
     * @param <type> $value 
     * @return <type> 
     */
    public function decrypt($value) {
        if(empty($value))
        {
            return "";
        }
        $td = mcrypt_module_open(MCRYPT_3DES, '', MCRYPT_MODE_CBC, '');
        $iv = base64_decode($this->iv);
        $key = base64_decode($this->key);
        mcrypt_generic_init($td, $key, $iv);
        $ret = trim(mdecrypt_generic($td, base64_decode($value)));
        $ret = $this->unPaddingPKCS7($ret);
        mcrypt_generic_deinit($td);
        mcrypt_module_close($td);
        return $ret;
    }

    private function paddingPKCS7($data) {
        $block_size = mcrypt_get_block_size('tripledes', 'cbc');
        $padding_char = $block_size - (strlen($data) % $block_size);
        $data .= str_repeat(chr($padding_char), $padding_char);
        return $data;
    }

    private function unPaddingPKCS7($text) {
        $pad = ord($text{strlen($text) - 1});
        if ($pad > strlen($text)) {
            return false;
        }
        if (strspn($text, chr($pad), strlen($text) - $pad) != $pad) {
            return false;
        }
        return substr($text, 0, -1 * $pad);
    }

}
?> 
