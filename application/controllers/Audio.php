<?php

/**
 * @name GroupController
 * @desc 后台音频控制器
 * @show
 * @author hph
 */
class AudioController extends Base {

    use Trait_Layout,
        Trait_Pagger;

    public $audioclass,
            $audio,
            $audioQiniu,
            $qiniu,
            $contentRecord,
            $mp3Record,
            $audioTopic,
            $Audiocontent,
            $tagMod,
            $audioTag;

    /**
     * 入口文件
     */
    public function init() {
        $this->initAdmin();
        $this->audioclass = new AudioClassModel();
        $this->audio = new AudioModel();
        $this->audioQiniu = new AudioQiniuModel();
        $this->qiniu = new QiniuModel();
        $this->contentRecord = new AudioContentRecordModel();
        $this->mp3Record = new AudioMp3RecordModel();
        $this->audioTopic = new AudioTopicModel();
        $this->Audiocontent = new AudioContentModel();
        $this->tag          = new TagModel();
        $this->audioTag     = new AudioTagModel();
    }

    //首页列表
    public function indexAction() {
        die('audio index');
    }

    public function listAction() {
        $this->checkRole();
        $p = (int) $this->getRequest()->getParam('p', 1);
        $t = $this->getRequest()->getParam('t', 0);
        $type = $this->getRequest()->getParam('type', 0);
        $kwd = $this->getRequest()->getParam('kwd', '');
        $kwd = urldecode($kwd);
        $where = '';
        $pageUrl = '/audio/list/p/{p}';

        if($t == 1){
            $pageUrl.='/t/1';
            $where = ' and a_schedule = 0';
        }

        // 收费 or 免费
        if ($type == 1) {
            // 免费
            $pageUrl.='/type/1';
            $where = ' AND a_type = 1';
        } elseif ($type == 2) {
            // 免费
            $pageUrl.='/type/2';
            $where = ' AND a_type = 2';
        }

        if(!empty($kwd)){
            $where.=" and a_title like '%".$kwd."%' ";
            $pageUrl.='/kwd/'.$kwd;
        }

        $pagesize = 100;
        //读取列表
        $total = $this->audio->getNumber($where);

        $this->renderPagger($p, $total, $pageUrl, $pagesize);




        $audioList = $this->audio->getAudioList($p, $pagesize,$where);
        if (!empty($audioList)) {
            foreach ($audioList as $v) {
                if ($v['schedule'] > 0) {
                    $aids[] = $v['schedule'];
                }
            }

            $aidstr = @implode(',', $aids);
            $tlists = $this->audioTopic->getListByIds($aidstr, ' t_id,t_title');
            if (!empty($tlists)) {
                foreach ($tlists as $v) {
                    $topics[$v['id']] = $v['title'];
                }
            }

            foreach ($audioList as $key => $v) {
                $audioList[$key]['topic_title'] = ($v['schedule'] > 0) ? ($topics[$v['schedule']]) : ('');
                $audioList[$key]['mp3_play_url'] = $this->qiniu->downloadfile($v['mp3_url']);
            }
        }

        $this->_view->alist = $audioList;
        $this->_view->t = $t;
        $this->_view->type = $type;
        $this->_view->kwd = $kwd;
        $this->layout('audio/audio_list.phtml');
    }

    //添加音频
    public function addAction() {
        $this->checkRole();
        $user = $_SESSION['a_user'];

        if ($_POST) {
            $data = array(
                'class_id' => $_POST['audio_class'],
                'title' => $_POST['title'], 
                'share_title' => $_POST['share_title'],
                'share_summary' => $_POST['share_summary'],
                'mp3_url' => $_POST['mp3key'], 
                'duration' => $_POST['duration'],
                'original_size'=>$_POST['size'],
                'content' => $_POST['editorValue'], 
                'content_count' => $_POST['textNum'], 
                'sign' => $_POST['sign']
            );

            $data['type'] = $this->getRequest()->getPost('type') ? : '';
            $data['price'] = $this->getRequest()->getPost('price') ? : '';
            $data['points'] = $this->getRequest()->getPost('points') ? : '';
            $data['icon'] = $this->getRequest()->getPost('icon') ? : '';
            $data['summary'] = $this->getRequest()->getPost('summary') ? : '';
            $data['banner'] = $this->getRequest()->getPost('banner') ? : '';

            $res = $this->audio->insert($data);
            $arr = array(
                "info" => "音频添加失败",
                "status" => 0,
                "url" => "/audio/list"
            );
            if ($res > 0) {
                $arr['info'] = '音频添加成功';
                $arr['status'] = 1;
                $recordArr = array('aid' => $res, 'mp3key' => $_POST['mp3key'], 'duration' => $_POST['duration']);

                if (!empty($_POST['mp3key'])) {
                    $fop = $this->qiniu->pfop($_POST['mp3key']);
                    if (empty($fop['error']) && !is_array($fop)) {
                        $next = (time() + 60);
                        $resfop = $this->audioQiniu->insert(array('aid' => $res, 'nid' => $fop, 'next' => $next));
                    }
                }

                
                //add audio tag 
                $audioTag = $_POST['tags'];
                if(!empty($audioTag) && is_array($audioTag)){
                    $this->addNewTag($res, $audioTag);
                }
                
                //添加版本记录
                //添加文稿
                if (!empty($_POST['editorValue'])) {
                    $this->contentRecord->insert(array('content' => $_POST['editorValue'], 'aid' => $res));
                }
                //添加mp3 key
                if (!empty($_POST['mp3key'])) {
                    $this->mp3Record->insert($recordArr);
                }
            }
            Tools::output($arr);
            exit;
        }

        $list = $this->audioclass->getAudioClassList();
        $this->_view->lists = $list;
        $this->_view->libpath = ROOT_PATH . '/applicatiion/library/';
        $this->_view->acturl = 'add';
        $this->layout('audio/audio_add.phtml');
    }

    //修改音频
    public function editAction() {
        $this->checkRole('edit');
        $aid = $this->getRequest()->getParam('aid');
        if (empty($aid)) {
            $arr = array(
                "info" => "音频id不能为空",
                "status" => 0,
                "url" => "/audio/list"
            );
            Tools::output($arr);
            exit;
        }

        if ($_POST) {
            $setNewThumb = false;
            $avinfo = $this->audio->findById($aid);
            $tid = $_POST['audio_topic'];
            $upTopic = false;

            $data = array(
                'id' => $aid, 
                'class_id' => $_POST['audio_class'],
                'title' => $_POST['title'], 
                'share_title' => $_POST['share_title'],
                'share_summary' => $_POST['share_summary'],
                'mp3_url' => $_POST['mp3key'], 
                'duration' => $_POST['duration'],
                'original_size'=>$_POST['size'],
                'thumb_size'=>$_POST['thumb_size'],
                'content' => $_POST['editorValue'], 
                'content_count' => $_POST['textNum'], 
                'sign' => $_POST['sign']
            );

            $data['type'] = $this->getRequest()->getPost('type') ? : '';
            $data['price'] = $this->getRequest()->getPost('price') ? : '';
            $data['points'] = $this->getRequest()->getPost('points') ? : '';
            $data['icon'] = $this->getRequest()->getPost('icon') ? : '';
            $data['summary'] = $this->getRequest()->getPost('summary') ? : '';
            $data['banner'] = $this->getRequest()->getPost('banner') ? : '';

            if ($_POST['amrid']) {
                $data['amr_url'] = $_POST['amrid'];
            }

            //如果 有选择排期
            if (!empty($tid) && is_numeric($tid)) {
                $data['schedule'] = $tid;
                $upTopic = true;
            }
            //检查是否更新mp3文件
            if (!empty($_POST['mp3key']) && !empty($avinfo['mp3_url']) && ($avinfo['mp3_url'] != $_POST['mp3key'])) {
                $data['amr_url'] = '';
                $setNewThumb = true;

            }





            $res = $this->audio->update($data);
            $arr = array(
                "info" => "音频修改失败",
                "status" => 0,
                "url" => "/audio/list"
            );

            if ($res >= 0) {
                $arr['info'] = '音频修改成功';
                $arr['status'] = 1;
                $recordArr = array('aid' => $aid, 'mp3key' => $_POST['mp3key'], 'duration' => $_POST['duration'],'original_size'=>$_POST['size'],'thumb_size'=>$_POST['thumb_size']);//版本记录数据
                $newAmr = !empty($_POST['amrid']) ? $_POST['amrid'] : $avinfo['amr_url'];
                if (!empty($newAmr)) {

                    $recordArr['amr'] = $newAmr;
                }
                  //更新了mp3文件,需要重新转码
                if ($setNewThumb ||  empty($avinfo['mp3_url']) ) {
                        $fop = $this->qiniu->pfop($_POST['mp3key']);
                        if (empty($fop['error']) && !is_array($fop)) {
                            $next = (time() + 60);
                            $resfop = $this->audioQiniu->insert(array('aid' => $aid, 'nid' => $fop, 'next' => $next));

                        }
                        

                        //更新排期内容中的音频大小和时长,区分重新传和回滚记录
                        if($avinfo['schedule'] >0){
                            if(($avinfo['duration']>0)  && ($avinfo['duration'] != $_POST['duration'])){
                                //更新排期表时长和大小
                                $re = $this->audioTopic->updateDurationByTid($avinfo['schedule']);
                            }
                        }

                }
                
                //add audio tag 
                $this->audioTag->delAudioTagByAid($aid);
                $audioTag = $_POST['tags'];
                if(!empty($audioTag) && is_array($audioTag)){
                    
                    $this->addNewTag($aid, $audioTag);
                }

                //添加文稿
                if (!empty($_POST['editorValue'])) {
                    if (empty($avinfo['content']) || ($avinfo['content'] != $_POST['editorValue'])) {
                        $this->contentRecord->insert(array('content' => $_POST['editorValue'], 'aid' => $aid));
                    }
                }

                //添加mp3 key
                if (!empty($_POST['mp3key'])) {
                    $this->mp3Record->insert($recordArr);
                }
                //更新排期表
                if ($upTopic) {
                    $tinfo = $this->audioTopic->getTopicById($tid);
                    $newAudio = !empty($tinfo['audio']) ? ($tinfo['audio'] . ',' . $aid) : $aid;
                    $this->audioTopic->update($tid, array('audio' => $newAudio));
                }
            }

            if ($res == 0) {
                $arr['info'] = '音频修改成功';
                $arr['status'] = 1;
            }

            Tools::output($arr);
            exit;
        }


        $mp3svn = $this->mp3Record->findCountByAid($aid);
        $contentSvn = $this->contentRecord->findCountByAid($aid);


        $avinfo = $this->audio->findById($aid);
        $list = $this->audioclass->getAudioClassList();
        
        $audioTag = $this->audioTag->getTagByAid($aid);

        $this->_view->topiclists = $topicList;
        $this->_view->contentSvn = $contentSvn['num'];
        $this->_view->mp3svn = $mp3svn['num'];
        $this->_view->lists = $list;
        $this->_view->libpath = ROOT_PATH . '/applicatiion/library/';
        $this->_view->acturl = 'edit/aid/' . $aid;
        $this->_view->avinfo = $avinfo;
        $this->_view->audioTag = $audioTag;//print_r($audioTag);exit;
        $this->layout('audio/audio_edit.phtml');
    }

    public function deleteAction() {
        die('delete audio ');
    }

    /**
     * mp3 上传
     *
     * @return string  echo json data
     */
    public function uploadAction() {
        $m = $this->getRequest()->getParam('m', 'add');
        $this->checkRole($m);
        $user = $_SESSION['a_user'];
        $mp3key = '';
        $duration = 0;
        $files = $this->getRequest()->getFiles('file');


        if (file_exists($files['tmp_name'])) {
            $getQiniu = $this->qiniu->uploadFile($files['tmp_name']);
            if (!empty($getQiniu['key'])) {
                $mp3key = $getQiniu['key'];
                $avinfo = $this->qiniu->getAvinfo($mp3key);
                if (!empty($avinfo['format']['duration'])) {
                    $duration = ceil($avinfo['format']['duration']);
                }
            }
        } else {
            echo json_encode(['info' => '上传失败:', 'status' => 0]);
            exit;
        }

        if (!empty($getQiniu['key']) && !empty($avinfo['format']['duration'])) {
            echo json_encode(['info' => '上传成功', 'status' => 1, 'data' => [
                    'savepath' => $mp3key, 'duration' => $duration, 'mp3key' => $mp3key,'size'=>$avinfo['format']['size']]]);
        } else {
            echo json_encode(['info' => '上传失败:' . $getQiniu['error'] . '--' . $avinfo['error'], 'status' => 0]);
        }
        exit;
    }

    //获取文稿记录列表
    public function contentlistAction() {
        $this->checkRole('edit');
        $aid = $_POST['aid'];
        $contentSvn = $this->contentRecord->getAudioList($aid, 1, 10);
        echo json_encode(array('list' => $contentSvn));
        exit;
    }

    //获取MP3记录列表
    public function mp3listAction() {
        $this->checkRole('edit');
        $aid = $_POST['aid'];
        $mp3svn = $this->mp3Record->getAudioList($aid, 1, 10);
        echo json_encode(array('list' => $mp3svn));
        exit;
    }

    //音频回滚记录
    public function rollbackMp3Action() {
        $this->checkRole('edit');
        $rid = $_POST['id']; //回滚记录的主键id
        $res = $this->mp3Record->findById($rid);
        $status = $res ? true : false;
        echo json_encode(array('status' => $status, 'res' => $res));
        exit;
    }

    //文稿回滚记录

    public function rollbackContentAction() {
        $this->checkRole('edit');
        $rid = $_POST['id']; //回滚记录的主键id
        $res = $this->contentRecord->findById($rid);
        $status = $res ? true : false;
        echo json_encode(array('status' => $status, 'res' => $res));
        exit;
    }

    public function setcontentAction() {
        $aid = $_POST['aid'];
        $content = $_POST['content'];
        if ($aid) {
            $res = $this->audio->update(array('id' => $aid, 'content' => $content));
            if ($res >= 0) {
                echo json_encode(array('status' => 'ok', 'res' => ''));
                exit;
            }
            echo json_encode(array('status' => 'error', 'res' => ''));
            exit;
        }
        echo json_encode(array('status' => 'error', 'res' => ''));
        exit;
    }

    public function getcontentAction() {
        $aid = $_POST['aid'];
        if (!empty($aid)) {
            $content = $this->Audiocontent->findById($aid);
            if ($content) {
                echo json_encode(array('status' => 'ok', 'res' => $content['content']));
                exit;
            }
            echo json_encode(array('status' => 'error', 'res' => ''));
            exit;
        }
        echo json_encode(array('status' => 'error', 'res' => ''));
        exit;
    }
    
    //search tag name
    public function searchTagAction(){
        $kwd = $_POST['kwd'];
        $kwd = !empty($kwd)?trim($kwd):'%';
        $res = $this->tag->searchTag($kwd);
        if(!empty($res)){
            echo json_encode(array('status' => 'ok', 'res' => $res));
            exit;
        }
        echo json_encode(array('status' => 'error', 'res' => ''));
        exit;
    }
    
    //add new tag
    public function addTagAction(){
        $tag = $_POST['tag'];
        if(empty($tag)){
            echo json_encode(array('status' => 'error', 'res' => '','code'=>'empty tag name'));
            exit;
        }
        
        $res = $this->tag->findByName($tag);
        if(!empty($res)){
            echo json_encode(array('status' => 'error', 'res' => $res['id'],'code'=>$tag.'标签已经存在'));
            exit;
        }else{
            $result = $this->tag->insert(array('name'=>$tag));
            $status = ($result>0)?'ok':'error';
            $code = ($result>0)?'添加成功':'添加失败';
        }
        echo json_encode(array('status' => $status, 'res' => $result,'code'=>$code));
        exit;
    }
    
    public function testAction(){
        $a = array('音乐','情感','媒体');
        
        $this->addNewTag(20, $a);
        exit;
    }
    
    public function addNewTag($aid,$tags){
        if(empty($aid) || empty($tags) || !is_array($tags)) return false;
        $tids = $this->tag->addNewTags($tags);
        
        if(!empty($tids) && is_array($tids)){
            foreach ($tids as $v){
                $array = array('tid'=>$v,'aid'=>$aid);
                $re = $this->audioTag->insert($array);
            }
            
        }
        return $re;
    }

//    public function adddataAction(){
//        $res = $this->audio->getAudioList(1,100);
//        foreach($res as $v){
//            $this->Audiocontent->insert(array('id'=>$v['id'],'content'=>$v['content'],'sign'=>$v['sign'],'content_count'=>$v['content_count']));
//        }
//    }
}
