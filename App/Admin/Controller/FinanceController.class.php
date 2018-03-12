<?php
namespace BackendAdmin\Controller;

use Think\Controller;

class FinanceController extends PublicController
{

    /*
    *
    * 构造函数，用于导入外部文件和公共方法
    */
    public function _initialize()
    {
        $this->order = M('Order');

        $this->order_product = M('Order_product');

        // // $order_status = array('10' => '待付款', '20' => '待发货', '30' => '待收货', '40' => '已收货', '50' => '交易完成');
        // //$order_status = array('0' => '已取消', '10' => '待付款', '20' => '待发货', '30' => '待收货', '40' => '待评价', '50' => '交易完成', '51' => '交易关闭');
        // $order_status = C('ORDER_STATUS');
        // unset($order_status['back']);

        // $this->assign('order_status', $order_status);

        vendor('WeiXinpay.wxpay');
    }

    public function check()
    {
        $bc = ['财务管理','查询订单支付'];
        $this->assign('bc', $bc);
        // $a = \Think\Log::write('[Wechat Transaction] 订单:');
        // dump($a);
        $this->display();
    }

    public function getData()
    {

        $status = ['SUCCESS'=>'支付成功','REFUND'=>'转入退款','NOTPAY'=>'未支付','CLOSED'=>'已关闭','PAYERROR'=>'支付失败(其他原因，如银行返回失败)'];

        $did = $_REQUEST["transaction_id"];
        $transaction_id = $this->order->where('order_sn ='.$did)->getField('trade_no');
        $input = new \WxPayOrderQuery();
        $input->SetTransaction_id($transaction_id);
        $order_status = \WxPayApi::orderQuery($input);
        
        $order_status['did'] = $did;
        $order_status['total_fee'] = $order_status['total_fee'] / 100;
        $order_status['status'] = $status[$order_status['trade_state']];
        $order_status['user'] = M('user')->where('openid = "'.$order_status['openid'].'"')->getField('name');


        if ($order_status['return_code'] == "FAIL") {
            $resuslt = ['code'=>0,msg=>'尚未支付!','data'=>$order_status['return_msg']];
        } elseif ($order_status['return_code'] == "SUCCESS" && $order_status['result_code'] == "SUCCESS") {
            $resuslt = ['code'=>1,msg=>'支付成功!','data'=>$order_status];
        } elseif ($order_status['return_code'] == "SUCCESS" && $order_status['trade_state'] == "REFUND") {
            $resuslt = ['code'=>2,msg=>'退款的支付!','data'=>$order_status];
        } else {
            $resuslt = ['code'=>3,msg=>'其他!','data'=>$order_status];
        }
        $this->ajaxReturn($resuslt);
    }

    public function printf_info($data)
    {
        foreach ($data as $key => $value) {
            echo "<font color='#f00;'>$key</font> : $value <br/>";
        }
    }
}
