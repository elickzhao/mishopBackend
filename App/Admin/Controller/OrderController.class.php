<?php

namespace Admin\Controller;

class OrderController extends PublicController
{
    /*
    *
    * 构造函数，用于导入外部文件和公共方法
    */

    public function _initialize()
    {
        $this->order = M('Order');

        $this->order_product = M('Order_product');

        $order_status = C('ORDER_STATUS');
        unset($order_status['back']);

        $this->assign('order_status', $order_status);
    }

    /*

    *

    * 获取、查询所有订单数据

    */

    public function index()
    {
        //搜索

        //获取商家id

        if (4 != intval($_SESSION['admininfo']['qx'])) {
            $shop_id = intval(M('adminuser')->where('id='.intval($_SESSION['admininfo']['id']))->getField('shop_id'));

            if (0 == $shop_id) {
                $this->error('非法操作.');
            }
        } else {
            $shop_id = intval($_REQUEST['shop_id']);
        }

        $pay_type = trim($_REQUEST['pay_type']); //支付类型

        $pay_status = intval($_REQUEST['pay_status']); //订单状态

        $receiver = $_REQUEST['receiver']; //订单状态

        $tel = intval($_REQUEST['tel']); //订单状态

        //构建搜索条件

        $condition = array();

        $condition['del'] = 0;

        $where = '1=1 AND del=0';

        //根据支付类型搜索

        if ($pay_type) {
            $condition['type'] = $pay_type;

            $where .= ' AND type=\''.$pay_type.'\'';

            //搜索内容输出

            $this->assign('pay_type', $pay_type);
        }

        //根据订单状态搜索

        if ($pay_status) {
            if ($pay_status < 10) {
                //小于10的为退款

                $condition['back'] = $pay_status;

                $where .= ' AND back='.intval($pay_status);
            } else {
                //大于10的为正常订单

                $condition['status'] = $pay_status;

                $where .= ' AND status='.intval($pay_status);
            }

            //搜索内容输出

            $this->assign('pay_status', $pay_status);
        }

        //根据下单时间搜索

        if ($receiver) {
            //$condition['receiver'] = array('gt', $receiver);

            $where .= ' AND receiver = " '.$receiver.'"';

            //搜索内容输出

            $this->assign('receiver', $receiver);
        }

        //根据下单时间搜索

        if ($tel) {
            //$condition['tel'] = array('lt', $tel);

            $where .= ' AND tel ='.$tel;

            //搜索内容输出

            $this->assign('tel', $tel);
        }

        /*if ($start_time && $end_time) {

            $condition['addtime'] = array('eq','addtime>'.$start_time.' AND addtime<='.$end_time);

        }*/

        //分页

        $count = $this->order->where($where)->count(); // 查询满足要求的总记录数

        $Page = new \Think\Page($count, 25); // 实例化分页类 传入总记录数和每页显示的记录数(25)

        //分页跳转的时候保证查询条件

        foreach ($condition as $key => $val) {
            $Page->parameter[$key] = urlencode($val);
        }

        if ($start_time && $end_time) {
            $addtime = 'addtime>'.$start_time.' AND addtime<'.$end_time;

            $Page->parameter['addtime'] = urlencode($addtime);
        }

        //头部描述信息，默认值 “共 %TOTAL_ROW% 条记录”

        $Page->setConfig('header', '<li class="rows">共<b>%TOTAL_ROW%</b>条&nbsp;第<b>%NOW_PAGE%</b>页/共<b>%TOTAL_PAGE%</b>页</li>');

        //上一页描述信息

        $Page->setConfig('prev', '上一页');

        //下一页描述信息

        $Page->setConfig('next', '下一页');

        //首页描述信息

        $Page->setConfig('first', '首页');

        //末页描述信息

        $Page->setConfig('last', '末页');

        /*

        * 分页主题描述信息

        * %FIRST%  表示第一页的链接显示

        * %UP_PAGE%  表示上一页的链接显示

        * %LINK_PAGE%  表示分页的链接显示

        * %DOWN_PAGE%  表示下一页的链接显示

        * %END%   表示最后一页的链接显示

        */

        $Page->setConfig('theme', '%FIRST%%UP_PAGE%%LINK_PAGE%%DOWN_PAGE%%END%%HEADER%');

        $show = $Page->show(); // 分页显示输出

        // 进行分页数据查询 注意limit方法的参数要使用Page类的属性

         $order_list = $this->order->where($where)->order('id desc')->limit($Page->firstRow.','.$Page->listRows)->select();

        //echo $this->order->getlastsql();

        foreach ($order_list as $k => $v) {
            $order_list[$k]['u_name'] = M('user')->where('id='.intval($v['uid']))->getField('name');
        }

        $bc = ['订单管理','全部订单'];
        $this->assign('bc', $bc);

        $this->assign('order_list', $order_list); // 赋值数据集

        $this->assign('page', $show); // 赋值分页输出

        $this->assign('admin_qx', $_SESSION['admininfo']['qx']); //后台用户权限，目前设置为超级管理员权限

        $this->display(); // 输出模板
    }


    /**
     * [getOrders ajax获取订单列表]
     * @return [json] [订单数据]
     */
    public function getOrders()
    {
        $where="1=1 AND del<1 ";

        // if ($_GET['cid'] == 0) {
        //     unset($_GET['cid']);
        // }
        
        //搜索优先级查询
        $arr = ['order_sn','tel','pay_status','type'];
        foreach ($arr as $key => $value) {
            if ($_GET[$value] != '') {
                if ($value == 'pay_status') {
                    if ($_GET['pay_status'] == 1 || $_GET['pay_status'] == 2) {
                        $where .= ' AND back = "'. $_GET[$value].'"';
                    } else {
                        $where .= ' AND back="0" AND status = "'. $_GET[$value].'"';
                    }
                } else {
                    $where .= ' AND '.$value.' = "'. $_GET[$value].'"';
                }
                //break;
            }
        }



        $count=M('order')->where($where)->count();
        $rows=ceil($count/rows);
        $page = (int) -- $_GET['page'] ;
        $rows = $_GET['limit'] ? $_GET['limit'] : 20;
        $limit= $page*$rows;
        $orderlist=M('order')->where($where)->order('id desc')->limit($limit, $rows)->select();
        foreach ($orderlist as $k => $v) {
            $orderlist[$k]['u_name'] = M('user')->where('id='.intval($v['uid']))->getField('name');
        }
        //$sql = M('order')->getlastsql();
        //$resuslt = [code=>0,msg=>'',count=>$count,data=>$orderlist,sql=>$sql];
        $resuslt = [code=>0,msg=>'',count=>$count,data=>$orderlist];

        $this->ajaxReturn($resuslt);
    }


    /*
    *
    * 选择商家里面的省市联动
    */

    public function get_city()
    {
        $id = (int) $_GET['id'];

        $data = M('china_city')->where('tid='.intval($id))->field('id,name')->select();

        $i = 0;

        $array = array();

        foreach ($data as $v) {
            $array[$i]['id'] = $v['id'];

            $array[$i]['name'] = $v['name'];

            $i += 1;
        }

        echo json_encode($array);
    }

    /*

    *

    * 查看订单详情

    */

    public function show()
    {
        //获取传递过来的id

        $order_id = intval($_GET['oid']);

        if (!$order_id) {
            $this->error('系统错误.');
        }

        //根据订单id获取订单数据还有商品信息
        $order_info = $this->order->where('id='.intval($order_id))->find();
        //获取会员名字
        $order_info['uname'] = M('user')->where('id='.intval($order_info['uid']))->getField('name');
        //因暂时无阿里支付 所以暂时这么写
        $order_info['type'] = ($order_info['type'] == 'weixin') ? '微信支付' : "线下支付" ;
        

        $order_pro = $this->order_product->where('order_id='.intval($order_id))->select();

        if (!$order_info || !$order_pro) {
            $this->error('订单信息错误.');
        }

        foreach ($order_pro as $k => $v) {
            $data = array();

            $data = unserialize($v['pro_guide']);

            if ($data) {
                $order_pro[$k]['g_name'] = $data['gname'];
            } else {
                $order_pro[$k]['g_name'] = '无';
            }
        }



        $post_info = array();

        if (intval($order_info['post'])) {
            $post_info = M('post')->where('id='.intval($order_info['post']))->find();
        }


        $bc = ['订单管理','订单详情'];
        $this->assign('bc', $bc);
        $this->assign('post_info', $post_info);

        $this->assign('order_info', $order_info);

        $this->assign('order_pro', $order_pro);

        $this->display();
    }

    /*

    *

    * 修改订单状态，添加物流名称、物流单号

    */

    public function sms_up()
    {
        $oid = intval($_POST['oid']);

        $o_info = $this->order->where('id='.intval($oid))->find();

        if (!$o_info) {
            $arr = array();

            $arr = array('returns' => 0, 'message' => '没有找到相关订单.');

            echo json_encode($arr);

            exit();
        }

        //接收ajax传过来的值

        $order_status = intval($_POST['order_status']);

        $kuaidi_name = $_POST['kuaidi_name'];

        $kuaidi_num = $_POST['kuaidi_num'];

        if ($o_info['kuaidi_name'] == $kuaidi_name && $o_info['kuaidi_num'] == $kuaidi_num && intval($o_info['status']) == $order_status) {
            $arr = array();

            $arr = array('returns' => 0, 'message' => '修改信息未发生变化.');

            echo json_encode($arr);

            exit();
        }

        try {
            if (('' == $kuaidi_name || '' == $kuaidi_num) && 30 == $order_status) {
                throw new Exception('参数不正确');
            }

            /*$msg = '您的订单（编号:%s）,已发货，送货快递:%s，运单号:%s 【%s】';

            $msg = sprintf($msg,$id,$kuaidi_name,$kuaidi_num,$partner_info['name']);*/

            //修改快递信息

            $data = array();

            if ($order_status) {
                $data['status'] = $order_status;
            }

            if ($kuaidi_name) {
                $data['kuaidi_name'] = $kuaidi_name;
            }

            if ($kuaidi_num) {
                $data['kuaidi_num'] = $kuaidi_num;
            }

            $up = $this->order->where('id='.intval($oid))->save($data);

            $json = array();

            if ($up) {
                $json['message'] = '操作成功.';

                $json['returns'] = 1;
            } else {
                $json['message'] = '操作失败.';

                $json['returns'] = 0;
            }
        } catch (Exception $e) {
            $json = array('returns' => 0, 'message' => $e->getMessage());
        }

        echo json_encode($json);

        exit();
    }

    /*

    *

    *  确认退款  修改退款状态

    */

    public function back()
    {
        vendor('WeiXinpay.wxpay');

        $id = (int) $_GET['oid'];

        $back_info = $this->order->where('id='.intval($id))->find();

        if (!$back_info || 1 != intval($back_info['back'])) {
            $this->error('订单信息错误.');
        }

        \Think\Log::write('[Wechat Transaction] 订单:'.$back_info['order_sn'].' 申请退款. 金额:'.$back_info['price_h'], 'INFO ');

        $out_trade_no = $back_info['order_sn'];                 //订单号
        $total_fee = $back_info['price_h'] * 100;       //订单总金额 单位分
        $refund_fee = $back_info['price_h'] * 100;      //退款总金额 单位分

        $input = new \WxPayRefund();
        $input->SetOut_trade_no($out_trade_no);
        $input->SetTotal_fee($total_fee);
        $input->SetRefund_fee($refund_fee);
        $input->SetOut_refund_no(\WxPayConfig::MCHID.date('YmdHis'));
        $input->SetOp_user_id(\WxPayConfig::MCHID);
        $res = \WxPayApi::refund($input);

        /*
         * return_code 此字段是通信标识(SUCCESS/FAIL )
         * result_code 业务结果 (SUCCESS/FAIL SUCCESS退款申请接收成功，结果通过退款查询接口查询 / FAIL 提交业务失败)
         *
         */
        if ('SUCCESS' == $res['return_code'] && 'SUCCESS' == $res['result_code']) {
            //$res['cash_refund_fee'] 这里的金额是现金退款金额. 因为微信存在现金和代金券
            \Think\Log::write('[Wechat Transaction] 订单:'.$back_info['order_sn'].' 退款成功. 商户订单号:'.$res['out_trade_no'].' 微信订单号:'.$res['transaction_id'].' 微信退款单号:'.$res['refund_id'].' 金额:'.$res['cash_refund_fee'], 'INFO ');
        } elseif ('SUCCESS' != $res['return_code']) {
            \Think\Log::write('[Wechat Transaction] 订单:'.$back_info['order_sn'].' 退款失败. 失败信息:'.$res['return_msg'], 'INFO ');
            $this->error('申请退款失败! 请稍后再试!');
        } elseif ('SUCCESS' != $res['result_code']) {
            \Think\Log::write('[Wechat Transaction] 订单:'.$back_info['order_sn'].' 退款失败. 错误代码:'.$res['err_code'].' 错误信息:'.$res['err_code_des'], 'INFO ');
            $this->error('申请退款失败! 请查看支付账号金额!');
        }

        $data = array();

        $data['back'] = 2;

        $up_back = $this->order->where('id='.intval($id))->save($data);

        if ($up_back) {
            $this->success('操作成功.');
        } else {
            $this->error('操作失败.');
        }
    }
    

    /**
     * [cancelBack 取消退款]
     * @return [type] [description]
     */
    public function cancelBack()
    {
        $id = (int) $_GET['oid'];

        $back_info = $this->order->where('id='.intval($id))->find();

        if (!$back_info || 1 != intval($back_info['back'])) {
            $this->error('订单信息错误.');
        }

        
        $up_back = $this->order->where('id='.$id)->save(['back'=>0]); // 根据条件更新记录


        if ($up_back) {
            $this->success('操作成功.');
        } else {
            $this->error('操作失败.');
        }
    }
    

    /*

    *

    *  订单删除方法

    */

    public function del()
    {
        //以后删除还要加权限登录判断

        $id = intval($_GET['did']);

        $check_info = $this->order->where('id='.intval($id))->find();

        if (!$check_info) {
            $this->error('系统错误，请稍后再试.');
        }

        $up = array();

        $up['del'] = 1;

        $res = $this->order->where('id='.intval($id))->save($up);
        if ($res) {
            $this->ajaxReturn([code=>0,msg=>"操作成功 - ".$up]);
            //$this->success('操作成功.');
        } else {
            $this->ajaxReturn([code=>1,msg=>'操作失败!']);
            //$this->error('操作失败.');
        }
    }

    /*

    *

    *  订单统计功能

    */

    public function order_count()
    {
        //查询类型 d日视图  m月视图

        $type = $_GET['type'];

        //查询商家id

        $where = '1=1';

        //获取商家id

        if (4 != intval($_SESSION['admininfo']['qx'])) {
            $shop_id = intval(M('adminuser')->where('id='.intval($_SESSION['admininfo']['id']))->getField('shop_id'));

            if (0 == $shop_id) {
                $this->error('非法操作.');
            }
        } else {
            $shop_id = intval($_REQUEST['shop_id']);
        }

        if ($shop_id) {
            $where .= ' AND shop_id='.intval($shop_id);

            $shop_name = M('shangchang')->where('id='.intval($shop_id))->getField('name');

            $this->assign('shop_name', $shop_name);

            $this->assign('shop_id', $shop_id);
        }

        for ($i = 0; $i < 12; ++$i) {
            //日期

            if ('m' == $type) {
                $day = strtotime(date('Y-m')) - 86400 * 30 * (11 - $i);

                $dayend = $day + 86400 * 30;

                $day_String .= ',"'.date('Y/m', $day).'"';
            } else {
                $day = strtotime(date('Y-m-d')) - 86400 * (11 - $i);

                $dayend = $day + 86400;

                $day_String .= ',"'.date('m/d', $day).'"';
            }

            //$hyxl=select('id','aaa_pts_order',"1 $where and addtime>$day and addtime<$dayend",'num');

            $hyxl = $this->order->where($where.' AND addtime>'.$day.' AND addtime<'.$dayend)->count('id');

            $data1 .= ',['.$i.','.$hyxl.']';
        }

        $this->assign('data1', $data1);

        $this->assign('day_String', $day_String);

        //当天日期的时间戳

        $today = strtotime(date('Y-m-d'));

        $this->assign('today', $today);

        //获取最近订单数据

        $order_list = $this->order->where($where)->order('id desc')->limit('0,20')->select();

        foreach ($order_list as $k => $v) {
            $order_list[$k]['shop_name'] = M('shangchang')->where('id='.$v['shop_id'])->getField('name');
        }

        $this->assign('order_list', $order_list);

        $this->assign('type', $type);

        //print_r($where);die();

        $this->display();
    }
}
