<?php

/**
 * @name AudiotopicController
 * @author hph
 * @desc 音频排期控制器
 */
class AudiotopicController extends Base {

    use Trait_Layout,
        Trait_Pagger;

    public $audioTopic;
    public $audio, $audioTopicBook, $book, $audioTopicRecord;
    public $audioQiniu,$qiniu,$verseRelation;

    public function init() {
        $this->initAdmin();
        $this->audioTopic = new AudioTopicModel();
        $this->audioTopicBook = new AudioTopicBookModel();

        $this->audio = new AudioModel();
        $this->audioTopicRecord = new AudioTopicRecordModel();
        $this->book = new BookModel();
        $this->audioQiniu = new AudioQiniuModel();
        $this->qiniu = new QiniuModel();
        $this->verseRelation = new VerseRelationModel();
    }

    /**
     * 添加排期
     */
    public function addAction() {
        $this->checkRole('adddailypackage');

        if ($_POST) {
            //Tools::output($_POST);
            //{"title":"sdfsda","type":"2","datetime":"2015-06-18","audiolist":"4,3,2","aid":["4","3"]}
            $user = $_SESSION['a_user'];
            $data = array(
                'title' => $_POST['title'],
                'type' => $_POST['type'] ? $_POST['type'] : 1,
                'datetime' => $_POST['datetime'],
                'icon' => $_POST['icon'],
                'uid' => $user['id'],
                'user' => $user['user'],
            );
            $audiolist = $_POST['audiolist'] ? $_POST['audiolist'] : '';
            $audioId = $_POST['aid'] ? $_POST['aid'] : array();


            //验证常规计费类型的排期是否存在当天的记录，不能重复
            if ($data['type'] == 1) {
                $dtime = $_POST['datetime'];
                $paycheck = $this->audioTopic->getNumber(array('datetime' => $dtime, 'type' => 1));
                if ($paycheck > 0) {
                    $data = array(
                        "info" => '当天常规计费类型的排期已经存在，不能重复',
                        "status" => 0,
                        "url" => "",
                    );
                    Tools::output($data);
                }
            }

            if ($audioId) {
                $aids = implode(',', $audioId);
            }

            $data['audio'] = !empty($aids) ? $aids : '';
            if (empty($data['audio'])) {
                $data = array(
                    "info" => '请填写正确的音频id,同一个音频不能被重复排期！',
                    "status" => 0,
                    "url" => "",
                );
                Tools::output($data);
            }

            $id = $this->audioTopic->add($data);
            if ($id) {
                $data = array(
                    "info" => '添加 音频排期成功！',
                    "status" => 1,
                    "url" => "/audiotopic/index",
                );
                Tools::output($data);
            }
            $data = array(
                "info" => '未知错误！',
                "status" => 0,
                "url" => "",
            );
            Tools::output($data);
        }

        $this->layout('audio/topic_add.phtml');
    }

    /**
     * 排期列表
     */
    public function indexAction() {
        $this->checkRole('adddailypackage');
        $p = (int) $this->getRequest()->getParam('p', 1);
        $pagesize = 10;
        $where = array();
        //读取列表
        $total = $this->audioTopic->getNumber();
        $limit = ($p - 1) * $pagesize . ',' . $pagesize;
        $list = $this->audioTopic->getList($where, $limit);
        $this->assign('list', $list);
        $this->renderPagger($p, $total, '/audiotopic/index/p/{p}', $pagesize);
        $this->layout('audio/topic_list.phtml');
    }

    public function audiolistAction() {
        $this->checkRole('adddailypackage');
        $tid = $this->getRequest()->getParam('tid', 0);
        $play = $this->getRequest()->getParam('play',0);
        $audioType = $this->getRequest()->getParam('type', 0);

        if ($tid == 0)
            $tid = $_POST['tid'];
        $act = $this->getRequest()->getParam('act', '');
        //Tools::output($_POST);
        $audiolist = $_POST['audiolist'];
        $audiolist = explode(',', $audiolist);

        if (is_array($audiolist) && count($audiolist) > 0) {

            if ($tid > 0) {
                $_list = $this->audio->getAudioById($audiolist, '`a_id`,`a_title`,`a_duration`,`a_mp3_url`', $tid);
            } else {
                $_list = $this->audio->getAudioById($audiolist, '`a_id`,`a_title`,`a_duration`,`a_mp3_url`',0);
            }
            if ($_list) {
                if($play == 1){
                    foreach ($_list as $k=>$v){
                        $_list[$k]['mp3_play_url'] = $this->qiniu->downloadfile($v['mp3_url']);
                    }
                }

                Tools::success('ok', '', $_list);
            }
            if ($act != 'list') {
                Tools::success('error', '没有符合条件的音频数据');
            }
        }
        if ($act != 'list') {
            Tools::success('error', '参数错误');
        }
    }

    public function editaudiolistAction() {
        $this->checkRole('adddailypackage');
        $tid = $this->getRequest()->getParam('tid', 0);
        $audioType = $this->getRequest()->getParam('type', 0);
        if ($tid == 0)
            $tid = $_POST['tid'];
        $act = $this->getRequest()->getParam('act', '');
        //Tools::output($_POST);
        $audiolist = $_POST['audiolist'];
        $audiolist = explode(',', $audiolist);

        if (is_array($audiolist) && count($audiolist) > 0) {

            if ($tid > 0) {
                $_list = $this->audio->getAudioByEdit($audiolist, '`a_id`,`a_title`,`a_duration`', $tid);
            } else {
                $_list = $this->audio->getAudioByEdit($audiolist, '`a_id`,`a_title`,`a_duration`',0);
            }
            if ($_list) {
                Tools::success('ok', '', $_list);
            }
            if ($act != 'list') {
                Tools::success('error', '没有符合条件的音频数据');
            }
        }
        if ($act != 'list') {
            Tools::success('error', '参数错误');
        }
    }

    /**
     * icon 上传排期封面
     *
     * @return string  echo json data
     */
    public function uploadAction() {
//        $m = $this->getRequest()->getParam('m', 'adddailypackage');
        $this->checkRole('adddailypackage');
        $user = $_SESSION['a_user'];
        $files = $this->getRequest()->getFiles('file');
        $params = [
            'uid' => $user['id'],
            'filedata' => '@' . $files['tmp_name'],
            'type' => $files['type'],
            'name' => $files['name']
        ];
        // 上传接口
        $url = API_SERVER_IMGURL . '/attachment/images/cate/audio/type/cover';
        $remoteRst = Curl::request($url, $params, 'post');
        if ($remoteRst && is_array($remoteRst) && 'ok' == $remoteRst['status']) {
            echo json_encode(['info' => '上传成功', 'status' => 1, 'data' => [
                    'savepath' => $remoteRst['data']['url']['url'],'path' => $remoteRst['data']['url']['path'],'host'=>IMG_HOST]]);
        } else {
            echo json_encode(['info' => '上传失败', 'status' => 0]);
        }
        exit;
    }

    public function editAction() {
        $this->checkRole('modifyDailyPackage');
        $id = (int) $this->getRequest()->getParam('id', 0);
        $tid = $id;
        if ($_POST) {
            //Tools::output($_POST);
            $user = $_SESSION['a_user'];
            $data = array(
                'title' => $_POST['title'],
                'type' => $_POST['type'] ? $_POST['type'] : 1,
                'datetime' => $_POST['datetime'],
                'icon' => $_POST['icon'],
                'uid' => $user['id'],
                'user' => $user['user'],
            );
            $audiolist = $_POST['audiolist'] ? $_POST['audiolist'] : '';
            $audioId = $_POST['aid'] ? $_POST['aid'] : array();

            if ($audioId) {
                $aids = implode(',', $audioId);
                $this->audio->updateAudioStatus(array('schedule' => 0), $id);
            }

            $data['audio'] = !empty($aids) ? $aids : '';
            if (empty($data['audio'])) {
                $data = array(
                    "info" => '请填写正确的音频id且同一个音频不能被重复排期！',
                    "status" => 0,
                    "url" => "",
                );
                Tools::output($data);
            }


            $id = $this->audioTopic->update($id, $data);
            if ($id >= 0) {
                $data = array(
                    "info" => '添加 音频排期成功！',
                    "status" => 1,
                    "url" => "/audiotopic/index",
                );
                Tools::output($data);
            }
            $data = array(
                "info" => '未知错误！',
                "status" => 0,
                "url" => "",
            );
            Tools::output($data);
        }

        //读取排期内容
        $topic_info = $this->audioTopic->getTopicById($id);
        if (!$topic_info) {
            Tools::output('排期信息不存在！');
        }

        //判断上线日期
        $now = date('Y-m-d');
        $datetime = $topic_info['datetime'];
        if ($datetime <= $now) {
            Tools::output('不能删除已经上线的排期！');
        }

        $topicRecordNum = $this->audioTopicRecord->getNumberByTid($id);

        $this->_view->topicRecordNum = $topicRecordNum;
        $this->_view->tid = $tid;
        $this->assign("topic", $topic_info);
        $this->layout('audio/topic_edit.phtml');
    }

    /**
     * 删除排期
     */
    public function deleteAction() {
        $this->checkRole();
        //Tools::output($_POST,'print_r');
        $type = $this->getRequest()->getParam('type');
        $date = $this->getRequest()->getParam('d');
        $tid = $this->getRequest()->getParam('tid');
        $return = array(
            "info" => '删除失败！',
            "status" => 0,
            "url" => "",
        );
        $id = (int) $_POST['data'];
        if (empty($tid)) {
            $return['info'] = '参数错误！';
            Tools::output($return);
        }
        $idstr = str_replace('-', ',', $tid);
        $idArr = explode(',', $idstr);
        //读取排期内容
        $topic_info = $this->audioTopic->getListByIds($idstr);
        if (!$topic_info) {
            $return['info'] = '排期信息不存在！';
            Tools::output($return);
        }

        //判断上线日期
        $now = date('Y-m-d');
        $datetime = $date;
        if ($datetime <= $now) {
            $return['info'] = '不能删除已经上线的排期！';
            Tools::output($return);
        }

        if (!empty($idArr)) {
            foreach ($idArr as $v) {
                $row = $this->audioTopic->update($v, array('status' => 0));
            }
        }

        if ($row > 0) {
            $return['info'] = '删除排期成功！';
            $return['status'] = 1;
            Tools::output($return);
        }
        Tools::output($return);
    }

    public function updateStatusAction() {
        $this->checkRole('ModifyDailyPackage');
        $type = $this->getRequest()->getParam('type','');//type:s 更新单条记录，''按日期更新记录的状态
        $date = $this->getRequest()->getParam('d');
        $tid = $this->getRequest()->getParam('tid');
        $status = $this->getRequest()->getParam('st');
        $status-=1;
        $return = array(
            "info" => '更新失败！',
            "status" => 0,
            "url" => "",
        );

        $ids = explode('-', $tid);
        $ids = array_filter($ids);
        $idstr = implode(',', $ids);
        //读取排期内容
        if($type == 's'){
            $list = $this->audioTopic->getListByIds($idstr);//按单条id更新
        }else{
            $list = $this->audioTopic->getList(array('datetime' => $date), 100);//按日期更新状态
        }

        //$topic_info = $this->audioTopic->getListByIds($idstr);
        //$topic_info = $list;
        //$list = $topic_info;
        if (!$list) {
            $return['info'] = '排期信息不存在！';
            Tools::output($return);
        }



        if (!empty($list)) {
            foreach ($list as $tv) {
                $row = $this->audioTopic->update($tv['id'], array('status' => $status));
            }
        }

        if ($row >= 0) {
            $return['info'] = '更新排期成功！';
            $return['status'] = 1;

            //添加购买汇总表记录
            if ($date <= date('Y-m-d')) {

                foreach ($list as $v) {
                    $tid = $v['id'];
                    if ($v['class'] == 0) {//只针对付费音频排期
                        if ($status == 1) {//添加购买列表
                            $this->audioTopicBook->insert(array('mixed_id' => $tid, 'type' => 1, 'date' => $v['datetime']));
                        } else {//删除购买列表
                            $this->audioTopicBook->destory($tid, 1);
                        }
                    }
                }
            }
            Tools::output($return);
        }
        Tools::output($return);
    }



    public function topiclistAction() {
        $this->checkRole('adddailypackage');
        $tid = $_POST['tid'] ? $_POST['tid'] : 0;

        if ($tid > 0) {
            $topiclist = $this->audioTopicRecord->getList($tid);
            if (!empty($topiclist)) {
                Tools::success('ok', '', $topiclist);
            }
            Tools::success('error', '没有数据');
        }

        Tools::success('error', '参数错误');
    }

    public function rollbackTopicAction() {
        $this->checkRole('adddailypackage');
        $rid = $_POST['rid'];
        if ($rid > 0) {
            $res = $this->audioTopicRecord->getRecordById($rid);
            if (!empty($res)) {
                Tools::success('ok', '', $res);
            }
            Tools::success('error', '没有数据');
        }

        Tools::success('error', '参数错误');
    }

    public function getAudioTopicRecordsAction() {
        $this->checkRole('adddailypackage');
        $rid = $_POST['rid'];
        if ($rid > 0) {
            $res = $this->audioTopicRecord->getList($rid);
            if (!empty($res)) {
                Tools::success('ok', '', $res);
            }
            Tools::success('error', '没有数据');
        }

        Tools::success('error', '参数错误');
    }

    /*
     * 添加排期时，检查确保常规计费每天只有一条
     */

    public function checkPayTypeRecordAction() {
        $dtime = $_POST['dtime'];
        $type = $_POST['topictype'] ? $_POST['topictype'] : 1;
        $res = $this->audioTopic->getNumber(array('datetime' => $dtime, 'type' => $type));
        if ($res > 0) {
            Tools::success('error');
        }
        Tools::success('ok');
    }

    /**
     * 添加日常包排期,主题包排期
     */
    public function addDailyPackageAction() {
        $this->checkRole();
        if ($_POST) {
            $this->TopicPackageSubmit();
        }

        $this->layout('audio/topic_daily_add.phtml');
    }

    //添加和修改 日常包，主题包  提交处理
    public function TopicPackageSubmit() {
        $act = $this->getRequest()->getParam('act', ''); //判断是否为编辑页面，act为空时是添加页面，act为edit  是编辑页面
        $tid = $this->getRequest()->getParam('tid', 0); //排期id id1-id2-id3,日常包三条记录，主题包两条记录
        $edited = false;

        if (($act == 'edit') && empty($tid)) {
            $data = array(
                "info" => '参数有误,提交失败',
                "status" => 0,
                "url" => "",
            );
            Tools::output($data);
        }

        //判断是否有关联金句,没有或是多个都会返回错误
        $response = array(
                    "info" => '提交失败！排期只能关联一条金句，不能关联多个',
                    "status" => 1,
                    "url" => '',
                );
        $vid = isset($_POST['verse_id']) ? trim($_POST['verse_id']):'';
//            if(empty($vid)){
//                $response['info'] = '提交失败！此排期没有设置推荐金句';
//                Tools::output($response);
//            }
//            $vidArr = explode(',',$vid);
//            if(count($vidArr)>1){
//
//                Tools::output($response);
//            }

        if (!empty($tid)) {
            $idArr = explode('-', $tid);
            $idArr = array_filter($idArr);
            $idstr = implode(',', $idArr);
            $tinfo = $this->audioTopic->getListByIds($idstr, 't_id,t_class');

            foreach ($tinfo as $tv) {
                $topicClass[$tv['class']] = $tv['id'];
            }

            $freeid = $topicClass['2']?$topicClass['2']:0; //免费音频排期主键id
            $payid = $topicClass['0']?$topicClass['0']:0; //收费音频排期主键id
            $bookid = $topicClass['1']?$topicClass['1']:0; //电子书排期主键id
        }


        $title = $_POST['title'];
        $publishDate = $_POST['datetime'];     //上线日期
        $cover = $_POST['icon'];         //排期封面
        $type = $_POST['type'];         //类型：1日常包,2主题包
        $free_audioList = $_POST['audiolist'];    //免费音频列表id
        $pay_audioLIst = $_POST['payaudiolist']; //收费音频列表id
        $bookList = $_POST['ebook'];        //电子书列表id

        $bookPrice = $_POST['bookprice']; //电子书价格
        $audioPirce = $_POST['audio_price']; //收费音频价格
        $audioPoints = $_POST['audio_points']; //付费音频购买 赠送的学分
        $audioIcon = $_POST['audio_cover'];   //付费音频封面
        $audioBanner = $_POST['audio_banner'];  //付费音频头图
        $audioSummary = $_POST['audio_summary']; //付费音频摘要
        $status = ($_POST['status'] - 1);
        $user = $_SESSION['a_user'];
        $audio_title = $_POST['audio_title'];
        $audioBrife = $_POST['audio_brife']; //付费音频摘要


        //提交成功后，返回地址：1日常包列表页面，2主题包列表页面
        $directUrl = ($type == 1) ? 'listDailyPackage' : 'listThemePackage';

        //添加音频验证是否重复排期
        if(!empty($free_audioList)){
            $free_audioList = $this->checkaids($free_audioList);
            $checkFree = $this->audio->checkRetopic(explode(',',$free_audioList),$freeid);
            if($checkFree){
                $data = array('info'=>'免费音频id不能重复排期，音频id为：'.$checkFree,'status'=>1);
                Tools::output($data);
            }
        }

        //编辑音频验证是否重复排期
        if(!empty($pay_audioLIst)){
            $pay_audioLIst = $this->checkaids($pay_audioLIst);
            $checkPaid = $this->audio->checkRetopic(explode(',',$pay_audioLIst),$payid);
            if($checkPaid){
                $data = array('info'=>'付费音频id不能重复排期，音频id为：'.$checkPaid,'status'=>1);
                Tools::output($data);
            }
        }

        if(!empty($free_audioList) && !empty($pay_audioLIst) ){
            $sameid = array_intersect($this->checkaids($free_audioList, 'array'),$this->checkaids($pay_audioLIst,'array'));
            if(!empty($sameid) && (count($sameid) > 0)){
                $sameid = implode(',',$sameid);
                $data = array('info'=>'音频不能同时为免费和付费排期，错误音频id为：'.$sameid,'status'=>1);
                Tools::output($data);
            }

        }



        //是否是编辑页面
        if ($act == 'edit') {
            $edited = true;
        }


        //插入前验证是否有当天的排期记录
        if (!$edited && ($type == 1)) {
            $paycheck = $this->audioTopic->getNumber(array('datetime' => $publishDate, 'type' => $type)," t_class > 0 ");
            if ($paycheck > 0) {
                $response['info'] = '当天的排期已经存在，不能重复';
                $response['url'] = '';
                $response['status'] = 1;
                Tools::output($response);
            }

        }


        $data = array(
            'title' => $title,
            'type' => $type,
            'datetime' => $publishDate,
            'audio' => $free_audioList,
            'icon' => $cover,
            'uid' => $user['id'],
            'class' => 2,
            'user' => $user['user'],
            'status' => $status,
            'audio_title' => $audio_title,
            'audio_brife' => $audioBrife
        );
        //只有日常包有免费音频，主题包没有
        if (($type == 1)) {

            if ($edited && !empty($freeid)) {//修改免费音频数据记录
                $re1 = $this->audioTopic->update($freeid, $data);
                $re1 = ($re1 >= 0) ? true : false;
            } else {//插入免费音频数据记录
                $re1 = $this->audioTopic->add($data);
            }
            //add verse relation
            $verseMod = new VerseRelationModel();

//            if($vidArr['0']>0){
//                $freeTopicid = ($freeid > 0)?$freeid:$re1;
//                $verseMod->add(array('object_id'=>$freeTopicid,'vid'=>$vidArr['0'],'type'=>1));
//            }

        }


        //插入付费音频数据记录
       if (!empty($pay_audioLIst)) {
            $data['audio'] = $pay_audioLIst;
            $data['audio_price'] = $audioPirce;
            $data['audio_points'] = $audioPoints;
            $data['class'] = 0;
            $data['audio_icon'] = $audioIcon;
            $data['audio_banner'] = $audioBanner;
            $data['audio_summary'] = $audioSummary;
            if(!empty($audioBrife)){
                $data['audio_brife'] = $audioBrife;
            }

            //只有日常包才有音频包标题
            if($type == 1){
                $data['audio_title']   = $audio_title;
            }



            if ($edited && !empty($payid)) {
                $re2 = $this->audioTopic->update($payid, $data);
                $re2 = ($re2 >= 0) ? true : false;
            } else {
                $re2 = $this->audioTopic->add($data);
            }
        }



        //插入电子书数据记录
        if (!empty($bookList)) {

            unset($data['audio_price']);
            unset($data['audio_points']);
            unset($data['audio_icon'], $data['audio_banner'], $data['audio_summary']);
            $data['audio'] = $bookList;
            $data['class'] = 1;
            if (!empty($bookPrice) && !empty($bookList)) {
                $bprice = $bookPrice;
                $blist = explode(',', $bookList);
                foreach ($blist as $k => $v) {
                    $ebookPrice[$v] = $bprice[$k];
                }
            }
            $data['ebook_price'] = serialize($ebookPrice);


            if ($edited && !empty($bookid)) {

                $re3 = $this->audioTopic->update($bookid, $data);
                $re3 = ($re3 >= 0) ? true : false;
            } else {
                $re3 = $this->audioTopic->add($data);
            }
            //add verse relation row
            $this->verseRelation->editTopicBookRelation($bookList,$data['datetime']);
        }


        $addRes = ($type == 1) ? ($re1) : ($re2);
        if ($addRes) {
            $data = array(
                "info" => '添加 排期成功！',
                "status" => 1,
                "url" => "/audiotopic/" . $directUrl,
            );
            if(!empty($idArr)){
                $data['info'] = '更新排期成功！';
            }
            Tools::output($data);
        }
        $data = array(
            "info" => '提交失败，请稍后重试！',
            "status" => 0,
            "url" => "",
        );
        Tools::output($data);
    }

    /**
     * 修改日常包排期
     */
    public function ModifyDailyPackageAction() {
        $this->checkRole();
        $tid = $this->getRequest()->getParam('tid');
        $date = $this->getRequest()->getParam('d');

        //$tinfo = $this->audioTopic->getList(array('datetime' => $date, 'type' => 1), 3);

        $ids = explode('-', $tid);
        $ids = array_filter($ids);
        $idstr = implode(',', $ids);

        $tinfo = $this->audioTopic->getListByIds($idstr,'*',false);

        foreach ($tinfo as $tv) {
            $editInfo[$tv['class']] = $tv;
        }
        $baseInfo = (!empty($editInfo[2])) ? $editInfo['2'] : array();
        $baseInfo['icon'] = isset($baseInfo['icon'])?(Tools::formatImg($baseInfo['icon'])):'';
        if ($_POST) {
            $this->TopicPackageSubmit();
        }

        $booksvn = $this->audioTopicRecord->getNumberByTid($tinfo['1']['id']);
        $verseInfo = $this->verseRelation->getVerseByTid($editInfo['2']['id'],0);
        $verseInfo = $verseInfo?$verseInfo['0']:'';
        $this->assign('booksvn', $mp3svn['num']);
        $this->assign('freeinfo', $editInfo['2']);
        $this->assign('payinfo', $editInfo['0']);
        $this->assign('bookinfo', $editInfo['1']);
        $this->assign('baseInfo', $baseInfo);
        $this->assign('booksvn', $booksvn);
        $this->assign('tid', $tid);
        $this->_view->verseinfo = $verseInfo;
        $this->layout('audio/topic_daily_modify.phtml');
    }

    /**
     * 日常包排期 列表
     */
    public function ListDailyPackageAction() {
        $this->checkRole();
        $user = $_SESSION['a_user'];
        $roles = $user['group'];
        $showEdit = false;
        if (($roles == 1) || ($roles == 17)) {
            $showEdit = true;
        }

        $p = (int) $this->getRequest()->getParam('p', 1);
        $t = $this->getRequest()->getParam('t', 0);

        $pagesize = 10;
        $pageUrl = '/audiotopic/ListDailyPackage/p/{p}';
        $where = array('type' => 1);

        if ($t > 0) {
            $where['class'] = ($t == 2) ? 2 : 0;
            $pageUrl.='/t/'.$t;
        }

        //读取列表
        $total = $this->audioTopic->getNumber($where);
        $limit = ($p - 1) * $pagesize . ',' . $pagesize;
        $list = $this->audioTopic->getList($where, $limit);

        $result = array();
        foreach ($list as $k => $v) {
            if (!empty($v['audio']) && $v['class'] == 1) {
                $booklist = $this->book->getBookById(explode(',', $v['audio']),'*',0);
                foreach ($booklist as $k => $bv) {
                    $priceList = unserialize($v['ebook_price']);
                    $newPrice = $priceList[$bv['id']];
                    $booklist[$k]['price'] = $newPrice;
                }
                $v['booklist'] = $booklist;
            }

            if(!empty($v['audio']) && $v['class'] == 0){
                $result[$v['datetime']][$v['class']][] = $v;
            }else{
                $result[$v['datetime']][$v['class']] = $v;
            }

        }//print_r($result);exit;

        $this->assign('list', $result);
        $this->assign('t', $t);
        $this->assign('showEdit', $showEdit);
        $this->renderPagger($p, $total, $pageUrl, $pagesize);
        $this->layout('audio/topic_daily_list.phtml');
    }

    /**
     * 获取列表内容
     */
    public function getDailyListAction() {
        //$this->checkRole('edit');
        $tid = $_POST['tid'] ? $_POST['tid'] : 0;

        if ($tid > 0) {
            $topiclist = $this->audioTopicRecord->getList($tid);
            if (!empty($topiclist)) {
                Tools::success('ok', '', $topiclist);
            }
            Tools::success('error', '没有数据');
        }

        Tools::success('error', '参数错误');
    }

    /**
     * 电子书列表预览
     */
    public function getEbookListAction() {
        //$this->checkRole('adddailypackage');
        //Tools::output($_POST);
        $ebooklist = $_POST['ebooklist'];
        $ebooklist = explode(',', $ebooklist);
        $act = $this->getRequest()->getParam('act', '');
        $tid = $this->getRequest()->getParam('tid', '');


        if (is_array($ebooklist) && count($ebooklist) > 0) {


            $_list = $this->book->getBookById($ebooklist, '`b_id`,`b_title`,`b_cover`,`b_type`,`b_author`,`b_price`',0);
            if ($_list) {
                if (($act == 'edit') && ($tid > 0)) {   //编辑页面的电子书列表
                    $tinfo = $this->audioTopic->getTopicById($tid);
                    $priceList = @unserialize($tinfo['ebook_price']);
                    foreach ($_list as $k => $v) {
                        $_list[$k]['price'] = $priceList[$v['id']];
                        $_list[$k]['original_price'] = $v['price'];
                    }
                }
                Tools::success('ok', '', $_list);
            }

            Tools::success('error', '没有符合条件的数据');
        }

        Tools::success('error', '参数错误');
    }

    /**
     * 添加 主题包排期
     */
    public function addThemePackageAction() {
        $this->checkRole();
        $this->layout('audio/topic_theme_add.phtml');
    }

    /**
     * 修改 主题包排期
     */
    public function modifyThemePackageAction() {
        $this->checkRole();
        $tid = $this->getRequest()->getParam('tid');
        $date = $this->getRequest()->getParam('d');

        $ids = explode('-', $tid);
        $ids = array_filter($ids);
        $idstr = implode(',', $ids);

        $tinfo = $this->audioTopic->getListByIds($idstr);
        //$tinfo = $this->audioTopic->getList(array('datetime' => $date, 'type' => 2), 3);

        foreach ($tinfo as $tv) {
            $editInfo[$tv['class']] = $tv;
        }
        $baseInfo = (!empty($editInfo[0])) ? $editInfo['0'] : $editInfo['1'];

        if ($_POST) {
            $this->TopicPackageSubmit();
        }

        $this->assign('payinfo', $editInfo['0']);
        $this->assign('bookinfo', $editInfo['1']);
        $this->assign('tid', $tid);
        $this->assign('baseInfo', $baseInfo);
        $this->layout('audio/topic_theme_modify.phtml');
    }

    /**
     *  主题包排期 列表
     */
    public function ListThemePackageAction() {
        $this->checkRole();
        $p = (int) $this->getRequest()->getParam('p', 1);
        $t = $this->getRequest()->getParam('t', 0);

        $user = $_SESSION['a_user'];
        $roles = $user['group'];
        $showEdit = false;
        if ($roles == 1) {
            $showEdit = true;
        }

        $pagesize = 10;
        $where = array('type' => 2);
        //读取列表
        $total = $this->audioTopic->getNumber($where);
        $limit = ($p - 1) * $pagesize . ',' . $pagesize;
        $list = $this->audioTopic->getList($where, $limit);

        $result = array();
        foreach ($list as $k => $v) {
            if (!empty($v['audio']) && $v['class'] == 1) {
                $booklist = $this->book->getBookById(explode(',', $v['audio']));
                foreach ($booklist as $k => $bv) {
                    $priceList = unserialize($v['ebook_price']);
                    $newPrice = $priceList[$bv['id']];
                    $booklist[$k]['price'] = $newPrice;
                }
                $v['booklist'] = $booklist;
            }
            $result[$v['datetime']][] = $v;
        }

        $this->assign('list', $result);
        $this->assign('showEdit', $showEdit);
        $this->renderPagger($p, $total, '/audiotopic/ListThemePackage/p/{p}', $pagesize);
        $this->layout('audio/topic_theme_list.phtml');
    }




    function checkaids($aids,$type=''){
        $ids = explode(',', $aids);
        $ids = array_filter($ids);

        if(!empty($type) && $type == 'array'){
            return $ids;
        }

        return implode(',', $ids);

    }


    function modifyPaidTopicAction(){
        $this->checkRole('ModifyDailyPackage');
        $tid = $this->getRequest()->getParam('tid');
        $date = $this->getRequest()->getParam('d');



        $tinfo = $this->audioTopic->getTopicById($tid);


        if ($_POST) {
            $this->paidTopicSubmit();
        }


        $this->assign('payinfo', $tinfo);
        $this->assign('tid', $tid);
        $this->layout('audio/modify_paid_topic.phtml');
    }

    function addPaidTopicAction(){
        $this->checkRole('addDailyPackage');
        if ($_POST) {
            $this->paidTopicSubmit();
        }

        $this->layout('audio/add_paid_topic.phtml');
    }


    function paidTopicSubmit(){

        $act = $this->getRequest()->getParam('act', ''); //判断是否为编辑页面，act为空时是添加页面，act为edit  是编辑页面
        $tid = $this->getRequest()->getParam('tid', 0); //排期id
        $edited = false;
        $user = $_SESSION['a_user'];

        if (($act == 'edit') && empty($tid)) {
            $data = array(
                "info" => '参数有误,提交失败',
                "status" => 0,
                "url" => "",
            );
            Tools::output($data);
        }

        if($act == 'edit'){
            $edited = true;
        }

        $pay_audioLIst = $_POST['payaudiolist'];
        $type = $_POST['type'];

         //编辑音频验证是否重复排期
        if(!empty($pay_audioLIst)){
            $pay_audioLIst = $this->checkaids($pay_audioLIst);
            $checkPaid = $this->audio->checkRetopic(explode(',',$pay_audioLIst),$tid);
            if($checkPaid){
                $data = array('info'=>'付费音频id不能重复排期，音频id为：'.$checkPaid,'status'=>1);
                Tools::output($data);
            }
        }

          //插入付费音频数据记录
       if (!empty($pay_audioLIst)) {
            $data['title'] = $_POST['audio_title'];
            $data['audio_title'] = $_POST['audio_title'];
            $data['audio'] = $pay_audioLIst;
            $data['audio_price'] = $_POST['audio_price'];
            $data['audio_points'] = $_POST['audio_points'];
            $data['class'] = 0;
            $data['audio_icon'] = $_POST['audio_cover'];
            $data['icon'] = $_POST['audio_cover'];
            $data['audio_banner'] = $_POST['audio_banner'];
            $data['audio_summary'] = $_POST['audio_summary'];

            $data['audio_brife'] = $_POST['audio_brife'];
            $data['datetime'] = $_POST['datetime'];
            $data['type'] = 1;
//            $data['status'] = ($_POST['status'] - 1);

            $data['uid'] = $user['id'];
            $data['user'] = $user['user'];




            if ($edited && !empty($tid)) {
                $re = $this->audioTopic->update($tid, $data);
                $re = ($re >= 0) ? true : false;
            } else {
                $re = $this->audioTopic->add($data);
            }
        }


        if ($re) {
            $data = array(
                "info" => '添加 排期成功！',
                "status" => 1,
                "url" => "/audiotopic/listDailyPackage" ,
            );
            if($edited){
                $data['info'] = '更新排期成功！';
            }
            Tools::output($data);
        }
        $data = array(
            "info" => '提交失败，请稍后重试！',
            "status" => 0,
            "url" => "",
        );
        Tools::output($data);


    }



    public function viewAction(){
        $tid = $this->getRequest()->getParam('tid');
        $tinfo = $this->audioTopic->getTopicById($tid);

        if(isset($tinfo['icon'])){
            $tinfo['icon'] = Tools::formatImg($tinfo['icon']);
        }

        if(isset($tinfo['audio_icon'])){
            $tinfo['audio_icon'] = Tools::formatImg($tinfo['audio_icon']);
        }
        if(isset($tinfo['audio_banner'])){
            $tinfo['audio_banner'] = Tools::formatImg($tinfo['audio_banner']);
        }
        $this->assign('topicInfo', $tinfo);
        $this->layout('audio/view_topic.phtml');
    }




}
