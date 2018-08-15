<?php

return array(
    //'配置项'=>'配置值'
    //更换模板变量规则，修改配置项
    'TMPL_PARSE_STRING'=>array(           //添加自己的模板变量规则
        '__DATA__'=>__ROOT__.'/Data'
    ),
    'TMPL_ACTION_SUCCESS'  => APP_PATH .'/BackendAdmin/View/Public/dispatch_jump.html',
    'TMPL_ACTION_ERROR'    => APP_PATH .'/BackendAdmin/View/Public/dispatch_jump.html',
    'ORDER_STATUS'         => array('0'=>'已取消','10' => '待付款', '20' => '待发货', '30' => '已发货', '40' => '已收货', '50' => '交易完成', '51' => '交易关闭' ,'back'=>'退款中'),
    'ORDER_MSG' => array('orderMSG'=>1,'autoClose'=>0,'heartbeat'=>15,'minimum'=>20,'freight'=>3),
    'PRO_TYPE' => array('1'=>'普通商品','2'=>'特价商品'),
);
