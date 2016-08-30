<?php

/**
 * PageController
 * @author yanbo
 */
class PageController extends Statbase {

    public function init() {
        parent::init();
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
            foreach ($productTop10['list'] as $product){
                $toppv[] = $product['total_pv'];
                $topuv[] = $product['total_uv'];

                $total_pv += $product['total_pv'];
            }
        }

        $this->assign('overview', $overview_data);
        $this->assign('top10', $productTop10['list']);
        $this->assign('toppv', $toppv);

        $this->_display('page/pagedata.phtml');
    }

    private function pvperday(){
        //获取数据
        $params['showcase_id'] = $this->showcase_id;
        $params['start_created'] = $this->start_created;
        $params['end_created'] = Tools::format_date($this->end_created);
        $total_list = $this->statcenter_model->views($params);
        $params['type'] = 'product';
        $foods_list = $this->statcenter_model->views($params);
        foreach ($foods_list as $item){
            $key = $item['odate'];
            $format_foods_list[$key]['goods_pv'] = $item['total_pv'];
            $format_foods_list[$key]['goods_uv'] = $item['total_uv'];
        }

        foreach ($total_list as &$item){
            $key = $item['odate'];
            $item['goods_pv'] = isset($format_foods_list[$key]) ? $format_foods_list[$key]['goods_pv'] : 0;
            $item['goods_uv'] = isset($format_foods_list[$key]) ? $format_foods_list[$key]['goods_uv'] : 0;
        }
        return $total_list;
    }

    public function pvperdayAction(){

        $format_data = $this->pvperday();
        if ($format_data){
            foreach ($format_data as $val){
                $key = '"'.date('m-d',strtotime($val['odate'])).'"';
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
        $this->_display('page/pvperday.phtml');
    }

    public function pagedetailAction(){
        $page_no  = $this->input_get_param('page_no', 1);

        $params['showcase_id'] = $this->showcase_id;
        $params['orderby'] = 'total_pv';
        $params['start_created'] = $this->start_created;
        $params['end_created'] = Tools::format_date($this->end_created);
        $params['page_no'] = $page_no;
        $params['page_size'] = 20;

        $result = $this->statcenter_model->ranklist($params);

        $this->assign("list", $result['list']);
        $this->assign("search", $this->input_get());

        $query_string = http_build_query($this->input_get());
        $this->renderPagger($page_no, $result['total_nums'], "/store/statcenter/pagedetail?$query_string&page_no={p}", $params['page_size']);

        $this->_display('page/pagedetail.phtml');
    }
}
