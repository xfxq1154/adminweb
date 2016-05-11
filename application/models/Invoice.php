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
    public $err_order;
    
    const PDF_UPLOAD = 'oss/upload';
    const GET_URLSIGN = 'oss/urlsign';
    const DWZ_HOST = 'http://apis.baidu.com/3023/shorturl/shorten';

    public function __construct() {
        $this->dbMaster = $this->getMasterDb('storecp_invoice');
        $this->dbSlave = $this->getSlaveDb('storecp_invoice');
    }

    /**
     * @param $page_no
     * @param $page_size
     * @param $use_hax_next
     * @param string $mobile
     * @param string $order_id
     * @param string $status
     * @param string $group
     * @param int $sType
     * @param string $time
     * @return mixed
     */
    public function getList($page_no, $page_size, $use_hax_next, $mobile = '', $order_id = '', $status = '', $group = '', $sType = 1, $time){
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

        if($group){
            $where .= ' AND `batch` = :group';
            $pdo_params[':group'] = $group;
        }

        if($sType){
            $where .= ' AND `sku_type` = :sku_type';
            $pdo_params[':sku_type'] = $sType;
        }

        if($time){
            $where .= " AND `update_time` LIKE '%$time%'";
        }
        
        $start = ($page_no - 1) * $page_size;
        
        try {
            $sql = 'SELECT * FROM `'.$this->tableName. '` WHERE '. $where .' ORDER BY id ASC LIMIT '. $start .','.$page_size ;
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
            $this->err_order .= '<tr><td>'.$params['order_id'].'</td><td>订单重复</td>></tr>';
            return false;
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
     * getALL
     * @desc 查询状态等于未发短信的发票信息
     */
    public function getAll() {
        $where = ' `state` = :state ';
        $pdo_params[':state'] = 2;
        try{
            $sql = "SELECT * FROM `".$this->tableName."` WHERE $where order by `id` desc";
            $stmt = $this->dbSlave->prepare($sql);
            $stmt->execute($pdo_params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }catch (Exception $ex) {
            echo $ex;
            return false;
        }
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
     *  @desc 获取发票链接
     * @param $object
     * @return array|bool
     */
    public function getInvoice($object){
        if(!$object){
            return FALSE;
        }
        $params['object'] = $object;
        $params['timeout'] = 2592000;  //设置失效时间是30天
        $result = Imgapi::request(self::GET_URLSIGN, $params, 'POST');
        return $result;
    }

    /**
     * @desc 调用百度dwz
     * @param $url
     * @return array
     */
    public function dwz($url){
        $params['url_long'] = $url;
        $headers = array('apikey:0d5cbc4c804d5850b913ff594aa0e6d4'); //此apikey 为个人调用，修改地址为http://apistore.baidu.com/apiworks/servicedetail/1466.html
        $result = Curl::request(self::DWZ_HOST, $params, 'GET', TRUE, $headers);
        return $result;
    }

    /**
     * 获取待开发票的订单
     */
    public function getPendingInvoice(){
        $where = '`state` = :state ' ;
        $where .= 'AND `sku_type` = :type ' ;
        $pdo_params[':state'] = 5;
        $pdo_params[':type'] = 0;
        try{
            $sql = 'SELECT * FROM '.$this->tableName.' WHERE '.$where. 'LIMIT 300';
            $stmt = $this->dbSlave->prepare($sql);
            $stmt->execute($pdo_params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }catch(PDOException $ex){
            echo $ex->getMessage();
        }
    }

    /**
     * @desc 获取批次列表
     * @return array
     */
    public function getBatchGroup(){
        try{
            $sql = " SELECT batch FROM ".$this->tableName. " WHERE batch != '' GROUP BY batch ORDER BY batch DESC";
            $stmt = $this->dbSlave->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }catch (PDOException $ex){
            echo $ex->getMessage();
        }
    }

    /**
     * @desc 获取组合订单
     */
    public function getCkdInvoice(){
        $where = ' `sku_type` = :type ';
        $where .= ' AND `state` = :state ';
        $pdo_params[':state'] = 5;
        $pdo_params[':type'] = 1;
        try{
            $sql = ' SELECT * FROM '.$this->tableName.' WHERE '.$where.' LIMIT 300';
            $stmt = $this->dbMaster->prepare($sql);
            $stmt->execute($pdo_params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }catch (PDOException $ex){
            echo $ex->getMessage();
            return false;
        }
    }

    /**
     * @param $time
     * @return array|bool
     * @desc 查询开票成功并且发送短信的的订单
     */
    public function getSuccessInvoice($time)
    {
        try{
            $sql = 'SELECT * FROM '.$this->tableName." WHERE `state` = :state AND update_time LIKE '%$time%'";
            $stmt = $this->dbMaster->prepare($sql);
            $stmt->execute([':state' => 4]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }catch(PDOException $ex){
            echo $ex->getMessage();
            return false;
        }
    }

    /**
     * @return array|bool
     * @desc 临时方法,用于统计历史数据
     */
    public function getInvoiceByState(){
        try{
            $sql = 'SELECT id, order_id, invoice_type FROM '.$this->tableName." WHERE `state` = :state ";
            $stmt = $this->dbMaster->prepare($sql);
            $stmt->execute([':state' => 4]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }catch(PDOException $ex){
            echo $ex->getMessage();
            return false;
        }
    }

    /**
     * @param $id
     * @return bool
     * @desc 删除订单
     */
    public function delete($id){
        try{
            $sql = ' DELETE FROM '.$this->tableName.' WHERE `id` = :id LIMIT 1';
            $stmt = $this->dbMaster->prepare($sql);
            return $stmt->execute([':id' => $id]);
        }catch (PDOException $ex){
            echo $ex->getMessage();
            return false;
        }
    }

    /**
     * @return array
     * @desc 获取错误
     */
    public function getError(){
        if(isset($this->err_order)){
            return $this->err_order;
        }
        return array();
    }

    /**
     * @return bool
     * explain: 获取历史遗留数据
     */
    public function dirtyData(){
        try{
            $sql = " SELECT * FROM `dirty_data` WHERE `state` != 2 ";
            $stmt = $this->dbMaster->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }catch (PDOException $ex){
            echo $ex->getMessage();
            return false;
        }
    }

    /**
     * @param $id
     * @param $params
     * @return bool
     * @explain 修改历史数据信息
     */
    public function updateDirtyData($id, $params)
    {
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
        $sql = "UPDATE `dirty_data` SET " . substr($f, 1) . " WHERE `id` = :id LIMIT 1";
        try {
            $stmt = $this->dbMaster->prepare($sql);
            return $stmt->execute($array);
        } catch (Exception $ex) {
            echo $ex->getMessage();
        }
    }
}

