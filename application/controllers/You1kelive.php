<?php

/**
 * @name You1keliveController;
 * @author Why
 * @desc 又一课课程数据列表
 */
class You1keliveController extends Base{
    
    use Trait_Layout;
    
    public $you1ke;
    
    public function init(){
        $this->initAdmin();
        $this->you1ke = new You1keLiveModel();
    }
    
    public function indexAction(){
        
        if($this->getRequest()->isPost()){
            $ctime = $this->getRequest()->getPost('start_time');
            $etime = $this->getRequest()->getPost('end_time');
            
            if(!$ctime || !$etime){
                return FALSE;
            }
        }
        
        $rs = $this->you1ke->getCountLive($ctime,$etime);
        
        if($_POST['excel'] == 1){
            //导出excel
            header("Content-type:application/vnd.ms-excel");
            header("Content-Type:text/html; charset=gbk");
            header("Content-Disposition:attachment;filename=live.xls");
            header('Pragma:no-cache');
            header('Expires:0');
            $title = array('播放次数','播放日期');
            echo iconv('utf-8', 'gbk', implode("\t", $title)),"\n";
            foreach ($rs as $value){
                echo iconv('utf-8', 'gbk', implode("\t", $value)),"\n";
            }
            exit;
        }
        $this->assign('data', $rs);
        $this->assign('st', $ctime);
        $this->assign('et', $etime);
        $this->layout('you1ke/livelist.phtml');
    }
}

