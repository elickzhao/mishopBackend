<?php

// 本类由系统自动生成，仅供测试用途

namespace Api\Controller;

use Think\Controller;

vendor("MysqlDate.MysqlDate");
use MysqlDate\MysqlDate;

vendor("Carbon.Carbon");
use Carbon\Carbon;

vendor("Underscore.Underscore");
use Underscore\Types\Arrays;

class NewsController extends PublicController
{
    public $cid;
    public $flag;
    public function __construct()
    {
        parent::__construct();
        $arr = M('category')->where(['bz_4'=>0])->getField('id', true);
        $this->cid = implode(',', $arr);

        $this->flag = ['is_hot','type','is_show','is_sale'];
    }

    //*****************************

    //  新闻列表

    //*****************************

    public function index()
    {
        $keyword=$_POST['keyword'];

        $where = '1=1';

        if ($keyword) {
            $where .=' AND name LIKE "%'.$keyword.'%"';
        }



        $list = M('news')->where($where)->field('id,cid,digest,name,photo,addtime,source')->order('sort desc,addtime desc')->limit(8)->select();

        foreach ($list as $k => $v) {
            $list[$k]['photo']=__DATAURL__.$v['photo'];

            $list[$k]['cname'] = M('news_cat')->where('id='.intval($v['cid']))->getField('name');

            $list[$k]['addtime']=date('Y-m-d', $v['addtime']);
        }

        //json加密输出

        //dump($json);

        echo json_encode(array('list'=>$list));

        exit();
    }



    //*****************************

    //  新闻列表  加载更多

    //*****************************

    public function getlist()
    {
        $page = intval($_REQUEST['page']);

        if (!$page) {
            $page = 2;
        }

        $limit = $page*8-8;



        $list = M('news')->where($where)->field('id,cid,digest,name,photo,addtime,source')->order('sort desc,addtime desc')->limit($limit.',8')->select();

        foreach ($list as $k => $v) {
            $list[$k]['photo']=__DATAURL__.$v['photo'];

            $list[$k]['cname'] = M('news_cat')->where('id='.intval($v['cid']))->getField('name');

            $list[$k]['addtime']=date('Y-m-d', $v['addtime']);
        }

        //json加密输出

        //dump($json);

        echo json_encode(array('list'=>$list));

        exit();
    }



    //*****************************

    //  新闻详情

    //*****************************

    public function detail()
    {
        $newid=intval($_REQUEST['news_id']);

        $detail=M('news')->where('id='.intval($newid))->find();

        if (!$detail) {
            echo json_encode(array('status'=>0,'err'=>'没有找到相关信息.'));

            exit();
        }

        $up = array();

        $up['click'] = intval($detail['click'])+1;

        M('news')->where('id='.intval($newid))->save($up);

        $content = str_replace('/minipetmrschool/Data/', __DATAURL__, $detail['content']);

        $detail['content']=html_entity_decode($content, ENT_QUOTES, "utf-8");

        $detail['addtime'] = date("Y-m-d", $detail['addtime']);


        //$this->ajaxReturn(['code' => 0,'msg'=>'','list'=>$ggtop]);
        echo json_encode(array('status'=>1,'info'=>$detail));

        exit();
    }

    /**
     * [doSign 签到]
     * @return [type] [description]
     */
    public function doSign()
    {
        if (IS_GET) {
            $jifen= 10; //每日签到 赠送积分数量

            $count = M('user_course')->where("uid = '".$_GET['uid']."' AND DATE(FROM_UNIXTIME(ADDTIME)) = '".Carbon::now()->format('Y-m-d')."'")->find();
            if ($count) {
                $this->ajaxReturn(['code' => 1, 'msg'=>'今日已签到!']);
            }

            $r = M('user')->where(['id'=>$_GET['uid']])->setInc('jifen', $jifen);
            //连续签到记录 在之前的记录加一
            $conDays += M('user_course')->where("uid = '".$_GET['uid']."'")->order('id desc')->getField('age');
            $rr = M('user_course')->add(['uid'=>$_GET['uid'],'addtime'=>time(),'age'=>$conDays]);
            if ($r && $rr) {
                $this->ajaxReturn(['code' => 0, 'msg'=>'']);
            } else {
                $this->ajaxReturn(['code' => 1, 'msg'=>'签到失败!']);
            }
        } else {
            $this->ajaxReturn(['code' => 1, 'msg'=>'非法请求','list'=> $list]);
        }
    }

    /**
     * [signInfo 过往签到信息]
     * @return [type] [description]
     */
    public function signInfo()
    {
        if (IS_GET) {
            if ($_GET['uid'] == "") {
                $this->ajaxReturn(['code' => 1, 'msg'=>'参数错误!']);
            }

            $score = M('user')->where("id = '".$_GET['uid']."'")->getField("jifen");
            $r = M('user_course')->field("DATE(FROM_UNIXTIME(ADDTIME)) as dtime,age")->where("uid = '".$_GET['uid']."'")->order('id desc')->find();
            //当第一次签到
            if (!$r) {
                $this->ajaxReturn(['code' => 0, 'msg'=>'','conDays'=>0,'hasSign'=>0,'score'=>0,'list'=>[]]);
            }
            //$sql = M('user_course')->getlastsql();
            $conDays = $r['age']?$r['age']:0; //连续签到天数
            //$dt = $date = new Carbon($r['dtime']);
            $date = new Carbon($r['dtime']);
            $rr = $date->diffInDays(Carbon::now());
        
            //如果已经不是连续签到 并且连续签到数据还是原始的 就设置为0
            if ($rr > 1 && $conDays > 0) {
                $conDays =0;
                $addr = M('user_course')->where("uid = '".$_GET['uid']."'")->order('id desc')->save(['age'=>$conDays]);
                if ($addr === false) {
                    $this->ajaxReturn(['code' => 1, 'msg'=>'签到出错','erro'=>'更新连续签到时保存数据库出错']);
                }
            }

            //如果签到间隔大于0就说明没有签到
            $hasSign = ($rr == 0)?1:0;

            $list = M('user_course')->field("DATE(FROM_UNIXTIME(ADDTIME)) as signTime,10 as signPoint,0 as isdeleted")->where("uid = '".$_GET['uid']."'")->order('id desc')->limit(30)->select();

            $this->ajaxReturn(['code' => 0, 'msg'=>'','conDays'=>$conDays,'hasSign'=>$hasSign,'score'=>$score,'list'=>$list,'sql'=>$rr."--".$date."--". $date->diffInDays(Carbon::now())."-->".$r."===>".$sql]);
        } else {
            $this->ajaxReturn(['code' => 1, 'msg'=>'非法请求']);
        }
    }


    /**
     * [signInfo 最近七天签到情况]
     * @return [type] [description]
     */
    public function getSignDate()
    {
        if (IS_GET) {
            if ($_GET['uid'] == "") {
                $this->ajaxReturn(['code' => 1, 'msg'=>'参数错误!']);
            }
            $list = [];

            for ($i=0; $i < 7; $i++) {
                $dd = Carbon::now()->subDays(6 - $i);
                $r = M('user_course')->where("uid = '".$_GET['uid']."' AND DATE(FROM_UNIXTIME(ADDTIME)) = '".$dd->format('Y-m-d')."'")->find();

                $flag = $r?1:0;
                $list[$i] = ['signed'=>$flag,'signTime'=>$dd->day."日",'sql'=>M('user_course')->getlastsql()];
            }
            $this->ajaxReturn(['code' => 0, 'msg'=>'','list'=> $list]);
        } else {
            $this->ajaxReturn(['code' => 1, 'msg'=>'非法请求','list'=> $list]);
        }
    }

    /**
     * [discountGoodsList 首页热销7个]
     * @return [type] [description]
     */
    public function discountGoodsList()
    {
        if (IS_GET) {
            // $arr = ['is_hot','is_show','is_sale'];
            $where = 'del=0 AND pro_type=1 AND is_down=0 AND type=1 AND cid in ('.$this->cid.')';   //目前是推荐+热销就会出现在首页7个
            if ($_GET['flag'] != "") {
                $where .= ' AND '.$this->flag[$_GET['flag']].'=1' ;
            } else {
                $this->ajaxReturn(['code' => 1, 'msg'=>'参数错误!','list'=> $list]);
            }
            $list = M('product')->field("id,name,intro,pro_number,price,price_yh,photo_x,photo_d,shiyong,type,is_show,is_hot,is_sale")->where($where)->cache(true, 1800)->order('sort desc,id desc')->limit(7)->select();

            $this->ajaxReturn(['code' => 0, 'msg'=>'','list'=> $list]);
        } else {
            $this->ajaxReturn(['code' => 1, 'msg'=>'非法请求!','list'=> $list]);
        }
    }

    /**
     * [searchGoodsList 搜索商品列表]
     * @return [type] [description]
     */
    public function searchGoodsList()
    {
        if (IS_GET) {
            $page = intval($_REQUEST['page']);
            // $arr = ['is_hot','type','is_show','is_sale'];
            $where = 'del=0 AND pro_type=1 AND is_down=0 AND cid in ('.$this->cid.')';

            $limit = intval($page * 16) - 16;

            //促销类型
            if ($_GET['locationFlag'] != "") {
                $where .= ' AND '.$this->flag[$_GET['locationFlag']].'=1' ;
            }

            //分类检索
            if ($_GET['cateCode'] != "") {
                $where .= ' AND cid='.$_GET['cateCode'] ;
            }

            //关键字查询
            $keyword = trim($_REQUEST['searchKeyWords']);
            $uid = $uid = intval($_REQUEST['uid']);
            if ($keyword != "") {
                /*=============================================
                =       这段是增加用户个人搜索个数的 block       =
                =============================================*/
                if ($uid) {
                    $check = M('search_record')->where('uid='.intval($uid).' AND keyword="'.$keyword.'"')->find();
                    if ($check) {
                        $num = intval($check['num'])+1;
                        M('search_record')->where('id='.intval($check['id']))->save(array('num'=>$num));
                    } else {
                        $add = array();
                        $add['uid'] = $uid;
                        $add['keyword'] = $keyword;
                        $add['addtime'] = time();
                        M('search_record')->add($add);
                    }
                }
                /*=====  End of Section 这段是增加用户个人搜索个数的 block  ======*/
                
                $where .=  'AND name LIKE "%'.$keyword.'%"';
            }

            //排序
            switch ($_GET['sort']) {
                case '4':
                    $order = "renqi desc";
                    break;
                case '3':
                    $order = "shiyong desc";
                    break;
                case '2':
                    $order = "price_yh asc";
                    break;
                case '1':
                    $order = "price_yh desc";
                    break;
                default:
                    $order = 'sort desc,id desc';
                    break;
            }


            //现在这个当做搜索 所以有可能是所有产品
            // else {
            //     $this->ajaxReturn(['code' => 1, 'msg'=>'参数错误!','list'=> $list]);
            // }
            $list = M('product')->field("id,name,intro,pro_number,price,price_yh,photo_x,photo_d,shiyong,type,is_show,is_hot,is_sale")->where($where)->cache(true, 1800)->order($order)->limit($limit.',16')->select();

            $total = M('product')->where($where)->count("id");
            $pageTotal = ceil($total /16);

            $this->ajaxReturn(['code' => 0, 'msg'=>'','list'=> $list,'page_total'=>$pageTotal]);
        } else {
            $this->ajaxReturn(['code' => 1, 'msg'=>'非法请求!']);
        }
    }

    /**
     * [rootCtegoryList 一级栏目列表]
     * @return [type] [description]
     */
    public function rootCtegoryList()
    {
        if (IS_GET) {
            $list = M('category')->where('tid=1 AND bz_4=0 ')->field('id,tid,name')->order('sort desc,id asc')->select();
            //$code = Arrays::pluck($list, 'id');
            $this->ajaxReturn(['code' => 0, 'msg'=>'','list'=> $list]);
        } else {
            $this->ajaxReturn(['code' => 1, 'msg'=>'非法请求!']);
        }
    }

    /**
     * [childGoodsCatetoryList 二级栏目列表]
     * @return [type] [description]
     */
    public function childGoodsCatetoryList()
    {
        if (IS_GET) {
            $catid = intval($_REQUEST['cat_id']);
            if (!$catid) {
                echo json_encode(array('status'=>0,'err'=>'没有找到产品数据.'));
                exit();
            }

            $catList = M('category')->where('tid='.intval($catid).' AND bz_4=0 ')->field('id,name,bz_1')->select();
 
            $this->ajaxReturn(['code' => 0, 'msg'=>'','list'=> $catList]);
        } else {
            $this->ajaxReturn(['code' => 1, 'msg'=>'非法请求!']);
        }
    }

    /**
     * [searchHotKey 热门搜索关键词列表]
     * @return [type] [description]
     */
    public function searchHotKey()
    {
        if (IS_GET) {
            $list =   M('search_record')->group('keyword')->field('keyword')->order('SUM(num) desc')->limit(10)->select();
            //$code = Arrays::pluck($list, 'id');
            $this->ajaxReturn(['code' => 0, 'msg'=>'','list'=> $list]);
        } else {
            $this->ajaxReturn(['code' => 1, 'msg'=>'非法请求!']);
        }
    }


    /**
     * [searchHotKey 历史搜索关键词列表]
     * @return [type] [description]
     */
    public function historyKeyList()
    {
        if (IS_GET) {
            if ($_GET['uid'] == "") {
                $this->ajaxReturn(['code' => 1, 'msg'=>'参数错误!']);
            }
            $list =   M('search_record')->where('uid='.intval($_GET['uid']))->order('addtime desc')->field('keyword')->limit(10)->select();
            $this->ajaxReturn(['code' => 0, 'msg'=>'','list'=> $list]);
        } else {
            $this->ajaxReturn(['code' => 1, 'msg'=>'非法请求!']);
        }
    }

    /**
     * [searchHotKey 删除历史搜索关键词列表]
     * @return [type] [description]
     */
    public function delHistory()
    {
        if (IS_GET) {
            if ($_GET['uid'] == "") {
                $this->ajaxReturn(['code' => 1, 'msg'=>'参数错误!']);
            }
            $res = $history = M('search_record')->where('uid='.intval($_GET['uid']))->delete();
            if (!$res) {
                $this->ajaxReturn(['code' => 1, 'msg'=>'删除失败!']);
            }
            $this->ajaxReturn(['code' => 0, 'msg'=>'','list'=> $list]);
        } else {
            $this->ajaxReturn(['code' => 1, 'msg'=>'非法请求!']);
        }
    }


    /**
     * [goods 商品详情]
     * @return [type] [商品详情]
     */
    public function goods()
    {
        if (IS_GET) {
            $product = M('product');

            $pro_id = intval($_REQUEST['pro_id']);
            if (!$pro_id) {
                $this->ajaxReturn(['code' => 1, 'msg'=>'商品不存在或已下架！']);
                echo json_encode(array('status' => 0, 'err' => '商品不存在或已下架！'));
                exit();
            }

            $pro = $product->where('id='.intval($pro_id).' AND del=0 AND is_down=0')->find();
            if (!$pro) {
                $this->ajaxReturn(['code' => 1, 'msg'=>'商品不存在或已下架！']);
                echo json_encode(array('status' => 0, 'err' => '商品不存在或已下架！'.__LINE__));
                exit();
            }

            $pro['photo_x'] = __DATAURL__.$pro['photo_x'];
            $pro['photo_d'] = __DATAURL__.$pro['photo_d'];
            $pro['brand'] = M('brand')->where('id='.intval($pro['brand_id']))->getField('name');
            $pro['cat_name'] = M('category')->where('id='.intval($pro['cid']))->getField('name');

            //图片轮播数组
            $img = explode(',', trim($pro['photo_string'], ','));
            $b = array();
            if ($pro['photo_string']) {
                foreach ($img as $k => $v) {
                    $b[] = __DATAURL__.$v;
                }
            } else {
                $b[] = $pro['photo_d'];
            }
            $pro['img_arr'] = $b; //图片轮播数组

            //处理产品属性
            $catlist = array();
            if ($pro['pro_buff']) {//如果产品属性有值才进行数据组装
                $pro_buff = explode(',', $pro['pro_buff']);
                $commodityAttr = array(); //产品库还剩下的产品规格
                $attrValueList = array(); //产品所有的产品规格
                foreach ($pro_buff as $key => $val) {
                    $attr_name = M('attribute')->where('id='.intval($val))->getField('attr_name');
                    $guigelist = M('guige')->where('attr_id='.intval($val).' AND pid='.intval($pro['id']))->field('id,name')->select();
                    $ggss = array();
                    $gg = array();
                    foreach ($guigelist as $k => $v) {
                        $gg[$k]['attrKey'] = $attr_name;
                        $gg[$k]['attrValue'] = $v['name'];
                        $ggss[] = $v['name'];
                    }
                    $commodityAttr[$key]['attrValueList'] = $gg;
                    $attrValueList[$key]['attrKey'] = $attr_name;
                    $attrValueList[$key]['attrValueList'] = $ggss;
                }
            }

            $content = str_replace('/minipetmrschool/Data/', __DATAURL__, $pro['content']);
            $pro['content'] = html_entity_decode($content, ENT_QUOTES, 'utf-8');

            //检测产品是否收藏
            $col = M('product_sc')->where('uid='.intval($_REQUEST['uid']).' AND pid='.intval($pro_id))->getField('id');
            if ($col) {
                $pro['collect'] = 1;
            } else {
                $pro['collect'] = 0;
            }

            $this->ajaxReturn(['code' => 0, 'msg'=>'','data' => $pro, 'commodityAttr' => $commodityAttr, 'attrValueList' => $attrValueList]);
        } else {
            $this->ajaxReturn(['code' => 1, 'msg'=>'非法请求!']);
        }
    }


    //***************************
    //  获取sessionkey 接口
    //***************************
    public function getsessionkey()
    {
        $wx_config = C('weixin');
        $appid = $wx_config['appid'];
        $secret = $wx_config['secret'];

        $code = trim($_REQUEST['code']);
        if (!$code) {
            echo json_encode(array('status'=>0,'err'=>'非法操作！'));
            exit();
        }

        if (!$appid || !$secret) {
            echo json_encode(array('status'=>0,'err'=>'非法操作！'.__LINE__));
            exit();
        }

        $get_token_url = 'https://api.weixin.qq.com/sns/jscode2session?appid='.$appid.'&secret='.$secret.'&js_code='.$code.'&grant_type=authorization_code';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $get_token_url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        $res = curl_exec($ch);
        curl_close($ch);
        echo $res;
        //echo json_encode(array('status'=>1,'result'=>$res));
        exit();
    }

    /**
     * [searchHotKey 用户登录并获取信息]
     * @return [type] [description]
     */
    public function user2session()
    {
        if (IS_GET) {
            if (!$_GET['openId']) {
                $this->ajaxReturn(['code' => 1, 'msg'=>'登录状态异常!']);
            }

            $con = array();
            $con['openid']=trim($_GET['openId']);
            $uid = M('user')->where($con)->getField('id');
            if ($uid) {
                $userinfo = M('user')->where('id='.intval($uid))->find();
                if (intval($userinfo['del'])==1) {
                    $this->ajaxReturn(['code' => 1, 'msg'=>'账号状态异常！']);
                }
                $arr = array();
                $arr['ID'] = intval($uid);
                $arr['NickName'] = $_GET['NickName'];
                $arr['avatarUrl'] = $_GET['avatarUrl'];
                $this->ajaxReturn(['code' => 0, 'msg'=>'','data'=> $arr]);
                // echo json_encode(array('status'=>1,'arr'=>$err));
                // exit();
            } else {
                $data = array();
                $data['name'] = $_GET['NickName'];
                $data['uname'] = $_GET['NickName'];
                $data['photo'] = $_GET['avatarUrl'];
                $data['sex'] = $_GET['gender'];
                $data['openid'] = $openid;
                $data['source'] = 'wx';
                $data['addtime'] = time();
                if (!$data['openid']) {
                    $this->ajaxReturn(['code' => 1, 'msg'=>'授权失败！'.__LINE__]);
                }
                $res = M('user')->add($data);
                if ($res) {
                    $arr = array();
                    $arr['ID'] = intval($res);
                    $arr['NickName'] = $data['name'];
                    $arr['avatarUrl'] = $data['photo'];
                    $this->ajaxReturn(['code' => 0, 'msg'=>'','data'=> $arr]);
                    // echo json_encode(array('status'=>1,'arr'=>$err));
                    // exit();
                } else {
                    $this->ajaxReturn(['code' => 1, 'msg'=>'授权失败！'.__LINE__]);
                    // echo json_encode(array('status'=>0,'err'=>'授权失败！'.__LINE__));
                    // exit();
                }
            }
        } else {
            $this->ajaxReturn(['code' => 1, 'msg'=>'非法请求!']);
        }
    }

    /**
     * [searchHotKey 起送费]
     * @return [type] [description]
     */
    public function miniNum()
    {
        if (IS_GET) {
            $m = F('ORDER_MSG');
            $this->ajaxReturn(['code' => 0, 'msg'=>'','data'=> $m]);
        } else {
            $this->ajaxReturn(['code' => 1, 'msg'=>'非法请求!']);
        }
    }

    /**
     * [searchHotKey 商品是否收藏]
     * @return [type] [description]
     */
    public function goodsIsFavorite()
    {
        if (IS_GET) {
            if ($_GET['uid'] == "" || $_GET['goodsId'] =="") {
                $this->ajaxReturn(['code' => 1, 'msg'=>'参数错误!']);
            }
            $where  = ['uid'=>$_GET['uid'],'course_id'=>$_GET['goodsId']];
            $m = M('user_course')->where($where)->count();
            $this->ajaxReturn(['code' => 0, 'msg'=>'','isFavorite'=> $m]);
        } else {
            $this->ajaxReturn(['code' => 1, 'msg'=>'非法请求!']);
        }
    }

    /**
     * [searchHotKey 商品是收藏]
     * @return [type] [description]
     */
    public function goodsFavoriteAdd()
    {
        if (IS_GET) {
            if ($_GET['uid'] == "" || $_GET['goodsId'] =="") {
                $this->ajaxReturn(['code' => 1, 'msg'=>'参数错误!']);
            }
            $data  = ['uid'=>$_GET['uid'],'course_id'=>$_GET['goodsId']];
            $m = M('user_course')->data($data)->add();
            if ($m == "") {
                $this->ajaxReturn(['code' => 1, 'msg'=>'收藏失败!']);
            }
            $this->ajaxReturn(['code' => 0, 'msg'=>'']);
        } else {
            $this->ajaxReturn(['code' => 1, 'msg'=>'非法请求!']);
        }
    }

    /**
     * [searchHotKey 商品是删除收藏]
     * @return [type] [description]
     */
    public function goodsFavoriteDelete()
    {
        if (IS_GET) {
            if ($_GET['uid'] == "" || $_GET['goodsId'] =="") {
                $this->ajaxReturn(['code' => 1, 'msg'=>'参数错误!']);
            }
            $data  = ['uid'=>$_GET['uid'],'course_id'=>$_GET['goodsId']];
            $m = M('user_course')->where($data)->delete();
            if ($m != 1) {
                $this->ajaxReturn(['code' => 1, 'msg'=>'取消收藏失败!']);
            }
            $this->ajaxReturn(['code' => 0, 'msg'=>'']);
        } else {
            $this->ajaxReturn(['code' => 1, 'msg'=>'非法请求!']);
        }
    }

    /**
     * [searchHotKey 添加购物车]
     * @return [type] [description]
     */
    public function goodsCartAdd()
    {
        if (IS_GET) {
            if ($_GET['uid'] == "") {
                $this->ajaxReturn(['code' => 1, 'msg'=>'登录状态异常!']);
            }

            $uid = $_GET['uid'];
            $pid = intval($_REQUEST['pid']);
            $num = intval($_REQUEST['num']);

            if (!intval($pid) || !intval($num)) {
                $this->ajaxReturn(['code' => 1, 'msg'=>'参数错误!']);
            }

            //加入购物车
            $check = $this->check_cart(intval($pid));
            if ($check['status']==0) {
                $this->ajaxReturn(['code' => 1, 'msg'=>$check['err']]);
            }

            $check_info = M('product')->where('id='.intval($pid).' AND del=0 AND is_down=0')->find();
            //判断库存
            if (intval($check_info['num'])<=$num) {
                $this->ajaxReturn(['code' => 1, 'msg'=>'库存不足！']);
            }

            $shpp=M("shopping_char");

            //判断购物车内是否已经存在该商品
            $data = array();
            $cart_info = $shpp->where('pid='.intval($pid).' AND uid='.intval($uid))->field('id,num')->find();
            if ($cart_info) {
                $data['num'] = intval($cart_info['num'])+intval($num);
                //判断库存
                if (intval($check_info['num'])<=$data['num']) {
                    $this->ajaxReturn(['code' => 1, 'msg'=>'库存不足！']);
                }
                $res = $shpp->where('id='.intval($cart_info['id']))->save($data);
            } else {
                $data['pid']=intval($pid);
                $data['num']=intval($num);
                $data['addtime']=time();
                $data['uid']=intval($uid);
                $data['shop_id']=intval($check_info['shop_id']);
                $ptype = 1;
                if (intval($check_info['pro_type'])) {
                    $ptype = intval($check_info['pro_type']);
                }
                $data['type']=$ptype;
                $data['price'] = $check_info['price_yh'];

                $res=$shpp->add($data);
            }

            if ($res) {
                $this->ajaxReturn(['code' => 0, 'msg'=>'']);
            } else {
                $this->ajaxReturn(['code' => 1, 'msg'=>'加入失败!']);
            }
        } else {
            $this->ajaxReturn(['code' => 1, 'msg'=>'非法请求!']);
        }
    }


    //购物车添加。删除检测公共方法
    public function check_cart($pid)
    {
        //检查产品是否存在或删除
        $check_info = M('product')->where('id='.intval($pid).' AND del=0 AND is_down=0')->find();
        if (!$check_info) {
            return array('status'=>0,'err'=>'商品不存在或已下架.');
        }

        return array('status'=>1);
    }

    /**
     * [searchHotKey 检查购物车所有商品信息]
     * @return [type] [description]
     */
    public function goodsCartCheckAll()
    {
        if (IS_GET) {
            if ($_GET['uid'] == "") {
                $this->ajaxReturn(['code' => 1, 'msg'=>'参数错误!']);
            }

            $uid = $_GET['uid'];
            $cart = M("shopping_char")->where('uid='.intval($uid))->field('pid')->select();
            foreach ($cart as $k => $v) {
                $this->goodsCartCheck(intval($v['pid']));
            }

            $this->ajaxReturn(['code' => 0, 'msg'=>'']);
        } else {
            $this->ajaxReturn(['code' => 1, 'msg'=>'非法请求!']);
        }
    }


    /**
     * [searchHotKey 单个商品添加购物车检查]
     * @return [type] [description]
     */
    public function goodsCartCheck($pro_id = '')
    {
        if (IS_GET) {
            $pid =  $_GET['pid']?$_GET['pid']:$pro_id;
            $uid = $_GET['uid'];

            if ($uid == "" || $pid =="") {
                $this->ajaxReturn(['code' => 1, 'msg'=>'参数错误!']);
            }

            //判断是否已经下架或删除
            $check = $this->check_cart(intval($pid));
            if ($check['status']==0) {
                $this->ajaxReturn(['code' => 1, 'msg'=>$check['err']]);
            }

            $cart_info = M("shopping_char")->where('pid='.intval($pid).' AND uid='.intval($uid))->field('id,num')->find();
            $check_info = M('product')->where('id='.intval($pid).' AND del=0 AND is_down=0')->field('id,num')->find();
            //判断库存
            if (intval($check_info['num']) <= intval($cart_info['num'])) {
                $this->ajaxReturn(['code' => 1, 'msg'=>'库存不足！']);
            }

            $this->ajaxReturn(['code' => 0, 'msg'=>'']);
        } else {
            $this->ajaxReturn(['code' => 1, 'msg'=>'非法请求!']);
        }
    }

    /**
     * [searchHotKey 更新购物车商品数量]
     * @return [type] [description]
     */
    public function cartUpdateNum()
    {
        if (IS_GET) {
            $uid = intval($_REQUEST['uid']);
            $cart_id = intval($_REQUEST['cid']);
            $num=intval($_REQUEST['num']);

            if ($uid == "" || $cart_id  =="" || $num == "") {
                $this->ajaxReturn(['code' => 1, 'msg'=>'参数错误!']);
            }

            $shopping=M("shopping_char");

            $check = $shopping->where('id='.intval($cart_id))->find();
            if (!$check) {
                $this->ajaxReturn(['code' => 1, 'msg'=>'购物车信息错误！']);
            }

            //检测库存
            $pro_num = M('product')->where('id='.intval($check['pid']))->getField('num');
            if ($num>intval($pro_num)) {
                $this->ajaxReturn(['code' => 1, 'msg'=>'库存不足！']);
            }

            $res = $shopping->where('id ='.intval($cart_id).' AND uid='.intval($uid))->save(['num'=>$num]);
            if (!$res) {
                echo json_encode(array('status'=>1,'succ'=>'操作失败!'));
                exit();
            }
            $this->ajaxReturn(['code' => 0, 'msg'=>'']);
        } else {
            $this->ajaxReturn(['code' => 1, 'msg'=>'非法请求!']);
        }
    }

    /**
     * [searchHotKey 购物车删除产品]
     * @return [type] [description]
     */
    public function goodsCartDelete()
    {
        if (IS_GET) {
            if ($_GET['uid'] == "" && $_REQUEST['cid']) {
                $this->ajaxReturn(['code' => 1, 'msg'=>'参数错误!']);
            }

            $shopping=M("shopping_char");
            $cart_id=intval($_REQUEST['cid']);
            $check_id = $shopping->where('id='.intval($cart_id))->getField('id');
            if (!$check_id) {
                $this->ajaxReturn(['code' => 1, 'msg'=>'非法请求!']);
            }

            $res = $shopping->where('id ='.intval($cart_id))->delete(); // 删除
            if (!$res) {
                $this->ajaxReturn(['code' => 1, 'msg'=>'删除失败!']);
            }

            $this->ajaxReturn(['code' => 0, 'msg'=>'']);
        } else {
            $this->ajaxReturn(['code' => 1, 'msg'=>'非法请求!']);
        }
    }
     

    /**
     * [searchHotKey 购物车商品列表]
     * @return [type] [description]
     */
    public function goodsCartList()
    {
        if (IS_GET) {
            $qz=C('DB_PREFIX');
            $shopping=M("shopping_char");
            $shangchang=M("shangchang");
            $product=M("product");
            $user_id = intval($_REQUEST['uid']);
            if ($_GET['uid'] == "") {
                $this->ajaxReturn(['code' => 1, 'msg'=>'参数错误!']);
            }


            $cart = $shopping->where('uid='.intval($user_id))->field('id,uid,pid,price,num')->select();

            foreach ($cart as $k => $v) {
                $pro_info = $product->where('id='.intval($v['pid']).' AND del=0 AND is_down=0 AND num > 0')->field('name,photo_x')->find();
                //$pro_info = $product->where('id='.intval($v['pid']).' AND del=0 AND is_down=0 ')->field('name,photo_x')->find();
                if ($pro_info['name'] == null) {
                    $status = 3;
                    $shopping->where('uid='.intval($user_id).' AND id='.$v['id'])->delete();
                    unset($cart[$k]);
                    continue;
                }
                $cart[$k]['pro_name']=$pro_info['name'];
                $cart[$k]['photo_x']=__DATAURL__.$pro_info['photo_x'];
                $cart[$k]['selected']=1;
                //$this->ajaxReturn(['status'=>1,'cart'=>$cart,'sql'=>$product->getlastsql(),'info'=>$pro_info]);
            }
            sort($cart);
            $this->ajaxReturn(['code' => 0, 'msg'=>'','list'=>$cart]);
        } else {
            $this->ajaxReturn(['code' => 1, 'msg'=>'非法请求!']);
        }
    }
}
