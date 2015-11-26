<?php
/**
 * Description of Api
 * 调用Api工具类
 * @author ellis
 */
class Api {

    private $host;

    public function __construct($host) {
        $this->host = $host;
    }

    /*
     * post 方式模拟请求指定地址
     * @param  string $uri   请求的指定地址
     * @param  array  $params 请求所带的
     * #patam  string $cookie cookie存放地址
     * @return string curl_exec()获取的信息
     **** @author ellisyliu<zyliu@in1001.com>
     */

    public function post($uri, $params, $cookie = "") {

        $url = $this->host . $uri;
  

        return Curl::post($url, $params, $cookie = '');
    }

    /*
     * get 方式获取访问指定地址
     * @param  string $uri 要访问的地址
     * @param  string $cookie cookie的存放地址,没有则不发送cookie
     * @return string curl_exec()获取的信息
     **** @author ellisyliu<zyliu@in1001.com>
     * */

    public function get($uri, $cookie = '') {
        $url = $this->host . $uri;

        return Curl::get($url, $cookie);
    }

    /**
     * 远程下载
     * @param string $remote 远程图片地址
     * @param string $local 本地保存的地址
     * @param string $cookie cookie地址 可选参数由
     * 于某些网站是需要cookie才能下载网站上的图片的
     * 所以需要加上cookie
     * @return void
     **** @author ellisyliu<zyliu@in1001.com>
     */
    public function reutersload($remote, $local, $cookie = '') {

        return Curl::reutersload($remote, $local, $cookie);
    }
    
    /**
     * GET POST DELETE　PUT 等方法
     * @param string $uri 访问接口地址
     * @param array $params 参数
     * @param string $requestMethod 请求类型 GET、POST、PUT、DELETE
     * @param boolean $jsonDecode 是否进行json解析
     * @param array $headers 自定义请求Header
     * @param int $timeout 请求超时时间
     * @return array
     */
    public function request($uri, $params = array(), $requestMethod = 'GET', $jsonDecode = true, $headers = array(), $timeout = 10) {
        $url = $this->host . $uri;

        return Curl::request($url, $params, $requestMethod, $jsonDecode, $headers, $timeout);
    }

}
