<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 * @author:why
 * @purpose:同步有赞商品数据脚本
 */


class DataanalysisController extends Base{ 
    
    use Trait_Layout,
        Trait_Pagger;
    
    public $data;
    public $ximalaya;
    public $appId = APP_ID;
    public $appSecret = AppSecret;
    public $client;
    public $commdity,$commImg,$commTag,$commSku;
    public $orders,$orderall;
    public $user;
    
    
    public function init(){
        Yaf_Loader::import(ROOT_PATH .'/application/library/youzan/KdtApiClient.php');
        $this->initAdmin();
        $this->client = new KdtApiClient($this->appId, $this->appSecret); 
        $this->data = new DataanalysisModel();
        $this->commImg = new YouZanImgsModel();
        $this->commdity = new CommdityModel();
        $this->commTag = new YouZanTagModel();
        $this->commSku = new YouZanSkuModel();
        $this->orders = new YouZanOrdersModel();
        $this->orderall = new YouZanOrderAllModel();
        $this->user = new YouZanUserModel();
    }

    /*
     * 同步有赞仓库商品
     * 已售馨
     */
    public function warehouseCommodityAction(){
        $client = new KdtApiClient($this->appId,  $this->appSecret);
        $method  = 'kdt.items.inventory.get';
        $data=[
            'page_size'=>500,
            'order_by'=>'created:desc',
            'banner'=>'sold_out'
        ];
        $rs = $client->post($method,$data);
        $contact = $rs['response']['items'];
        $array = [
            'num_iid'=>'num_iid','alias'=>'alias','title'=>'title','cid'=>'cid','promotion_cid'=>'promotion_cid','tag_ids'=>'tag_ids','`desc`'=>'`desc`','origin_price'=>'origin_price',
            'outer_id'=>'outer_id','outer_buy_url'=>'outer_buy_url','buy_quota'=>'buy_quota','created'=>'created','is_virtual'=>'is_virtual','is_listing'=>'is_listing','is_lock'=>'is_lock',
            'is_used'=>'is_used','auto_listing_time'=>'auto_listing_time','detail_url'=>'detail_url','share_url'=>'share_url','pic_thumb_url'=>'pic_thumb_url','num'=>'num','sold_num'=>'sold_num',
            'price'=>'price','post_type'=>'post_type','post_fee'=>'post_fee','delivery_template_fee'=>'delivery_template_fee',serialize('skus')=>'skus',serialize('item_imgs')=>'item_imgs',serialize('item_qrcodes')=>'item_qrcodes',
            serialize('item_tags')=> 'item_tags','item_type'=>'item_type','is_supplier_item'=>'is_supplier_item'
        ];
        foreach ($contact as $key=>$val){
            foreach ($array as $k=>$r){
                $content[$r] = $val[$k];
            }
            $this->data->insert('`warehouse_commodity`', $content);
        }
        exit;
    }
    
    /*
     *获取出售中的商品
     * 
     */
    public function getOnsaleAction(){
        $this->client = new KdtApiClient($this->appId, $this->appSecret);
        
        $method  = "kdt.items.onsale.get";
        $data = [
            'page_size'=>500,
            'order_by'=>'created:desc'
        ];
        //请求接口
        $rs = $this->client->post($method,$data);
        $items = $rs['response']['items'];
        $array = [
            'num_iid'=>'num_iid','alias'=>'alias','title'=>'title','cid'=>'cid','promotion_cid'=>'promotion_cid','tag_ids'=>'tag_ids','`desc`'=>'`desc`','origin_price'=>'origin_price',
            'outer_id'=>'outer_id','outer_buy_url'=>'outer_buy_url','buy_quota'=>'buy_quota','created'=>'created','is_virtual'=>'is_virtual','is_listing'=>'is_listing','is_lock'=>'is_lock',
            'is_used'=>'is_used','auto_listing_time'=>'auto_listing_time','detail_url'=>'detail_url','share_url'=>'share_url','pic_thumb_url'=>'pic_thumb_url','num'=>'num','sold_num'=>'sold_num',
            'price'=>'price','post_type'=>'post_type','post_fee'=>'post_fee','delivery_template_fee'=>'delivery_template_fee',serialize('skus')=>'skus',serialize('item_imgs')=>'item_imgs',serialize('item_qrcodes')=>'item_qrcodes',
            serialize('item_tags')=> 'item_tags','item_type'=>'item_type','is_supplier_item'=>'is_supplier_item'
        ];
        foreach ($items as $key=>$val){
            foreach ($array as $r=>$f){
                $content[$f] = $val[$r];
            }
            $this->data->insert('`onsela_commodity`', $content);
        }
        exit;
    }
    
    /*
     * 后去推广栏目列表
     */
    public function getPromotionsAction(){
        $this->client = new KdtApiClient($this->appId, $this->appSecret);
        
        $method = "kdt.itemcategories.promotions.get";
        $data = [];
        $rs = $this->client->post($method,$data);
        $items = $rs['response']['categories'];
        $array = [
            'id '=>'id',
            'name'=>'name'
        ];
        foreach ($items as $key=>$val){
            foreach ($array as $r=>$f){
                $content[$f] = $val[$r]; 
            }
            $this->data->insert('`promotions`', $content);
        }
        exit;
    }
    
    /*
     * 获取商品自定义标签列表
     */
    
    public function getTagsAction(){
        $method = "kdt.itemcategories.tags.get";
        $this->client = new KdtApiClient($this->appId, $this->appSecret);
        $data = [];
        //请求接口
        $rs = $this->client->post($method,$data);
        $contact = $rs['response']['tags'];
        $array = [
            'id'=>'pid','name'=>'name','item_num'=>'item_num','tag_url'=>'tag_url',
            'share_url'=>'share_url','type'=>'type','created'=>'created'
        ];
        foreach ($contact as $key=>$val){
            foreach ($array as $r=>$f){
                $content[$f] = $val[$r];
            }
            $this->data->insert('`custom_tags_list`', $content);
        }
        exit;
    }
    
    /*
     * 获取订单
     */
    public function getOrdersAction(){
        $method  = "kdt.trades.sold.get";
        $this->client = new KdtApiClient($this->appId, $this->appSecret);
        $data = [
            'page_no'=>1,
            'page_size'=>1
        ];
        $rs = $this->client->post($method,$data);
        $count = $rs['response']['total_results'];       //订单总数
        $cont_size = ($count / 100) + 1;
        $size = (int) $cont_size;
        $array = [
            'num'=>'num','num_iid'=>'num_iid','price'=>'price','pic_path'=>'pic_path','pic_thumb_path'=>'pic_thumb_path','title'=>'`title`','type'=>'`type`',
            'discount_fee'=>'discount_fee','status'=>'status','refund_state'=>'refund_state','shipping_type'=>'shipping_type','post_fee'=>'post_fee','total_fee'=>'total_fee','refunded_fee'=>'refunded_fee',
            'payment'=>'payment','created'=>'created','update_time'=>'update_time','pay_time'=>'pay_time','pay_type'=>'pay_type','consign_time'=>'consign_time','sign_time'=>'sign_time',
            'buyer_area'=>'buyer_area','seller_flag'=>'seller_flag','buyer_message'=>'buyer_message','fetch_detail'=>'fetch_detail',
            'adjust_fee'=>'adjust_fee','weixin_user_id'=>'weixin_user_id','tid'=>'tid','buyer_type'=>'buyer_type','buyer_id'=>'buyer_id','trade_memo'=>'trade_memo','receiver_city'=>'receiver_city',
            'receiver_district'=>'receiver_district','receiver_name'=>'receiver_name','receiver_state'=>'receiver_state',
            'receiver_address'=>'receiver_address','receiver_zip'=>'receiver_zip','receiver_mobile'=>'receiver_mobile','feedback'=>'feedback','outer_tid'=>'outer_tid','tid'=>'tid'
        ];
        $orders = [
            'oid'=>'oid','num_iid'=>'num_iid','sku_id'=>'sku_id','sku_unique_code'=>'sku_unique_code','num'=>'num','outer_sku_id'=>'outer_sku_id','outer_item_id'=>'outer_item_id',
            'title'=>'title','seller_nick'=>'seller_nick','fenxiao_price'=>'fenxiao_price','fenxiao_payment'=>'fenxiao_payment','price'=>'price','total_fee'=>'total_fee','discount_fee'=>'discount_fee',
            'payment'=>'payment','sku_properties_name'=>'sku_properties_name','pic_path'=>'pic_path','pic_thumb_path'=>'pic_thumb_path','item_type'=>'item_type'
        ];
        for($i=1;$i<=$size;$i++){
            $data2 = ['page_no'=>$i,'page_size'=>100];
            $rs = $this->client->post($method,$data2);
            $order_all = $rs['response']['trades'];
            foreach ($order_all as $key=>$val){
                foreach ($array as $r=>$f){
                    $contact[$f] = $val[$r];
                    $contact['orders'] = serialize($val['orders']);
                    $contact['coupon_details'] = serialize($val['coupon_details']);
                    $contact['promotion_details'] = serialize($val['promotion_details']);
                    $contact['sub_trades'] = serialize($val['sub_trades']);
                    $contact['buyer_nick'] = '`'.$val['buyer_nick'].'`';
                    $order = $val['orders'];
                }
                $this->orderall->insert($contact);
                foreach ($order as $okey=>$oval){
                    foreach ($orders as $orkey=>$orval){
                        $contact2[$orval] = $oval[$orkey];
                        $contact2['buyer_messages'] = serialize($oval['buyer_messages']);
                        $contact2['order_promotion_details'] = serialize($oval['order_promotion_details']);
                    }
                    $this->orders->insert($contact2);
                }
            }
        }
        exit;
    }
    
    
    /*
     * 获取粉丝列表
     */
    public function wechatFollowersAction(){
        
        $method = 'kdt.users.weixin.followers.get';
        $this->client = new KdtApiClient($this->appId, $this->appSecret);
        $data = ['page_size' =>1];
        $rs = $this->client->post($method, $data);
        //获取总数
        $number = $rs['response']['total_results'];
        $size = ($number / 500) + 1;
        //循环次数
        $page_no = (int) $size;
        $array = [
            'user_id'=>'user_id','weixin_openid'=>'weixin_openid','avatar'=>'avatar',
            'follow_time'=>'follow_time','sex'=>'sex','province'=>'province','city'=>'city'
        ];
        for($i=1;$i<=$page_no;$i++){
            $data2 = [ 'page_size'  =>500,'page_no'=>$i];
            $rs = $this->client->post($method, $data2);
            $users = $rs['response']['users'];
            foreach ($users as $key=>$val){
                foreach ($array as $ak=>$av){
                    $contact[$av] = $val[$ak];
                    $contact['nick'] = '`'.$val['nick'].'`';
                    $contact['tags'] = serialize($val['tags']);
                }
                $this->user->insert($contact);
            }
        }
        exit;
    }
    
    
    /*
     * 获取区域列表信息
     */
    
    public function getRegionsAction(){
        $method = 'kdt.regions.get';
        $this->client = new KdtApiClient($this->appId, $this->appSecret);
        $data = [
            'level' =>0
        ];
        $rs = $this->client->post($method,$data);
        $contact = $rs['response']['regions'];
        
        $array = [
            'id'=>'aid','name'=>'name','parent_id'=>'parent_id'
        ];
        foreach ($contact as $key=>$val){
            foreach ($array as $r => $f) {
                $contents[$f] = $val[$r];
            }
            $this->data->insert('`regoin`', $contents);
        }
        exit;
    }
    
    /*
     *获取商品
     */
    
    public function getCommAction(){
        $method = 'kdt.items.onsale.get';
        $this->client = new KdtApiClient($this->appId, $this->appSecret);
        $data = [
            'page_size'=>1000,
            'order_by'=>'created:desc'
        ];
        $rs = $this->client->post($method, $data);
        $sum = $rs['response']['total_results'];
        $itmes = $rs['response']['items'];
        $itmes_arr = [
            'num_iid'=>'num_iid','alias'=>'alias','title'=>'title','cid'=>'cid','promotion_cid'=>'promotion_cid','tag_ids'=>'tag_ids','desc'=>'`desc`','origin_price'=>'origin_price',
            'outer_id'=>'outer_id','outer_buy_url'=>'outer_buy_url','buy_quota'=>'buy_quota','created'=>'created','is_virtual'=>'is_virtual','is_listing'=>'is_listing','is_lock'=>'is_lock',
            'is_used'=>'is_used','auto_listing_time'=>'auto_listing_time','detail_url'=>'detail_url','share_url'=>'share_url','pic_url'=>'pic_url','pic_thumb_url'=>'pic_thumb_url','num'=>'num','sold_num'=>'sold_num',
            'price'=>'price','post_type'=>'post_type','post_fee'=>'post_fee','delivery_template_fee'=>'delivery_template_fee','item_type'=>'item_type','is_supplier_item'=>'is_supplier_item'
        ];
        $itmes_tag = [
            'id'=>'bid','name'=>'name','type'=>'type','created'=>'created',
            'item_num'=>'item_num','tag_url'=>'tag_url','share_url'=>'share_url',
            'desc'=>'`desc`','num_iid'=>'num_iid'
        ];
        $itmes_sku = [
            'outer_id'=>'outer_id','sku_id'=>'sku_id','sku_unique_code'=>'sku_unique_code',
            'num_iid'=>'num_iid','quantity'=>'quantity','properties_name'=>'properties_name',
            'properties_name_json'=>'properties_name_json','with_hold_quantity'=>'with_hold_quantity',
            'price'=>'price','created'=>'created','modified'=>'modified'
        ];
        $itmes_img = [
            'num_iid'=>'num_iid','id'=>'pid','created'=>'created','url'=>'url',
            'thumbnail'=>'thumbnail','medium'=>'medium','combine'=>'combine'
        ];
        foreach ($itmes as $key=>$val){
            foreach ($val['item_imgs'] as $a_key=>$a_val){
                $a_val['num_iid'] = $val['num_iid'];
                $images[] = $a_val; 
            }
            foreach ($val['item_tags'] as $t_key=>$t_val){
                $t_val['num_iid'] = $val['num_iid'];
                $tages[] = $t_val;
            }
            foreach ($val['skus'] as $s_key=>$s_val){
                $s_val['num_iid'] = $val['num_iid'];
                $skus[] = $s_val;
            }
            foreach ($itmes_arr as $arr_key=>$arr_val){
                $content[$arr_val] = $val[$arr_key];
                $content['skus'] = serialize($val['skus']);
                $content['item_imgs'] = serialize($val['item_imgs']);
                $content['item_qrcodes'] = serialize($val['item_qrcodes']);
                $content['item_tags'] = serialize($val['item_tags']);
            }
            $this->commdity->insert($content);
        }
        //插入商品图片数据
        foreach ($images as $image_key=>$image_val){
            foreach ($itmes_img as $img_key=>$img_val){
                $img_content[$img_val] = $image_val[$img_key];
            }
            $this->commImg->insert($img_content);
        }
        //插入商品sku
        foreach ($skus as $sku_key=>$sku_val){
            foreach ($itmes_sku as $item_key=>$item_val){
                $sku_content[$item_val] = $sku_val[$item_val];
            }
            $this->commSku->insert($sku_content);
        }
        //插入商品标签
        foreach($tages as $tag_key=>$tag_val){
            foreach ($itmes_tag as $tag_k=>$tag_v){
                $tag_content[$tag_v] = $tag_val[$tag_k];
            }
            $this->commTag->insert($tag_content);
        }
        exit;
    }
}
