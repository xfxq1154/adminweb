<?php
/**
 * Description of Benchmark
 * @author ellis
 */
class Benchmark {

    /**
     * 记录时间标记
     */
    public static function mark() {
        static $time = 0;

        if ($time == 0) {
            $time = microtime(true);
            echo "beanchmark report: start at " . date('H:i:s') . PHP_EOL;
        } else {

            $delta = microtime(true) - $time;
            $mins = floor($delta / 60);

            $secs = $delta - $mins * 60;

            $hours = floor($mins / 60);

            $mins = $mins - $hours * 60;

            echo "beanchmark report:  " . $hours . "h" . $mins . 'm' . $secs . 's' . PHP_EOL;
        }
    }

}
