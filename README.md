1、App/Common/Conf/db.php 数据库连接参数修改；

2、App/Api/Conf/config.php 微信小程序的appid、secret、mchid、key、notify_url，SELF_ROOT的参数修改；

3、ThinkPHP\Library\Vendor\wxpay\lib\WxPay.Config.php  微信小程序的appid、appsecret、mchid、key参数修改；

4、ThinkPHP\Library\Vendor\WeiXinpay\lib\WxPay.Config.php  微信小程序的appid、appsecret、mchid、key、notify_url参数修改；

5、App/Api/Controller/WxPayController.class.php 50行修改链接

后台登录的用户名是admin，密码是123456

小程序源码：https://github.com/hxxy2003/wechat_shop_xcx


SSL开启

先下载证书,然后在宝塔面板 网站管理里 打开ssl 输入 key和crt

上传证书和密钥到 /www/server/apache/conf/cert 目录下面 

替换配置文件 

	//原版
    #SSL
    SSLEngine On
    SSLCertificateFile /etc/letsencrypt/live/hqjs0001.huanqiujishi.com/fullchain.pem
    SSLCertificateKeyFile /etc/letsencrypt/live/hqjs0001.huanqiujishi.com/privkey.pem
    SSLCipherSuite EECDH+AESGCM:EDH+AESGCM:AES256+EECDH:AES256+EDH
    SSLProtocol All -SSLv2 -SSLv3
    SSLHonorCipherOrder On 

    //需要替换成的样子
    #SSL
    SSLEngine On
    SSLCertificateFile /www/server/apache/conf/crt/hqjs0004/2_hqjs0004.huanqiujishi.com.crt
    SSLCertificateKeyFile /www/server/apache/conf/crt/hqjs0004/3_hqjs0004.huanqiujishi.com.key
    SSLCertificateChainFile /www/server/apache/conf/crt/hqjs0004/1_root_bundle.crt
    
    SSLCipherSuite EECDH+AESGCM:EDH+AESGCM:AES256+EECDH:AES256+EDH
    SSLProtocol All -SSLv2 -SSLv3
    SSLHonorCipherOrder On