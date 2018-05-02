<?php
namespace Admin\Controller;

use Think\Controller;

class BrandController extends PublicController
{

    /*
    *
    * 构造函数，用于导入外部文件和公共方法
    */
    public function _initialize()
    {
        $this->Brand = D('Brand');
    }

    /*
    *
    * 获取、查询品牌数据
    */
    public function index()
    {
        //搜索，根据广告标题搜索
        $brand_name = intval($_REQUEST['brand_name']);
        $condition = array();
        if ($brand_name) {
            $condition['name'] = array('LIKE','%'.$brand_name.'%');
            $this->assign('name', $brand_name);
        }

        //分页
        $count   = $this->Brand->where($condition)->count();// 查询满足要求的总记录数
        $Page    = new \Think\Page($count, 25);// 实例化分页类 传入总记录数和每页显示的记录数(25)

        //头部描述信息，默认值 “共 %TOTAL_ROW% 条记录”
        $Page->setConfig('header', '<li class="rows">共<b>%TOTAL_ROW%</b>条&nbsp;第<b>%NOW_PAGE%</b>页/共<b>%TOTAL_PAGE%</b>页</li>');
        //上一页描述信息
        $Page->setConfig('prev', '上一页');
        //下一页描述信息
        $Page->setConfig('next', '下一页');
        //首页描述信息
        $Page->setConfig('first', '首页');
        //末页描述信息
        $Page->setConfig('last', '末页');
        $Page->setConfig('theme', '%FIRST%%UP_PAGE%%LINK_PAGE%%DOWN_PAGE%%END%%HEADER%');

        $show  = $Page->show();// 分页显示输出

        $list = $this->Brand->where($condition)->limit($Page->firstRow.','.$Page->listRows)->select();


        $bc = ['供货商管理','全部供货商'];
        $this->assign('bc', $bc);

        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display(); // 输出模板
    }

    /**
     * [getGoods ajax获取品牌列表]
     * @return [json] [品牌数据]
     */
    public function getBrands()
    {
        $where="1=1";
        if ($_GET['brand_name'] != "") {
            $where .= ' AND name like "%'. $_GET['brand_name'].'%"';
        }

        $count=M('brand')->where($where)->count();
        $rows=ceil($count/rows);
        $page = (int) -- $_GET['page'] ;
        $rows = $_GET['limit'] ? $_GET['limit'] : 10;
        $limit= $page*$rows;
        $brandlist=M('brand')->where($where)->order('addtime desc')->limit($limit, $rows)->select();
        $sql = M('brand')->getlastsql();

        //$resuslt = [code=>0,msg=>'',count=>$count,data=>$productlist,sql=>$sql];
        $resuslt = [code=>0,msg=>'',count=>$count,data=>$brandlist];

        $this->ajaxReturn($resuslt);
    }

    /**
     * [getGoods ajax获取商品列表]
     * @return [json] [商品数据]
     */
    public function getGoods()
    {
        $where="1=1 AND pro_type = 1 AND del<1 AND brand_id=".$_GET['bid'];

        if ($_GET['cid'] == 0) {
            unset($_GET['cid']);
        }

        //搜索优先级查询
        $arr = ['pro_number','name','cid'];
        foreach ($arr as $key => $value) {
            if ($_GET[$value] != '') {
                if ($value == 'name') {
                    $where .= ' AND '.$value.' like "%'. $_GET[$value].'%"';
                } else {
                    $where .= ' AND '.$value.' = '. $_GET[$value];
                }
                break;
            }
        }

        $count=M('product')->where($where)->count();
        $rows=ceil($count/rows);
        $page = (int) -- $_GET['page'] ;
        $rows = $_GET['limit'] ? $_GET['limit'] : 10;
        $limit= $page*$rows;
        $productlist=M('product')->where($where)->order('updatetime desc')->limit($limit, $rows)->select();
        $sql = M('product')->getlastsql();

        //$resuslt = [code=>0,msg=>'',count=>$count,data=>$productlist,sql=>$sql];
        $resuslt = [code=>0,msg=>'',count=>$count,data=>$productlist];

        $this->ajaxReturn($resuslt);
    }



    /*
    *
    * 跳转添加或修改品牌数据页面
    */
    public function add()
    {
        //如果是修改，则查询对应广告信息
        if (intval($_GET['id'])) {
            $id = intval($_GET['id']);
        
            $brand_info = $this->Brand->where('id='.intval($id))->find();
            $this->assign('info', $brand_info);
        }
        $bc = ['供货商管理','添加供货商'];
        $this->assign('bc', $bc);
        $this->display();
    }


    /*
    *
    * 添加或修改品牌信息
    */
    public function save()
    {
        //构建数组
        $this->Brand->create();
        $photo = '';
        if (!empty($_FILES["file"]["tmp_name"])) {
            //文件上传
            $info = $this->upload_images($_FILES["file"], array('jpg','png','jpeg'), 'brand/'.date(Ymd));
            if (!is_array($info)) {// 上传错误提示错误信息
                $this->error($info);
                exit();
            } else {// 上传成功 获取上传文件信息
                $this->Brand->photo = 'UploadFiles/'.$info["file"]['savepath'].$info["file"]['savename'];
                if (intval($_POST['id'])) {
                    $photo = $this->Brand->where('id='.intval($_POST['id']))->getField('photo');
                }
                //生成国定大小的缩略图
                /*$path_url = './Data/UploadFiles/'.$info['savepath'].$info['savename'];
                $image = new \Think\Image();
                $image->open($path_url);
                $image->thumb(310, 120,\Think\Image::IMAGE_THUMB_FIXED)->save($path_url);*/
            }
        }

        //保存数据
        if (intval($_POST['id'])) {
            $result = $this->Brand->where('id='.intval($_POST['id']))->save();
        } else {
            //保存添加时间
            $this->Brand->addtime = time();
            $result = $this->Brand->add();
        }
        //判断数据是否更新成功
        if ($result) {
            if (!empty($photo) && intval($_POST['id'])) {
                $img_url = "Data/".$photo;
                if (file_exists($img_url)) {
                    @unlink($img_url);
                }
            }
            $this->success('操作成功.', 'index');
        } else {
            $this->error('操作失败.');
        }
    }


    /**
     * [setBrandAtrr 设置品牌属性]
     */
    public function setBrandAtrr()
    {
        if (IS_POST) {
            $brand_id = $_POST['id'];
            $filed = $_POST['filed'];
            $val = $_POST['val'];

            if (is_array($brand_id)) {
                $where = 'id in ('. implode(',', $brand_id).')';
            } else {
                $where = 'id='.intval($brand_id);
            }

            $data[$filed] = $val;
            $up = M('brand')->where($where)->save($data);
            $rr = M('brand')->getlastsql();

            $resuslt = [code=>$up,msg=>$up];
            $this->ajaxReturn($resuslt);
        } else {
            $this->ajaxReturn([code=>1,msg=>'非法请求']);
        }
    }


    /*
    *
    * 品牌删除
    */
    public function del()
    {
        //获取广告id，查询数据库是否有这条数据
        $id = intval($_REQUEST['did']);
        $check_info = $this->Brand->where('id='.intval($id))->find();
        if (!$check_info) {
            $this->error('参数错误！');
            die();
        }

        $count = M('product')->where(['brand_id'=>$id,'del'=>0])->count();

        if ($count != 0) {
            //$this->error('删除错误!该品牌下有产品.', 'index', 5);
            $this->ajaxReturn([code=>1,msg=>'删除错误!该品牌下有产品.']);
        }

        //修改对应的显示状态
        $up = $this->Brand->where('id='.intval($id))->delete();
        if ($up) {
            if ($check_info['photo']) {
                $img_url = "Data/".$check_info['photo'];
                if (file_exists($img_url)) {
                    @unlink($img_url);
                }
            }
            $this->ajaxReturn([code=>0,msg=>"操作成功 - ".$up]);
        } else {
            $this->error('操作失败.');
        }
    }

    /*
    *
    * 品牌推荐
    */
    public function set_tj()
    {
        //获取广告id，查询数据库是否有这条数据
        $id = intval($_REQUEST['id']);
        $check_info = $this->Brand->where('id='.intval($id))->find();
        if (!$check_info) {
            $this->error('参数错误！');
            die();
        }

        //修改对应的显示状态
        $data=array();
        $data['type'] = $check_info['type'] == '1' ?  0 : 1;
        $up = $this->Brand->where('id='.intval($id))->save($data);
        if ($up) {
            $this->redirect('index');
        } else {
            $this->error('操作失败.');
        }
    }
}
