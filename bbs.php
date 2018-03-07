<?php
// 检测程序安装
if(!is_file(__DIR__ . '/install/install.lock')){
	header('Location: ./install/index.php');
	exit;
}

// 定义URL
if (!defined('WEB_URL')) { 
$url = rtrim(dirname(rtrim($_SERVER['SCRIPT_NAME'], '/')), '/'); 
define('WEB_URL', (('/' == $url || '\\' == $url) ? '' : $url)); 
}
if (!defined('CBOPEN')) { 
	$cbarr = include 'application/extra/cbopen.php';
	define('CBOPEN',$cbarr['cb_open']);	
}
// 定义应用目录
define('APP_PATH', __DIR__ . '/application/');

// 加载框架引导文件
require __DIR__ . '/thinkphp/start.php';