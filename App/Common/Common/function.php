<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2014 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------


//这个是自动加载的函数库
//functions那个并不是
vendor("Guzzle.autoloader");
vendor("Guzzle.GuzzleHttp.Client");
use GuzzleHttp\Client;

/**
 * [cutMoney 截取金额 小数点后有数字显示 没有取整]
 * @param  [string] $m [金额]
 * @return [string]    [处理后的金额]
 */
function cutMoney($m)
{
    $y=explode(".", $m);
    return ($y[1] > 0) ? $m : $y[0];
}

    /**
     * [getAccessToken 获取token用于请求二维码]
     * @return [type] [description]
     */
function getAccessToken()
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
function getQRcode()
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
    file_put_contents('Data\UploadFiles\product\223.jpg', $b);
}
