<?php

/** 
 * @name TaskModel;
 * @author why;
 * @desc 大平台队列管理
 */
class TaskModel{
    
    use Trait_Api;
    
    const TASK_GETLIST = 'task/getlist';
    
    public function getList($data){
        $result = $this->request(self::TASK_GETLIST,$data);
        if($result === FALSE){
            return FALSE;
        }
        return $result;
    }
    
    private function request($uri, $params = array(), $requestMethod = 'GET', $jsonDecode = true, $headers = array(), $timeout = 10) {

        $sapi = $this->getApi('sapi');

        $params['sourceid'] = Yaf_Application::app()->getConfig()->api->sapi->source_id;
        $params['timestamp'] = time();
        
        $result = $sapi->request($uri, $params, $requestMethod);
        
        if (isset($result['status_code']) && $result['status_code'] == 0) {
            return isset($result['data']) ? $result['data'] : array();
        } else {
            return false;
        }
    }
}

