<?php

/**
 * @name UserController
 * @desc 后台用户控制器
 * @show
 * @author hph
 */
class ScriptpaymentController extends Base {
    use Trait_DB,Trait_Redis;
    
    const PAGE_SIZE = 100; //每次取出50条
    public $dbPassport;
    public $dbBusiness;
    
    public function init() {
        $this->dbPassport = $this->getDb('passport_master');
        $this->dbBusiness = $this->getDb('business_master');        
    }
    
    public function updateAction() {
        $i = 0;
        do {
            $offset = $i*self::PAGE_SIZE;
            $payment = $this->_getPayment($offset, self::PAGE_SIZE);
            if ($payment) {
                foreach ($payment as $v) {
                    $res = $this->_update($v['o_buyer_id'], $v['o_payment']);
                    echo '>';
                }
            }
            $i++;
            echo $i.' ';
        } while(!empty($payment));
        
        exit;
    }


    public function _getPayment($offset, $limit) {
        $sql = "select *  FROM `order` WHERE (`o_iid`='5472915' or `o_iid`='5471613' or `o_iid`='5472768' or `o_iid`='5472960') and (`o_status` = 'WAIT_SELLER_SEND_GOODS' or `o_status` = 'WAIT_BUYER_CONFIRM_GOODS' or `o_status` = 'TRADE_BUYER_SIGNED') ORDER BY `o_id` LIMIT ".$offset.",".$limit;
        //$sql = "SELECT `o_buyer_id`,`o_payment` FROM `order` WHERE `o_sku`='21874323' or `o_sku`='21780706' ORDER BY `o_id` LIMIT ".$offset.",".$limit;
        $stmt = $this->dbBusiness->prepare($sql);
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $data;
    }
    
    public function _update($kdt_uid, $payment) {
        $sql = 'UPDATE `vip` SET `v_payment` = :payment WHERE `v_kdt_uid` = :id ';
        $stmt = $this->dbPassport->prepare($sql);
        $res = $stmt->execute(array(':id' => $kdt_uid,':payment' => $payment));
        return $res;
    }
    
}
