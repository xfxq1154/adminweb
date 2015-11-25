<?php
/**
 * 输出工具
 * @author ellis
 */
trait Trait_Output {

    /**
     * 输出错误信息
     * @param string $errorMessage
     * @param string $errorCode
     * @param array $data
     * @param ErrorResult $errorResultObject
     * @param array $data
     * @return ErrorResult 
     */
    public function outputError($errorMessage, $errorCode, $data) {
        $errorResultObject = new ErrorResult();
        $errorResultObject->errorMessage = $errorMessage;
        $errorResultObject->errorCode = $errorCode;
        $errorResultObject->errorData = $data;

        $this->output($errorResultObject);
    }
    

    /**
     * 输出成功信息
     * @param array $data
     * @param string $message
     */
    public function outputSuccess($data,$message="success") {
        $obj = new SuccessResult();
        $obj->message = $message;
        $obj->data = $data;

        $this->output($obj);
    }

    /**
     * 输出信息(default:Json)
     * @param mixed $data
     */
    public function output($data) {

        $dataType = $_REQUEST['dataType'];

        switch ($dataType) {
            case 'string':
                header("Content-type:text/html;charset=utf-8");
                var_dump((array)$data);
                break;
            default :
                echo json_encode($data);
        }


        exit();
    }

}
