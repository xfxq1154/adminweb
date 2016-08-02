<?php

/* 
 * StatcenterController
 * @author yanbo
 * @desc 数据统计控制器
 */
class StatcenterController extends Storebase{

    /**
     * @var StoreStatcenterModel
     */
    public $statcenter_model;
    public $date;

    private $showcase_id;
    private $start_created;
    private $end_created;
    private $showcase_list;
    
    public function init(){
        parent::init();

        $this->statcenter_model = new StoreStatcenterModel();

        $default_start = date('Y-m-d', strtotime('-7 day'));
        $default_end = date('Y-m-d', strtotime('-1 day'));

        $this->start_created = $this->input_get_param('start_time', $default_start);
        $this->end_created = $this->input_get_param('end_time', $default_end);
        $this->showcase_id = $this->input_get_param('showcase_id');

        $this->setShowcaseList();
    }

    public function productAction(){

        $params['showcase_id'] = $this->showcase_id;
        $params['orderby'] = 'total_pay';
        $params['start_created'] = $this->start_created;
        $params['end_created'] = Tools::format_date($this->end_created);
        $params['page_size'] = 10;
        $sell_top10 = $this->statcenter_model->skulist($params);
        if ($sell_top10){
            foreach ($sell_top10['skulist'] as $item){
                $ordertop[] = $item['total_order'];
                $paytop[] = $item['total_pay'];
            }
        }

        $params['showcase_id'] = $this->showcase_id;
        $params['type'] = 2;
        $params['orderby'] = 'total_pv';
        $params['start_created'] = $this->start_created;
        $params['end_created'] = Tools::format_date($this->end_created);
        $params['page_size'] = 10;
        $productTop10 = $this->statcenter_model->ranklist($params);
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

        $this->assign('sell_top10', $sell_top10['skulist']);
        $this->assign('ordertop', $ordertop);
        $this->assign('paytop', $paytop);

        $this->_display('statcenter/product.phtml');
    }

    public function pagedataAction(){
        $params['showcase_id'] = $this->showcase_id;
        $params['orderby'] = 'total_pv';
        $params['start_created'] = $this->start_created;
        $params['end_created'] = Tools::format_date($this->end_created);
        $params['page_size'] = 10;

        $overview_data = $this->statcenter_model->pageOverview($params);

        $productTop10 = $this->statcenter_model->ranklist($params);
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

        $this->_display('statcenter/pagedata.phtml');
    }

    private function pvperday(){
        //获取数据
        $params['showcase_id'] = $this->showcase_id;
        $params['start_created'] = $this->start_created;
        $params['end_created'] = Tools::format_date($this->end_created);
        $total_list = $this->statcenter_model->views($params);
        $params['type'] = 2;
        $foods_list = $this->statcenter_model->views($params);
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
        $this->_display('statcenter/pvperday.phtml');
    }

    public function spmAction() {
        $params['spm'] = $this->input_get_param('spm');
        $params['start_created'] = $this->start_created;
        $params['end_created'] = Tools::format_date($this->end_created);

        $visited_list = $this->statcenter_model->views($params);
        $uv = [];
        $total_uv = [];
        $total_pv = [];
        foreach ($visited_list as $visited){
            if (!$visited['spm']){
                continue;
            }
            $uv[$visited['spm']] = $visited['total_uv'];
            $total_uv[] = $visited['total_uv'];
            $total_pv[] = $visited['total_pv'];
        }

        $spms = [];
        $spm_order = [];
        $paied_num = [];
        $paied_fee = [];
        $result = $this->statcenter_model->orderOverview($params);
        if ($result){
            foreach ($result as &$val){
                if (!$val['spm']){
                    continue;
                }
                $spms[] = $val['spm'];
                $paied_num[] = $val['paied_num'];
                $paied_fee[] = $val['paied_sum'];

                $spm_order[$val['spm']] = $val;
            }
        }

        $channel_model = new StoreChannelModel();
        $spm_list = $channel_model->detail_mulit($spms);
        if ($spm_list){
            foreach ($spm_list as &$spm){
                $spm_uv = $uv[$spm['spm']];
                $order_people = $spm_order[$spm['spm']]['order_people'];

                $spm['paied_num'] = $spm_order[$spm['spm']]['paied_num'];
                $spm['paied_sum'] = $spm_order[$spm['spm']]['paied_sum'];
                $spm['rate'] = ($spm_uv) ? round($order_people / $spm_uv, 2) * 100 : '0.00';
            }
        }

        $this->assign('total_uv', array_sum($total_uv)); //访客数
        $this->assign('total_pv', array_sum($total_pv)); //访客数
        $this->assign('paied_num', array_sum($paied_num)); //访客数
        $this->assign('paied_fee', array_sum($paied_fee)); //访客数
        $this->assign('spmlist', $spm_list); //访客数
        $this->assign('spm', ($params['spm'] != 1) ? $params['spm'] : '');
        $this->_display('statcenter/spm.phtml');
    }

    public function orderAction(){

        $params['showcase_id'] = $this->showcase_id;
        $params['start_created'] = $this->start_created;
        $params['end_created'] = Tools::format_date($this->end_created);

        $res = $this->statcenter_model->pageOverview($params);
        $total_uv = ($res['total_uv']) ? : 0;

        $result = $this->statcenter_model->orderOverview($params);
        foreach ($result as $val){
            $key = '"'.date('m-d',strtotime($val['date'])).'"';
            $chart_data[$key] = $val;
        }

        $order_peo = [];
        $order_num = [];
        $order_sum = [];
        $paied_peo = [];
        $paied_num = [];
        $paied_sum = [];
        $dates = $this->_get_time_string();
        if($dates){
            foreach ($dates as $val){
                if(isset($chart_data[$val])){
                    $order_peo[] = $chart_data[$val]['order_people'];     //下单人数
                    $order_num[] = $chart_data[$val]['order_num'];        //下单笔数
                    $order_sum[] = $chart_data[$val]['order_sum'];        //下单金额
                    $paied_peo[] = $chart_data[$val]['paied_people'];     //付款人数
                    $paied_num[] = $chart_data[$val]['paied_num'];        //付款笔数
                    $paied_sum[] = $chart_data[$val]['paied_sum'];        //付款金额
                    $paied_num_wx[] = $chart_data[$val]['paied_num_wx'];  //微信付款笔数
                    $paied_num_jd[] = $chart_data[$val]['paied_num_jd'];  //京东付款笔数
                } else {
                    $order_num[] = 0;  //下单笔数
                    $paied_num[] = 0;  //付款笔数
                    $paied_sum[] = 0;  //付款金额
                    $paied_num_wx[] = 0;  //微信付款笔数
                    $paied_num_jd[] = 0;  //京东付款笔数
                }
            }
            $order_num_string = implode(',', $order_num);
            $paied_num_string = implode(',', $paied_num);
            $paied_sum_string = implode(',', $paied_sum);


        }
        $order_peo_total = array_sum($order_peo);
        $order_num_total = array_sum($order_num);
        $order_sum_total = array_sum($order_sum);
        $paied_peo_total = array_sum($paied_peo);
        $paied_num_total = array_sum($paied_num);
        $paied_sum_total = array_sum($paied_sum);
        $paied_people_avg = ($paied_peo_total) ? round($paied_sum_total / $paied_peo_total, 2) : 0;  //客单价

        $this->assign('dates', implode(',', $dates));
        $this->assign('total_uv', $total_uv); //访客数
        $this->assign('order_peo', $order_peo_total); //下单人数
        $this->assign('order_num', $order_num_total); //下单笔数
        $this->assign('order_sum', $order_sum_total); //下单金额
        $this->assign('paied_peo', $paied_peo_total); //付款人数
        $this->assign('paied_num', $paied_num_total); //付款笔数
        $this->assign('paied_sum', $paied_sum_total); //付款金额
        $this->assign('paied_people_avg', $paied_people_avg);
        
        $this->assign('paied_num_wx', array_sum($paied_num_wx));
        $this->assign('paied_num_jd', array_sum($paied_num_jd));
        $this->assign('order_num_string', $order_num_string);
        $this->assign('paied_num_string', $paied_num_string);
        $this->assign('paied_sum_string', $paied_sum_string);
        $this->_display('statcenter/order.phtml');
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

