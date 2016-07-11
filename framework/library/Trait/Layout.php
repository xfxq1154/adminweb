<?php
/**
 * 布局的Trait
 * @author ellis
 */
trait Trait_Layout {
    
    var $js,$css;
    
    
    /**
     * 启用布局功能
     * @param type $viewName
     * @param type $layout
     */
    
    public function layout($viewName='', $layout = 'layout', $exit = true) {
        $module = $this->getRequest()->module;
        $view_path = ($module != 'Index') ? "modules/$module/views" : "views";
        $this->getView()->setScriptPath(ROOT_PATH."/application/$view_path");
        $this->getView()->assign("js", $this->formatStatic('js'));
        $this->getView()->assign("css", $this->formatStatic('css'));
        $this->getView()->assign('page', $this->getView()->render($viewName));

        $this->getView()->setScriptPath(ROOT_PATH."/application/views");
        $this->getView()->display('layout/' . $layout . '.phtml');
        if($exit === true) exit;
    }
    
    
    
    
    private function formatStatic($t = 'js'){
        if($t == 'js'){
            $static = $this->js;
        }else{
            $static = $this->css;
        }
        $_aj = json_decode($this->_view->get($t), true);
        $_js = array();
        if(is_array($static) && count($static) > 0){
            foreach ($static as $_j){
                $_js[] = Asset::getResource($_j[0], $_j[1]);
            }
        }
        if(is_array($_aj)){
            $_js = array_merge($_aj,$_js);
        }
        
        return implode( " " , $_js);
    }
    
    private  function setJs($file, $path){
        $key = md5($file . $path);
        $this->js[$key] = array($file, $path);
    }
    private  function setCss($file, $path){
        $key = md5($file . $path);
        $this->css[$key] = array($file, $path);
    }

}
