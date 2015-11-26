<?php
class Captcha{    
    /**
     * @var type 
     * 当前验证码
     */
    var $vcode;
    
    /**
     *
     * @var type 
     * 验证码数组，可自己配置
     */
    var $ca = array();
    
    var $imagewidth = 130;
    
    var $imagehight = 40;
    
    public function __construct($c = array()) {
        if(empty($c)){
            $c = $this->setCode();
        }
         $this->ca = $c;
    }
            
    /**
     * 生成验证码图片
     */
    function getImage(){
        //生成验证码图片
        $im = imagecreate($this->imagewidth, $this->imagehight); // 画一张指定宽高的图片
        $back = ImageColorAllocate($im, 255, 255, 255); // 定义背景颜色
        imagefill($im,0,0,$back); //把背景颜色填充到刚刚画出来的图片中
        $vcodes = "";
        srand((double)microtime()*1000000);
        //生成4位验证码
        for($i=0;$i<4;$i++){
            $font = ImageColorAllocate($im, rand(100,255),rand(0,100),rand(100,255)); // 生成随机颜色
            $authnum=$this->ca[$i];
            $vcodes.=$authnum;
            
            $array = array(-1,1);
            $p = array_rand($array);
            $an = $array[$p]*mt_rand(1,10); //扭曲角度
            $size = 14;                     //字体大小
//            echo $an;exit;
//            $ttf = "simhei.ttf";
//            imagettftext($im, $size, $an, 10+$i*10, rand(13,20), $font, $ttf, $authnum);
            imagestring($im, 5, 10+$i*10, 3, $authnum, $font);
        }
        $this->vcode = $vcodes;
        
        $_SESSION['vcode'] = $vcodes;
        for($i=0;$i<$this->imagewidth;$i++) //加入干扰象素
        {
            $randcolor = ImageColorallocate($im,rand(0,255),rand(0,255),rand(0,255));
            imagesetpixel($im, rand()%$this->imagewidth , rand()%$this->imagehight , $randcolor); // 画像素点函数
        }
        Header("Content-type: image/PNG");
        ImagePNG($im);
        ImageDestroy($im);
    }
    
    private function setCode(){
        //要显示的字符，可自己进行增删
        $str = "1,2,3,4,5,6,7,8,9,a,b,c,d,f,g,h,i,j,k,l,m,n,o,p,q,r,s,t,u,v,w,x,y,z";      
        $list = explode(",", $str);
        $cmax = count($list) - 1;
        $verifyCode = '';
        for ( $i=0; $i < 4; $i++ ){
              $randnum = mt_rand(0, $cmax);
              //取出字符，组合成为我们要的验证码字符
              $ca[] = $list[$randnum];
        }   
        return $ca;
    }
            
    function getVcode(){
        return $this->vcode;
    }
}