<?php
namespace Admin\Controller;

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
        } elseif ($order_status['trade_state'] == "SUCCESS" && $order_status['result_code'] == "SUCCESS") {
            $resuslt = ['code'=>1,msg=>'支付成功!','data'=>$order_status];
        } elseif ($order_status['return_code'] == "SUCCESS" && $order_status['trade_state'] == "REFUND") {
            $resuslt = ['code'=>2,msg=>'退款的支付!','data'=>$order_status];
        } else {
            $resuslt = ['code'=>3,msg=>'其他!','data'=>$order_status];
        }
        $this->ajaxReturn($resuslt);
    }

    public function getRefund()
    {

        $status = ['SUCCESS'=>'退款成功','REFUNDCLOSE'=>'退款关闭','PROCESSING'=>'退款处理中','CHANGE'=>'退款异常，退款到银行发现用户的卡作废或者冻结了，导致原路退款银行卡失败'];
        $channel = ['ORIGINAL'=>'原路退款','BALANCE'=>'退回到余额','OTHER_BALANCE'=>'原账户异常退到其他余额账户','OTHER_BANKCARD'=>'原银行卡异常退到其他银行卡'];

        $did = $_REQUEST["transaction_id"];
        $transaction_id = $this->order->where('order_sn ='.$did)->getField('trade_no');

        // $input = new \WxPayOrderQuery();
        // $input->SetTransaction_id($transaction_id);
        // $order_status = \WxPayApi::orderQuery($input);

        $input = new \WxPayRefundQuery();
        $input->SetTransaction_id($transaction_id);
        $refund_status = \WxPayApi::refundQuery($input);

        $refund_status['did'] = $did;
        //$refund_status['total_fee'] = $refund_status['total_fee'] / 100;
        $refund_status['total_fee'] = $refund_status['refund_fee_0'] / 100;
        $refund_status['status'] = $status[$refund_status['refund_status_0']];
        $refund_status['user'] = $channel[$refund_status['refund_channel_0']];


        if ($refund_status['return_code'] == "FAIL") {
            $resuslt = ['code'=>0,msg=>$refund_status['return_msg'],'data'=>$refund_status['return_msg']];
        } elseif ($refund_status['return_code'] == "SUCCESS" && $refund_status['result_code'] == "SUCCESS") {
            $resuslt = ['code'=>1,msg=>'退款成功!','data'=>$refund_status];
        } else {
            $resuslt = ['code'=>2,msg=>'其他!','data'=>$refund_status];
        }
        $this->ajaxReturn($resuslt);
    }


    public function downloadbill()
    {
        if (IS_POST) {
            if (isset($_REQUEST["bill_date"]) && $_REQUEST["bill_type"] != "") {
                $bill_date = $_REQUEST["bill_date"];
                $bill_type = $_REQUEST["bill_type"];
                $input = new \WxPayDownloadBill();
                $input->SetBill_date($bill_date);
                $input->SetBill_type($bill_type);
                $file = \WxPayApi::downloadBill($input);
                echo $file;
                //TODO 对账单文件处理
                exit(0);
            }
        }

        $bc = ['财务管理','下载对账单'];
        $this->assign('bc', $bc);
        $this->display();
    }
}
