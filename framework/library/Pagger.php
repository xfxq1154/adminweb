<?php

/**
 * 分页类
 * @author ellis
 * @package masamaso
 * @subpackage util
 * @date 2010-4-20
 */
class Pagger {

    /**
     * 总记录数
     * @var int
     * @access private
     */
    private $rowCount;

    /**
     * 每页记录数
     * @var int
     * @access private
     */
    private $pageSize;

    /**
     * 当前页
     * @var int
     * @access private
     */
    private $currentPage;

    /**
     * 显示左右页码跨度
     * @var int
     * @access private
     */
    private $pageRange;
    
    /**
     * url模板
     * @var string 
     */
    private $urlTemplate = '';

    /**
     * @param int $currentPage 当前页码
     * @param int $rowCount 总记录数
     * @param int $pageSize 每页记录数
     * @param int $pageRange 左右页码跨度
     */
    public function __construct($currentPage, $rowCount, $pageSize = null, $pageRange = 6) {


        if ($pageSize == null) {
            $pageSize = 15;
        }

        $this->pageSize = $pageSize;

        $this->pageRange = $pageRange;
        $this->rowCount = $rowCount;

        $this->currentPage = $currentPage == 0 ? 1 : $currentPage;
    }

    /**
     * 获得总页数
     * @return int
     */
    public function getPageCount() {
        return ceil($this->rowCount / $this->pageSize);
    }

    /**
     * 当前页是否是第一页
     * @return boolean
     */
    public function isFisrt() {
        return $this->currentPage <= 1;
    }

    /**
     * 当前页是否是最后一页
     * @return boolean
     */
    public function isLast() {
        return $this->currentPage >= $this->getPageCount();
    }

    /**
     * 获得前一页
     * @return int
     */
    public function getPrevous() {
        if ($this->isFisrt())
            return $this->getFirst();
        return $this->currentPage - 1;
    }

    /**
     * 获得后一页
     * @return int
     */
    public function getNext() {
        if ($this->isLast())
            return $this->getLast();
        return $this->currentPage + 1;
    }

    /**
     * 获得最后一页
     * @return int
     */
    public function getLast() {
        return $this->getPageCount();
    }

    /**
     * 获得第一页
     * @return int
     */
    public function getFirst() {
        return 1;
    }

    public function setBaseUrl($baseUrl) {
        $this->baseUrl = $baseUrl;
    }

 

    /**
     * 获得当前页开始记录
     * @return int
     */
    public function getRowStart() {
        return ($this->currentPage - 1) * $this->pageSize;
    }

    /**
     * 获得当前页结束记录
     * @return int
     */
    public function getRowEnd() {
        return $this->currentPage * $this->pageSize;
    }

    /**
     * 获得每页记录数
     * @return int
     */
    public function getPageSize() {
        return $this->pageSize;
    }

    /**
     * 获得当前页
     * @return int
     */
    public function getCurrentPage() {
        return $this->currentPage;
    }

    /**
     * 设置当前页码
     * @param int $p
     */
    public function SetCurrentPage($p) {
        $this->currentPage = $p;
    }

    /**
     * 获得总记录数
     * @return number
     */
    public function getTotalRecord() {
        return $this->rowCount;
    }

    /**
     * 获得limit
     * @return string
     */
    public function getLimit() {
        return $this->getRowStart() . ',' . $this->getPageSize();
    }

 

    /**
     * 根据设置的url模板,获得url
     * @param int $pageNo
     * @return string url
     */
    public function getPageUrl($pageNo) {

        return str_replace('{p}', $pageNo, $this->urlTemplate);
    }

    /**
     * 获得开始页
     * @return int
     */
    public function getPageStart() {
        return ($this->pageRange >= $this->currentPage) ? 1 : ($this->currentPage - $this->pageRange);
    }

    /**
     * 获得结束页
     * @return int
     */
    public function getPageEnd() {
        return (($this->currentPage + $this->pageRange) > $this->getPageCount()) ? $this->getPageCount() : ($this->currentPage + $this->pageRange);
    }

    /**
     * url模板 页面用变量{p}代替 
     * @example  
     *  '/list/p/{p}'
     * @param type $urlTeamplate
     */
    public function setUrlTemplate($urlTeamplate) {
        $this->urlTemplate = $urlTeamplate;
    }

}

?>