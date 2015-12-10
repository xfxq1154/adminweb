<?php

/**
 * @desc 运营活动
 * @author why
 * @name OperateController
 */
class OperateController extends Base{
    
    use Trait_Layout,
        Trait_Pagger;
    
    public $winuser;
    public $works;
    const oos_host = IMG_OSS_HOST; 
    
    public function init(){
        $this->initAdmin();
        $this->winuser = new WinningUserModel();
        $this->works = new WorksModel();
    }
    
    /**
     * 中奖用户/总榜
     */
    public function winningAction(){
        $this->checkRole();
        $list = $this->winuser->total();
        foreach ($list as &$val){
            $val['poster_img_url'] = self::oos_host.$val['poster_img_url'];
        }
        $this->assign('list', $list);
        $this->layout('operate/winning.phtml');
    }
    
    /**
     * 中奖用户日榜
     */
    public function onedayAction(){
        $rs = $this->winuser->isWinning();
        $this->assign('list', $rs);
        $this->layout('operate/oneday.phtml');
    }
    /**
     * 用户作品
     */
    public function worksAction(){
        $this->checkRole();
        $p = (int)$this->getRequest()->get('p', 1);
        $kw = $this->getRequest()->get('kw', '');
        $time_start = $this->getRequest()->get('time_start');
        $time_end = $this->getRequest()->get('time_end');
        $kw = urldecode($kw);
        $where = '';
        
        if($time_start){
            $where .= " AND ctime >= '$time_start' ";
        }
        if($time_end){
            $where .= " AND ctime <= '$time_end' ";
        }
        
        if ($kw) {
            $where .= " AND  `poster_text` like '%$kw%' ";
        }

        $pagesize = 10;
        //读取列表
        $total = $this->works->getCount($where);
        $this->renderPagger($p, $total, "/operate/works?p={p}&time_start=$time_start&time_end=$time_end&kw=$kw", $pagesize);
        $limit = ($p - 1) * $pagesize . ',' . $pagesize;
        $list = $this->works->getList($limit, $where);
        
        foreach ($list as &$val){
            $val['poster_img_url'] = self::oos_host.$val['poster_img_url'];
        }
        $this->assign('number', $kw);
        $this->assign('time_start', $time_start);
        $this->assign('time_end', $time_end);
        $this->assign('list', $list);
        $this->layout('operate/works.phtml');
    }
    
    /**
     * 禁封作品
     */
    public function blockAction(){
        $id = json_decode($this->getRequest()->getPost('data'), true)['id'];
        $result = $this->works->update($id,2);
        $msg = ($result === TRUE) ? "禁封成功" : "禁封失败";
        $status = ($result === "") ? 1 : 0;
        echo json_encode(['info' => $msg, 'status' => $status]);
        exit;
        
    }
    
    /**
     * 解封作品
     */
    function unblockAction() {
        $id = json_decode($this->getRequest()->getPost('data'), true)['id'];
        $result = $this->works->update($id,1);
        $msg = ($result === TRUE) ? "解封成功" : "解封失败";
        $status = ($result == "") ? 1 : 0;
        echo json_encode(['info' => $msg, 'status' => $status]);
        exit;
    }
}
