<?php
namespace Admin\Controller;

use Think\Controller;

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
            $this->assign('adv_info', $adv_info);
        }

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
        //判断数据是否更新成功
        if ($result) {
            $this->success('操作成功.', 'index');
        } else {
            $this->error('操作失败.');
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

    /**
     * [setGoodsAtrr 设置门店属性]
     */
    public function setAtrr()
    {
        if (IS_POST) {
            $pro_id = $_POST['id'];
            $filed = $_POST['filed'];
            $val = $_POST['val'];

            if (is_array($pro_id)) {
                $where = 'id in ('. implode(',', $pro_id).')';
            } else {
                $where = 'id='.intval($pro_id);
            }

            $data[$filed] = $val;
            $up = $this->shop->where($where)->save($data);
            //$rr = $this->shop->getlastsql();

            $resuslt = [code=>$up,msg=>$up];
            $this->ajaxReturn($resuslt);
        } else {
            $this->ajaxReturn([code=>1,msg=>'非法请求']);
        }
    }


}
