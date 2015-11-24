<?php

/**
 * @name WechatModules
 * @author why
 * @desc 微信菜单
 */
class AdminWechatModules{
    
    use Trait_DB;
    use Trait_Redis;
    
    public $dbMaster, $dbSlave; //主从数据库 配置
    public $tableName = '``'; //数据表
}

