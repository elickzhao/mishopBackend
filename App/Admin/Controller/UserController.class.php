<?php
namespace Admin\Controller;

use Think\Controller;

class UserController extends PublicController
{

    //*************************
    // 普通会员的管理
    //*************************
    public function index()
    {
        $aaa_pts_qx=1;
        $type=$_GET['type'];
        $id=(int)$_GET['id'];
        $tel = trim($_REQUEST['tel']);
        $name = trim($_REQUEST['name']);

        $names=$this->htmlentities_u8($_GET['name']);
        //搜索
        $where="1=1";
        $name!='' ? $where.=" and name like '%$name%'" : null;
        $tel!='' ? $where.=" and tel like '%$tel%'" : null;

        define('rows', 20);
        $count=M('user')->where($where)->count();
        $rows=ceil($count/rows);

        $page=(int)$_GET['page'];
        $page<0?$page=0:'';
        $limit=$page*rows;
        $userlist=M('user')->where($where)->order('id desc')->limit($limit, rows)->select();
        $page_index=$this->page_index($count, $rows, $page);
        foreach ($userlist as $k => $v) {
            $userlist[$k]['addtime']=date("Y-m-d H:i", $v['addtime']);
        }
        //====================
        // 将GET到的参数输出
        //=====================
        $this->assign('name', $name);
        $this->assign('tel', $tel);


        $bc = ['会员管理','全部会员'];
        $this->assign('bc', $bc);
        //=============
        //将变量输出
        //=============
        $this->assign('page_index', $page_index);
        $this->assign('page', $page);
        $this->assign('userlist', $userlist);
        $this->display();
    }

    /**
     * [getUsers ajax获取用户列表]
     * @return [json] [用户数据]
     */
    public function getUsers()
    {
        $where="1=1";

        if ($_GET['uname']) {
            $where .= ' AND uname like "%'. $_GET['uname'].'%"';
        }
        

        $count=M('user')->where($where)->count();
        $rows=ceil($count/rows);
        $page = (int) -- $_GET['page'] ;
        $rows = $_GET['limit'] ? $_GET['limit'] : 10;
        $limit= $page*$rows;
        $userlist=M('user')->where($where)->order('addtime desc')->limit($limit, $rows)->select();
        $sql = M('user')->getlastsql();

        $resuslt = [code=>0,msg=>'',count=>$count,data=>$userlist,sql=>$sql];
        //$resuslt = [code=>0,msg=>'',count=>$count,data=>$userlist];

        $this->ajaxReturn($resuslt);
    }

    //*************************
    //会员地址管理
    //*************************
    public function address()
    {
        // $aaa_pts_qx=1;
        $id=(int)$_GET['id'];
        if ($id<1) {
            return;
        }
        if ($_GET['type']=='del' && $id>0 && $_SESSION['admininfo']['qx']==4) {
            $this->delete('address', $id);
        }
        //搜索
        $address=M('address')->where("uid=$id")->select();
        
        //=============
        //将变量输出
        //=============
        $this->assign('address', $address);
        $this->display();
    }

    public function del()
    {
        $id = intval($_REQUEST['did']);
        $info = M('user')->where('id='.intval($id))->find();
        if (!$info) {
            $this->error('会员信息错误.'.__LINE__);
            exit();
        }

        $data=array();
        $data['del'] = $info['del'] == '1' ?  0 : 1;
        $up = M('user')->where('id='.intval($id))->save($data);
        if ($up) {
            $this->ajaxReturn([code=>0,msg=>"操作成功 - ".$up]);
            // $this->redirect('User/index', array('page'=>intval($_REQUEST['page'])));
            // exit();
        } else {
            $this->ajaxReturn([code=>1,msg=>'操作失败']);
            // $this->error('操作失败.');
            // exit();
        }
    }
}
