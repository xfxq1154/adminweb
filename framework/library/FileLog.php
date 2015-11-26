<?php
/**
 * @author ellis
 */
class FileLog {

    use Trait_Singleton;

    private $config = array();

    /**
     * 
     * @param type $name
     * @return PDO
     */
    private function __construct() {

        if (isset(Application::app()->getConfig()->log)) {
            $c = Application::app()->getConfig()->log->filelog;

            if (empty($c)) {
                return null;
            }

            $this->config = $c;

            $savePath = $c->savepath;

            if (!is_dir($savePath)) {
                mkdir($savePath, 0777, true);
            }
        }
    }

    /**
     * 添加日志
     * @param type $content
     * @param type $type
     */
    public function addLog($content, $type = 'sql') {
        $date = date('Y-m-d');

        $filename = $this->config->savepath . '/' . $type . '_' . $date . '.log';
        $time = date('Y-m-d H:i:s');
        $content = "[$time] " . $content . PHP_EOL;

        file_put_contents($filename, $content, FILE_APPEND);
    }

}
