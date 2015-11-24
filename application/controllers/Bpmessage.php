<?php

/* 
 * @name:BpmessageController
 * @author:why
 * @desc:短信推送控制器
 */
class BpmessageController extends Base{
    
    use Trait_Api,
        Trait_Pagger,
        Trait_Layout;
    
    public $message; //短信接口
    public $sourceid = SAPI_SOURCE_ID; //请求sapi接口必传参数
    
    public function init(){
        $this->initAdmin();
        $this->message = $this->getApi('sapi');
    }
    
    public function indexAction(){
        $t = (int) $this->getRequest()->get('t',9);
        $p = (int) $this->getRequest()->get('p' ,1);
        
        $page_size = 10;
        $message_list = [
            'page_no'=> $p,
            'page_size'=> $page_size,
            'state' => $t,
            'sourceid'=> $this->sourceid,
            'timestamp'=> time()
        ];
        
        if($t === 9){
            unset($message_list['state']);
        }
        
        $url = 'message/getlist?' . http_build_query($message_list);
        $rs = $this->message->get($url);
        $rs = json_decode($rs,TRUE);
        
        $t = urldecode($t);
        
        $list = $rs['data']['messages'];
        $count = $rs['data']['total_nums'];
        $this->renderPagger($p, $count, "/bpmessage/index/p/{p}/t/{$t}", $page_size);
        $this->assign('list', $list);
        $this->layout('platform/message_list.phtml');
    }
    
}

