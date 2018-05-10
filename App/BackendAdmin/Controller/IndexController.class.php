<?php
namespace BackendAdmin\Controller;

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
        $menu="";
        $index="";
        $menu="<include File='Page/adminusermenu'/>";
        $index="<iframe src='".U('Page/adminindex')."' id='iframe' name='iframe'></iframe>";
        $bc = ['管理首页','欢迎页面'];


        $m = F('ORDER_MSG');
            
        $this->assign('orderMSG', $m['orderMSG']);
        $this->assign('autoClose', $m['autoClose']);
        $this->assign('heartbeat', $m['heartbeat']);

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
