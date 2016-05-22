<?php

/* 
 * @name:SdataController
 * @author yanbo
 * @desc 数据统计控制器
 */
class SdataController extends Base{
    
    use Input,
        Trait_Pagger,
        Trait_Layout;

    /**
     * @var SdataModel
     */
    public $sdata; 
    public $date;

    private $showcase_id;
    private $start_created;
    private $end_created;
    
    public function init(){
        $this->initAdmin();
        $this->checkRole();

        $this->sdata = new SdataModel();

        $default_start = date('Y-m-d', strtotime('-7 day'));
        $default_end = date('Y-m-d', strtotime('-1 day'));

        $this->start_created = $this->input_get_param('start_time', $default_start);
        $this->end_created = $this->input_get_param('end_time', $default_end);
        $this->showcase_id = $this->input_get_param('showcase_id');
    }

    public function productAction(){
        $params['showcase_id'] = $this->showcase_id;
        $params['type'] = 2;
        $params['orderby'] = 'total_pv';
        $params['start_created'] = $this->start_created;
        $params['end_created'] = Tools::format_date($this->end_created);
        $params['page_size'] = 10;

        $productTop10 = $this->sdata->ranklist($params);
        if ($productTop10){
            $total_pv = 0;
            foreach ($productTop10 as $product){
                $toppv[] = $product['total_pv'];
                $topuv[] = $product['total_uv'];

                $total_pv += $product['total_pv'];
            }
        }

        $this->assign('top10', $productTop10);
        $this->assign('total_pv', $total_pv);
        $this->assign('toppv', $toppv);
        $this->assign('topuv', $topuv);

        $this->_display('sdata/product.phtml');
    }

    public function pagedataAction(){
        $params['showcase_id'] = $this->showcase_id;
        $params['orderby'] = 'total_pv';
        $params['start_created'] = $this->start_created;
        $params['end_created'] = Tools::format_date($this->end_created);
        $params['page_size'] = 10;

        $overview_data = $this->sdata->overview($params);

        $productTop10 = $this->sdata->ranklist($params);
        if ($productTop10){
            $total_pv = 0;
            foreach ($productTop10 as $product){
                $toppv[] = $product['total_pv'];
                $topuv[] = $product['total_uv'];

                $total_pv += $product['total_pv'];
            }
        }

        $this->assign('overview', $overview_data);
        $this->assign('top10', $productTop10);
        $this->assign('toppv', $toppv);

        $this->_display('sdata/pagedata.phtml');
    }

    private function pvperday(){
        //获取数据
        $params['showcase_id'] = $this->showcase_id;
        $params['start_created'] = $this->start_created;
        $params['end_created'] = Tools::format_date($this->end_created);
        $total_list = $this->sdata->views($params);
        $params['type'] = 2;
        $foods_list = $this->sdata->views($params);
        foreach ($foods_list as $item){
            $key = $item['date'];
            $format_foods_list[$key]['goods_pv'] = $item['total_pv'];
            $format_foods_list[$key]['goods_uv'] = $item['total_uv'];
        }

        foreach ($total_list as &$item){
            $key = $item['date'];
            $item['goods_pv'] = isset($format_foods_list[$key]) ? $format_foods_list[$key]['goods_pv'] : 0;
            $item['goods_uv'] = isset($format_foods_list[$key]) ? $format_foods_list[$key]['goods_uv'] : 0;
        }
        return $total_list;
    }

    public function pvperdayAction(){

        $format_data = $this->pvperday();
        if ($format_data){
            foreach ($format_data as $val){
                $key = '"'.date('m-d',strtotime($val['date'])).'"';
                $chart_data[$key] = $val;
            }
        }

        //生成图表所需数据
        $dates = $this->_get_time_string();
        foreach ($dates as $val){
            $total_pv[] = (isset($chart_data[$val])) ? $chart_data[$val]['total_pv'] : 0;
            $total_uv[] = (isset($chart_data[$val])) ? $chart_data[$val]['total_uv'] : 0;
            $goods_pv[] = (isset($chart_data[$val])) ? $chart_data[$val]['goods_pv'] : 0;
            $goods_uv[] = (isset($chart_data[$val])) ? $chart_data[$val]['goods_uv'] : 0;
        }
        $this->assign('dates', $dates);
        $this->assign('total_pv', $total_pv);
        $this->assign('total_uv', $total_uv);
        $this->assign('goods_pv', $goods_pv);
        $this->assign('goods_uv', $goods_uv);
        $this->assign('view_list', $format_data);
        $this->_display('sdata/pvperday.phtml');
    }
    
    public function orderAction(){

        $params['showcase_id'] = $this->showcase_id;
        $params['start_created'] = $this->start_created;
        $params['end_created'] = Tools::format_date($this->end_created);
        
        $result = $this->sdata->getList($params);
        $order_people = 0;
        $paied_people = 0;
        $paied_num_total = 0;
        $paied_sum_total = 0;
        foreach ($result as $val){
            $key = '"'.date('m-d',strtotime($val['date'])).'"';
            $data[$key] = $val;
            
            $order_people += $val['order_people'];  //付款人数
            $paied_people += $val['paied_people'];  //付款人数
            $paied_num_total += $val['paied_num'];     //付款笔数
            $paied_sum_total += $val['paied_sum'];  //付款金额
        }
        $paied_people_sum = 0;
        if($paied_people > 0){
            $paied_people_sum = round($paied_sum_total / $paied_people, 2);  //客单价
        }

        $this->assign('order_people', $order_people);
        $this->assign('paied_people', $paied_people);
        $this->assign('paied_num', $paied_num_total);
        $this->assign('paied_sum', $paied_sum_total);
        $this->assign('paied_people_sum', $paied_people_sum);
        
        $dates = $this->_get_time_string();
        $order_num_string = '';
        $paied_num_string = '';
        $paied_sum_string = '';
        if($dates){
            foreach ($dates as $val){
                if(isset($data[$val])){
                    $order_num[] = $data[$val]['order_num'];  //下单笔数
                    $paied_num[] = $data[$val]['paied_num'];  //付款笔数
                    $paied_num_wx[] = $data[$val]['paied_num_wx'];  //微信付款笔数
                    $paied_num_jd[] = $data[$val]['paied_num_jd'];  //京东付款笔数
                    $paied_sum[] = $data[$val]['paied_sum'];  //付款金额
                } else {
                    $order_num[] = 0;  //下单笔数
                    $paied_num[] = 0;  //付款笔数
                    $paied_num_wx[] = 0;  //微信付款笔数
                    $paied_num_jd[] = 0;  //京东付款笔数
                    $paied_sum[] = 0;  //付款金额
                }
            }

            $order_num_string = implode(',', $order_num);
            $paied_num_string = implode(',', $paied_num);
            $paied_sum_string = implode(',', $paied_sum);

            $this->assign('dates', implode(',', $dates));
        }
        
        $this->assign('paied_num_wx', array_sum($paied_num_wx));
        $this->assign('paied_num_jd', array_sum($paied_num_jd));
        $this->assign('order_num_string', $order_num_string);
        $this->assign('paied_num_string', $paied_num_string);
        $this->assign('paied_sum_string', $paied_sum_string);
        $this->_display('sdata/order.phtml');
    }
    
    public function _get_time_string() {
        $start  = strtotime($this->start_created);
        $stop   = strtotime(Tools::format_date($this->end_created));
        $extend = ($stop-$start)/86400;
        $date = [];
        for ($i = 0; $i < $extend; $i++) {
            $date[] = '"'.date('m-d',$start + 86400 * $i).'"';
        }
        return $date;
    }

    private function _display($layout){
        $this->assign('showcase_id', $this->showcase_id);
        $this->assign('start_time', $this->start_created);
        $this->assign('end_time', $this->end_created);

        $this->layout($layout);
    }
}

