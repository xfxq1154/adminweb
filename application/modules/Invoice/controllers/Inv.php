<?php
/**
 * @auth why
 * @explain 生活作风电子发票
 */
class InvController extends Base
{
    use Trait_Layout;
    use Trait_Pagger;

    /** @var  ShzfInvModel */
    private $shzfInvModel;
    /** @var  ShzfSkuModel */
    private $shzfSkuModel;

    public $state_name = [
        1 => '<span class="tag bg-green">未开发票</span>',
        2 => '<span class="tag bg-yellow">开票成功</span>',
        3 => '<span class="tag bg-blue">开票失败</span>',
        4 => '<span class="tag bg-bg-mix">已发短信</span>',
        5 => '<span class="tag bg-bg-blue">开票中</span>',
    ];

    public function init() {
        $this->initAdmin();
        $this->shzfInvModel = new ShzfInvModel();
        $this->shzfSkuModel = new ShzfSkuModel();
    }

    /**
     * inv list
     */
    public function listAction()
    {
        $this->checkRole();
        $page_no = $this->getRequest()->getParam('page_no', 1);
        $order_id = $this->input_get_param('order_id');
        $mobile = $this->input_get_param('mobile');
        $month = $this->input_get_param('time');

        $result = $this->shzfInvModel->getList($page_no, 20, $mobile, $order_id, $month);
        $this->renderPagger($page_no, $result['total_nums'], '/invoice/inv/list/page_no/{p}?order_id='.$order_id.'&mobile='.$mobile.'&time='.$month, 20);
        $this->assign('data', $result);
        $this->assign('search', ['time' => $month,'mobile' => $mobile, 'order_id' => $order_id]);
        $this->assign('state_name', $this->state_name);
        $this->layout("inv/list.phtml");
    }

    /**
     * sku list
     */
    public function skuListAction()
    {
        $this->checkRole();
        $page_no = $this->getRequest()->getParam('page_no', 1);
        $sku_id = $this->input_get_param('sku_id');

        $result = $this->shzfSkuModel->getList($page_no, 20, $sku_id);
        $this->renderPagger($page_no, $result['total_nums'], '/invoice/inv/skulist/page_no/{p}', 20);
        $this->assign('data', $result);
        $this->layout('inv/skulist.phtml');
    }

    /**
     * @explain 税率
     */
    public function taxAction()
    {
        $this->checkRole();
        $orders = $this->input_getpost_param('orderlist');
        $fpsl = $this->input_getpost_param('sl');
        if(!$orders){
            Tools::output(array('msg' => '请先勾选编码', 'status' => 2));
        }
        //截取最后一个符号
        $ordersing = substr($orders,0, -1);
        //更新多个数据到数据表
        $rs = $this->shzfSkuModel->updateSl($ordersing, $fpsl);
        if(!$rs){
            echo json_encode(array('msg' => '修改失败'));exit;
        }
        echo json_encode(array('msg' => '修改成功', 'status' => 2));exit;
    }

    /**
     * @explain 删除订单
     */
    public function delAction()
    {
        $this->checkRole();
        $data = $this->getRequest()->get('data');
        $id = json_decode($data, true)['id'];

        $result = $this->shzfInvModel->delete($id);
        if(!$result){
            echo json_encode(['info' => '删除失败', 'status' => 0]);exit;
        }
        echo json_encode(['info' => '删除成功', 'status' => 1]);exit;
    }

    /**
     * @explain 删除sku
     */
    public function delSkuAction()
    {
        $this->checkRole();
        $data = $this->getRequest()->get('data');
        $id = json_decode($data, true)['id'];

        $result = $this->shzfSkuModel->delete($id);
        if(!$result){
            echo json_encode(['info' => '删除失败', 'status' => 0]);exit;
        }
        echo json_encode(['info' => '删除成功', 'status' => 1]);exit;
    }

    /**
     * @explain 更改订单基本信息
     */
    public function editAction()
    {
        $this->checkRole();
        $id = $this->getRequest()->get('id');
        if($this->getRequest()->isPost()){
            $params = array();
            $params['buyer_phone'] = $this->getRequest()->getPost('buyer_phone');
            $params['title'] = $this->getRequest()->getPost('title');
            $params['order_id'] = $this->getRequest()->getPost('order_id');
            $params['buyer_tax_id'] = $this->getRequest()->getPost('buyer_tax_id');
            $i_id = $this->getRequest()->getPost('i_id');
            //更新
            $result = $this->shzfInvModel->update($i_id, $params);
            if(!$result){
                Tools::output(array('info' => '修改失败', 'status' => 0));
            }
            Tools::output(array('info' => '修改成功', 'status' => 1));
        }
        $i_info = $this->shzfInvModel->getInfo($id);
        $this->assign('data', $i_info);
        $this->layout('inv/update.phtml');
    }

    /**
     * @explain 重发短信
     */
    public function resendMsgAction()
    {
        $this->checkRole();
        $mobile = $this->getRequest()->getPost('phoneNum');
        $id = $this->getRequest()->getPost('id');

        if (!$id || !$mobile) {
            echo json_encode(['info' => '参数不合法']);exit;
        }
        if (!$this->isTelNumber($mobile)) {
            echo json_encode(['info' => '骚年,手机号格式不对!']);exit;
        }
        $info = $this->shzfInvModel->getInfo($id);
        $sms = new Sms();
        $message = '您好，您在罗辑思维所购产品的电子发票地址为:'.$info['invoice_url'].'。地址有效期为30天，请尽快在电脑端查看。';
        $status = $sms->sendmsg($message, $mobile);
        if($status['status'] == 'ok'){
            echo json_encode(['info' => '短信发送成功']);exit;
        }else{
            echo json_encode(['info' => '短信发送失败']);exit;
        }
    }

    /**
     * @param $phone
     * @return bool
     * @explain 校验手机号
     */
    private function isTelNumber($phone) {
        if (strlen ( $phone ) != 11 || ! preg_match ( '/^1[3|4|5|7|8][0-9]\d{4,8}$/', $phone )) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * @explain 单独添加发票
     */
    public function addInvAction()
    {
        $this->checkRole();
        if ($this->getRequest()->isPost()){
            $invoices = array();
            $invoices['buyer_phone'] = $this->getRequest()->getPost('buyer_phone');
            $invoices['order_id'] = $this->getRequest()->getPost('order_id');
            $invoices['buyer_tax_id'] = $this->getRequest()->getPost('buyer_tax_id');
            $invoices['title'] = $this->getRequest()->getPost('title');

            $result = $this->shzfInvModel->insert($invoices);
            if(!$result){
                Tools::output(array('info' => '添加失败', 'status' => 0));
            }
            Tools::output(array('info' => '添加成功', 'status' => 1));
        }
        $this->layout('inv/addInv.phtml');
    }

    /**
     * @explain 开具发票
     */
    public function issuedAction()
    {
        $this->checkRole();
        $orders = $this->getRequest()->getPost('orders');
        $kj_id = $this->getRequest()->getPost('id');
        $marchatName = $this->input_post_param('marchatName');
        $drawer = $this->input_post_param('drawer');
        $payee = $this->input_post_param('payee');
        $review = $this->input_post_param('review');
        $address = $this->input_post_param('address');

        if (!empty($orders) && is_array($orders)) {
            $this->batchIssueInv($orders, $marchatName, $drawer, $payee, $review, $address);
        }
        if (empty($orders) && empty($kj_id)) {
            Tools::output(array('info' => '请勾选要开票的订单'));
        }
        //单个发票开具
        $params = [
            'seller_address' => $address,
            'seller_name' => $marchatName,
            'drawer' => $drawer,
            'state' => 5,
            'review' => $review,
            'payee' => $payee
        ];
        $this->shzfInvModel->update($kj_id, $params);
        Tools::output(array('info' => '开票申请已经提交,请稍后查看'));
    }

    /**
     * @param $orders
     * @param $marchatName
     * @param $drawer
     * @param $payee
     * @param $review
     * @param $address
     * @explain 批量开票
     */
    private function batchIssueInv($orders, $marchatName, $drawer, $payee, $review, $address)
    {
        $orderdata = array_filter($orders);
        foreach ($orderdata as $value){
            $params = [
                'seller_address' => $address,
                'seller_name' => $marchatName,
                'drawer' => $drawer,
                'state' => 5,
                'payee' => $payee,
                'review' => $review
            ];
            $this->shzfInvModel->update($value['id'],$params);
        }
        echo json_encode(array('info' => '批量开票申请已经提交,请稍后查看'));exit;
    }

    /**
     * @explain 开具红票
     */
    public function redInvoiceAction()
    {
        $this->checkRole();
        $data = $this->getRequest()->get('data');
        $id = json_decode($data,true)['id'];

        $info = $this->shzfInvModel->getInfo($id);
        if($info['type'] == 1 && $info['state'] == 4){
            echo json_encode(array('info' => '已经开具的红票,无法重新开具', 'status' => 0));exit;
        }
        $params = array(
            'seller_name'      => $info['seller_name'],
            'seller_address'   => $info['seller_address'],
            'title'            => $info['title'],
            'drawer'           => $info['drawer'],
            'payee'            => $info['payee'],
            'review'           => $info['review'],
            'buyer_tax_id'     => $info['buyer_tax_id'],
            'order_id'         => $info['order_id'].'RED',
            'jshj'             => $info['jshj'],
            'total_fee'        => $info['total_fee'],
            'total_tax'        => $info['total_tax'],
            'blue_inv_id'      => $info['id'],
            'one_tax'          => $info['one_tax'],
            'two_tax'          => $info['two_tax'],
            'three_tax'        => $info['three_tax'],
            'one_fee'          => $info['one_fee'],
            'two_fee'          => $info['two_fee'],
            'three_fee'        => $info['three_fee'],
            'buyer_phone'      => $info['buyer_phone'],
            'invoice_number'   => $info['invoice_number'],
            'invoice_code'     => $info['invoice_code'],
            'type'             => 1,
            'state'            => 5
        );
        $this->shzfInvModel->insert($params);
        Tools::output(array('info' => '开票申请已经提交,请稍后查看', 'status' => 2));
    }
}