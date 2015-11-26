<?php
/**
 * Description of BaseModel
 * @author ellis
 */
class ModelBase {

    /**
     * @return Redis
     */
    public function getRedis($name) {
        return RedisFactory::factory($name);
    }

    /**
     * 
     * @return PDO
     */
    public function getDb($name) {
        return DBFactory::factory($name);
    }

    /**
     * 
     * @return PDO
     */
    public function getMasterDb() {
        return $this->getDb('master');
    }

    /**
     * 
     * @return PDO
     */
    public function getSlaveDb() {
        return $this->getDb('slave');
    }

    /**
     * 
     * @return Redis
     */
    public function getMasterRedis() {
        return $this->getRedis('master');
    }

    /**
     * 
     * @return Redis
     */
    public function getSlaveRedis() {
        return $this->getRedis('slave');
    }

}
