<?php

namespace Api\Controller;

vendor("Guzzle.autoloader");
vendor("Guzzle.GuzzleHttp.Client");
use GuzzleHttp\Client;

vendor("QRCode.QRCode");
use QRCode\QRCode;

// import("ORG.File");

class IndexController extends PublicController
{
    //***************************
    //  首页数据接口
    //***************************
    public function index()
    {
        //如果缓存首页没有数据，那么就读取数据库
        /***********获取首页顶部轮播图************/
        $ggtop = M('guanggao') -> where('position=1') -> order('sort desc,id asc') -> field('id,name,photo,action') -> limit(10) -> select();
        foreach ($ggtop as $k => $v) {
            if (!empty($v['action']) && M('product') -> find($v['action'])) {
                $ggtop[$k]['link'] = '../product/detail?productId=' . $v['action'];
            } else {
                $ggtop[$k]['link'] = 'index';
            }
            $ggtop[$k]['photo'] = __DATAURL__ . $v['photo'];
            $ggtop[$k]['name'] = urlencode($v['name']);
        }
        /***********获取首页顶部轮播图 end************/

        //======================
        //首页推荐品牌 20个
        //======================
        $brand = M('brand') -> where('1=1') -> field('id,name,photo') -> limit(20) -> select();
        foreach ($brand as $k => $v) {
            $brand[$k]['photo'] = __DATAURL__ . $v['photo'];
        }

        //======================
        //首页培训课程
        //======================
        $course = M('course') -> where('del=0') -> order('id desc') -> field('id,title,intro,photo') -> select();
        foreach ($course as $k => $v) {
            $course[$k]['photo'] = __DATAURL__ . $v['photo'];
        }

        //======================
        //首页推荐产品
        //======================
        //XXX 把隐藏分类的商品去除掉了
        // $pro_list = M('product')->where('del=0 AND pro_type=1 AND is_down=0 AND type=1')->order('sort desc,id desc')->field('id,name,intro,photo_x,price_yh,price,shiyong')->limit(16)->select();
        $arr = M('category') -> where(['bz_4' => 0]) -> getField('id', true);
        $str = implode(',', $arr);
        $pro_list = M('product') -> where('del=0 AND pro_type=1 AND is_down=0 AND type=1 AND cid in (' . $str . ')') -> order('sort desc,id desc') -> field('id,name,photo_x,price_yh,price,shiyong') -> limit(16) -> select();
        foreach ($pro_list as $k => $v) {
            $pro_list[$k]['photo_x'] = __DATAURL__ . $v['photo_x'];
        }

        //======================
        //首页热销产品
        //======================
        //这里直接用上面做好的 分类列表
        //$hot_list = M('product')->where('del=0 AND pro_type=1 AND is_down=0 AND is_hot=1')->order('sort desc,id desc')->field('id,name,intro,photo_x,price_yh,price,shiyong')->limit(4)->select();
        $hot_list = M('product') -> where('del=0 AND pro_type=1 AND is_down=0 AND is_hot=1 AND cid in (' . $str . ')') -> order('sort desc,id desc') -> field('id,name,intro,photo_x,price_yh,price,shiyong') -> limit(4) -> select();
        foreach ($hot_list as $k => $v) {
            $hot_list[$k]['photo_x'] = __DATAURL__ . $v['photo_x'];
        }

        //======================
        //首页第一栏广告
        //======================
        $ad_1 = M('guanggao') -> where('position=2') -> order('sort desc,id desc') -> field('action,photo') -> limit(1) -> find();
        $ad_1['photo'] = __DATAURL__ . $ad_1['photo'];

        //======================
        //首页分类 自己组建数组
        //======================
        $indeximg = M('indeximg') -> where('1=1') -> order('id asc') -> field('photo') -> select();
        $procat = array();
        $procat[0]['name'] = '新闻资讯';
        $procat[0]['imgs'] = __DATAURL__ . $indeximg[0]['photo'];
        $procat[0]['link'] = 'other';
        $procat[0]['ptype'] = 'news';

        $procat[1]['name'] = '教学优势';
        $procat[1]['imgs'] = __DATAURL__ . $indeximg[1]['photo'];
        $procat[1]['link'] = 'other';
        $procat[1]['ptype'] = 'jxys';

        $procat[2]['name'] = '学员风采';
        $procat[2]['imgs'] = __DATAURL__ . $indeximg[2]['photo'];
        $procat[2]['link'] = 'other';
        $procat[2]['ptype'] = 'xyfc';

        $procat[3]['name'] = '关于我们';
        $procat[3]['imgs'] = __DATAURL__ . $indeximg[3]['photo'];
        $procat[3]['link'] = 'other';
        $procat[3]['ptype'] = 'gywm';

        echo json_encode(array('ggtop' => $ggtop, 'procat' => $procat, 'prolist' => $pro_list, 'brand' => $brand, 'course' => $course, 'hotlist' => $hot_list, 'ad1' => $ad_1));
        exit();
    }

    //***************************
    //  首页产品 分页
    //***************************
    public function getlist()
    {
        $page = intval($_REQUEST['page']);
        $limit = intval($page * 16) - 16;

        $arr = M('category') -> where(['bz_4' => 0]) -> getField('id', true);
        $str = implode(',', $arr);
        // $pro_list = M('product')->where('del=0 AND pro_type=1 AND is_down=0 AND type=1')->order('sort desc,id desc')->field('id,name,photo_x,price_yh,shiyong')->limit($limit.',16')->select();
        $pro_list = M('product') -> where('del=0 AND pro_type=1 AND is_down=0 AND type=1 AND cid in (' . $str . ')') -> order('sort desc,id desc') -> field('id,name,photo_x,price_yh,price,shiyong') -> limit($limit . ',16') -> select();
        foreach ($pro_list as $k => $v) {
            $pro_list[$k]['photo_x'] = __DATAURL__ . $v['photo_x'];
        }
        echo json_encode(array('prolist' => $pro_list));
        exit();
    }

    public function ceshi()
    {
        $str = null;
        $strPol = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz';
        $max = strlen($strPol) - 1;

        for ($i = 0; $i < 32; ++$i) {
            $str .= $strPol[rand(0, $max)];
            //rand($min,$max)生成介于min和max两个数之间的一个随机整数
        }

        echo $str;
        $QRCode = new QRCode();
        // \File::write_file('Data\UploadFiles\product\3333.png', $QRCode->getQRCode());
        //  file_put_content 无法创建目录 只能拿已经存在的目录测试
        if (!$QRCode->isHasQR('152076592')) {
            $QRCode->createQRCode('152076592');
        } else {
            dump('已经存在!');
            dump('Data\UploadFiles\product\222\qrcode.png');
        }
    }

    /**
     * [ggtop 首页广告swiper]
     * @return [json] [广告滚动图]
     */
    public function ggtop()
    {
        //如果缓存首页没有数据，那么就读取数据库
        /***********获取首页顶部轮播图************/
        $ggtop = M('guanggao') -> where('position=1') -> order('sort desc,id asc') -> field('id,name,photo,action') -> limit(10) -> select();
        foreach ($ggtop as $k => $v) {
            if (!empty($v['action']) && M('product') -> find($v['action'])) {
                $ggtop[$k]['link'] = '/pages/goods_detail?id=' . $v['action'];
            } else {
                $ggtop[$k]['link'] = 'index';
            }
            $ggtop[$k]['photo'] = __DATAURL__ . $v['photo'];
            $ggtop[$k]['name'] = urlencode($v['name']);
        }
        /***********获取首页顶部轮播图 end************/

        $this -> ajaxReturn(['code' => 0, 'msg' => '', 'list' => $ggtop]);
    }

    public function sysConfig()
    {
        $m = F('ORDER_MSG');
        echo json_encode(['minimum' => $m['minimum'], 'freight' => $m['freight']]);
        exit();
    }

    /**
     * [getAccessToken 获取token用于请求二维码]
     * @return [type] [description]
     */
    public function getAccessToken()
    {
        // 这个配置的appid和$secret 有错误
        $wx_config = C('weixin');
        $appid = $wx_config['appid'];
        $secret = $wx_config['secret'];
        $client = new Client(['headers' => ['Accept' => 'application/json', 'Content-Type' => 'application/json']]);
        // $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid={$appid}&secret={$secret}";
        $url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=wx026f26d858d16098&secret=4ec65ec9c4d07568e78b0954608125ad';
        $response = $client -> request('GET', $url, ['verify' => false]);
        $json = $response -> getBody() -> getContents();
        $r = json_decode($json);
        $accessToken = $r->access_token;
        S('accessToken', $accessToken, 7100);
        return $accessToken;
    }

    /**
     * [getQRcode 获得二维码]
     * @return [type] [description]
     */
    public function getQRcode()
    {
        $client = new Client(['headers' => ['Accept' => 'application/json', 'Content-Type' => 'application/json']]);
        $token;
        if (S('accessToken')) {
            $token = S('accessToken');
        } else {
            $token = $this->getAccessToken();
        }
        dump($token);

        $url = "https://api.weixin.qq.com/wxa/getwxacodeunlimit?access_token=" . $token;
        $body = ["scene" => 1, "page" => "pages/coupon_xianxia", "auto_color" => true, "is_hyaline" => true];
        $response = $client -> request('POST', $url, ['json' => $body, 'verify' => false]);
        $b = $response -> getBody() -> getContents();
        // 靠 是dump的问题 dump() 不显示返回的jpg编码 只显示 错误信息 估计是string格式
        // var_dump($b);
        // 所以这里用dump() 显示信息 如果返回正确图片 就为空了 不显示
        dump($b);
        // 保存这里 也得需要改  这里应该返回数据 还写个保存图片的方法
        file_put_contents('Data/UploadFiles/product/23.jpg', $b);
    }
}
