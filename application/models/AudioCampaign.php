<?php

/**
 * AudioClassModel
 */
class AudioCampaignModel {

    use Trait_DB,Trait_Api;

    use Trait_Redis;
    private $coinApi;
    private $coinCode = 'IGET';
    private $coin_secret,$audioapi;
    public $api_key = 'ljsw-jwl';


    public function __construct() {
        $this->coinApi = $this->getApi('coin');
        $this->audioapi = $this->getApi('audio');
        $this->coin_secret = API_COIN_SECRET;
    }


    
    public function add($data,$interface='offers'){
        if(empty($data)) return false;
        
        $buildQuery= $data;
        $queryUrl = '/api/v1/'.$interface.'/';
        $ret = $this->coinApi->post($queryUrl,$data);
        $ret = json_decode($ret, true);
        return $ret;
        
    }
    
    public function getOne($cid,$interface='offers'){
        if(empty($cid)|| ($cid<1))return false;
        $queryUrl = '/api/v1/'.$interface.'/'.$cid.'/';
        $ret = $this->coinApi->get($queryUrl);
        $ret = json_decode($ret, true);
        
        return $ret;
    }
    
    public function edit($cid,$data,$interface='offers'){
        if(empty($cid)|| ($cid<1))return false;
        $queryUrl = '/api/v1/'.$interface.'/'.$cid.'/';
        $data['pk'] = $cid;
        $ret = $this->coinApi->request($queryUrl,  $data,'PUT');
        
        
        return $ret;
    }
    
    public function getlist($page=1,$page_size=20,$interface='offers'){
        $queryUrl = '/api/v1/'.$interface.'/?page='.$page.'&page_size='.$page_size;
        $ret = $this->coinApi->get($queryUrl);
        $ret = json_decode($ret, true);
        return $ret;
        
    }
    
    public function audioapi($interface){
        $u = 0;
        $data = array(
                'h' => array(
                    'u' => (int)$u,
                    's' => substr(md5('l%j#' . $u . 'k*t!'), 0, 16),
                    'v' => 2,
                    't' => 'json',
                    'd' => 'm5dvfre8dileasd3v',
                ),
            );
        $ahost = API_AUDIO_HOST;
        $sign = $this->getSign(json_encode($data));
        $url = $ahost . $interface . '?sign=' . $sign;
        $re    = Curl::post($url,  json_encode($data));
        if($re){
            $re = json_decode($re);
        }
        return $this->object_array($re);
    }
    
    
    public function getSign($datatype) {
        $keys = $this->api_key;
        return md5($keys . $datatype);
    }
    
    
    //PHP stdClass Object转array 
    public function object_array($array) {
        if(is_object($array)) { 
            $array = (array)$array; 
         } if(is_array($array)) { 
             foreach($array as $key=>$value) { 
                 $array[$key] = $this->object_array($value); 
                 } 
         } 
         return $array; 
    }
    
    
    

}
