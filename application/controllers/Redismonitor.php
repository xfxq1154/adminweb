<?php

/**
 * redis监控
 */
class RedisMonitorController extends Base {

    use Input,
        Trait_Layout,
        Trait_Pagger,
        Trait_Redis;

    private $masterRedis;


    public function init() {
        $this->initAdmin();
        $this->checkRole();

        $this->masterRedis = $this->getMasterRedis();
        //$this->slaveRedis = $this->getSlaveRedis();
    }

    public function indexAction() {

        $c = Application::app()->getConfig()->redis->master;
        $redis_host = $c->host;
        $redis_port = $c->port;

        $redis_info = $this->masterRedis->info('all');

        $used_memory_human = $redis_info['used_memory_human'];//占用内存总量
        $used_memory_peak = $redis_info['used_memory_peak_human'];//占用内存峰值
        $uptime_in_days = $redis_info['uptime_in_days'];//持续运行天数
        $connected_clients = $redis_info['connected_clients'];//已连接客户端的数量
        $keyspace_hits = $redis_info['keyspace_hits'];//查找数据库键成功的次数
        $keyspace_misses = $redis_info['keyspace_misses'];//查找数据库键失败的次数
        $db0 = $redis_info['db0'];//db0:keys=5370,expires=1643,avg_ttl=45057331
        $db1 = isset($redis_info['db1']) ? $redis_info['db1'] : '' ;//db1
        $db2 = isset($redis_info['db2']) ? $redis_info['db2'] : '' ;//db2
        $db3 = isset($redis_info['db3']) ? $redis_info['db3'] : '' ;//db3

        $list_store_main = (int)$this->masterRedis->llen('store:task:main');
        $list_store_logs = (int)$this->masterRedis->llen('store:task:logs');
        $list_store_sh5logs = (int)$this->masterRedis->llen('store:task:sh5logs');
        $list_store_ready_main = (int)$this->masterRedis->llen('store:task:ready:main');
        $list_store_dealy_async = (int)$this->masterRedis->zCard('store:task:delay:async');

        $keyspace_hits_percentage = round(($keyspace_hits/($keyspace_hits+$keyspace_misses)) * 100, 2); //命中率

        $this->assign('redis_host', $redis_host);
        $this->assign('redis_port', $redis_port);
        $this->assign('redis_info', $redis_info);
        $this->assign('used_memory_human', $used_memory_human);
        $this->assign('used_memory_peak', $used_memory_peak);
        $this->assign('uptime_in_days', $uptime_in_days);
        $this->assign('connected_clients', $connected_clients);
        $this->assign('keyspace_hits_percentage', $keyspace_hits_percentage);
        $this->assign('db0', $db0);
        $this->assign('db1', $db1);
        $this->assign('db2', $db2);
        $this->assign('db3', $db3);

        $this->assign('list_store_main', $list_store_main);
        $this->assign('list_store_logs', $list_store_logs);
        $this->assign('list_store_sh5logs', $list_store_sh5logs);
        $this->assign('list_store_ready_main', $list_store_ready_main);
        $this->assign('list_store_dealy_async', $list_store_dealy_async);

        $this->layout('platform/redismonitor.phtml');
    }

    public function zsetAction(){
        $name = $this->input_get_param('name');
        $started = $this->input_get_param('started', time());
        $ended = $this->input_get_param('ended', strtotime('+1 days'));

        $store_dealy_async_jobs = $this->masterRedis->zRangeByScore('store:task:delay:async', $started, $ended);

        $jobs = [];
        foreach ($store_dealy_async_jobs as $jobid){
            $time = $this->masterRedis->zScore('store:task:delay:async', $jobid);
            $jobs[] = [
                'time'    => date('Y-m-d H:i:s', $time),
                'body'    => $this->masterRedis->get("store:job:$jobid"),
                'remain'  => $time - time()
            ];
        }

        $this->assign('jobs', $jobs);
        $this->layout('platform/zsetmonitor.phtml');
    }
}
