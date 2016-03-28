<?php
/**
 * @author why
 * @desc 电子发票
 */
class InvoiceModel{
    
    use Trait_DB;
    
    public $tableName = 'invoice';
    public $dbMaster;
    public $dbSlave;
    
    const PDF_UPLOAD = 'oss/upload';
    const GET_URLSIGN = 'oss/urlsign';

    public function __construct() {
        $this->dbMaster = $this->getMasterDb('storecp_invoice');
        $this->dbSlave = $this->getSlaveDb('storecp_invoice');
    }
    
    /**
     * @desc 列表
     * @param type $page_no
     * @param type $page_size
     * @param type $use_hax_next
     * @param type $mobile
     * @param type $order_id
     * @param type $status
     * @return array()
     */
    public function getList($page_no, $page_size, $use_hax_next, $mobile = '', $order_id = '', $status = ''){
        $where = '1';
        
        if($mobile){
            $where .= ' AND `buyer_phone` = :mobile';
            $pdo_params[':mobile'] = $mobile;
        }
        if($order_id){
            $where .= ' AND `order_id` = :order_id ';
            $pdo_params[':order_id'] = $order_id;
        }
        if($status){
            if($status == 2){
                $where .= ' AND `state` = :status OR `state` = :status2';
                $pdo_params[':status'] = $status;
                $pdo_params[':status2'] = 4;
            } else {
                $where .= ' AND `state` = :status ';
                $pdo_params[':status'] = $status;
            }
        }
        
        $start = ($page_no - 1) * $page_size;
        
        try {
            $sql = 'SELECT * FROM `'.$this->tableName. '` WHERE '. $where .' ORDER BY id DESC LIMIT '. $start .','.$page_size ;
            $stmt = $this->dbSlave->prepare($sql);
            $stmt->execute($pdo_params);
            $data['data'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $data['total_nums'] = $this->getCount($where,$pdo_params);
        } catch (Exception $ex) {
            echo $ex->getMessage();
        }
        
        if ($use_hax_next) {
            $data['has_next'] = count($data['data']) < $page_size ? 0 : 1;
        }
        return $data;
    }
    
    /**
     *@desc 添加开票信息
     */
    public function insert($params){
        try {
            $sql = ' INSERT INTO '. $this->tableName . ' SET ' . $this->makeSet($params);
            $stmt = $this->dbMaster->prepare($sql);
            $stmt->execute();
            return $this->dbMaster->lastInsertId();
        } catch (Exception $exc) {
            echo $exc->getMessage();
        }
    }
    
    /**
     *@desc 获取总数
     */
    public function getCount($where, $pdo_params) {
        try {
            $sql = "SELECT count(*) as num FROM " . $this->tableName . ' WHERE '. $where ;
            $stmt = $this->dbSlave->prepare($sql);
            $stmt->execute($pdo_params);
            $data = $stmt->fetch(PDO::FETCH_ASSOC);
            return $data['num'] ? : 0;
        } catch (Exception $ex) {
            Output::jsonStr(Error::ERROR_DB_EXCEPTION, $ex->getMessage());
        }
    }
    
    /**
     * @desc 获取详情
     */
    public function getInfo($id){
        $where = ' `id` =  :id';
        $pdo_params[':id'] = $id;
        try {
            $sql = ' SELECT * FROM ' . $this->tableName. ' WHERE ' .$where;
            $stmt = $this->dbSlave->prepare($sql);
            $stmt->execute($pdo_params);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $ex) {
            echo $ex->getMessage();
        }
    }
    
    /**
     * @desc 更新开票信息
     */
    public function update($id, $params){
        if(!$id || !$params){
            return FALSE;
        }
        $f = '';
        $array = array(':id' => $id);
        foreach ($params as $key => $value) {
            //不传递则跳过
            if ($value === null) {
                continue;
            }
            $f .= ",`" . $key . "` = :$key";
            $array[':' . $key] = $value;
        }
        $sql = "UPDATE `" . $this->tableName . "` SET " . substr($f, 1) . " WHERE `id` = :id LIMIT 1";
        try {
            $stmt = $this->dbMaster->prepare($sql);
            return $stmt->execute($array);
        } catch (Exception $ex) {
            echo $ex->getMessage();
        }
    }
    
    /**
     * @desc 修改多个税率
     */
    public function updateSl($ids, $fpsl){
        if( !$ids || !$fpsl){
            return FALSE;
        }
        
        try {
            $sql = ' UPDATE `'. $this->tableName. "` SET `tax_rate` = :fpsl WHERE `id` IN ($ids) " ;
            $stmt = $this->dbMaster->prepare($sql);
            $stmt->execute(array(':fpsl' => $fpsl));
            return 1;
        } catch (Exception $ex) {
            echo $ex->getMessage();
        }
    }
    
    /**
     * getALL
     * @desc 查询状态等于未发短信的发票信息
     */
    public function getAll() {
        $where = ' `state` = :state ';
        $pdo_params[':state'] = 2;
        $data = array();
        $sql = "SELECT * FROM `".$this->tableName."` WHERE $where order by `id` desc";
        try{
            $stmt = $this->dbSlave->prepare($sql);
            $stmt->execute($pdo_params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }catch (Exception $ex) {
            echo $ex;
            return false;
        }

        return $data;
    }
    
    /**
     * @desc 上传发票到oss
     */
    public function ossUpload($file){
        if(!$file){
            return FALSE;
        }
        $params['file_content'] = $file;
        $result = Imgapi::request(self::PDF_UPLOAD, $params, 'POST');
        return $result;
    }
    
    /**
     * @desc 获取发票链接
     */
    public function getInvoice($object){
        if(!$object){
            return FALSE;
        }
        $params['object'] = $object;
        $params['timeout'] = 3600;
        $result = Imgapi::request(self::GET_URLSIGN, $params, 'POST');
        return $result;
    }
    
}

