<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Dzfp
 *
 * @author yanbo
 */
class Dzfp {
    
    const WSDL = 'http://111.202.226.70:8002/zxkp_base64/services/WKWebService?wsdl';
    const APPID = 'DZFP';
    const REQUEST_CODE = '110104201110174';    #企业纳税人识别号
    
    private $err_msg;
    private $content;
    
    public function fpkj($d1, $d2) {
        //*必填参数
        $kjxx_data = array(
            'KPLX'          =>'12',   //*发票请求流水号
            'FPQQLSH'       =>'20150722170928',   //*开票类型 0 蓝字发票 1红字发票
            'XSF_NSRSBH'    =>'110104201110174',   //*销售方纳税人识别号
            'XSF_MC'        =>'测试',   //*销售方名称
            'XSF_DZDH'      =>'safd',   //*销售方地址电话
            'XSF_YHZH'      =>'sdf',   //销售方银行账号
            'GMF_NSRSBH'    =>'sadf',   //购买方纳税人识别号
            'GMF_MC'        =>'asf',   //*购买方名称
            'GMF_DZDH'      =>'sadf',   //购买方地址
            'GMF_YHZH'      =>'sdaf',   //购买方银行账号
            'KPR'           =>'asdf',   //*开票人
            'SKR'           =>'sdaf',   //收款人
            'FHR'           =>'asdf',   //复核人 
            'YFP_DM'        =>'sadf',   //原发票代码 红字发票时必须
            'YFP_HM'        =>'asdf',   //原发票号码 红字发票时必须
            'JSHJ'          =>'asdf',   //*价税合计 单位元 (2位小数)
            'HJJE'          =>'asdf',   //*合计金额 单位元 (2位小数)
            'HJSE'          =>'asdf',   //*合计税额 单位元 (2位小数)
            'BZ'            =>'sadf',   //备注
            'DDRQ'          =>'sadf',   //*订单日期 同下
            'KPRQ'          =>'sadf',   //*开票日期 yyyymmddhh24miss
            'DDH'           =>'asdf',   //订单号
            'XFZ_YX'        =>'asdf',   //消费者邮箱
            'XFZ_SJH'       =>'asdf'    //*消费者手机号
         );
        $kjxxmx = array(
            array(
                'FPHXZ'     => 'asf',   //*发票行性质 0正常行、1折扣行、2被折扣行
                'HH'        => 'asf',   //*行号 按商品明细排序 第一行1，第二行2 一次类推
                'XMMC'      => 'asdf',   //*项目名称
                'GGXH'      => 'asfd',   //计量单位
                'DW'        => 'asdf',   //规格型号
                'XMSL'      => 'asf',   //项目数量
                'XMDJ'      => 'czv',   //项目单价 小数点后六位 不含税
                'XMJE'      => 'af',   //*项目金额 不含税，单位元(2位小数)
                'SL'        => 'sdfg',   //*税率 6位小数例：1%为0.01
                'SE'        => '123',   //*税额 单位：元(2位小数)
                'SN'        => '13'    //商品SN号
            )
        );
        $string = "<?xml version='1.0' encoding='utf-8'?><BUSINESS ID='REQUEST_E_FAPIAO_KJ'></BUSINESS>";
        $requestXML = simplexml_load_string($string);
        $kjxx = $requestXML->addChild('KJXX');
        
        foreach ($kjxx_data as $key => $val){
            $kjxx->addChild($key,$val);
        }
        
        $kjxxmx = $requestXML->addChild('KJXXMX');
        $kjxxmx->addAttribute('COUNT', 1);
        foreach ($kjxxmx as $value){
            $kjmx = $requestXML->addChild('KJMX');
            foreach ($value as $vk=>$vl){
                $kjmx->addChild($vk,$vl);
            }
        }
        $this->doService('REQUEST_E_FAPIAO_KJ', $requestXML->asXML());
    }
    
    /**
     * 发票请求流水号
     * @param type $FPQQLSH
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
        return $resxml->RESPONSE_COMMON_FPCX->EWM;
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
     * 电子发票统一调用接口
     * @param string $interfaceCode
     * @param string $requestXML
     */
    private function doService($interfaceCode, $requestXML) {
        if(!$interfaceCode || !$requestXML){
            return FALSE;
        }
        
        $content = base64_encode($requestXML); 
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
                    'content' => $content, 
                    'signature' => '',]
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
        
        ini_set('default_socket_timeout', 3);
        $client = new SoapClient(self::WSDL);
        $result = $client->doService(['xml' => $xmlstring]);
        $xmlobj = simplexml_load_string($result->return);
        if((string)$xmlobj->returnStateInfo->returnCode !== '0000'){
            $this->err_msg = (string)$xmlobj->returnStateInfo->returnMessage ? :base64_decode($xmlobj->data->content);
            return FALSE;
        }
        return base64_decode($xmlobj->data->content);
    }
    
    public function getError() {
        return $this->err_msg;
    }
}
