<?php
/**
 * @name ErrorController
 * @desc 错误控制器, 在发生未捕获的异常时刻被调用
 * @show http://www.php.net/manual/en/yaf-dispatcher.catchexception.php
 * @author ellis
 */
class IndexController extends Base {

    public $tag;
    public function init(){
        $this->initAdmin();
        $this->tag = new TagsModel();
    }

    //从2.1开始, errorAction支持直接通过参数获取异常
    public function indexAction() {
        
        $userInfo = $this->userInfo;
        $navlist = $this->adminNav->getNavList($userInfo['group']);
        $navlist = $this->adminNav->formatIconNav($navlist);
        
        $tags = $this->tag->selectAll();
        $nav_tag = [];
        $nav_tag[0] = '';
        if($tags){
            foreach ($tags as $t) {
                $nav_tag[$t['tag_top_id']][$t['tag_id']] = ['id'=>$t['tag_id'],'name' => $t['tag_name']];
            }
            
        }
        krsort($navlist);
        foreach ($navlist as $key => $nav) {
            if($nav['menu']){
                foreach ($nav['menu'] as $menu){
                    $nav_tag[$nav['order']][$menu['tid']]['menu'][] = $menu;
                }
                if(empty($nav_tag[$nav['order']][0]['id'])){
                    $nav_tag[$nav['order']][0]['id'] = '0';
                }
                if(empty($nav_tag[$nav['order']][0]['name'])){
                    $nav_tag[$nav['order']][0]['name'] = '标签';
                }
                foreach($nav_tag[$nav['order']] as $tid => $taginfo){
                    if(empty($taginfo['menu'])){
                        unset($nav_tag[$nav['order']][$tid]);
                    }
                }                
                $nav['tags'] = array_values($nav_tag[$nav['order']]);
                unset($nav['menu']);
            }
            $navlist2[] = $nav;
            
        }
        $this->assign('uid', $userInfo['id']);
        $this->assign('nav_tag', $nav_tag);
        $this->getView()->assign('nav', $navlist2);

    }
}
