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
                $file = explode("\r\n", $file);
                if ($file[0] != "") {
                    $this->dealWechat($file, $_REQUEST["bill_type"]);
                } else {
                    $this->error('没有相应的对账单可供下载', '', 1.5);
                }
                exit(0);
            }
        }

        //dump($this->deal_wechat_return_result($file));
        //$this->deal_wechat_return_result($file);

        $bc = ['财务管理','下载对账单'];
        $this->assign('bc', $bc);
        $this->display();
    }

    public function dealWechat($arr, $title)
    {
        $cellName = explode(",", $arr[0]);
        $data =  str_replace(",", " ", array_slice($arr, 1, count($arr)-3));    //订单数据
        $total =  str_replace(",", " ", array_slice($arr, -3, 2));              //统计数据  网上记得改成3
        $data_nums = count($data);  //行数 用于计算统计起始行

        //引入核心文件
        vendor("PHPExcel.PHPExcel");
        $objPHPExcel = new \PHPExcel();
        //定义配置
        $topNumber = 2;//表头在第几行
        $xlsTitle = iconv('utf-8', 'gb2312', $title);//文件名称
        $fileName = $title.date('_YmdHis');//文件名称
        $cellKey = array(
                'A','B','C','D','E','F','G','H','I','J','K','L','M',
                'N','O','P','Q','R','S','T','U','V','W','X','Y','Z',
                'AA','AB','AC','AD','AE','AF','AG','AH','AI','AJ','AK','AL','AM',
                'AN','AO','AP','AQ','AR','AS','AT','AU','AV','AW','AX','AY','AZ'
                );
        //标题头设置高度
        $objPHPExcel->getActiveSheet()->getRowDimension('1')->setRowHeight(30);//设置行高度

        //处理表头标题
        $objPHPExcel->getActiveSheet()->mergeCells('A1:'.$cellKey[count($cellName) -1 ].'1');//合并单元格（如果要拆分单元格是需要先合并再拆分的，否则程序会报错）
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A1', $title);
        $objPHPExcel->getActiveSheet()->getStyle('A1')->getFont()->setBold(true);
        $objPHPExcel->getActiveSheet()->getStyle('A1')->getFont()->setSize(18);
        $objPHPExcel->getActiveSheet()->getStyle('A1')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $objPHPExcel->getActiveSheet()->getStyle('A1')->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
    
        //处理表头
        foreach ($cellName as $k => $v) {
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue($cellKey[$k].$topNumber, $v);//设置表头数据
            //$objPHPExcel->getActiveSheet()->freezePane($cellKey[$k].($topNumber+1));//冻结窗口
            $objPHPExcel->getActiveSheet()->getColumnDimension($cellKey[$k])->setWidth(30);//设置列宽度
            $objPHPExcel->getActiveSheet()->getStyle($cellKey[$k].$topNumber)->getFont()->setBold(true);//设置是否加粗
            $objPHPExcel->getActiveSheet()->getStyle($cellKey[$k].$topNumber)->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);//垂直居中
        }
        //处理数据
        foreach ($data as $key => $value) {
            $cell = explode("`", $value);
            array_shift($cell);
            foreach ($cell as $k => $v) {
                $objPHPExcel->getActiveSheet()->setCellValue($cellKey[$k].($key+1+$topNumber), $v);
            }
        }

        //处理统计数据
        foreach ($total as $key => $value) {
            if ($key ==0) {
                $cell = explode(" ", $value);
                foreach ($cell as $k => $v) {
                    $objPHPExcel->getActiveSheet()->setCellValue($cellKey[$k].($data_nums+2+$topNumber), $v);
                    $objPHPExcel->getActiveSheet()->getStyle($data_nums+2+$topNumber)->getFont()->setBold(true);//设置是否加粗
                    $objPHPExcel->getActiveSheet()->getStyle($data_nums+2+$topNumber)->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);//垂直居中
                }
            } else {
                $cell = explode("`", $value);
                //dump($cell);
                array_shift($cell);
                //dump($cell);
                foreach ($cell as $k => $v) {
                    $objPHPExcel->getActiveSheet()->setCellValue($cellKey[$k].($data_nums+3+$topNumber), $v);
                }
            }
        }
        //exit();


        //导出execl
        header('pragma:public');
        header('Content-type:application/vnd.ms-excel;charset=utf-8;name="'.$xlsTitle.'.xls"');
        header("Content-Disposition:attachment;filename=$fileName.xls");//attachment新窗口打印inline本窗口打印
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
        exit;

        //这是另一种玩法 可以根据单元个数 指定输出的信息 因为 当日所有订单 和 当日退款的订单 头部信息不同 所以这个固定的 24 个单元 要小心 要根据选项而修改才行
        // $reponse = explode("\r\n", $reponse);
        // dump($reponse);
        // $result = array();
        // $reponse = str_replace(",", " ", $reponse);
        // $reponse = explode("`", $reponse);
        // $total_order_count =(count($reponse) - 6) / 24;
        // for ($i = 0; $i< $total_order_count; $i++) {
        //     $base_index = 24 * $i;
        //     $result[$reponse[$base_index + 7]] = array(
        //         'wechat_order_no' => $reponse[$base_index + 6],
        //         'order_count' => $reponse[$base_index + 13],
        //         'order_discount' => $reponse[$base_index + 23]
        //     );
        // }
        //return $result;
    }

    public function apitest()
    {
        $input = new \WxApiTest();
        $refund_status = \WxPayApi::apiTest($input);
    }
}
