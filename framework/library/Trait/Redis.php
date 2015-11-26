<?php
/**
 * Redis Trait,Redis调用工具
 * @author ellis
 */
Trait Trait_Redis {

    /**
     * @return Redis
     */
    protected function getRedis($name) {
        return RedisFactory::factory($name);
    }

    /**
     * @return Redis
     */
    public function getMasterRedis() {
        return $this->getRedis('master');
    }

    /**
     * @return Redis
     */
    public function getSlaveRedis() {
        return $this->getRedis('slave');
    }

}
