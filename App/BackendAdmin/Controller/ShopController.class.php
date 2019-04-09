<?php
namespace BackendAdmin\Controller;

use Think\Controller;

vendor("MysqlDate.MysqlDate");
use MysqlDate\MysqlDate;

class ShopController extends PublicController
{

    /*
    *
    * 构造函数，用于导入外部文件和公共方法
    */
    public function _initialize()
    {
        $this->shop = M('shop');
    }

    /*
    *
    * 获取、查询广告表数据
    */
    public function index()
    {
        $bc = ['门店管理','全部门店'];
        $this->assign('bc', $bc);

        $this->display(); // 输出模板
    }


    /*
    *
    * 获取门店统计
    */
    public function count()
    {
        $chooseDay = $_POST['addtime'];
        $mysqlDate = new MysqlDate();
        //指定日期或当天
        $betweenDay = $chooseDay ? $mysqlDate->dayPeriod($chooseDay) : $mysqlDate->todadyPeriod();

        $this->assign('chooseDay', $chooseDay ? $chooseDay : date('Y-m-d', time()));
        $bc = ['门店统计', '门店统计新'];
        $this->assign('bc', $bc);
        $this->display('count_new'); // 输出模板
    }

    public function getCount()
    {
        $chooseDay = $_GET['addtime'];
        $mysqlDate = new MysqlDate();
        //指定日期或当天
        $betweenDay = $chooseDay ? $mysqlDate->dayPeriod($chooseDay) : $mysqlDate->todadyPeriod();

        $map['addtime'] = array('between', $betweenDay);
        $map['back'] = '0';
        $map['status'] = array('in', [20, 30, 40, 50]);

        //$arr = ['鑫乐生活广场店','晓庄国际彩虹广场店','金盛田广场店'];
        $arr = M('shop')->getField('name', true);

        $r = [];
        for ($i = 0; $i < count($arr); ++$i) {
            $map['kuaidi_name'] = $arr[$i];
            $r[$i]['name'] = $arr[$i];
            //$r[$i]['price'] = M('order')->field('SUM(price) as total')->where($map)->find();
            $r[$i]['price'] = M('order')->where($map)->getField('SUM(price) as total');
            $r[$i]['price'] = $r[$i]['price'] ? $r[$i]['price'] : 0;
            $r[$i]['count'] = M('order')->where($map)->count();
        }
        // dump($r);
        // $sql= M('order')->getlastsql();

        // $resuslt = [code => 0, msg => '', data => $r, sql=>$sql];
        $resuslt = [code => 0, msg => '', data => $r];
        $this->ajaxReturn($resuslt);
    }


    /**
      * [getGoods ajax获取广告列表]
      * @return [json] [广告数据]
      */
    public function getshops()
    {
        $where="1=1";
        if ($_GET['name'] != "") {
            $where .= ' AND name like "%'. $_GET['name'].'%"';
        }

        $count=M('shop')->where($where)->count();
        $rows=ceil($count/rows);
        $page = (int) -- $_GET['page'] ;
        $rows = $_GET['limit'] ? $_GET['limit'] : 20;
        $limit= $page*$rows;
        $shoplist=M('shop')->where($where)->order('id desc')->limit($limit, $rows)->select();
        $sql = M('shop')->getlastsql();
        
        //$resuslt = [code=>0,msg=>'',count=>$count,data=>$shoplist,sql=>$sql];
        $resuslt = [code=>0,msg=>'',count=>$count,data=>$shoplist];

        $this->ajaxReturn($resuslt);
    }


    /*
    *
    * 跳转添加或修改广告数据页面
    */
    public function add()
    {
        //如果是修改，则查询对应广告信息
        if (intval($_GET['adv_id'])) {
            $adv_id = intval($_GET['adv_id']);
            $adv_info = $this->shop->where('id='.intval($adv_id))->find();

            if (!$adv_info) {
                $this->error('没有找到相关信息.');
                exit();
            }

            //配送范围
            // if ($adv_info['scope']) {
            //     $scope = explode(',', $adv_info['scope']);
            //     $scopeName="";
            //     foreach ($scope as $v) {
            //         $r = M('china_city')->where(['id'=>$v])->getField('name');
            //         $scopeName .= $r.',';
            //     }
            //     $adv_info['scopeName'] = trim($scopeName, ',');
            // }
            $adv_info['lnglat'] = $adv_info['longitude'].','.$adv_info['latitude'];
            $this->assign('shop_info', $adv_info);
            
            $cityList = M('china_city')->where(['tid'=>$adv_info['province']])->field('id,name')->select();
            $districtList = M('china_city')->where(['tid'=>$adv_info['city']])->field('id,name')->select();
            $this->assign('city_list', $cityList);
            $this->assign('district_list', $districtList);
        }

        $cateList = M('china_city')->where('tid=0')->field('id,name')->select();
        $this->assign('cate_list', $cateList);

        $bc = ['门店管理','添加门店'];
        $this->assign('bc', $bc);
        $this->display();
    }


    /*
    *
    * 添加或修改门店信息
    */
    public function save()
    {
        //构建数组
        /*if (!$this->shop->create()) {
            $this->error($this->shop->getError());
        }*/
        $this->shop->create();

        //保存数据
        if (intval($_POST['adv_id'])) {
            $result = $this->shop->where('id='.intval($_POST['adv_id']))->save();
        } else {
            $result = $this->shop->add();
        }


        //缓存配送区域
        // $r = M('shop')->field('id,name,scope')->select();
        // $arr = [];
        // foreach ($r as $k => $v) {
        //     if ($v['scope']) {
        //         $scope = explode(',', $v['scope']);
        //         if (is_array($scope)) {
        //             foreach ($scope as $vv) {
        //                 $arr[$vv] = $v['name'];
        //             }
        //         }
        //     }
        // }

        // F('shopScope', $arr);

        //判断数据是否更新成功
        if ($result) {
            $this->success('操作成功.', 'index');
        } else {
            $this->error('操作失败,数据无变化!');
        }
    }

    /*
    *
    * 广告删除
    */
    public function del()
    {
        //获取广告id，查询数据库是否有这条数据
        $adv_id = intval($_GET['did']);
        $check_info = $this->shop->where('id='.intval($adv_id))->find();
        if (!$check_info) {
            $this->error('系统繁忙，请时候再试！');
            exit();
        }

        //修改对应的删除状态
        $up = $this->shop->where('id='.intval($adv_id))->delete();
        if ($up) {
            $url = "Data/".$check_info['photo'];
            if (file_exists($url)) {
                @unlink($url);
            }
            $this->success('操作成功.', 'index');
        } else {
            $this->error('操作失败.');
        }
    }


    public function getScope()
    {
        //原本去除郊区县 现在打开了
        //SELECT * FROM lr_china_city WHERE tid="891" AND id NOT IN(904,905);

        $r = M('china_city')->field('ID,name')->where('tid="891"')->select();
        $shop = M('shop')->field('name,scope')->select();
        
        $arr = [];
        $all = [];
        foreach ($shop as $k => $v) {
            if ($v['scope']) {
                $scope = explode(',', $v['scope']);
                if (is_array($scope)) {
                    foreach ($scope as $vv) {
                        $arr[$vv] = $v['name'];
                        $all[] = $vv;
                    }
                }
            }
        }
        
        $limit=intval($_REQUEST['limit']);
        if ($limit) {
            $shopScope = M('shop')->where('id='.$limit)->getField('scope');
            $shopScope = explode(',', $shopScope);
            $exclude=array_values(array_diff($all, $shopScope));
        } else {
            // sort($all);
            $exclude =$all;
        }

        foreach ($r as $k => $v) {
            $r[$k]['shop'] = $arr[$v['id']];
        }

        $resuslt = [code=>0,msg=>'',count=>1,data=>$r,exc=>$exclude,shopScope=>$shopScope];

        $this->ajaxReturn($resuslt);
    }

    /**
     * [orderDetail 店铺订单详情]
     * @return [type] [description]
     */
    public function orderDetail()
    {
        // dump($_GET);
        $orderStatus = C('ORDER_STATUS');
        $chooseDay = trim($_GET['date']);
        $mysqlDate = new MysqlDate();
        //指定日期或当天
        $betweenDay = $chooseDay ? $mysqlDate->dayPeriod($chooseDay) : $mysqlDate->todadyPeriod();

        $map['addtime'] = array('between', $betweenDay);
        $map['back'] = '0';
        $map['status'] = array('in', [0,10, 20, 30, 40, 50,51]);
        $map['kuaidi_name'] = $_GET['shop'];

        // 订单总量 = 订单状态数 + 退款
        $count = M('order')->where($map)->count();
        $all = M('order')->field('id,order_sn,receiver,price_h,kuaidi_name,status,addtime,back')->where($map)->select();

        // 查询退款订单
        $map['back'] = ['in',['1','2']];
        $backCount = M('order')->where($map)->count();
        $backAll = M('order')->field('id,order_sn,receiver,price_h,kuaidi_name,status,addtime,back')->where($map)->select();

        // 合并退款订单
        $count = $count + $backCount;
        $all = array_merge($all, $backAll);
 
        // 图表数组
        $arr = [];
        // 数据数组
        $list = [];

        // 初始化图表数组
        foreach ($orderStatus as $k => $v) {
            $arr[$k]['status'] = $k;
            $arr[$k]['type'] = $v;
            $arr[$k]['count'] = 0;
        }

        // 组织图表数组和数据数组
        foreach ($all as $k => $v) {
            if (key_exists($v['status'], $arr)) {
                if ($v['back'] != 0) {
                    $v['status'] = 'back';
                }
                if ($list[$v['status']] != '') {
                    array_push($list[$v['status']], $v);
                } else {
                    $list[$v['status']] = [$v];
                }
                $arr[$v['status']]['count']++;
            }
        }

        // 图表数据 去除下标并编码json格式
        $arr = json_encode(array_values($arr));
        // 分类订单数据
        $list = json_encode($list);
        // 订单总数据
        $all = json_encode($all);
        // 订单状态
        $orderStatus = json_encode($orderStatus);

        
        $this->assign('orderStatus', $orderStatus);
        $this->assign('all', $all);
        $this->assign('list', $list);
        $this->assign('total', $count);  // 订单总数
        $this->assign('g2', $arr);
        $this->display('get_shop_order_detail');
    }

        /*
    * 商品获取二级分类
    */
    public function getcid()
    {
        $cateid = intval($_REQUEST['cateid']);
        $catelist = M('china_city')->where('tid='.intval($cateid))->field('id,name')->select();
        echo json_encode(array('catelist'=>$catelist));
        exit();
    }

}
