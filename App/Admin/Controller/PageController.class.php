<?php
namespace Admin\Controller;

use Think\Controller;

vendor("MysqlDate.MysqlDate");
use MysqlDate\MysqlDate;

vendor("Carbon.Carbon");
use Carbon\Carbon;

class PageController extends PublicController
{
    private $order;     //订单模型类
    private $mysqlDate; //时间段类
    private $user;

    public function __construct()
    {
        parent::__construct();
        $this->mysqlDate =  new MysqlDate();
        $this->order = M("Order");
        $this->user = M("User");
    }

    public function adminindex()
    {
        //echo Carbon::now()->startOfDay()->timestamp;
        $todayAmount = $this->todayAmount();    //今日销售金额
        $todayCount = $this->todayCount();      //今日销量总数
        $userCount = $this->userCount();        //用户统计
        $productCount = $this->productCount();  //商品统计

        $orderStatus = $this->orderStatus();

        $this->assign('todayAmount', $todayAmount);
        $this->assign('todayCount', $todayCount);
        $this->assign('userCount', $userCount);
        $this->assign('productCount', $productCount);
        $this->assign('orderStatus', $orderStatus);
        $this->display();
    }
    public function shopindex()
    {
        $this->display();
    }

    /**
     * [todayAmount 今天销售总额]
     * @return [int/string] [金额]
     */
    public function todayAmount()
    {
        $map['addtime']  = array('between',$this->mysqlDate->todadyPeriod());
        $sum = $this->order->where($map)->cache(true, 60)->sum('price');
        return $sum ? cutMoney($sum) : 0;
    }

    /**
     * [todayCount 今日销量总数]
     * @return [int] [总数]
     */
    public function todayCount()
    {
        $map['addtime']  = array('between',$this->mysqlDate->todadyPeriod());
        $count = $this->order->where($map)->cache(true, 60)->count();
        return $count;
    }

    /**
     * [userCount 用户统计]
     * @return [int] [统计数字]
     */
    public function userCount()
    {
        //$map['addtime']  = array('between',$this->mysqlDate->todadyPeriod());
        $count = $this->user->cache(true, 60)->count();
        return $count;
    }

    /**
     * [productCount 商品统计]
     * @return [int] [统计数字]
     */
    public function productCount()
    {
        //$map['addtime']  = array('between',$this->mysqlDate->todadyPeriod());
        $count = M('Product')->cache(true, 60)->count();
        return $count;
    }


    /**
     * [orderStatus 所有订单状态]
     * @return [array] [状态数量及标识]
     */
    public function orderStatus()
    {
        $list = [];
        /**
            TODO:
            - 这个订单状态很混乱等下次更改的时候 好好整理下 目前先这么写
            - 这是原本订单状态选项 取消好像没什么意义 看看和那个合并下
            - $order_status = array('0' => '已取消', '10' => '待付款', '20' => '待发货', '30' => '待收货', '40' => '待评价', '50' => '交易完成', '51' => '交易关闭');
            - 还有个问题是在于 当订单多的时候 考虑改成时间段读取 要不多去所有订单实在太耗资源了
         */
        
        $order_status = array('10' => '待付款', '20' => '待发货', '30' => '待收货', '40' => '待评价', '50' => '交易完成', '51' => '交易关闭' ,'back'=>'退款中');
        foreach ($order_status as $k => $v) {
            if ($k == 'back') {
                $count = $this->order->where(['back'=>'1'])->cache(true, 60)->count();
            } else {
                $count =  $this->order->where(['status'=>$k])->cache(true, 60)->count();
            }

            $list[] = ['title'=>$v,'count'=>$count];
        }
        return $list;
    }

    /**
     * array(7) {
  [0] => array(3) {
    ["title"] => string(9) "待付款"
    ["count"] => string(2) "26"
    ["key"] => int(10)
  }
  [1] => array(3) {
    ["title"] => string(9) "待发货"
    ["count"] => string(2) "95"
    ["key"] => int(20)
  }
  [2] => array(3) {
    ["title"] => string(9) "待收货"
    ["count"] => string(1) "0"
    ["key"] => int(30)
  }
  [3] => array(3) {
    ["title"] => string(9) "待评价"
    ["count"] => string(1) "2"
    ["key"] => int(40)
  }
  [4] => array(3) {
    ["title"] => string(12) "交易完成"
    ["count"] => string(3) "163"
    ["key"] => int(50)
  }
  [5] => array(3) {
    ["title"] => string(12) "交易关闭"
    ["count"] => string(1) "4"
    ["key"] => int(51)
  }
  [6] => array(3) {
    ["title"] => string(9) "退款中"
    ["count"] => string(1) "2"
    ["key"] => string(4) "back"
  }
}
     */
}
