<?php
/**
 * 加密cookie工具
 * @author ellis
 */
trait Trait_EncryptCookie {

    /**
     * 
     * @return \EncryptCookie
     */
    public function getEncryptCookie() {
        $cookie = new EncryptCookie();
        return $cookie;
    }

}
