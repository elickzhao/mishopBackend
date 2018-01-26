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
    private $user;      //用户模型
    private $product;       //用户模

    public function __construct()
    {
        parent::__construct();
        $this->mysqlDate =  new MysqlDate();
        $this->order = M("Order");
        $this->user = M("User");
        $this->product =M('Product');
    }

    public function adminindex()
    {
        //echo Carbon::now()->startOfDay()->timestamp;
        $todayAmount = $this->todayAmount();    //今日销售金额
        $todayCount = $this->todayCount();      //今日销量总数
        $userCount = $this->userCount();        //用户统计
        $productCount = $this->productCount();  //商品统计  //XXX 这个统计包括删除的商品
        

        $orderStatus = $this->orderStatus();    //订单状态统计
        $productStatus = $this->productStatus();//商品状态统计

        $this->assign('todayAmount', $todayAmount);
        $this->assign('todayCount', $todayCount);
        $this->assign('userCount', $userCount);
        $this->assign('productCount', $productCount);
        $this->assign('orderStatus', $orderStatus);
        $this->assign('productStatus', $productStatus);
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
        $count = $this->product->cache(true, 60)->count();
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
        
        $order_status = C('ORDER_STATUS');  //订单状态数组

        foreach ($order_status as $k => $v) {
            if ($k == 'back') {
                $count = $this->order->where(['back'=>'1'])->cache(true, 60)->count();
                $k = 1;
            } else {
                $count =  $this->order->where(['status'=>$k])->cache(true, 60)->count();
            }

            $list[] = ['title'=>$v,'count'=>$count,'key'=>$k];
        }
        return $list;
    }


    /**
     * [productStatus 商品状态]
     * @return [array] [状态统计]
     */
    public function productStatus()
    {
        /**
            TODO:
            - 因为数据库的问题 所以跳转到筛选页面还没做 以后有机会调整吧
         */
        
        $list = [];

        //出售中
        $count  = $this->product->where(['del'=>0])->count();
        $list[] = ['title'=>'出售中','count'=>$count];

        //推荐中
        $count  = $this->product->where(['type'=>1])->count();
        $list[] = ['title'=>'推荐中','count'=>$count];

        //新品
        $count  = $this->product->where(['is_show'=>1])->count();
        $list[] = ['title'=>'新品','count'=>$count];

        //热卖
        $count  = $this->product->where(['is_hot'=>1])->count();
        $list[] = ['title'=>'热卖','count'=>$count];

        //已删除
        $count  = $this->product->where(['del'=>1])->count();
        $list[] = ['title'=>'已删除','count'=>$count];

        return $list;
    }
}
