<?php

/**
 * 订单
 *
 * @author yanbo
 */
class BpOrderController extends Base {

    use Trait_Layout,
        Trait_Pagger;
    

    public $order;
    public $showcase;

    public function init() {
        $this->initAdmin();
        $this->order = new BpOrderModel();
        $this->showcase = new BpShowcaseModel();
    }

    function indexAction() {
        $this->checkRole();
        $p = (int) $this->getRequest()->get('p' ,1);
        $number = $this->getRequest()->get('number');
        $order_no = $this->getRequest()->get('order_no');
        $showcase_id = $this->getRequest()->get('showcase');
        $status = $this->getRequest()->get('order_status');
        
        
        $state = $status ? $status : '';
        $mobile = $number ? $number : '';
        $order_id = $order_no ? $order_no : '';
        
        $page_size = 20;
        $order_list = [
            'page_no'=> $p,
            'page_size'=> $page_size,
            'mobile' => $mobile,
            'order_id' => $order_id,
            'status' => $state,
            'showcase_id' => $showcase_id
            
        ];
        $orderList = $this->order->getList($order_list);
        
        $showlist = $this->showcase->getList(array('page_no'=>1,'page_size'=>100));
        $idlist = [];
        $showcase = [];
        foreach ($showlist['showcases'] as $key=>$val){
            $idlist[$key]['id'] = $val['showcase_id'];
            $idlist[$key]['name'] = $val['name'];
            $showcase[$val['showcase_id']] = $val['name'];
        }
        
        $this->renderPagger($p ,$orderList['total_nums'] , "/BpOrder/index/p/{p}?number={$mobile}&order_no={$order_id}&order_status={$state}&showcase={$showcase_id}", $page_size);
        $this->assign('mobile', $mobile);
        $this->assign('order_no', $order_id);
        $this->assign('showcase', $showcase_id);
        $this->assign("list", $orderList['orders']);
        $this->assign('idlist', $idlist);
        $this->assign('name', $showcase);
        $this->layout("platform/order.phtml");
    }

    function editAction() {
        $this->checkRole();

        if (!$this->getrequest()->isPost()) {
            $order_id = $this->getrequest()->get('id');
            $order = $this->order->getInfoById($order_id);

            $this->assign("order", $order);
            $this->layout('platform/order_edit.phtml');
        } else {

            $params = $this->getrequest()->getPost();

            $result = $this->order->update($params);
            $msg = ($result === "") ? "修改成功" : "修改失败";
            $status = ($result === "") ? 1 : 0;
            echo json_encode(['info' => $msg, 'status' => $status]);
            exit;
        }
    }

    function deleteAction() {
        $this->checkRole();

//        $order_id = json_decode($this->getRequest()->getPost('data'), true)['id'];

//        $result = $this->order->delete($order_id);
        $msg = ($result === "") ? "删除成功" : "删除失败";
        $status = ($result === "") ? 1 : 0;
        echo json_encode(['info' => $msg, 'status' => $status]);
        exit;
    }

    function infoAction() {

        $this->checkRole();
        $order_id = $this->getRequest()->get('id');
        $showcase_id = $this->getRequest()->get('showcase_id');
        
        $info = $this->order->getInfoById($order_id);
        $sname = $this->order->getShowcaseName(array('showcase_id'=>$showcase_id));
        $info['showcase_name'] = $sname;
        $this->assign("oinfo", $info);
        $this->layout("platform/order_info.phtml");
    }

}
