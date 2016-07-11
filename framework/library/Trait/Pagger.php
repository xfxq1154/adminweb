<?php
/**
 * Description of Pagger
 * @author ellis
 */
trait Trait_Pagger {

    var $pagger;

    /**
     * 为页面设置一个分页
     * @param int $currentPage
     * @param int $total
     * @param string $urlTemplate url模板 页面变量用{p}代替 如 /list/p-{p}
     * @param int $pageSize
     * @param int $pageRange
     * @param string $template
     * @return type
     */
    public function renderPagger($currentPage, $total, $urlTemplate, $pageSize = 20, $pageRange = 6,$template = 'common/pagger.phtml') {

        $paggerObj = new Pagger($currentPage, $total, $pageSize, $pageRange);

        $paggerObj->setUrlTemplate($urlTemplate);

        $this->getView()->setScriptPath(ROOT_PATH.'/application/views');
        $pagger = $this->getView()->render($template, array('paggerObj' => $paggerObj));

        $this->pagger = $pagger;
        $this->getView()->assign('pagger', $pagger);
        return $paggerObj;
    }
    
    public function getPagger() {
        return $this->pagger;
    }

}
