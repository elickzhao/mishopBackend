<?php

// 本类由系统自动生成，仅供测试用途

namespace Api\Controller;

use Think\Controller;

vendor("MysqlDate.MysqlDate");
use MysqlDate\MysqlDate;

vendor("Carbon.Carbon");
use Carbon\Carbon;

class NewsController extends PublicController
{

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

            $count = M('user_course')->where("weixin = '".$_GET['openId']."' AND DATE(FROM_UNIXTIME(ADDTIME)) = '".Carbon::now()->format('Y-m-d')."'")->find();
            if ($count) {
                $this->ajaxReturn(['code' => 1, 'msg'=>'今日已签到!']);
            }

            $r = M('user')->where(['openid'=>$_GET['openId']])->setInc('jifen', $jifen);
            //连续签到记录 在之前的记录加一
            $conDays += M('user_course')->where("weixin = '".$_GET['openId']."'")->order('id desc')->getField('age');
            $rr = M('user_course')->add(['weixin'=>$_GET['openId'],'addtime'=>time(),'age'=>$conDays]);
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
            //因为现在appid没有改所以还无法读取积分
            //$score = M('user')->where("weixin = '".$_GET['openId']."'")->getField("jifen");
            $score = 20;
            $r = M('user_course')->field("DATE(FROM_UNIXTIME(ADDTIME)) as dtime,age")->where("weixin = '".$_GET['openId']."'")->order('id desc')->find();

            $conDays = $r['age']; //连续签到天数
            $dt = $date = new Carbon($r['dtime']);
            $rr = $dt->diffInDays(Carbon::now());
        
            //如果已经不是连续签到 并且连续签到数据还是原始的 就设置为0
            if ($rr > 1 && $conDays > 0) {
                $conDays =0;
                $addr = M('user_course')->where("weixin = '".$_GET['openId']."'")->order('id desc')->save(['age'=>$conDays]);
                if ($addr === false) {
                    $this->ajaxReturn(['code' => 1, 'msg'=>'签到出错','erro'=>'更新连续签到时保存数据库出错']);
                }
            }

            //如果签到间隔大于0就说明没有签到
            $hasSign = ($rr == 0)?1:0;

            $list = M('user_course')->field("DATE(FROM_UNIXTIME(ADDTIME)) as signTime,10 as signPoint,0 as isdeleted")->where("weixin = '".$_GET['openId']."'")->order('id desc')->limit(30)->select();

            $this->ajaxReturn(['code' => 0, 'msg'=>'','conDays'=>$conDays,'hasSign'=>$hasSign,'score'=>$score,'list'=>$list,'sql'=>$rr]);
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
            $list = [];

            for ($i=0; $i < 7; $i++) {
                $dd = Carbon::now()->subDays(6 - $i);
                $r = M('user_course')->where("weixin = '".$_GET['openId']."' AND DATE(FROM_UNIXTIME(ADDTIME)) = '".$dd->format('Y-m-d')."'")->find();
                //$r = M('user_course')->where("DATE(FROM_UNIXTIME(ADDTIME)) = '2017-05-24'")->find();
                $flag = $r?1:0;
                $list[$i] = ['signed'=>$flag,'signTime'=>$dd->day."日",'sql'=>M('user_course')->getlastsql()];
                //$list[$i] = ['signed'=>$flag,'signTime'=>$dd->day."日"];
            }

            // $this->ajaxReturn(['code' => 0, 'msg'=>'','list'=> [['signed'=>1,'signTime'=>'23日'],['signed'=>0,'signTime'=>'24日','award'=>false],['signed'=>1,'signTime'=>'25日','award'=>false],['signed'=>1,'signTime'=>'26日','award'=>false]]]);
            $this->ajaxReturn(['code' => 0, 'msg'=>'','list'=> $list]);
        } else {
            $this->ajaxReturn(['code' => 1, 'msg'=>'非法请求','list'=> $list]);
        }
    }
}
