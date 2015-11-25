<?php
/**
 * 取得资源
 * @author ellis
 */
class Asset {
    /**
     * 取得资源
     * @param string $filename 资源名
     * @param string 项目名
     */
    public static function getResource($filename, $project = "") { 
        
        $type = pathinfo($filename, PATHINFO_EXTENSION);
        $configPrefix = $type;
        
        if ( in_array($type, array('jpg', 'png', 'gif', 'ico')) ) {
            $type = 'image';
            $configPrefix = 'img';
        }elseif ( in_array($type, array('css')) ) {
            $type = 'css';
            $configPrefix = 'css';
        }elseif ( in_array($type, array('js')) ) {
            $type = 'js';
            $configPrefix = 'js';
        }else {
            exit("非法资源引用！ " . $project . " - " . $filename);
        }
        
        $baseUrl = Application::app()->getConfig()->asset->$configPrefix->url;
        $basePath = Application::app()->getConfig()->asset->$configPrefix->path;

        if (!empty($project)) {
            $baseUrl = str_replace('/' . $type, '/' . $project . '/' . $type, $baseUrl);
            $basePath = str_replace('/' . $type, '/' . $project . '/' . $type, $basePath);
        }

        $url = $baseUrl . '/' . $filename;

        $pathname = $basePath . '/' . $filename;
        if (file_exists($pathname)) {
            $filemtime = filemtime($pathname);
            if ($filemtime) {
                $url .= '?' . $filemtime;
            }
        }
        
        if ($type == 'css') {
            return '<link rel="stylesheet" type="text/css" href="' . $url . '" meta="screen"> ';
        } elseif ($type == 'js') {
            return '<script type="text/javascript" src="' . $url . '"></script>';
        }elseif ($type == 'image') {
            return $url;
        }        
        return '';
    }

}
