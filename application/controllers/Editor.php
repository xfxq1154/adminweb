<?php

/**
 * @name EditorController
 * @desc 编辑器上传文件
 */
Class EditorController extends Base {
    
    
    
    
    
    /**
     * 入口文件
     */
    public function init(){
        
    }
    
    public  function indexAction(){
        
        
        $CONFIG = json_decode(preg_replace("/\/\*[\s\S]+?\*\//", "", file_get_contents(ROOT_PATH . "/application/library/config.json")), true);
        
        $action = $this->getRequest()->get('action');

        switch ($action) {
            case 'config':
                $result = json_encode($CONFIG);
                break;
            /* 上传图片 */
            case 'uploadimage':
                $result = $this->uploadfile();
                break;
        }
        
        
        /* 输出结果 */
        if (isset($_GET["callback"])) {
            if (preg_match("/^[\w_]+$/", $_GET["callback"])) {
                echo htmlspecialchars($_GET["callback"]) . '(' . $result . ')';
            } else {
                echo json_encode(array(
                    'state'=> 'callback参数不合法'
                ));
            }
        } else {
            echo $result;
        }
        exit;
    }
    
    
    
    public function uploadfile(){
        $user = $_SESSION['a_user'];
        $files = $this->getRequest()->getFiles('upfile');
        $params = [
            'uid' => $user['id'],
            'filedata' => '@' . $files['tmp_name'],
            'type' => $files['type'],
            'name' => $files['name']
        ];
        // 上传接口
        $url = API_SERVER_IMGURL . '/attachment/images/cate/audio/type/text';
        $remoteRst = Curl::request($url, $params, 'post');
        $params['size'] = $files['size'];
        if ($remoteRst && is_array($remoteRst) && 'ok' == $remoteRst['status']) {
            $return =  ['info' => 'SUCCESS', 'status' => 1, 'data' => [
                    'savepath' => $remoteRst['data']['url']['url'],'path' => $remoteRst['data']['url']['path'],'host'=>IMG_HOST]];
        } else {
            $return =  ['info' => '上传失败', 'status' => 0, 'path' => ''];
        }
        //http://img.dev.com/audio/index/u/3680359121/t/cover/n/big_2015102710025532716.jpg
        return json_encode(array(
            "state" => $return['info'],
            "url" => IMG_HOST . '/' .$return['data']['path'],
            "title" => $params['name'],
            "original" => $params['name'],
            "type" => strtolower(strrchr($params['name'], '.')),
            "size" => $params['size']
        ));
    }
    
}
