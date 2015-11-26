<?php

/**
 * 加密算法trait
 * @author ellis
 */
trait Trait_Encrypt {

    /**
     * 
     * @return \Encrypt_DES
     */
    public function getDes() {
        $c = Application::app()->getConfig()->encrypt->des;

        $des = new Encrypt_DES($c->key, $c->iv);
        return $des;
    }

}
