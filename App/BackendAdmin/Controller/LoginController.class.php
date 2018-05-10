<?php
namespace BackendAdmin\Controller;

use Think\Controller;

class LoginController extends PublicController
{
    public function index()
    {
        if (IS_POST) {
            $username=$_POST['username'];
            $admininfo=M('adminuser')->where("name='$username' AND del<1")->find();
            //查询app表看使用该系统的客户时间
            $appcheck=M('program')->find();
            if ($admininfo) {
                if (MD5(MD5($_POST['pwd']))==$admininfo['pwd']) {
                    $admin=array(
                       "id"         =>$admininfo["id"],
                       "name"       =>$admininfo["name"],
                       "qx"         =>$admininfo["qx"],
                       "group"    =>$admininfo["uname"],
                       
                    );
                    $system=array(
                        "name"       =>$appcheck['name'],//系统购买者
                        "sysname"    =>$appcheck['title'],//系统名称
                        "photo"      =>$appcheck['logo']//中心认证图片
                    );
                    unset($_SESSION['admininfo']);
                    unset($_SESSION['system']);
                    $_SESSION['admininfo']=$admin;
                    $_SESSION['system']=$system;
                    $this->success('欢迎回来!', U('Index/index'), 1);   //这里也很奇怪 这里总是比别感觉慢些 而且这里的1秒 弹出框样式改变的bug这里不出现
                } else {
                    $this->error('账号密码错误!', '', 2);
                }
            } else {
                $this->error('账号不存在或已注销!', '', 2);
            }
        } else {
            $sysname= M('program')->find();
            $this->assign('sysname', $sysname['title']);
            $this->display();
        }
    }
    public function logout()
    {
        unset($_SESSION['admininfo']);
        unset($_SESSION['system']);
        //echo "<script>alert('注销成功');location.href='".U('Login/index')."'</script>";
        $this->redirect('Login/index');
        exit;
    }
}
