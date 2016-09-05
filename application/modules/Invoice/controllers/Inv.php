<?php
/**
 * @auth why
 * @explain 生活作风电子发票
 */
class InvController extends Base
{
    use Trait_Layout;

    /** @var  ShzfInvModel */
    private $shzfInvModel;
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
    }

    public function listAction()
    {
        $page_no = $this->input_get_param('page_no', 1,'abs');
        $page_size = $this->input_get_param('page_size', 20,'abs');
        $order_id = $this->input_get_param('order_id');
        $mobile = $this->input_get_param('mobile');

        $result = $this->shzfInvModel->getList($page_no, $page_size, $mobile, $order_id);
        $this->assign('data', $result);
        $this->assign('state_name', $this->state_name);
        $this->layout("inv/list.phtml");
    }

    /**
     * @explain 开具发票
     */
    public function issuedAction()
    {
        $orders = $this->getRequest()->getPost('orders');
        $marchatName = $this->input_post_param('marchatName');
        $drawer = $this->input_post_param('drawer');
        $payee = $this->input_post_param('payee');
        $review = $this->input_post_param('review');
        $address = $this->input_post_param('address');
        $id = json_decode($this->getRequest()->get('data'))['id'];

        if (is_array($orders)) {
            $this->batchIssueInv($orders, $marchatName, $drawer, $payee, $review, $address);
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
        $this->shzfInvModel->update($id, $params);
        Tools::output(array('info' => '开票申请已经提交,请稍后查看', 'status' => 1));
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
        echo json_encode(array('info' => '批量开票申请已经提交,请稍后查看', 'status' => 1));exit;
    }

    /**
     * @explain 开具红票
     */
    public function redInvoiceAction()
    {
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
            'blue_invoice_id'  => $info['id'],
            'one_tax'          => $info['one_tax'],
            'two_tax'          => $info['two_tax'],
            'three_tax'        => $info['three_tax'],
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