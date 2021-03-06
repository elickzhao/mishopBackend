<?php

// 本类由系统自动生成，仅供测试用途

namespace Api\Controller;

use Think\Controller;

class CategoryController extends PublicController
{
    //***************************
    // 产品分类
    //***************************
    public function index()
    {
        $list = M('category')->where('tid=1 AND bz_4=0 ')->field('id,tid,name')->order('sort desc,id asc')->select();
        $catList = M('category')->where('tid='.intval($list[0]['id']).' AND bz_4=0 ')->field('id,name,bz_1')->order('sort desc,id asc')->select();
        foreach ($catList as $k => $v) {

            $catList[$k]['bz_1'] = __DATAURL__.$v['bz_1'];

        }
        //$sql = M('category')->getlastsql();

        //json加密输出
        //dump($json);
        //echo json_encode(array('status'=>1,'list'=>$list,'catList'=>$catList,'currType'=>intval($list[0]['id']),'sql'=>$sql));
        echo json_encode(array('status'=>1,'list'=>$list,'catList'=>$catList,'currType'=>intval($list[0]['id'])));
        exit();

    }



    //***************************

    // 产品分类

    //***************************
    public function getcat()
    {
        $catid = intval($_REQUEST['cat_id']);

        if (!$catid) {

            echo json_encode(array('status'=>0,'err'=>'没有找到产品数据.'));

            exit();

        }

        $catList = M('category')->where('tid='.intval($catid).' AND bz_4=0 ')->field('id,name,bz_1')->select();
        foreach ($catList as $k => $v) {

            $catList[$k]['bz_1'] = __DATAURL__.$v['bz_1'];

        }



        //json加密输出

        //dump($json);

        echo json_encode(array('status'=>1,'catList'=>$catList));

        exit();

    }
}
