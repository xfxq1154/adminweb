<?php
/**
 * Description of Api
 * Api Trait,Api调用工具
 * @author ellis
 */
Trait Trait_Api {
    
    /**
     * 
     * @param string(url) $ApiName
     * @return Api
     */
    public function getApi($ApiName) {
        return ApiFactory::factory($ApiName);
    }

    
}
