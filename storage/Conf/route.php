<?php
//此配置文件只能在全局配置
return [
	'default' => [
		'/'=> 'auth&convert>index/index',
		'auth&convert' => [
			'/{c}/{a}'=>'{c}/{a}',
			'/{c}/{a}/{user|d}'=>'{c}/{a}',
		]
	]
];