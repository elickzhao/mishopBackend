<?php

namespace QRCode;

vendor("Guzzle.autoloader");
vendor("Guzzle.GuzzleHttp.Client");
use GuzzleHttp\Client;

class QRCode
{
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
        $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid={$appid}&secret={$secret}";
        // $url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=wx026f26d858d16098&secret=4ec65ec9c4d07568e78b0954608125ad';
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
    public function getQRCode($id)
    {
        $client = new Client(['headers' => ['Accept' => 'application/json', 'Content-Type' => 'application/json']]);
        $token;
        if (S('accessToken')) {
            $token = S('accessToken');
        } else {
            $token = $this->getAccessToken();
        }
        // dump($token);

        $url = "https://api.weixin.qq.com/wxa/getwxacodeunlimit?access_token=" . $token;
        $body = ["scene" => 'id='.$id, "page" => "pages/goods_detail", "auto_color" => true, "is_hyaline" => true];
        $response = $client -> request('POST', $url, ['json' => $body, 'verify' => false]);
        $b = $response -> getBody() -> getContents();
        // 靠 是dump的问题 dump() 不显示返回的jpg编码 只显示 错误信息 估计是string格式
        // var_dump($b);
        // 所以这里用dump() 显示信息 如果返回正确图片 就为空了 不显示
        // dump($b);
        // 解决偶尔出现的过期问题
        $c = json_decode($b);
        if ($c->errcode == '40001') {
            // dump($c->errcode);
            S('accessToken', null);
            $this->getQRCode();
        } else {
            return $b;
        }
    }

    /**
     * [isHasQR 判读是否存在二维码]
     * @param  string  $id [description]
     * @return boolean     [description]
     */
    public function isHasQR($pro_number = '')
    {
        // return file_exists('Data/UploadFiles/product/'.$pro_number.'/qrcode.png');
        return file_exists('Data/UploadFiles/product/'.$pro_number.'/'.$pro_number.'.png');
    }

    public function createQRCode($id = '', $pro_number = '')
    {
        $qr = $this->getQRCode($id);
        // $r = file_put_contents('Data/UploadFiles/product/'.$pro_number.'/qrcode.png', $qr);
        $r = file_put_contents('Data/UploadFiles/product/'.$pro_number.'/'.$pro_number.'.png', $qr);
        // $r = file_put_contents('Data/UploadFiles/product/qrcode.png', $qr);
        // dump('成功!-------->'.$r);
    }
}
