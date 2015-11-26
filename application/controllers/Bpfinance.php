<?php

/**
 * @desc 财务对账
 *
 * @author why
 */
class BpfinanceController extends Base {

    use Trait_Layout,
        Trait_Pagger;
    

    public $finance;

    public function init() {
        $this->initAdmin();
        $this->finance = new BpFinanceModel();
    }

    function indexAction() {

        $this->checkRole();
        $p = (int) $this->getRequest()->get('p' ,1);
        $number = $this->getRequest()->get('number');
        $order_no = $this->getRequest()->get('order_no');
        $showcase_id = $this->getRequest()->get('showcase');
        $status = $this->getRequest()->get('status');
        $time_start = $this->getRequest()->get('start_time');
        $time_end = $this->getRequest()->get('end_time');
        
        $mobile = $number ? $number : '';
        $order_id = $order_no ? $order_no : '';
        
        $page_size = 20;
        $order_list = [
            'page_no'=> $p,
            'page_size'=> $page_size,
            'mobile' => $mobile,
            'order_id' => $order_id,
            'status' => $status == 'ALL' ? '' : $status,
            'paid' => $status == 'ALL' ? 1 : '',
            'showcase_id' => $showcase_id,
            'start_created' => $time_start,
            'end_created' => $time_end
        ];
        $orderList = $this->finance->getList($order_list);
        //导出
        if($_GET['excel'] == 1){
            $export_data['page_size'] = 100;
            $export_data['mobile'] = $mobile;
            $export_data['order_id'] = $order_id;
            $export_data['status'] = $status == 'ALL' ? '' : $status;
            $export_data['paid'] = $status == 'ALL' ? 1 : '';
            $export_data['start_created'] = $time_start;
            $export_data['end_created'] = $time_end;
            $export_data['use_has_next'] = 1;
            
            $n = 0;
            do{
                $n++;
                $export_data['page_no'] = $n;
                $order_data = $this->finance->getList($export_data);
                foreach ($order_data['orders'] as $key => &$val){
                    $exnum = $this->finance->getExpress(array('order_id'=> $val['order_id']));
                    $val = $val + $val['order_detail'][0];
                    $val = $val + $exnum;
                    unset($val['order_detail']);
                }
                if($n == 1){
                    $title=array('订单ID','订单号','商品总价','优惠金额','实付金额','运费','店铺ID','购买人ID','省份','城市','地区','详细地址','邮政编码','收货人的姓名','收货人的手机号码','外部交易编号',
                                '订单状态','支付类型','付款时间','订单创建时间','订单更新时间','订单明细ID','商品ID','商品货号','SKU_ID','外部SKU号','SKU详情','商品标题','商品主图片地址','购买数量',
                                '已发货数量','商品售价','商品支付单价','预售商品预发货时间','是否预售','快递公司','快递单号','快递状态');
                    Base::export($order_data['orders'], $title);
                }else{
                    Base::export($order_data['orders']);
                }
                
                if($order_data['has_next'] == 0){
                    exit;
                }
                
            }while(1);
        }
        
        $count = $orderList['total_nums'];
        
        $this->renderPagger($p ,$count , "/bpfinance/index/p/{p}?number={$mobile}&order_no={$order_id}&status={$status}&showcase={$showcase_id}&start_time={$time_start}&end_time={$time_end}", $page_size);
        $this->assign('mobile', $mobile);
        $this->assign('order_no', $order_id);
        $this->assign('showcase', $showcase_id);
        $this->assign('start_time', $time_start);
        $this->assign('end_time', $time_end);
        $this->assign("list", $orderList['orders']);
        $this->layout("platform/finance.phtml");
    }
    
    /**
     * 获取详情
     */
    function infoAction() {

        $this->checkRole();
        $order_id = $this->getRequest()->get('id');
        $showcase_id = $this->getRequest()->get('showcase_id');
        
        $info = $this->finance->getInfoById($order_id);
        $sname = $this->finance->getShowcaseName(array('showcase_id'=>$showcase_id));
        $info['showcase_name'] = $sname;
        $this->assign("oinfo", $info);
        $this->layout("platform/order_info.phtml");
    }

}
