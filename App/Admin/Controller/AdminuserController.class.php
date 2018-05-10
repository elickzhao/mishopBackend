<?php
namespace Admin\Controller;

use Think\Controller;

class AdminuserController extends PublicController
{
    //*************************
    // 管理员的管理
    //*************************
    public function adminuser()
    {
        $id=(int)$_GET['id'];
        $name = trim($_REQUEST['name']);

        $names=$this->htmlentities_u8($_GET['name']);
        //$qx=$_GET['qx']=='' ? '' : $_GET['qx'];
        $where= "1=1 AND del<1";
        if ($name) {
            $where.=" AND name like '%$name%'";
        }

        define('rows', 10);//定义每页显示数量
        $count=M('adminuser')->where($where)->count();
        $rows=ceil($count/rows);

        $page=(int)$_GET['page'];
        $page<0?$page=0:'';
        $limit=$page*rows;
        $userlist=M('adminuser')->where($where)->order('id desc')->limit($limit, rows)->select();
        $page_index=$this->page_index($count, $rows, $page);//分页
        foreach ($userlist as $k => $v) {
            $userlist[$k]['addtime']=date("Y-m-d H:i", $v['addtime']);
        }
        //XXX
        //=============
        //将变量输出
        //=============
        $bc = ['管理员管理','管理员列表'];
        $this->assign('bc', $bc);
        $this->assign('name', $name);
        $this->assign('page_index', $page_index);
        $this->assign('page', $page);
        $this->assign('userlist', $userlist);
        $this->display();
    }

        /**
     * [getGoods ajax获取管理员列表]
     * @return [json] [管理员数据]
     */
    public function getAdminusers()
    {
        $where="del<1";
        // if ($_GET['name'] != "") {
        //     $where .= ' AND name like "%'. $_GET['name'].'%"';
        // }

        $count=M('adminuser')->where($where)->count();
        $rows=ceil($count/rows);
        $page = (int) -- $_GET['page'] ;
        $rows = $_GET['limit'] ? $_GET['limit'] : 10;
        $limit= $page*$rows;
        $adminuserlist=M('adminuser')->where($where)->order('addtime desc')->limit($limit, $rows)->select();
        $sql = M('adminuser')->getlastsql();

        //$resuslt = [code=>0,msg=>'',count=>$count,data=>$adminuserlist,sql=>$sql];
        $resuslt = [code=>0,msg=>'',count=>$count,data=>$adminuserlist];

        $this->ajaxReturn($resuslt);
    }


    //*************************
    // 管理员&商家会员的添加
    //*************************
    public function add()
    {
        //==================
        // GET到的参数集合
        //==================
        $id=(int)$_GET['id'];
        if ($_POST['submit']==true) {
            if (!$_POST['name']) {
                $this->error('请输入登录账号.'.__LINE__);
                exit();
            }

            $array = array(
                'name' => trim($_POST['name']),
                'uname' => '普通管理员',
                'qx' => 5,
                'pwd' => MD5(MD5($_POST['password'])) ,
            );
            if (intval($_POST['admin_id'])>0) {
                //更新
                //密码为空则去掉unset，防止空置原密码
                if (!$_POST['password']) {
                    unset($array['pwd']);
                }
                $sql= M('adminuser')->where("id=".intval($_POST['admin_id']))->save($array);
            } else {
                //添加
                $check = M('adminuser')->where('name="'.$array['name'].'" AND del=0 AND (qx=5 or qx=4)')->getField('id');
                if ($check) {
                    $this->error('账号已存在！');
                    exit();
                }
                $array['addtime'] = time();
                $sql= M('adminuser')->add($array);
                $id= $sql;
            }
            
            if ($sql) {
                $this->success('保存成功！',U('Adminuser/adminuser'));
                exit();
            } else {
                $this->success('保存失败！');
                exit();
            }
        }
        //id>0则为编辑状态
        $adminuserinfo = $id>0 ? M('adminuser')->where("id=$id")->find():"";
        //=============
        //将变量输出
        //=============
        $bc = ['管理员管理','添加管理员'];
        $this->assign('bc', $bc);
        $this->assign('id', $id);
        $this->assign('adminuserinfo', $adminuserinfo);
        $this->display();
    }

    public function del()
    {
        $id = intval($_REQUEST['did']);
        $info = M('adminuser')->where('id='.intval($id))->find();
        if (!$info) {
            $this->error('参数错误.'.__LINE__);
            exit();
        }

        if (intval($info['qx'])==4) {
            $this->error('该账号不能删除.'.__LINE__);
            exit();
        }

        if ($info['del']==1) {
            $resuslt = [code=>0,msg=>'操作成功!'];
            //$this->redirect('Adminuser/adminuser', array('page'=>intval($_REQUEST['page'])));
            exit();
        }

        $data=array();
        $data['del'] = 1;
        $up = M('adminuser')->where('id='.intval($id))->save($data);
        if ($up) {
            $resuslt = [code=>0,msg=>'操作成功!'];
            $this->ajaxReturn($resuslt);
            //$this->redirect('Adminuser/adminuser', array('page'=>intval($_REQUEST['page'])));
            exit();
        } else {
            $this->error('操作失败.');
            exit();
        }
    }
}
