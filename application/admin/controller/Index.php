<?php
namespace app\admin\controller;

use app\common\controller\AdminBase;
use think\Db;
use think\Config;
use think\Url;



/**
 * 后台首页
 * Class Index
 * @package app\admin\controller
 */
class Index extends AdminBase
{
    protected function _initialize()
    {
        parent::_initialize();

    }
public function undefined(){
	return $this->fetch();
}

    /**
     * 首页
     * @return mixed
     */
    public function adminindex()
    {
    	$baseUrl = str_replace('\\','/',dirname($_SERVER['SCRIPT_NAME'])).'/';
    	
        $root='http://'.$_SERVER['HTTP_HOST'].$baseUrl;
        $version = Db::query('SELECT VERSION() AS ver');
        $config  = [
            'url'             => $_SERVER['HTTP_HOST'],
            'document_root'   => $_SERVER['DOCUMENT_ROOT'],
            'server_os'       => PHP_OS,
            'server_port'     => $_SERVER['SERVER_PORT'],
            'server_soft'     => $_SERVER['SERVER_SOFTWARE'],
            'php_version'     => PHP_VERSION,
            'mysql_version'   => $version[0]['ver'],
            'max_upload_size' => ini_get('upload_max_filesize')
        ];
        $this->assign('root',$root);
        return $this->fetch('adminindex', ['config' => $config]);
    }
    public function home(){
    	
    	  return $this->fetch();
    }

     public function deal_sql() {
     	
    	$path = dirname($_SERVER['SCRIPT_FILENAME']) . '/update/updatedb.php';
    	
    	if (! file_exists ( $path )) {
    		return json(array('code' => 0, 'msg' => '升级文件不存在，请先把升级文件updatedb.php放置在/update/ 目录下'));
    		
    	}
    
    return 	require_once $path;
    
    
    	//$this->ajaxReturn('更新完毕，请清理缓存！');
    
    }
}