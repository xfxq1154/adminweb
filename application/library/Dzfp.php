<?php
/**
 * 电子发票操作类
 * @author why yanbo
 */
class Dzfp {
    
    const WSDL = DZFP_HOST;
    const APPID = 'DZFP';
    const REQUEST_CODE = TAXPAYER_NUMBER;    #企业纳税人识别号
    const ENVIRONMENT = ENVIRONMENT;    #运行环境
    
    const FPHXZ = 0; //发票行性质 0正常行 1折扣行 2被折扣行

    private $err_msg;
    private $content;
    
    public function fpkj($order, $detail) {
        if(!$order || !$detail){
            $this->err_msg = '必传参数缺失';
            return FALSE;
        }
        if($order['type'] == 1){
            if(!$order['yfp_dm'] || !$order['yfp_hm']){
                $this->err_msg = '原发票号码或代码不能为空';
                return FALSE;
            }
        }
        //*必填参数
        $kjxx_data = array(
            'FPQQLSH'       => $order['invoice_no'],   //*发票请求流水号
            'KPLX'          => $order['type'],   //*开票类型 0 蓝字发票 1红字发票
            'XSF_NSRSBH'    => self::REQUEST_CODE,   //*销售方纳税人识别号
            'XSF_MC'        => $order['xsf_mc'],   //*销售方名称
            'XSF_DZDH'      => $order['xsf_dzdh'],   //*销售方地址电话
            'XSF_YHZH'      => '',   //销售方银行账号
            'GMF_NSRSBH'    => '',   //购买方纳税人识别号
            'GMF_MC'        => $order['invoice_title'],   //*购买方名称
            'GMF_DZDH'      => '',   //购买方地址
            'GMF_YHZH'      => '',   //购买方银行账号
            'KPR'           => $order['kpr'],   //*开票人
            'SKR'           => '',   //收款人
            'FHR'           => '',   //复核人 
            'YFP_DM'        => $order['type'] == 1 ? $order['yfp_dm'] : '',   //原发票代码 红字发票时必须
            'YFP_HM'        => $order['type'] == 1 ? $order['yfp_hm'] : '',   //原发票号码 红字发票时必须
            'JSHJ'          => $order['type'] == 1 ? -$order['payment'] : $order['payment'],   //*价税合计 单位元 (2位小数)
            'HJJE'          => $order['type'] == 1 ? -$order['hjje'] : $order['hjje'],   //*合计金额 单位元 (2位小数)
            'HJSE'          => $order['type'] == 1 ? -$order['hjse'] : $order['hjse'],   //*合计税额 单位元 (2位小数)
            'BZ'            => '',   //备注
            'DDRQ'          => $order['created'],   //*订单日期 同下
            'KPRQ'          => date('Y-m-d H:i:s'),   //*开票日期 yyyymmddhh24miss
            'DDH'           => $order['tid'],   //订单号
            'XFZ_YX'        => '',   //消费者邮箱
            'XFZ_SJH'       => $order['receiver_mobile']    //*消费者手机号
        );
        
        $string = "<?xml version='1.0' encoding='utf-8'?><BUSINESS ID='REQUEST_E_FAPIAO_KJ'></BUSINESS>";
        $requestXML = simplexml_load_string($string);
        $kjxx = $requestXML->addChild('KJXX');
        
        foreach ($kjxx_data as $key => $val){
            $kjxx->addChild($key,$val);
        }
        
        $kjxxmx = $requestXML->addChild('KJXXMX');
        $kjxxmx->addAttribute('COUNT', $order['count']);
        
        //格式化订单商品详情
        $ks_info = $this->batch($detail, $order['type']);
        
        foreach ($ks_info as $value){
            $kjmx = $kjxxmx->addChild('KJMX');
            foreach ($value as $vk=>$vl){
                $kjmx->addChild($vk,$vl);
            }
        }
        $result = $this->doService('REQUEST_E_FAPIAO_KJ', $requestXML->asXML());
        if(!$result){
            return FALSE;
        }
        
        $resxml = simplexml_load_string($result);
        return (array)$resxml->RESULT;
    }

    /**
     * @param $FPQQLSH
     * @return array|bool
     */
    public function fpcx($FPQQLSH) {
        $string = "<?xml version='1.0' encoding='utf-8'?><BUSINESS ID='FPCX' comment='发票查询'></BUSINESS>";
        $requestXML = simplexml_load_string($string);
        $REQUEST_COMMON_FPCX = $requestXML->addChild('REQUEST_COMMON_FPCX');
        $REQUEST_COMMON_FPCX->addAttribute('class', 'REQUEST_COMMON_FPCX');
        $REQUEST_COMMON_FPCX->addChild('XSF_NSRSBH', self::REQUEST_CODE);
        $REQUEST_COMMON_FPCX->addChild('FPQQLSH', $FPQQLSH);
        $xmlstring = $requestXML->asXML();
        
        $result =  $this->doService('FPCX', $xmlstring);
        if(!$result){
            return FALSE;
        }
        $resxml = simplexml_load_string($result);
        if($resxml->RESPONSE_COMMON_FPCX->CODE == 0){
            $this->err_msg = $resxml->RESPONSE_COMMON_FPCX->DESC;
            return FALSE;
        }
        return (array)$resxml->RESPONSE_COMMON_FPCX;
    }
    
    public function getpdf($FP_DM, $FP_HM, $JYM) {
        if(!$FP_DM || !$FP_HM || $$JYM){
            return FALSE;
        }
        
        $string = "<?xml version='1.0' encoding='utf-8'?><business ID='GETPDF'></business>";
        $requestXML = simplexml_load_string($string);
        $REQUEST_COMMON_FPCX = $requestXML->addChild('REQUEST_COMMON_GETPDF');
        $REQUEST_COMMON_FPCX->addAttribute('class', 'REQUEST_COMMON_GETPDF');
        $REQUEST_COMMON_FPCX->addChild('FP_DM', $FP_DM);
        $REQUEST_COMMON_FPCX->addChild('FP_HM', $FP_HM);
        $REQUEST_COMMON_FPCX->addChild('JYM', $JYM);
        $xmlstring = $requestXML->asXML();
        
        $result =  $this->doService('GETPDF', $xmlstring);
        if(!$result){
            return FALSE;
        }
        $resxml = simplexml_load_string($result);
        return $resxml->RESPONSE_COMMON_GETPDF->PDF_TYPE;
    }

    /**
     * @desc 统一电子发票调用接口
     * @param $interfaceCode
     * @param $requestXML
     * @return bool
     */
    private function doService($interfaceCode, $requestXML) {
        if(!$interfaceCode || !$requestXML){
            return FALSE;
        }
        
        $encryCf = $this->encrypt($requestXML);
        $data = [
            'globalInfo' => [
                'appId' => self::APPID,
                'interfaceCode' => $interfaceCode,
                'requestCode' => self::REQUEST_CODE,
                'requestTime' => date('Y-m-d H:i:s'),
                'responseCode' => 'tunkong'],
            'returnStateInfo' => [
                'returnCode' => '', 
                'returnMessage' => '',
                ],
            'data' => [
                'content' => $encryCf['encrypt'], 
                'signature' => $encryCf['sign']
                ]
        ];
        
        $string = "<?xml version='1.0' encoding='utf-8'?><interface></interface>";
        $xml = simplexml_load_string($string);
        foreach ($data as $k1 => $r1) {
            $item = $xml->addChild($k1);
            foreach ($r1 as $key => $row) {
              $item->addChild($key, $row);
            }
        }
        $xmlstring = $xml->asXML();
        
        ini_set('default_socket_timeout', 10);
        $client = new SoapClient(self::WSDL);
        $result = $client->doService(['xml' => $xmlstring]);
        $xmlobj = simplexml_load_string($result->return);
        
        if((int)$xmlobj->returnStateInfo->returnCode == 1){
            $this->err_msg = (string)$xmlobj->returnStateInfo->returnMessage;
            return FALSE;
        }
        return $this->decrypt((string)$xmlobj->data->content, (string)$xmlobj->data->signature);
    }
    
    /**
     * foreach orderdetail
     * @return type
     */
    public function batch($data, $type){
        if(!$data){
            return FALSE;
        }
        $i=1;
        $kj_data = array();
        foreach ($data as $key => $value){
            $kj_data[$key]['FPHXZ'] = self::FPHXZ;  //*发票行性质 0正常行、1折扣行、2被折扣行
            $kj_data[$key]['HH'] = $i;  //*行号 按商品明细排序 第一行1，第二行2 一次类推
            $kj_data[$key]['XMMC'] = $value['title'];  //*项目名称
            $kj_data[$key]['GGXH'] = '';  //计量单位
            $kj_data[$key]['DW'] = ''; //规格型号
            $kj_data[$key]['XMSL'] = ''; //项目数量
            $kj_data[$key]['XMDJ'] = '';  //项目单价 小数点后六位 不含税
            $kj_data[$key]['XMJE'] = $type == 1 ? -$value['xmje'] : $value['xmje'];  //*项目金额 不含税，单位元(2位小数)
            $kj_data[$key]['SL'] = $value['sl'];  //*税率 6位小数例：1%为0.01
            $kj_data[$key]['SE'] = $type == 1 ? -$value['se'] : $value['se'];  //*税额 单位：元(2位小数)
            $kj_data[$key]['SN'] = '';  //商品SN号
            $i++;
        }
        return $kj_data;
    }
    
    public function getError() {
        return $this->err_msg;
    }

    /**
     * 进行加密，开发环境采用base64加密，生产环境采用CA加密
     * @param type $src
     * @return type
     */
    public function encrypt($src) {
        if(self::ENVIRONMENT == 'develop'){
            $encrypt = base64_encode($src);
            $sign = '';
        }else{
            $client = $this->get_ca_client();
            $sign = $client->signature($src, $this->pfxPath, $this->pfxPwd);
            $encrypt = $client->encryCfca($src, $this->cerPath);
        }
        
        return ['encrypt' => (string)$encrypt, 'sign' => (string)$sign];
    }
    
    /**
     * 进行解密，如果签名不正确返回False
     * @param type $encrypt
     * @param type $sign
     * @return boolean
     */
    public function decrypt($encrypt, $sign) {
        if(self::ENVIRONMENT == 'develop'){
            $decrypt = base64_decode($encrypt);
        }else{
            $client = $this->get_ca_client();
            $decrypt = $client->deEncryCfca($encrypt, $this->pfxPath, $this->pfxPwd);
            $verifySign = $client->verifySign($decrypt, $sign, $this->cerPath);
            if(!$verifySign){
                return FALSE;
            }
        }
        return (string)$decrypt;
    }

    /**
     * @return Java
     */
    private function get_ca_client(){
        // Dir
        $here = ROOT_PATH.'/application/library/dzfpca';
        $javaDir = $here.DIRECTORY_SEPARATOR."java";
        $libDir = $here.DIRECTORY_SEPARATOR."lib";
        $configDir = $here.DIRECTORY_SEPARATOR."config";

        // Java.inc
        Yaf_Loader::import($javaDir.DIRECTORY_SEPARATOR."Java.inc");

        // Library path
        java_set_library_path($libDir);
        // Cert
        $this->cerPath = $configDir.DIRECTORY_SEPARATOR."donggang.cer";
        $this->pfxPath = $configDir.DIRECTORY_SEPARATOR."luojilab.pfx";
        $this->pfxPwd  = "000000";

        // Test5 instance
        return new Java("Test5");
    }
}
