<?php
class Curl {
    /**
     * 请求错误
     * @var type 
     */
    static $request_error;
 
    /*
     * get 方式获取访问指定地址
     * @param  string url 要访问的地址
     * @param  string cookie cookie的存放地址,没有则不发送cookie
     * @return string curl_exec()获取的信息
     * @author ellis
     **/ 
    public static function get( $url, $cookie='' ) 
    { 
        // 初始化一个cURL会话  
        $curl = curl_init($url); 
        // 不显示header信息  
        curl_setopt($curl, CURLOPT_HEADER, 0); 
        // 将 curl_exec()获取的信息以文件流的形式返回，而不是直接输出。  
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); 
        // 使用自动跳转  
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1); 
        if(!empty($cookie)) { 
            // 包含cookie数据的文件名，cookie文件的格式可以是Netscape格式，或者只是纯HTTP头部信息存入文件。  
            curl_setopt($curl, CURLOPT_COOKIEFILE, $cookie); 
        } 
        // 自动设置Referer  
        curl_setopt($curl, CURLOPT_AUTOREFERER, 1); 
        // 执行一个curl会话  
        $tmp = curl_exec($curl); 
        // 关闭curl会话  
        curl_close($curl); 
        return $tmp; 
    } 
 
    /*
     * post 方式模拟请求指定地址
     * @param  string url   请求的指定地址
     * @param  array  params 请求所带的
     * #patam  string cookie cookie存放地址
     * @return string curl_exec()获取的信息
     **** @author ellisndy
     **/ 
    public static function post( $url, $params, $cookie ='' ) 
    { 
        $curl = curl_init($url); 
        curl_setopt($curl, CURLOPT_HEADER, 0); 
        // 对认证证书来源的检查，0表示阻止对证书的合法性的检查。  
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); 
        // 从证书中检查SSL加密算法是否存在  
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 1); 
        //模拟用户使用的浏览器，在HTTP请求中包含一个”user-agent”头的字符串。  
        curl_setopt($curl, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']); 
        //发送一个常规的POST请求，类型为：application/x-www-form-urlencoded，就像表单提交的一样。  
        curl_setopt($curl, CURLOPT_POST, 1); 
        // 将 curl_exec()获取的信息以文件流的形式返回，而不是直接输出。  
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); 
        // 使用自动跳转  
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);  
        // 自动设置Referer  
        curl_setopt($curl, CURLOPT_AUTOREFERER, 1); 
        // Cookie地址  
        curl_setopt($curl, CURLOPT_COOKIEJAR, $cookie); 
        // 全部数据使用HTTP协议中的"POST"操作来发送。要发送文件，  
        // 在文件名前面加上@前缀并使用完整路径。这个参数可以通过urlencoded后的字符串  
        // 类似'para1=val1¶2=val2&...'或使用一个以字段名为键值，字段数据为值的数组  
        // 如果value是一个数组，Content-Type头将会被设置成multipart/form-data。
        if(is_array($params)) {
            $params = http_build_query($params);
        }
        curl_setopt($curl, CURLOPT_POSTFIELDS, $params); 
        $result = curl_exec($curl); 
        curl_close($curl); 
        return $result; 
    } 
 
    /**
     * 远程下载
     * @param string $remote 远程图片地址
     * @param string $local 本地保存的地址
     * @param string $cookie cookie地址 可选参数由
     * 于某些网站是需要cookie才能下载网站上的图片的
     * 所以需要加上cookie
     * @return void
     **** @author ellisndy
     */ 
    public static function reutersload($remote, $local, $cookie= '') { 
        $cp = curl_init($remote); 
        $fp = fopen($local,"w"); 
        curl_setopt($cp, CURLOPT_FILE, $fp); 
        curl_setopt($cp, CURLOPT_HEADER, 0); 
        if($cookie != '') { 
            curl_setopt($cp, CURLOPT_COOKIEFILE, $cookie); 
        } 
        curl_exec($cp); 
        curl_close($cp); 
        fclose($fp); 
    } 
    
    
    /**
     * 发起请求操作
     * @param String $url 
     * @param array $params
     * @param String $requestMethod
     * @param bool $jsonDecode
     * @param arrat $headers
     * @param int $timeout
     * @return array
     */
    public static function request($url, $params = array(), $requestMethod = 'GET', $jsonDecode = true, $headers = array(), $timeout = 10) {
        $ci = curl_init();
        curl_setopt($ci, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($ci, CURLOPT_USERAGENT, 'bwq http V1');
        curl_setopt($ci, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ci, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ci, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ci, CURLOPT_ENCODING, "");
        curl_setopt($ci, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ci, CURLOPT_HEADER, FALSE);

        $requestMethod = strtoupper($requestMethod);
        switch ($requestMethod) {
            case 'POST':
                curl_setopt($ci, CURLOPT_POST, TRUE);
                if ($params) {
                    curl_setopt($ci, CURLOPT_POSTFIELDS, http_build_query($params));
                } else {
                    curl_setopt($ci, CURLOPT_POSTFIELDS, '');
                }
                break;
            case 'DELETE':
                curl_setopt($ci, CURLOPT_CUSTOMREQUEST, 'DELETE');
                if ($params) {
                    $url = "{$url}?{$params}";
                }
                break;
            case 'GET':
                if ($params) {
                    $sep = false === strpos($url, '?') ? '?' : '&';
                    $url .= $sep . http_build_query($params);
                }
                break;
            case 'PUT':
                if ($params) {
                    curl_setopt($ci, CURLOPT_CUSTOMREQUEST, "PUT");
                    curl_setopt($ci, CURLOPT_POSTFIELDS, http_build_query($params));
                }
                break;
        }
        curl_setopt($ci, CURLOPT_URL, $url);
        curl_setopt($ci, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ci, CURLINFO_HEADER_OUT, TRUE);
        
        $response = curl_exec($ci);
        $httpCode = curl_getinfo($ci, CURLINFO_HTTP_CODE);
        curl_close($ci);
        if ( $httpCode >= 300 || $httpCode == 0){
            self::$request_error = array(
                    'code' => $httpCode,
                    'error' => $response,
                    );
            $return = false;
            
        } else {
            $return = $jsonDecode ? json_decode($response, true) : $response;
        }
        return $return;
    }
    
    /**
     * 获取错误
     * @return type
     */
    public function get_request_error(){
        return self::$request_error;
    }
 
}