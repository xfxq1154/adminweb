<?php
use Yaf\Exception;

/**
 * Created by PhpStorm.
 * User: momoko
 * Date: 15/11/5
 * Time: 11:27
 */
class AudioUsersModel
{
    use Trait_DB, Trait_Api;

    protected $audioDB;
    protected $tableName = ' a_user ';
    protected $UCApi;

    public function __construct()
    {
        // 音频项目DB
        $this->audioDB = $this->getDb('audio');
        $this->UCApi = $this->getApi('ucapi');
    }

    /**
     * 获取audio用户信息
     * @param array $condition array(
            'u_uid = :uid',
     *      'bind' => array(
                ':uid' => 123
     *      ),
     *      'order by' => 'u_uid desc',
     *      'limit' => 1
     * )
     * @return array|bool
     */
    public function getAudioUsers(array $condition = array())
    {

        if (!is_array($condition)) {
            return false;
        }

        $sql = 'SELECT * FROM ' . $this->tableName;
        $bindArray = array();

        if (isset($condition[0])  && $condition[0]) {
            $where = $condition[0];
            unset($condition[0]);
            $sql .= ' WHERE 1 ' . $where . ' ';

            try {

                if (!is_array($condition['bind']) || !$condition['bind']) {
                    throw new InvalidArgumentException('缺失 bind 元素');
                }

                foreach ($condition['bind'] as $bind => $value) {
                    $bindArray[$bind] = $value;
                }

                unset($condition['bind']);
            } catch (InvalidArgumentException $e) {
                Tools::error($e);
            }
        }

        if ($condition) {

            foreach ($condition as $key => $value) {

                $sql .= ' ' . strtoupper($key) . ' ' . $value;
            }
        }

        try {
            $stmt = $this->audioDB->prepare($sql);
            $stmt->execute($bindArray);

            $tmp = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $result = array();

            if ($tmp) {
                foreach ($tmp as $index => $value) {

                    $result[$index] = CustomArray::removeKeyPrefix($value, 'u_');
                }

                return $result;
            }

            return false;

        } catch (PDOException $e) {
            Tools::error($e);
        }
    }

    /**
     * 获取ucpai用户信息
     * @param array $ids
     * @return array|bool
     */
    public function getUCApiUserInfo(array $ids = array()) {

        if (!$ids || !is_array($ids)) {
            return false;
        }

        $buildQuery = [
            'sourceid' => API_UCAPI_KEY,    // 请求UCAPI 必传参数
            'timestamp' => time(),          // 请求UCAPI 必传参数
        ];

        if (count($ids) == 1) {
            $buildQuery['uid'] = implode(',', $ids);
            $path = '/user/getinfo?';
        } else {
            $buildQuery['uids'] = implode(',', $ids);
            $path = '/user/getinfo_batch?';
        }

        $buildQuery = http_build_query($buildQuery);
        $request = $path . $buildQuery;

        $ret = $this->UCApi->get($request);
        $ret = json_decode($ret, true);

        try {

            if ($ret['status_code'] != 0) {
                throw new LogicException($ret['status_msg']);
            }

            return (array) $ret['data'];

        } catch (LogicException $e) {
            Tools::error($e);
        }
    }

    /**
     * 查询用户信息
     * @param null $mobile
     * @return bool
     */
    public function getUserByMobile($mobile = null)
    {

        if (!$mobile) {
            return false;
        }

        $postData = array(
            'sourceid' => API_UCAPI_KEY,
            'timestamp' => time(),
            'phone' => $mobile
        );

        $postData = http_build_query($postData);

        $result = $this->UCApi->get('/user/search?' . $postData);
        $result = json_decode($result, true);

        try {
            if ($result['status_code'] != 0) {
                throw new LogicException($result['status_msg']);
            }

            return (array) $result['data'][0];
        } catch (LogicException $e) {
            Tools::error($e);
        }
    }
    
    public function getUserList($page = 1, $size = 20,$where = '') {
        $p = $page > 0 ? $page : 1;
        $limit = ($p - 1) * $size . ',' . $size;

        try {
            $sql = 'SELECT * FROM ' . $this->tableName . ' where 1  '.$where.' ORDER BY u_id asc  limit ' . $limit;
            $stmt = $this->audioDB->prepare($sql);
            $stmt->execute();
            
            $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $stmt->closeCursor();
            $return = [];

            if ($rs) {
                foreach ($rs as $key => $val) {
                    $return[$key] = CustomArray::removekeyPrefix($val, 'u_');
                }
            }

            return $return;
        } catch (PDOException $e) {
            die($e->getMessage());
        }
    }
    
    /**
     * update audio_user info
     * @param type $data
     * @return boolean
     */
    public function update($data) {

        if (!$data || !isset($data['uid'])) {
            return false;
        }

        $uid = $data['uid'];
        unset($data['uid']);
        $data = CustomArray::addKeyPrefix($data, 'u_');

        try {
            $sql = "UPDATE " . $this->tableName . ' SET ' . $this->makeSet($data) .
                    ' WHERE u_uid = :uid   LIMIT 1';
            $stmt = $this->audioDB->prepare($sql);
            $stmt->execute([':uid' => $uid]);
            $res = $stmt->rowCount();
            $stmt->closeCursor();
            return $res;
        } catch (PDOException $e) {
            die($e->getMessage());
        }
    }
    
    public function getCount($where='')
    {
            $sql = ' select count(*) as num from  '.$this->tableName.' where 1 '.$where;
            $stmt = $this->audioDB->prepare($sql);
            $stmt->execute();
            $rs = $stmt->fetch(PDO::FETCH_ASSOC);
            $stmt->closeCursor();
            return $rs['num'];
    }
    
    
}