<?php
/**
 * Description of ErrorResult
 * @author ellis
 */
class ErrorResult {

    
    /**
     * 系统错误
     */
    const CODE_SYSTEM_ERR = 1000;
    
    /**
     * 参数格式错误
     */
    const CODE_PARM_FORMAT_ERR = 1001;
    
    /**
     * 参数值错误
     */
    const CODE_PARAM_VALUE_ERR = 1002;
    
    /**
     * 无数据
     */
    const CODE_NO_RESULT = 1003;
    
    /**
     * 错误的业务结果
     */
    const CODE_BUSINESS_RESUERR = 1004;


    /**
     * 错误信息
     * @var string 
     */
    var $errorMessage;

    /**
     * 错误编码
     * @var string 
     */
    var $errorCode;

    /**
     * 错误数据
     * @var array 
     */
    var $errorData;
    
    /**
     * 0为失败，1为成功
     * @var int 
     */
    var $status = 0;

}
