<?php

return array(
    //'配置项'=>'配置值'
    //更换模板变量规则，修改配置项
    'TMPL_PARSE_STRING'=>array(           //添加自己的模板变量规则
        '__DATA__'=>__ROOT__.'/Data'
    ),
    'TMPL_ACTION_SUCCESS'  => APP_PATH .'/BackendAdmin/View/Public/dispatch_jump.html',
    'TMPL_ACTION_ERROR'    => APP_PATH .'/BackendAdmin/View/Public/dispatch_jump.html',
    'ORDER_STATUS'         => array('0'=>'已取消','10' => '待付款', '20' => '待发货', '30' => '已发货', '40' => '已收货', '50' => '交易完成', '51' => '交易关闭' ,'back'=>'退款订单'),
    'ORDER_MSG' => array('orderMSG'=>1,'autoClose'=>0,'heartbeat'=>15,'minimum'=>20,'freight'=>3),
    'PRO_TYPE' => array('1'=>'普通商品','2'=>'特价商品'),
    //微信配置参数    // 后台有个保存微信参数 不过没有保存这里 有空添加一下
    'weixin'=>array(
        'appid' =>'wx026f26d858d16098',         //微信appid
        'secret'=>'4ec65ec9c4d07568e78b0954608125ad', //微信secret
    )
);
