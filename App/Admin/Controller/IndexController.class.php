<?php
namespace Admin\Controller;

use Think\Controller;

//import("Vendor.Carbon.Carbon");
vendor("Carbon.Carbon");
use Carbon\Carbon;

class IndexController extends PublicController
{
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
        //查询本周订单没问题了
        $star = Carbon::now()->startOfWeek()->timestamp;
        $end = Carbon::now()->endOfWeek()->timestamp;
        $o = M("Order");
        $map['addtime']  = array('between',array($star,$end));
        $r = $o->where($map)->field('id,addtime')->select(); //详细数据
        // $r = $o->where($map)->count(); //订单个数
        // $r = $o->where($map)->sum('price'); //总价格
        //echo $o->getlastsql();

        $ds = Carbon::parse('2018-01-11')->startOfDay()->timestamp;
        $de = Carbon::parse('2018-01-11')->endOfDay()->timestamp;
        $map['addtime']  = array('between',array($ds,$de));
        $r = $o->where($map)->field('id,addtime')->select(); //详细数据
        $arr = [];
        foreach ($r as $key => $value) {
            $r[$key]['addtime'] = date('Y-m-d H:i:s',$value['addtime']);
            $arr[$key] = [$value['addtime'],$value['id']];
        }
        //echo json_encode($arr);
        //dump($r);
        //exit();


        $menu="";
        $index="";
        $menu="<include File='Page/adminusermenu'/>";
        $index="<iframe src='".U('Page/adminindex')."' id='iframe' name='iframe'></iframe>";
        // $bc = ['bc1'=>'管理首页','bc2'=>'欢迎页面'];
        $bc = ['管理首页','欢迎页面'];
        //版权
        $copy=M('web')->where('id=5')->getField('concent');
        $this->assign('copy', $copy);
        $this->assign('menu', $menu);
        $this->assign('index', $index);
        $this->assign('bc', $bc);
        $this->display();
    }
}
