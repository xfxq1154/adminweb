<?php

/**
 * 
 */
class CacheModel{
    use Trait_Redis;
    
    public $redisMaster;
    
    public function __construct() {
        $this->redisMaster = $this->getRedis('audio');
    }
    
    public function removeTopicRedis($day){
        $this->redisMaster->del('audio:iget:iget_topic_' . $day);
    }
}