<?php
return array(
	//'配置项'=>'配置值'
	//更换模板变量规则，修改配置项
	'TMPL_PARSE_STRING'=>array(           //添加自己的模板变量规则
		'__DATA__'=>__ROOT__.'/Data'
	),
	'TMPL_ACTION_SUCCESS'  => APP_PATH .'/Admin/View/Public/dispatch_jump.html',
    'TMPL_ACTION_ERROR'    => APP_PATH .'/Admin/View/Public/dispatch_jump.html',
);