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

vendor("Guzzle.autoloader");
vendor("Guzzle.GuzzleHttp.Client");
use GuzzleHttp\Client;

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
            $conDays = M('user_course')->where("uid = '".$_GET['uid']."'")->order('id desc')->limit(1)->getField('age');
            $conDays +=1;
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
        
            //如果已经不是连续签到 并且连续签到数据不是原始的 就设置为0
            if ($rr > 1 && $conDays > 0) {
                $conDays =0;
                $addr = M('user_course')->where("uid = '".$_GET['uid']."'")->order('id desc')->limit(1)->save(['age'=>$conDays]);
            }
            
            if ($addr === false) {
                $this->ajaxReturn(['code' => 1, 'msg'=>'签到出错','erro'=>'更新连续签到时保存数据库出错']);
            }

            //如果签到间隔大于0就说明没有签到
            $hasSign = ($rr == 0)?1:0;

            $list = M('user_course')->field("DATE(FROM_UNIXTIME(ADDTIME)) as signTime,10 as signPoint,0 as isdeleted,sex")->where("uid = '".$_GET['uid']."'")->order('id desc')->limit(30)->select();

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
            $where = 'del=0  AND is_down=0 AND type=1 AND cid in ('.$this->cid.')';   //目前是推荐+热销就会出现在首页7个
            if ($_GET['flag'] != "") {
                $where .= ' AND '.$this->flag[$_GET['flag']].'=1' ;
            } else {
                $this->ajaxReturn(['code' => 1, 'msg'=>'参数错误!','list'=> $list]);
            }
            $list = M('product')->field("id,name,intro,pro_number,price,price_yh,photo_x,photo_d,shiyong,type,is_show,is_hot,is_sale")->where($where)->cache(true, 1800)->order('sort desc,id desc')->limit(7)->select();
            //$list = M('product')->field("id,name,intro,pro_number,price,price_yh,photo_x,photo_d,shiyong,type,is_show,is_hot,is_sale")->where($where)->order('sort desc,id desc')->limit(7)->select();

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
            $where = 'del=0  AND is_down=0 AND cid in ('.$this->cid.')';

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
            $list = M('product')->field("id,name,intro,pro_number,price,price_yh,photo_x,photo_d,shiyong,type,is_show,is_hot,is_sale,num")->where($where)->cache(true, 1800)->order($order)->limit($limit.',16')->select();

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

            if ($_GET['NickName'] == "undefined") {
                $this->ajaxReturn(['code' => 1, 'msg'=>'用户昵称获取失败,请授权!']);
            }
            $openid = trim($_GET['openId']);
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
                    $this->ajaxReturn(['code' => 1, 'msg'=>'授权失败！'.__LINE__, 'errData'=>$data]);
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
            $where  = ['uid'=>$_GET['uid'],'pid'=>$_GET['goodsId']];
            $m = M('product_sc')->where($where)->count();
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
            $data  = ['uid'=>$_GET['uid'],'pid'=>$_GET['goodsId']];
            $m = M('product_sc')->data($data)->add();
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
            $data  = ['uid'=>$_GET['uid'],'pid'=>$_GET['goodsId']];
            $m = M('product_sc')->where($data)->delete();
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

    /**
     * [searchHotKey 购物车商品列表]
     * @return [type] [description]
     */
    public function getOrderCount()
    {
        if (IS_GET) {
            if ($_GET['uid'] == "") {
                $this->ajaxReturn(['code' => 1, 'msg'=>'参数错误!']);
            }
            $orders = M('order');
            $count = [];
            $order_status = array('10' => '待付款', '20' => '待发货', '30' => '待收货');
            foreach ($order_status as $k => $v) {
                $count[] = $orders->where(['status'=>$k,'uid'=>$_GET['uid'],'del'=>0,'back'=>'0'])->count();
            }

            //$sql = $orders->getlastsql();
            //$this->ajaxReturn(['code' => 0, 'msg'=>'','count'=>$count,'sql'=>$sql]);
            $this->ajaxReturn(['code' => 0, 'msg'=>'','count'=>$count]);
        } else {
            $this->ajaxReturn(['code' => 1, 'msg'=>'非法请求!']);
        }
    }

    /**
     * [searchHotKey 获取订单列表]
     * @return [type] [description]
     */
    public function getMyOrderList()
    {
        if (IS_GET) {
            if ($_GET['uid'] == "") {
                $this->ajaxReturn(['code' => 1, 'msg'=>'参数错误!']);
            }

            //分页

            $pages = intval($_REQUEST['page']);

            if (!$pages) {
                $pages = 0;
            }

            $orders = M('order');

            $orderp = M('order_product');

            $shangchang = M('shangchang');

            //按条件查询

            $condition = array();

            $condition['del'] = 0;

            $condition['uid'] = intval($_GET['uid']);

            //$condition['status'] = 10;

            $order_type = $_REQUEST['order_type'];

            if (!$order_type) {
                $order_type = 10;
            }

            if ($order_type == "refund") {
                $condition['back'] = array('gt', '0');
            } else {
                $condition['back'] = '0';
                $condition['status'] =$order_type;
            }
            


            $eachpage = 7;

            $limit = intval($pages * $eachpage) - $eachpage;

            $order_status = array('0' => '已取消', '10' => '待付款', '20' => '待发货', '30' => '待收货', '40' => '待评价', '50' => '交易完成', '51' => '交易关闭');

            $order = $orders->where($condition)->order('id desc')->field('id,order_sn,pay_sn,status,price,amount,type,product_num,addtime,back')->limit($limit.','.$eachpage)->select();
            
            $total = $orders->where($condition)->count("id");
            $pageTotal = ceil($total / $eachpage);

            //$sql = $orders->getlastsql();
            
            foreach ($order as $n => $v) {
                $order[$n]['desc'] = $order_status[$v['status']];

                $prolist = $orderp->where('order_id='.intval($v['id']))->find();

                $order[$n]['photo_x'] = __DATAURL__.$prolist['photo_x'];

                $order[$n]['pid'] = $prolist['pid'];

                $order[$n]['name'] = $prolist['name'];

                $order[$n]['price_yh'] = $prolist['price'];

                $order[$n]['pro_count'] = $orderp->where('order_id='.intval($v['id']))->getField('COUNT(id)');
                $order[$n]['addtime'] =  date('Y-m-d H:i', $v['addtime']);
            }

            // echo json_encode(array('status' => 1, 'ord' => $order, 'eachpage' => $eachpage));
            // exit();
            //$this->ajaxReturn(['code' => 0, 'msg'=>'','list'=>$order,'eachpage' => $eachpage,'page_total'=>$pageTotal,'sql'=>$sql]);
            $this->ajaxReturn(['code' => 0, 'msg'=>'','list'=>$order,'eachpage' => $eachpage,'page_total'=>$pageTotal]);
        } else {
            $this->ajaxReturn(['code' => 1, 'msg'=>'非法请求!']);
        }
    }

    /**
     * [searchHotKey 获取订单详情]
     * @return [type] [description]
     */
    public function getOrderDetail()
    {
        if (IS_GET) {
            $order_id = intval($_REQUEST['order_id']);
            if ($order_id  == "") {
                $this->ajaxReturn(['code' => 1, 'msg'=>'参数错误!']);
            }

            //订单详情

            $orders = M('order');

            $product_dp = M('product_dp');

            $orderp = M('order_product');

            $id = intval($_REQUEST['id']);

            $qz = C('DB_PREFIX');   //前缀

            $order_info = $orders->where('id='.intval($order_id).' AND del=0')->field('id,order_sn,shop_id,status,addtime,price,amount,type,post,tel,receiver,address_xq,remark,back,kuaidi_num')->find();

            if (!$order_info) {
                $this->ajaxReturn(['code' => 1, 'msg'=>'订单信息错误!']);
            }

            //订单状态

            $order_status = array('0' => '已取消', '10' => '待付款', '20' => '待发货', '30' => '待收货', '40' => '已收货', '50' => '交易完成');

            $expressNo = "";
            //XXX 快递信息
            if ($order_info['kuaidi_num'] != "" && strlen($order_info['kuaidi_num']) > 5) {
                //$post_info = array();
                //快递公司简称 暂时没用
                // if (intval($order_info['post'])) {
                //     $post_info = M('post')->where('id='.intval($order_info['post']))->find();
                // }

                //这里也调整了 暂时不在这个页面显示了 以后再说
                // $client = new Client();
                // //$response = $client->request('GET', 'http://sp0.baidu.com/9_Q4sjW91Qh3otqbppnN2DJv/pae/channel/data/asyncqury?appid=4001&com=&nu=3369785056558');
                // $url = "http://www.kuaidi100.com/query?type=shentong&postid=".$order_info['kuaidi_num'];
                // $response = $client->request('GET', $url);
                // $a = $response->getBody()->getContents();
                // $b = json_decode($a);
                // //dump($b->data);

                // $steps = [];
                // foreach ($b->data as $key => $value) {
                //     $steps[$key]['title'] =  $value->context;
                //     $steps[$key]['desc'] =$value->time;
                // }
                //dump($steps);
                $expressNo = $order_info['kuaidi_num'];
            }

            //支付类型

            $pay_type = array('cash' => '现金支付', 'alipay' => '支付宝', 'weixin' => '微信支付');

            $order_info['shop_name'] = M('shangchang')->where('id='.intval($order_info['shop_id']))->getField('name');

            $order_info['order_status'] = $order_status[$order_info['status']];

            $order_info['pay_type'] = $pay_type[$order_info['type']];

            $order_info['addtime'] = date('Y-m-d H:i:s', $order_info['addtime']);

            $order_info['yunfei'] = 0;

            if ($order_info['post']) {
                $order_info['yunfei'] = M('post')->where('id='.intval($order_info['post']))->getField('price');
            }

            //获取产品

            $pro = $orderp->where('order_id='.intval($order_info['id']))->select();

            foreach ($pro as $k => $v) {
                $pro[$k]['photo_x'] = __DATAURL__.$v['photo_x'];
            }

            $this->ajaxReturn(['code' => 0, 'msg'=>'', 'pro' => $pro, 'ord' => $order_info,'expressNo'=>$expressNo]);
        } else {
            $this->ajaxReturn(['code' => 1, 'msg'=>'非法请求!']);
        }
    }


    /**
     * [orderExpressInfo 查询订单快递信息]
     * @return [type] [查询订单快递信息]
     */
    public function orderExpressInfo()
    {
        if (IS_GET) {
            $expressNo = intval($_REQUEST['expressNo']);
            if ($expressNo  == "") {
                $this->ajaxReturn(['code' => 1, 'msg'=>'参数错误!']);
            }

            $client = new Client();
            //$response = $client->request('GET', 'http://sp0.baidu.com/9_Q4sjW91Qh3otqbppnN2DJv/pae/channel/data/asyncqury?appid=4001&com=&nu=3369785056558');
            $url = "http://www.kuaidi100.com/query?type=shentong&postid=".$expressNo;
            $response = $client->request('GET', $url);
            $a = $response->getBody()->getContents();
            $b = json_decode($a);
            //dump($b->data);

            $list = [];
            foreach ($b->data as $key => $value) {
                $list[$key]['title'] =  $value->time;
                $list[$key]['desc'] =$value->context;
            }

 
            $this->ajaxReturn(['code' => 0, 'msg'=>'','list'=>$list]);
        } else {
            $this->ajaxReturn(['code' => 1, 'msg'=>'非法请求!']);
        }
    }


    /**
     * [searchHotKey 购物车商品列表]
     * @return [type] [description]
     */
    public function ordersEdit()
    {
        if (IS_GET) {
            vendor('WeiXinpay.wxpay');

            $orders = M('order');
            $order_id = intval($_REQUEST['id']);
            $uid = intval($_REQUEST['uid']);
            $type = $_REQUEST['flag'];

            if ($order_id  == "" ||  $type== "" || $uid =="") {
                $this->ajaxReturn(['code' => 1, 'msg'=>'参数错误!']);
            }

            $order_sn = $orders->where('id='.intval($order_id)." AND del=0 AND back='0' AND uid =".$uid)->getField('order_sn');

            if (!$order_sn || !$type) {
                $this->ajaxReturn(['code' => 1, 'msg'=>'订单信息错误!']);
            }

            $data = array();

            if ('cancel' === $type) {
                $data['status'] = 0;
            } elseif ('receive' === $type) {
                $data['status'] = 40;
            } elseif ('refund' === $type) {
                $transaction_id = $orders->where('id='.intval($order_id)." AND del=0 AND back='0'")->getField('trade_no');

                if (!$transaction_id) {
                    $this->ajaxReturn(['code' => 1, 'msg'=>'订单信息错误!','err'=>__LINE__]);
                }

                $input = new \WxPayOrderQuery();
                $input->SetTransaction_id($transaction_id);
                $res = \WxPayApi::orderQuery($input);
                /*
                 * return_code 此字段是通信标识(SUCCESS/FAIL )
                 * result_code 业务结果 (SUCCESS/FAIL )
                 * trade_state 交易状态 (SUCCESS—支付成功 REFUND—转入退款 NOTPAY—未支付 CLOSED—已关闭 REVOKED—已撤销（刷卡支付） USERPAYING--用户支付中 PAYERROR--支付失败(其他原因，如银行返回失败))
                 */

                if ('SUCCESS' != $res['return_code'] || 'SUCCESS' != $res['result_code'] || 'SUCCESS' != $res['trade_state']) {
                    $this->ajaxReturn(['code' => 1, 'msg'=>'申请退款失败!','err'=>__LINE__]);
                }

                $data['back'] = 1;
                $data['back_remark'] = $_REQUEST['back_remark'];
            }

            if ($data) {
                $result = $orders->where('id='.intval($order_id))->save($data);

                if (false !== $result) {
                    $this->ajaxReturn(['code' => 0, 'msg'=>'']);
                } else {
                    $this->ajaxReturn(['code' => 1, 'msg'=>'操作失败!','err'=>__LINE__]);
                }
            } else {
                $this->ajaxReturn(['code' => 1, 'msg'=>'订单信息错误!','err'=>__LINE__]);
            }

            $this->ajaxReturn(['code' => 0, 'msg'=>'']);
        } else {
            $this->ajaxReturn(['code' => 1, 'msg'=>'非法请求!']);
        }
    }



    /**
     * [searchHotKey 商品收藏]
     * @return [type] [description]
     */
    public function favoriteInfo()
    {
        if (IS_GET) {
            $uid = intval($_REQUEST['uid']);
            $page = intval($_REQUEST['page']);

            if ($uid == "") {
                $this->ajaxReturn(['code' => 1, 'msg'=>'参数错误!']);
            }

            $eachpage = 7;

            $limit = intval($page * $eachpage) - $eachpage;

            $arr = M('product_sc')->where('uid='.intval($uid))->getField('pid', true);
            $total = M('product_sc')->where('uid='.intval($uid))->count("id");
            $pageTotal = ceil($total / $eachpage);

            if (!$arr) {
                $this->ajaxReturn(['code' => 0, 'msg'=>'','list'=>[]]);
            }

            $map['id'] = array('in', $arr);
            $list = M('product')->field('id,name,price_yh,photo_x')->where($map)->limit($limit.','.$eachpage)->select();
            foreach ($list as $key => $value) {
                $list[$key]['photo_x'] = __DATAURL__.$value['photo_x'];
            }

            if ($list) {
                $this->ajaxReturn(['code' => 0, 'msg'=>'','list'=>$list,'page_total'=>$pageTotal]);
            } else {
                $this->ajaxReturn(['code' => 1, 'msg'=>'网络错误']);
            }
        } else {
            $this->ajaxReturn(['code' => 1, 'msg'=>'非法请求!']);
        }
    }

    /**
     * [searchHotKey 收货地址]
     * @return [type] [description]
     */
    public function getUserAddress()
    {
        if (IS_GET) {
            $uid = intval($_REQUEST['uid']);
            if ($uid == "") {
                $this->ajaxReturn(['code' => 1, 'msg'=>'参数错误!']);
            }

            //所有地址
            $addressModel = M('address');
            $adds_list=$addressModel->where('uid='.$uid)->order('is_default desc,id desc')->select();
        
            $this->ajaxReturn(['code' => 0, 'msg'=>'','list'=>$adds_list,'sheng_list'=>$sheng]);
        } else {
            $this->ajaxReturn(['code' => 1, 'msg'=>'非法请求!']);
        }
    }


    /**
     * [searchHotKey 保存收货地址]
     * @return [type] [description]
     */
    public function saveAddress()
    {
        if (IS_GET) {
            $uid = intval($_REQUEST['uid']);
            if ($uid == "") {
                $this->ajaxReturn(['code' => 1, 'msg'=>'参数错误!']);
            }

            $data = array();
            $data['name'] = trim($_REQUEST['receiver']);
            $data['tel'] = trim($_REQUEST['tel']);
            $data['sheng'] = intval($_REQUEST['sheng']);
            $data['city'] = intval($_REQUEST['city']);
            $data['quyu'] = intval($_REQUEST['quyu']);
            $data['address'] = $_REQUEST['adds'];
            $data['code'] = $_REQUEST['code'];
            $data['uid'] = intval($uid);
            if (!$data['name'] || !$data['tel'] || !$data['address']) {
                $this->ajaxReturn(['code' => 1, 'msg'=>'请先完善信息后再提交.!']);
            }

            //为了老版本兼容 如果传过来的是文字 不是 地址代码 则查询id
            if (!$data['sheng'] || !$data['city'] || !$data['quyu']) {
                $data['sheng'] = M('china_city')->where('name="'.$_REQUEST['sheng'].'"')->getField('id');
                $data['city'] = M('china_city')->where('name="'.$_REQUEST['city'].'"')->getField('id');
                $data['quyu'] = M('china_city')->where('name="'.$_REQUEST['quyu'].'"')->getField('id');
                $data['address_xq'] = $_REQUEST['sheng'].' '.$_REQUEST['city'].' '.$_REQUEST['quyu'].' '.$data['address'];

                if (!$data['sheng'] || !$data['city'] || !$data['quyu']) {
                    $this->ajaxReturn(['code' => 1, 'msg'=>'请选择省市区.!']);
                }
            } else {
                //如果传来的是id 则查询文字 组合成详细地址
                $province = M('china_city')->where('id='.intval($data['sheng']))->getField('name');
                $city_name = M('china_city')->where('id='.intval($data['city']))->getField('name');
                $quyu_name = M('china_city')->where('id='.intval($data['quyu']))->getField('name');
                $data['address_xq'] = $province.' '.$city_name.' '.$quyu_name.' '.$data['address'];
            }

            //这个好像意义不大 暂时取消了
            //有ID说明是更新 所以当不是更新的时候 检查下是否存在了
            // if(!intval($_REQUEST['id'])){
            //     $check_id = M('address')->where($data)->getField('id');
            //     if ($check_id) {
            //         $this->ajaxReturn(['code' => 1, 'msg'=>'该地址已经添加了.!']);
            //     }
            // }



            //如果没有地址设为默认地址
            if (M('address')->where(['uid'=>$data['uid']])->count() == 0) {
                $data['is_default']=1;
            } elseif ($_REQUEST['isDef']) {
                $r = M('address')->where(['uid'=>$data['uid'],'is_default'=>1])->getField('id');
                if ($r) {
                    M('address')->where(['id'=>$r])->save(['is_default'=>0]);
                }
                $data['is_default']=1;
            }

            //有ID是更新 所以
            if (intval($_REQUEST['id'])) {
                $res = M('address')->where(['id'=>intval($_REQUEST['id'])])->save($data);
                $this->ajaxReturn(['code' => 0, 'msg'=>'']);
                // if ($res) {
                //     $this->ajaxReturn(['code' => 0, 'msg'=>'']);
                // } else {
                //     $this->ajaxReturn(['code' => 1, 'msg'=>'操作失败!']);
                // }
            }

            $res = M('address')->add($data);
            if ($res) {
                $arr = array();
                $arr['addr_id'] = $res;
                $arr['rec'] = $data['name'];
                $arr['tel'] = $data['tel'];
                $arr['addr_xq'] = $data['address_xq'];
                $this->ajaxReturn(['code' => 0, 'msg'=>'','data'=>$arr]);
            } else {
                $this->ajaxReturn(['code' => 1, 'msg'=>'操作失败!']);
            }
        } else {
            $this->ajaxReturn(['code' => 1, 'msg'=>'非法请求!']);
        }
    }

    /**
     * [searchHotKey 获取编辑的收货地址]
     * @return [type] [description]
     */
    public function receiverInfoById()
    {
        if (IS_GET) {
            $addr_id = intval($_REQUEST['id']);
            if (!$addr_id) {
                $this->ajaxReturn(['code' => 1, 'msg'=>'参数错误!']);
            }

            $address = M('address')->where('id='.intval($addr_id))->find();
            if (!$address) {
                $this->ajaxReturn(['code' => 2, 'msg'=>'查询失败!']);
            }
            $arr=array();
            $arr['id']=$address['id'];
            $arr['isDef']=$address['is_default'];
            $arr['addr_id']=$address['id'];
            $arr['name'] = $address['name'];
            $arr['tel'] = $address['tel'];
            $arr['addr_xq'] = $address['address'];

            $arr['provinceName'] = M('china_city')->where('id='.intval($address['sheng']))->getField('name');
            $arr['cityName'] = M('china_city')->where('id='.intval($address['city']))->getField('name');
            $arr['areaName'] = M('china_city')->where('id='.intval($address['quyu']))->getField('name');
        
            $this->ajaxReturn(['code' => 0, 'msg'=>'','receiverInfo'=>$arr]);
        } else {
            $this->ajaxReturn(['code' => 1, 'msg'=>'非法请求!']);
        }
    }

    /**
     * [searchHotKey 获取编辑的收货地址]
     * @return [type] [description]
     */
    public function delUserAddress()
    {
        if (IS_GET) {
            $addr_id = intval($_REQUEST['id']);
            $uid = intval($_REQUEST['uid']);
            if (!$addr_id || !$uid) {
                $this->ajaxReturn(['code' => 1, 'msg'=>'参数错误!']);
            }

            $res = M('address')->where('uid='.intval($uid).' AND id = '.$addr_id)->delete();
            if ($res) {
                $this->ajaxReturn(['code' => 0, 'msg'=>'']);
            } else {
                $this->ajaxReturn(['code' => 1, 'msg'=>'操作失败!']);
            }
        } else {
            $this->ajaxReturn(['code' => 1, 'msg'=>'非法请求!']);
        }
    }

    /**
     * [searchHotKey 购物车下单]
     * @return [type] [description]
     */
    public function buyCart()
    {
        if (IS_GET) {
            $cart_id = trim($_REQUEST['cart_id'], ',');
            $uid = intval($_REQUEST['uid']);
            if (!$uid || !$cart_id) {
                $this->ajaxReturn(['code' => 1, 'msg'=>'参数错误!','err'=>$cart_id ."-->".__LINE__]);
            }

            $address = M('address');

            //收货地址
            $add = $address->where('uid='.intval($uid).' AND is_default=1')->order('is_default desc,id desc')->limit(1)->find();

            $product = M('product');
            $shopping = M('shopping_char');
            
            $id = explode(',', $cart_id);
            $qz = C('DB_PREFIX');
            $pro = array();
            $pro1 = array();
            foreach ($id as $k => $v) {
                //检测购物车是否有对应数据
                $check_cart = $shopping->where('id='.intval($v))->getField('id');
                if (!$check_cart) {
                    $this->ajaxReturn(['code' => 1, 'msg'=>'非法操作!','err'=>__LINE__]);
                }

                $pro[$k] = $shopping->where(''.$qz.'shopping_char.uid='.intval($uid).' and '.$qz.'shopping_char.id='.$v)->join('LEFT JOIN __PRODUCT__ ON __PRODUCT__.id=__SHOPPING_CHAR__.pid')->join('LEFT JOIN __SHANGCHANG__ ON __SHANGCHANG__.id=__SHOPPING_CHAR__.shop_id')->field(''.$qz.'product.num as pnum,'.$qz.'shopping_char.id,'.$qz.'shopping_char.pid,'.$qz.'product.name,'.$qz.'product.shop_id,'.$qz.'product.photo_x,'.$qz.'product.price_yh,'.$qz.'product.pro_type,'.$qz.'shopping_char.num,'.$qz.'shopping_char.buff,'.$qz.'shopping_char.price')->find();


                if ($pro[$k]['buff'] != '') {
                    $pro[$k]['zprice'] = $pro[$k]['price'] * $pro[$k]['num'];
                } else {
                    $pro[$k]['price'] = $pro[$k]['price_yh'];
                    $pro[$k]['zprice'] = $pro[$k]['price'] * $pro[$k]['num'];
                }
                $pro[$k]['photo_x'] = __DATAURL__.$pro[$k]['photo_x'];

                //获取可用优惠券
                $vou = $this->get_voucher($uid, intval($pro[$k]['pid']), $id);
            }

            //计算总价
            // foreach ($id as $ks => $vs) {
            //     $pro1[$ks] = $shopping->where(''.$qz.'shopping_char.uid='.intval($uid).' and '.$qz.'shopping_char.id='.$vs)->join('LEFT JOIN __PRODUCT__ ON __PRODUCT__.id=__SHOPPING_CHAR__.pid')->join('LEFT JOIN __SHANGCHANG__ ON __SHANGCHANG__.id=__SHOPPING_CHAR__.shop_id')->field(''.$qz.'product.num as pnum,'.$qz.'shopping_char.id,'.$qz.'shangchang.name as sname,'.$qz.'product.name,'.$qz.'product.photo_x,'.$qz.'product.price_yh,'.$qz.'shopping_char.num,'.$qz.'shopping_char.buff,'.$qz.'shopping_char.price')->find();
            //     if ($pro1[$ks]['buff']) {
            //         $pro1[$ks]['zprice'] = $pro1[$ks]['price'] * $pro1[$ks]['num'];
            //     } else {
            //         $pro1[$ks]['price'] = $pro1[$ks]['price_yh'];
            //         $pro1[$ks]['zprice'] = $pro1[$ks]['price'] * $pro1[$ks]['num'];
            //     }
            //     $price += $pro1[$ks]['zprice'];
            // }
            
            //去掉特惠商品的总价
            $limitPrice = 0;
            //计算总价
            foreach ($id as $ks => $vs) {
                $pro1[$ks] = $shopping->where(''.$qz.'shopping_char.uid='.intval($uid).' and '.$qz.'shopping_char.id='.$vs)->join('LEFT JOIN __PRODUCT__ ON __PRODUCT__.id=__SHOPPING_CHAR__.pid')->join('LEFT JOIN __SHANGCHANG__ ON __SHANGCHANG__.id=__SHOPPING_CHAR__.shop_id')->field(''.$qz.'product.num as pnum,'.$qz.'shopping_char.id,'.$qz.'shangchang.name as sname,'.$qz.'product.name,'.$qz.'product.photo_x,'.$qz.'product.price_yh,'.$qz.'shopping_char.num,'.$qz.'shopping_char.buff,'.$qz.'shopping_char.price,'.$qz.'product.pro_type')->find();
                if ($pro1[$ks]['buff']) {
                    $pro1[$ks]['zprice'] = $pro1[$ks]['price'] * $pro1[$ks]['num'];
                } else {
                    $pro1[$ks]['price'] = $pro1[$ks]['price_yh'];
                    $pro1[$ks]['zprice'] = $pro1[$ks]['price'] * $pro1[$ks]['num'];
                }
                if ($pro1[$ks]['pro_type'] == 2) {
                    $limitPrice += $pro1[$ks]['zprice'];
                }
                $price += $pro1[$ks]['zprice'];
            }

            $limitPrice = $price - $limitPrice;


            if (!$add) {
                $addemt = 0;
            } else {
                $addemt = 1;
            }

            //获取用户可用积分
            $userScore = M('user')->where(['id'=>$uid])->getField('jifen');

            //vou 优惠券 price总价 adds收货地址 addemt 是否存在收货地址
            //$this->ajaxReturn(['code' => 0, 'msg'=>'','list'=>$pro,'vou' => $vou, 'price' => floatval($price), 'adds' => $add, 'addemt' => $addemt,'userScore'=>intval($userScore)]);
            $this->ajaxReturn(['code' => 0, 'msg'=>'','list'=>$pro,'vou' => $vou, 'price' => floatval($price), 'adds' => $add, 'addemt' => $addemt,'userScore'=>intval($userScore), 'limitPrice'=>floatval(number_format($limitPrice, 2))]);
            // echo json_encode(array('status' => 1, 'vou' => $vou, 'price' => floatval($price), 'pro' => $pro, 'adds' => $add, 'addemt' => $addemt));
            // exit();
        } else {
            $this->ajaxReturn(['code' => 1, 'msg'=>'非法请求!']);
        }
    }

    //****************************
    // 获取可用优惠券
    //****************************
    public function get_voucher($uid, $pid, $cart_id)
    {
        $qz = C('DB_PREFIX');
        //计算总价
        $prices = 0;
        foreach ($cart_id as $ks => $vs) {
            $pros = M('shopping_char')->where(''.$qz.'shopping_char.uid='.intval($uid).' AND '.$qz.'shopping_char.id='.$vs)->join('LEFT JOIN __PRODUCT__ ON __PRODUCT__.id=__SHOPPING_CHAR__.pid')->join('LEFT JOIN __SHANGCHANG__ ON __SHANGCHANG__.id=__SHOPPING_CHAR__.shop_id')->field(''.$qz.'shopping_char.num,'.$qz.'shopping_char.price,'.$qz.'shopping_char.type')->find();
            $zprice = $pros['price'] * $pros['num'];
            $prices += $zprice;
        }

        $condition = array();
        $condition['uid'] = intval($uid);
        $condition['status'] = array('eq', 1);
        $condition['start_time'] = array('lt', time());
        $condition['end_time'] = array('gt', time());
        $condition['full_money'] = array('elt', floatval($prices));

        $vou = M('user_voucher')->where($condition)->order('addtime desc')->select();
        $vouarr = array();
        foreach ($vou as $k => $v) {
            $chk_order = M('order')->where('uid='.intval($uid).' AND vid='.intval($v['vid']).' AND status>0')->find();
            $vou_info = M('voucher')->where('id='.intval($v['vid']))->find();
            $proid = explode(',', trim($vou_info['proid'], ','));
            if (('all' == $vou_info['proid'] || '' == $vou_info['proid'] || in_array($pid, $proid)) && !$chk_order) {
                $arr = array();
                $arr['vid'] = intval($v['vid']);
                $arr['full_money'] = floatval($v['full_money']);
                $arr['amount'] = floatval($v['amount']);
                $vouarr[] = $arr;
            }
        }

        return $vouarr;
    }

    /**
     * [searchHotKey  购物车结算 下订单]
     * @return [type] [description]
     */
    public function payment()
    {
        if (IS_GET) {
            $cart_id = trim($_REQUEST['cart_id'], ',');
            $uid = intval($_REQUEST['uid']);
            if (!$cart_id || !$uid) {
                $this->ajaxReturn(['code' => 1, 'msg'=>'参数错误!']);
            }

            $product = M('product');
            //运费
            $post = M('post');
            $order = M('order');
            $order_pro = M('order_product');
            $shopping = M('shopping_char');


            /*=============================================
            =            检查订单商品库存                 =
            =============================================*/
        
            $cid = explode(',', $cart_id);  //这是购物车id
            $arr = [];
            //检测产品数量
            foreach ($cid as $key => $var) {
                $pid = $shopping->where('id='.intval($var))->getField('pid');
                $num = $product->where('id='.$pid.' AND del=0 AND is_down=0')->getField('num');
                if ($num == 0) {
                    $arr[$key] = $pid;
                }
            }
            if (count($arr) > 0) {
                $this->ajaxReturn(['code' => 1, 'msg'=>'该商品已抢光!','err'=>$arr]);
            }
        
            /*=====  End of 检查订单商品库存       ======*/
        

            //生成订单
            try {
                $qz = C('DB_PREFIX'); //前缀

                $cart_id = explode(',', $cart_id);  //产品id
                $shop = array();
                foreach ($cart_id as $ke => $vl) {
                    $shop[$ke] = $shopping->where(''.$qz.'shopping_char.uid='.intval($uid).' and '.$qz.'shopping_char.id='.$vl)->join('LEFT JOIN __PRODUCT__ ON __PRODUCT__.id=__SHOPPING_CHAR__.pid')->field(''.$qz.'shopping_char.pid,'.$qz.'shopping_char.num,'.$qz.'shopping_char.shop_id,'.$qz.'shopping_char.buff,'.$qz.'shopping_char.price,'.$qz.'product.price_yh')->find();
                    $num += $shop[$ke]['num'];
                    if ($shop[$ke]['buff'] != '') {
                        $ozprice += $shop[$ke]['price'] * $shop[$ke]['num'];
                    } else {
                        $shop[$ke]['price'] = $shop[$ke]['price_yh'];
                        $ozprice += $shop[$ke]['price'] * $shop[$ke]['num'];
                    }
                }

                $yunPrice = array();
                if ($_REQUEST['yunfei']) {
                    $yunPrice = $post->where('id='.intval($_REQUEST['yunfei']))->find();
                }

                $data['shop_id'] = $shop[$ke]['shop_id'];
                $data['uid'] = intval($uid);

                if (!empty($yunPrice)) {
                    $data['post'] = $yunPrice['id'];
                    $data['price'] = floatval($ozprice) + $yunPrice['price'];
                } else {
                    $data['post'] = 0;
                    $data['price'] = floatval($ozprice);
                }

                $data['amount'] = $data['price'];
                $vid = intval($_REQUEST['vid']);
                if ($vid) {
                    $vouinfo = M('user_voucher')->where('status=1 AND uid='.intval($uid).' AND vid='.intval($vid))->find();
                    $chk = M('order')->where('uid='.intval($uid).' AND vid='.intval($vid).' AND status>0')->find();
                    if (!$vouinfo || $chk) {
                        //throw new \Exception("此优惠券不可用，请选择其他.".__LINE__);
                        echo json_encode(array('status' => 0, 'err' => '此优惠券不可用，请选择其他.'));
                        exit();
                    }
                    if ($vouinfo['end_time'] < time()) {
                        //throw new \Exception("优惠券已过期了.".__LINE__);
                        echo json_encode(array('status' => 0, 'err' => '优惠券已过期了.'.__LINE__));
                        exit();
                    }
                    if ($vouinfo['start_time'] > time()) {
                        //throw new \Exception("优惠券还未生效.".__LINE__);
                        echo json_encode(array('status' => 0, 'err' => '优惠券还未生效.'.__LINE__));
                        exit();
                    }
                    $data['vid'] = intval($vid);
                    $data['amount'] = floatval($data['price']) - floatval($vouinfo['amount']);
                }

                $data['addtime'] = time();
                $data['del'] = 0;
                $data['type'] = $_REQUEST['type'];
                $data['status'] = 10;

                $adds_id = intval($_REQUEST['aid']);
                if (!$adds_id && $_REQUEST['address'] == '') {
                    throw new \Exception('请选择收货地址.'.__LINE__);
                }
                //$adds_info =  M('address')->where('id='.intval($adds_id))->find();
                $adds_info = ($_REQUEST['address'] == '') ? M('address')->where('id='.intval($adds_id))->find() : json_decode($_REQUEST['address'], true);
                $data['receiver'] = $adds_info['name'];
                $data['tel'] = $adds_info['tel'];
                $data['address_xq'] = $adds_info['address_xq'];
                $data['code'] = $adds_info['code']?$adds_info['code']:100000;
                $data['product_num'] = $num;
                $data['remark'] = $_REQUEST['remark'];
                $data['order_sn'] = $this->build_order_no(); //生成唯一订单号

                $result = $order->add($data);
                if ($result) {
                    //$prid = explode(",", $_REQUEST['ids']);
                    foreach ($cart_id as $key => $var) {
                        $shops[$key] = $shopping->where(''.$qz.'shopping_char.uid='.intval($uid).' and '.$qz.'shopping_char.id='.intval($var))->join('LEFT JOIN __PRODUCT__ ON __PRODUCT__.id=__SHOPPING_CHAR__.pid')->field(''.$qz.'shopping_char.pid,'.$qz.'shopping_char.num,'.$qz.'shopping_char.shop_id,'.$qz.'shopping_char.buff,'.$qz.'shopping_char.price,'.$qz.'product.name,'.$qz.'product.photo_x,'.$qz.'product.price_yh,'.$qz.'product.num as pnum')->find();
                        if ($shops[$key]['buff'] == '' || !$shops[$key]['buff']) {
                            $shops[$key]['price'] = $shops[$key]['price_yh'];
                        }

                        $buff_text = '';
                        if ($shops[$key]['buff']) {
                            //验证属性
                            $buff = explode(',', $shops[$key]['buff']);
                            if (is_array($buff)) {
                                foreach ($buff as $keys => $val) {
                                    $ggid = M('guige')->where('id='.intval($val))->getField('name');
                                    $buff_text .= $ggid.' ';
                                }
                            }
                        }

                        $date = array();
                        $date['pid'] = $shops[$key]['pid'];
                        $date['name'] = $shops[$key]['name'];
                        $date['order_id'] = $result;
                        $date['price'] = $shops[$key]['price'];
                        $date['photo_x'] = $shops[$key]['photo_x'];
                        $date['pro_buff'] = trim($buff_text, ' ');
                        $date['addtime'] = time();
                        $date['num'] = $shops[$key]['num'];
                        $date['pro_guige'] = '';
                        $res = $order_pro->add($date);
                        if (!$res) {
                            throw new \Exception('下单 失败！'.__LINE__);
                        }
                        //检查产品是否存在，并修改库存
                        $check_pro = $product->where('id='.intval($date['pid']).' AND del=0 AND is_down=0')->field('num,shiyong')->find();
                        $up = array();
                        $up['num'] = intval($check_pro['num']) - intval($date['num']);
                        $up['shiyong'] = intval($check_pro['shiyong']) + intval($date['num']);
                        $product->where('id='.intval($date['pid']))->save($up);
                        //echo  $product->getLastSql();
                        //删除购物车数据
                        $shopping->where('uid='.intval($uid).' AND id='.intval($var))->delete();
                    }
                } else {
                    throw new \Exception('下单 失败！');
                }
            } catch (Exception $e) {
                $this->ajaxReturn(['code' => 1, 'msg'=>$e->getMessage()]);
                // echo json_encode(array('code' => 1, 'msg' => $e->getMessage()));
                // exit();
            }

            //把需要的数据返回
            $arr = array();
            $arr['order_id'] = $result;
            $arr['order_sn'] = $data['order_sn'];
            $arr['pay_type'] = $_REQUEST['type'];

            $this->ajaxReturn(['code' => 0, 'msg'=>'','data' => $arr]);
        } else {
            $this->ajaxReturn(['code' => 1, 'msg'=>'非法请求!']);
        }
    }


    /**
     * [searchHotKey  购物车结算 下订单]
     * @return [type] [description]
     */
    public function payment1()
    {
        if (IS_GET) {
            $cart_id = trim($_REQUEST['cart_id'], ',');
            $uid = intval($_REQUEST['uid']);
            if (!$cart_id || !$uid) {
                $this->ajaxReturn(['code' => 1, 'msg'=>'参数错误!']);
            }

            $product = M('product');
            //运费
            $post = M('post');
            $order = M('order');
            $order_pro = M('order_product');
            $shopping = M('shopping_char');


            /*=============================================
            =            检查订单商品库存                 =
            =============================================*/
        
            $cid = explode(',', $cart_id);  //这是购物车id
            $arr = [];
            //检测产品数量
            foreach ($cid as $key => $var) {
                $pid = $shopping->where('id='.intval($var))->getField('pid');
                $num = $product->where('id='.$pid.' AND del=0 AND is_down=0')->getField('num');
                if ($num == 0) {
                    $arr[$key] = $pid;
                }
            }
            if (count($arr) > 0) {
                $this->ajaxReturn(['code' => 1, 'msg'=>'该商品已抢光!','err'=>$arr]);
            }
        
            /*=====  End of 检查订单商品库存       ======*/
        

            //生成订单
            try {
                $qz = C('DB_PREFIX'); //前缀

                $cart_id = explode(',', $cart_id);  //产品id
                $shop = array();
                foreach ($cart_id as $ke => $vl) {
                    $shop[$ke] = $shopping->where(''.$qz.'shopping_char.uid='.intval($uid).' and '.$qz.'shopping_char.id='.$vl)->join('LEFT JOIN __PRODUCT__ ON __PRODUCT__.id=__SHOPPING_CHAR__.pid')->field(''.$qz.'shopping_char.pid,'.$qz.'shopping_char.num,'.$qz.'shopping_char.shop_id,'.$qz.'shopping_char.buff,'.$qz.'shopping_char.price,'.$qz.'product.price_yh')->find();
                    $num += $shop[$ke]['num'];
                    if ($shop[$ke]['buff'] != '') {
                        $ozprice += $shop[$ke]['price'] * $shop[$ke]['num'];
                    } else {
                        $shop[$ke]['price'] = $shop[$ke]['price_yh'];
                        $ozprice += $shop[$ke]['price'] * $shop[$ke]['num'];
                    }
                }

                $yunPrice = array();
                if ($_REQUEST['yunfei']) {
                    $yunPrice = $post->where('id='.intval($_REQUEST['yunfei']))->find();
                }

                $data['shop_id'] = $shop[$ke]['shop_id'];
                $data['uid'] = intval($uid);

                if (!empty($yunPrice)) {
                    $data['post'] = $yunPrice['id'];
                    $data['price'] = floatval($ozprice) + $yunPrice['price'];
                } else {
                    $data['post'] = 0;
                    $data['price'] = floatval($ozprice);
                }


                //优惠券id
                $vid = intval($_REQUEST['vid']);

                //使用积分优惠券 -5元
                if ($_REQUEST['deduScore'] && !$vid) {
                    $data['amount'] = floatval($ozprice - 5);
                    //XXX 检测一下然后 减去相应积分
                    // actualPrice 这是前台传来的计算的结果
                    // 一旦下单就必须减去积分 因为下单后支付金额是不会变的
                    M('user')->where(['id'=>$uid])->setDec('jifen', 100);
                    // 减是没错了 不过没有记录到积分使用里
                    M('user_course')->add(['uid'=>$uid,'addtime'=>time(),'sex'=>2]);
                } else {
                    $data['amount'] = $data['price'];
                }

                //使用后台优惠券
                if ($vid) {
                    $vouinfo = M('user_voucher')->where('status=1 AND uid='.intval($uid).' AND vid='.intval($vid))->find();
                    $chk = M('order')->where('uid='.intval($uid).' AND vid='.intval($vid).' AND status>0')->find();
                    if (!$vouinfo || $chk) {
                        //throw new \Exception("此优惠券不可用，请选择其他.".__LINE__);
                        // echo json_encode(array('status' => 0, 'err' => '此优惠券不可用，请选择其他.'));
                        // exit();
                        $this->ajaxReturn(['code' => 1, 'msg'=>'此优惠券不可用，请选择其他.','err'=>__LINE__]);
                    }
                    if ($vouinfo['end_time'] < time()) {
                        //throw new \Exception("优惠券已过期了.".__LINE__);
                        // echo json_encode(array('status' => 0, 'err' => '优惠券已过期了.'.__LINE__));
                        // exit();
                        $this->ajaxReturn(['code' => 1, 'msg'=>'优惠券已过期了.','err'=>__LINE__]);
                    }
                    if ($vouinfo['start_time'] > time()) {
                        //throw new \Exception("优惠券还未生效.".__LINE__);
                        // echo json_encode(array('status' => 0, 'err' => '优惠券还未生效.'.__LINE__));
                        // exit();
                        $this->ajaxReturn(['code' => 1, 'msg'=>'优惠券还未生效.','err'=>__LINE__]);
                    }
                    $data['vid'] = intval($vid);
                    $data['amount'] = floatval($data['price']) - floatval($vouinfo['amount']);
                    M('user_voucher')->where('uid='.intval($uid).' AND vid='.intval($vid))->save(['status'=>2]);
                }

                $data['addtime'] = time();
                $data['del'] = 0;
                $data['type'] = $_REQUEST['type'];
                $data['status'] = 10;

                $adds_id = intval($_REQUEST['aid']);
                if (!$adds_id && $_REQUEST['address'] == '') {
                    throw new \Exception('请选择收货地址.'.__LINE__);
                }
                $adds =  M('address')->field('city,quyu')->where('id='.intval($adds_id))->find();
                //如果是南京本地发货 自动选择当地区域门店为配送起点
                if ($adds['city'] == 891) {
                    $rr = F('shopScope');
                    $data['kuaidi_name'] = (!$rr[$adds['quyu']]) ? "仓库配送" : $rr[$adds['quyu']];
                }

                $adds_info = ($_REQUEST['address'] == '') ? M('address')->where('id='.intval($adds_id))->find() : json_decode($_REQUEST['address'], true);
                $data['receiver'] = $adds_info['name'];
                $data['tel'] = $adds_info['tel'];
                $data['address_xq'] = $adds_info['address_xq'];
                $data['code'] = $adds_info['code']?$adds_info['code']:100000;
                $data['product_num'] = $num;
                $data['remark'] = $_REQUEST['remark'];
                $data['order_sn'] = $this->build_order_no(); //生成唯一订单号

                $result = $order->add($data);
                if ($result) {
                    //$prid = explode(",", $_REQUEST['ids']);
                    foreach ($cart_id as $key => $var) {
                        $shops[$key] = $shopping->where(''.$qz.'shopping_char.uid='.intval($uid).' and '.$qz.'shopping_char.id='.intval($var))->join('LEFT JOIN __PRODUCT__ ON __PRODUCT__.id=__SHOPPING_CHAR__.pid')->field(''.$qz.'shopping_char.pid,'.$qz.'shopping_char.num,'.$qz.'shopping_char.shop_id,'.$qz.'shopping_char.buff,'.$qz.'shopping_char.price,'.$qz.'product.name,'.$qz.'product.photo_x,'.$qz.'product.price_yh,'.$qz.'product.num as pnum')->find();
                        if ($shops[$key]['buff'] == '' || !$shops[$key]['buff']) {
                            $shops[$key]['price'] = $shops[$key]['price_yh'];
                        }

                        $buff_text = '';
                        if ($shops[$key]['buff']) {
                            //验证属性
                            $buff = explode(',', $shops[$key]['buff']);
                            if (is_array($buff)) {
                                foreach ($buff as $keys => $val) {
                                    $ggid = M('guige')->where('id='.intval($val))->getField('name');
                                    $buff_text .= $ggid.' ';
                                }
                            }
                        }

                        $date = array();
                        $date['pid'] = $shops[$key]['pid'];
                        $date['name'] = $shops[$key]['name'];
                        $date['order_id'] = $result;
                        $date['price'] = $shops[$key]['price'];
                        $date['photo_x'] = $shops[$key]['photo_x'];
                        $date['pro_buff'] = trim($buff_text, ' ');
                        $date['addtime'] = time();
                        $date['num'] = $shops[$key]['num'];
                        $date['pro_guige'] = '';
                        $res = $order_pro->add($date);
                        if (!$res) {
                            throw new \Exception('下单 失败！'.__LINE__);
                        }
                        //检查产品是否存在，并修改库存
                        $check_pro = $product->where('id='.intval($date['pid']).' AND del=0 AND is_down=0')->field('num,shiyong')->find();
                        $up = array();
                        $up['num'] = intval($check_pro['num']) - intval($date['num']);
                        $up['shiyong'] = intval($check_pro['shiyong']) + intval($date['num']);
                        $product->where('id='.intval($date['pid']))->save($up);
                        //echo  $product->getLastSql();
                        //删除购物车数据
                        $shopping->where('uid='.intval($uid).' AND id='.intval($var))->delete();
                    }
                } else {
                    throw new \Exception('下单 失败！');
                }
            } catch (Exception $e) {
                $this->ajaxReturn(['code' => 1, 'msg'=>$e->getMessage()]);
                // echo json_encode(array('code' => 1, 'msg' => $e->getMessage()));
                // exit();
            }

            //把需要的数据返回
            $arr = array();
            $arr['order_id'] = $result;
            $arr['order_sn'] = $data['order_sn'];
            $arr['pay_type'] = $_REQUEST['type'];

            $this->ajaxReturn(['code' => 0, 'msg'=>'','data' => $arr]);
        } else {
            $this->ajaxReturn(['code' => 1, 'msg'=>'非法请求!']);
        }
    }
    /**针对涂屠生成唯一订单号
    *@return int 返回16位的唯一订单号
    */
    public function build_order_no()
    {
        return date('Ymd').substr(implode(null, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8);
    }


    /**
     * [searchHotKey 查询订单详情]
     * @return [type] [description]
     */
    public function getPayOrderDetail()
    {
        if (IS_GET) {
            $oid = intval($_REQUEST['oid']);
            if (!$oid) {
                $this->ajaxReturn(['code' => 1, 'msg'=>'参数错误!']);
            }

            $orders = M('order');

            //$res = $orders->where('id='.$oid)->field('price_h')->find();

            $res = $orders->where('id='.$oid)->getField('price_h');

            if ($res) {
                $this->ajaxReturn(['code' => 0, 'msg'=>'','order'=>$res]);
            } else {
                $this->ajaxReturn(['code' => 1, 'msg'=>'操作失败!']);
            }
        } else {
            $this->ajaxReturn(['code' => 1, 'msg'=>'非法请求!']);
        }
    }

    /**
      * [searchHotKey 查询优惠券列表  这是优惠券领取页面]
      * @return [type] [description]
      */
    public function getCouponList()
    {
        if (IS_GET) {
            $uid = intval($_REQUEST['uid']);
            if (!$uid) {
                $this->ajaxReturn(['code' => 1, 'msg'=>'参数错误!']);
            }

            if ($_REQUEST['flag'] == "xianxia") {
                $res = M('voucher')->where("end_time > UNIX_TIMESTAMP(NOW()) AND del = 0 AND type > 2")->select();
            } else {
                $res = M('voucher')->where("end_time > UNIX_TIMESTAMP(NOW()) AND del = 0 AND type = 1")->select();
            }
            
            if ($res === false) {
                $this->ajaxReturn(['code' => 1, 'msg'=>'查询失败!']);
            }
            //已经拥有的优惠券
            $vids = M('user_voucher')->where(['uid'=>$uid])->getField('vid', true);

            //状态说明 status = 0 未领取优惠券 1 已经领取的优惠券
            foreach ($res as $k => $v) {
                $res[$k]['start_time'] = date('Y.m.d h:i', $v['start_time']);
                $res[$k]['end_time'] = date('Y.m.d h:i', $v['end_time']);
                $res[$k]['receive_num'] = intval($v['receive_num']);
                $res[$k]['count'] = intval($v['count']);
                $date = new Carbon(date('Y-m-d h:i:s', $v['end_time']));
                $res[$k]['littleTime'] = $date->diffInDays(Carbon::now())+1;
                $res[$k]['status'] = (in_array($v['id'], $vids)) ? 1 : 0;
                //$res[$k]['percent'] = (intval($v['receive_num']) / intval($v['count'])) * 100;
            }

            $this->ajaxReturn(['code' => 0, 'msg'=>'','list'=>$res]);
        } else {
            $this->ajaxReturn(['code' => 1, 'msg'=>'非法请求!']);
        }
    }

    /**
      * [searchHotKey 获得优惠券]
      * @return [type] [description]
      */
    public function addVoucher()
    {
        if (IS_GET) {
            $uid = intval($_REQUEST['uid']);
            $vid = intval($_REQUEST['vid']);
            if (!$uid || !$vid) {
                $this->ajaxReturn(['code' => 1, 'msg'=>'参数错误!']);
            }

            
            $voucher = M('voucher')->where(['id'=>$vid,'del'=>0])->find();
            //这里删除的验证
            if (!$voucher) {
                $this->ajaxReturn(['code' => 1, 'msg'=>'活动已下线!']);
            }
            if ($voucher['receive_num'] == $voucher['count']) {
                $this->ajaxReturn(['code' => 1, 'msg'=>'已领完!']);
            }
            //不能二次领取
            $r = M('user_voucher')->where(['uid'=>$uid,'vid'=>$vid])->find();
            if ($r) {
                $this->ajaxReturn(['code' => 1, 'msg'=>'不能重复领取!']);
            }

            M('voucher')->where(['id'=>$vid])->setInc("receive_num");

            $data['uid']=$uid;
            $data['vid']=$vid;
            $data['shop_id']=$voucher['shop_id'];
            $data['full_money']=$voucher['full_money'];
            $data['amount']=$voucher['amount'];
            $data['start_time']=$voucher['start_time'];
            $data['end_time']=$voucher['end_time'];
            $data['addtime']=time();
            $data['status']=1;

            $res = M('user_voucher')->data($data)->add();

            if ($res === false) {
                $this->ajaxReturn(['code' => 1, 'msg'=>'查询失败!']);
            }
 
            $this->ajaxReturn(['code' => 0, 'msg'=>'成功领取!','list'=>$res]);
        } else {
            $this->ajaxReturn(['code' => 1, 'msg'=>'非法请求!']);
        }
    }

    /**
      * [searchHotKey 查询优惠券列表 这是付款的时候的优惠券页面]
      * @return [type] [description]
      */
    public function getCustCouponList()
    {
        if (IS_GET) {
            $uid = intval($_REQUEST['uid']);
            $flag = intval($_REQUEST['flag']);
            $totalPrice = floatval($_REQUEST['totalPrice']);    //浮点数比较
            if (!$uid && !$flag && !$totalPrice) {
                $this->ajaxReturn(['code' => 1, 'msg'=>'参数错误!']);
            }


            //好像失效状态没什么意义 没有监控 只有这时再改变一下看过期的给与一个状态 但也好像没必要
            // if($flag == 1){
            //     $where['status'] = $flag;
            // }
            
            $olds = M('user_voucher')->where('uid='.$uid.' and end_time < UNIX_TIMESTAMP(NOW())')->getField('id', true);
            if (count($olds)) {
                $strOlds = implode(",", $olds);
                M('user_voucher')->where(" id in (".$strOlds.")")->save(['status'=>3]);
            }
            

            if ($totalPrice) {
                $where = 'uid='.$uid.' and status=1 and end_time > UNIX_TIMESTAMP(NOW()) AND  full_money <'.$totalPrice;
            } else {
                $where['uid'] = $uid;
                $where['status'] = $flag;
            }

            //已经拥有的优惠券
            $vids = M('user_voucher')->where($where)->getField('vid', true);
            if (!count($vids)) {
                $this->ajaxReturn(['code' => 0, 'msg'=>'未有可使用优惠券','list'=>'']);
            } else {
                $strVids = implode(",", $vids);
                //把时间去掉 就是失效的也查询
                $res = M('voucher')->where("del = 0 and id in (".$strVids.")")->select();
                if ($res === false) {
                    $this->ajaxReturn(['code' => 1, 'msg'=>'查询失败!']);
                }
            }



            //状态说明 status = 0 未领取优惠券 1 已经领取的优惠券
            foreach ($res as $k => $v) {
                $res[$k]['start_time'] = date('Y.m.d h:i', $v['start_time']);
                $res[$k]['end_time'] = date('Y.m.d h:i', $v['end_time']);
                $date = new Carbon(date('Y-m-d h:i:s', $v['end_time']));
                $res[$k]['littleTime'] = $date->diffInDays(Carbon::now())+1;
                if ($flag == 3) {
                    $res[$k]['status'] = 4;
                } else {
                    $res[$k]['status'] = ($flag == 1) ? 2 : 3;
                }
                

                //$res[$k]['percent'] = (intval($v['receive_num']) / intval($v['count'])) * 100;
            }

            $this->ajaxReturn(['code' => 0, 'msg'=>'','list'=>$res,'sql'=>$strVids]);
        } else {
            $this->ajaxReturn(['code' => 1, 'msg'=>'非法请求!']);
        }
    }

    /**
     * [messageInfo 发送模版消息]
     * @return [type] [发送模版消息]
     */
    public function messageInfo()
    {
        if (IS_GET) {
            $openid = $_REQUEST['openid'];
            $fid = $_REQUEST['fid'];
            $oid = intval($_REQUEST['oid']);
            $flag = intval($_REQUEST['flag']);
            $msg = $_REQUEST['msg']?$_REQUEST['msg']:'null';
            if (!$openid || !$fid || !$oid || $flag<0) {
                $this->ajaxReturn(['code' => 1, 'msg'=>'参数错误!']);
            }



            $client = new Client();
            // S('name',$value,300);
            $token = S('access_token');
            if (!$token) {
                $r = $client->request('GET', 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=wx026f26d858d16098&secret=4ec65ec9c4d07568e78b0954608125ad', ['verify' => false]);
                $b = json_decode($r->getBody());
                $token = $b->access_token;
                S('access_token', $token, 7000);
            }
            // $token = $client->request('GET', 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=wx026f26d858d16098&secret=4ec65ec9c4d07568e78b0954608125ad', ['verify' => false]);
            // $b = json_decode($token->getBody());
            //echo $b->access_token;  //== 13_Uf2nfA8eQ17qTYcwhdVcsQuT2CFwx2AwW9FCqBvq5SMUII2wRqWebzBc5xOwoI5WBOWwm2MJgUgOqQDZpbb3d_-7AVbUCElUYIIbdRB3-UX5keqVdqhb3UB257oxR4kPwHUXB2ZGOD6iQkX5KLNgAJAEFH
            //dump($token);
            $url = 'https://api.weixin.qq.com/cgi-bin/message/wxopen/template/send?access_token='.$token;

            $d = $this->messageData($oid, $flag, $msg);
            
            //模版id flag 0 购买成功通知 1 订单支付失败通知 2 待付款提醒     //这里还是放在配置文件里吧
            $template_id = ['WLBNaOxKetJ86Sw8B8H8Fd2YpShGAHAEr8ierqcmOkE','62mYgCdy3XKPL6bd7c1eGpOzf0AgoLaZhetJIyvfvmk'];

            $r = $client->request('POST', $url, [
                'json'=>[
                    'touser'=>$openid,
                    'template_id'=>$template_id[$flag],
                    'page'=> '/pages/home',
                    'form_id'=> $fid,
                    'page'=> '/pages/info',
                    'data'=>$d
                ],
                'verify' => false]);
            
            //dump($r->getBody()->getContents());

        
            $this->ajaxReturn(['code' => 0, 'msg'=>'成功领取!','data'=>$r->getBody()->getContents()]);
        } else {
            $this->ajaxReturn(['code' => 1, 'msg'=>'非法请求!']);
        }
    }

    /**
     * 组合发送信息的格式 比如模版
     */
    public function messageData($oid, $flag, $msg)
    {
        //$oid = "2018082055504999";
        //读取
        $order_info = M('order')->field('id,amount,addtime,order_sn')->where(['id'=>$oid])->find();
        $goods = M('order_product')->field('name')->where(['order_id'=>$order_info['id']])->find();
        //dump($order_info);
        switch ($flag) {
            case 0:
                $data = [
                          "keyword1"=> [
                            "value"=>  $goods['name'],
                            "color"=> "#4a4a4a"
                          ],
                          "keyword2"=> [
                            "value"=> $order_info['amount']."元",
                            "color"=> "#9b9b9b"
                          ],
                          "keyword3"=> [
                            "value"=> $order_info['order_sn'],
                            "color"=> "#9b9b9b"
                          ],
                          "keyword4"=> [
                            "value"=> date('Y-m-d H:i:s', $order_info['addtime']),
                            "color"=> "#9b9b9b"
                          ]
                        ];
                break;
            case 1:
                $data = [
                          "keyword1"=> [
                            "value"=> $goods['name'],
                            "color"=> "#9b9b9b"
                          ],
                          "keyword2"=> [
                            "value"=> $order_info['amount']."元",
                            "color"=> "#9b9b9b"
                          ],
                           "keyword3"=> [
                            "value"=> date('Y-m-d H:i:s', $order_info['addtime']),
                            "color"=> "#4a4a4a"
                           ],
                          "keyword4"=> [
                            "value"=> $order_info['order_sn'],
                            "color"=> "#9b9b9b"
                          ],
                          "keyword5"=> [
                            "value"=> $msg,
                            "color"=> "#9b9b9b"
                          ]
                        ];
                break;
                // 代付款 已经发货 等等....
        }
        // dump($data);
        //$this->ajaxReturn(['code' => 1, 'msg'=>'非法请求!','data'=>$data]);
        return $data;
    }
}
