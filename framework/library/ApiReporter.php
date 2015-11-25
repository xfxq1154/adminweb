<?php
/**
 * api 报告类,用于输出api 报告
 * @author ellis
 */
class ApiReporter {

    private $classes = array();

    /**
     * 注册类
     * @param type $classFullName 类名 
     * @param type $className    显示名
     * @param type $categoryName    分组名
     */
    public function registerClass($classFullName, $className, $categoryName = 'default') {
        $this->classes[$categoryName][$className] = new ReflectionClass($classFullName);
    }

    /**
     * 注册实体类
     * @param type $classFullName 类名 
     * @param type $className    显示名
     */
    public function registerType($classFullName, $className) {
        $this->registerClass($classFullName, $className, 'entity');
    }

    /**
     * 获得报告数据
     */
    public function getReporterData() {
        return $this->classes;
    }

}
