<?php
namespace Admin\Controller;

use Think\Controller;

//import("Vendor.Carbon.Carbon");
vendor("Carbon.Carbon");
use Carbon\Carbon;

vendor("MysqlDate.MysqlDate");
use MysqlDate\MysqlDate;

class IndexController extends PublicController
{
    private $order;     //订单模型类
    private $mysqlDate; //时间段类
    public function __construct()
    {
        parent::__construct();
        $this->mysqlDate =  new MysqlDate();
        $this->order = M("Order");
    }

    //***********************************
    // iframe式显示菜单和index页
    //**********************************
    public function index()
    {
        // $b = vendor("wxpay.wxpay");
        // //printf("Now: %s", \Carbon\Carbon::now());
        // $input = new \WxPayUnifiedOrder();
        // dump($input);
        // $a = vendor("Carbon.Carbon");
        // dump($a);
        // printf("Now: %s", \Carbon\Carbon::now());
        // echo  Carbon::now()->subDay(1)->timestamp;
        // echo '前一天开始时间:'.\Carbon\Carbon::now()->yesterday()->startOfDay()->timezone('Asia/Shanghai')->format('Y-m-d H:i:s').'<br />';
        // echo Carbon::parse('last Mon')->toDateTimeString();
        // echo Carbon::parse('Sunday')->endOfDay()->toDateTimeString();
        // echo '本周开始时间:'.\Carbon\Carbon::now()->startOfWeek()->timestamp.'<br />';
        // echo '本周结束时间:'.\Carbon\Carbon::now()->endOfWeek()->timestamp.'<br />';
        // echo(strtotime("next Monday") . "<br>");
        
        //echo Carbon::now()->startOfDay()->timestamp;
        // echo "<br>";
        // echo Carbon::now()->endOfDay()->timestamp;



        //查询本周订单没问题了
        // $star = Carbon::now()->startOfWeek()->timestamp;
        // $end = Carbon::now()->endOfWeek()->timestamp;
        // $star = Carbon::now()->startOfDay()->timestamp;
        // $end = Carbon::now()->endOfDay()->timestamp;
        $o = M("Order");
        $map['addtime']  = array('between',array($star,$end));
        $r = $o->where($map)->field('id,addtime')->select(); //详细数据
        // $r = $o->where($map)->count(); //订单个数
        $r = $o->where($map)->sum('price'); //总价格
        // echo $o->getlastsql();
        // dump($r);
        // exit();
        $ds = Carbon::parse('2018-01-11')->startOfDay()->timestamp;
        $de = Carbon::parse('2018-01-11')->endOfDay()->timestamp;
        $map['addtime']  = array('between',array($ds,$de));
        $r = $o->where($map)->field('id,addtime')->select(); //详细数据
        $arr = [];
        foreach ($r as $key => $value) {
            $r[$key]['addtime'] = date('Y-m-d H:i:s', $value['addtime']);
            $arr[$key] = [$value['addtime'],$value['id']];
        }

        //echo json_encode($arr);
        //dump($r);
        //exit();
        //



        $menu="";
        $index="";
        $menu="<include File='Page/adminusermenu'/>";
        $index="<iframe src='".U('Page/adminindex')."' id='iframe' name='iframe'></iframe>";
        $bc = ['管理首页','欢迎页面'];
        //版权
        $copy=M('web')->where('id=5')->getField('concent');
        $this->assign('copy', $copy);
        $this->assign('menu', $menu);
        $this->assign('index', $index);
        $this->assign('bc', $bc);

        $this->display();
    }

    /**
     * [clearCache 清理缓存]
     * @return [string]
     */
    public function clearCache()
    {
        if (IS_POST) {
            $cache = new \Think\Cache;
            //第一个参数为 缓存类型 这里是从配置里读取  第二个参数为清理的文件夹 因为默认清理的是 Temp 这里修改成Cache
            //缓存文件是存放在Temp里的 所以还得清理Temp
            $cache->getInstance()->clear();
            echo $cache->getInstance(C('DATA_CACHE_TYPE'), ['temp'=>CACHE_PATH])->clear();
        } else {
            $this->error('非法请求');
        }
    }
}
