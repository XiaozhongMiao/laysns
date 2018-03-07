<?php
namespace app\admin\controller;

use think\Config;
use think\Controller;
use think\Db;
use think\Session;
use Captcha\Captcha;
/**
 * 后台登录
 * Class Login
 * @package app\admin\controller
 */
class Login extends Controller
{
 public function captcha(){
       $m = new Captcha(Config::get('captcha'));
       
       $img = $m->entry();
       return $img;
    }
	protected function _initialize()
	{
		
		
	}
	
    /**
     * 后台登录
     * @return mixed
     */
    public function index()
    {
    	$site_config = Db::name('system')->field('value')->where('name', 'site_config')->find();
    	$site_config = unserialize($site_config['value']);
    	$yzmarr=explode(',', $site_config['site_yzm']);
    	if(in_array(4, $yzmarr)){
    		$yzm=1;
    	}else{
    		$yzm=0;
    	}
    	$this->assign('yzm',$yzm);
        return $this->fetch();
    }

    /**
     * 登录验证
     * @return string
     */
    public function login()
    {
    
    	$site_config = Db::name('system')->field('value')->where('name', 'site_config')->find();
    	$site_config = unserialize($site_config['value']);
    	$yzmarr=explode(',', $site_config['site_yzm']);
    	if(in_array(4, $yzmarr)){
    		 $yzm=1;
    	}else{
    		$yzm=0;
    	}
 
        if ($this->request->isPost()) {
            $data            = $this->request->only(['username', 'password', 'verify']);
          //  $validate_result = $this->validate($data, 'Login');
            
           
            if($yzm==1){
            	if(!captcha_check($data['verify'])){
            		//$this->error('验证码错误');
            		return json(array('code' => 0, 'msg' => '验证码错误'));
            		//  };
            		// if ($validate_result !== true) {
            	
            	}
            	
            }                    
                $salt=Db::name('admin_user')->where(array('username'=>$data['username']))->value('salt');
                $where['username'] = $data['username'];
                $where['password'] = md5($data['password'] . $salt);
                
                $admin_user = Db::name('admin_user')->field('id,username,status')->where($where)->find();
                
                
                if (!empty($admin_user)) {
                    if ($admin_user['status'] != 1) {
                        //$this->error('当前用户已禁用');
                        return json(array('code' => 0, 'msg' => '当前用户已禁用'));
                    } else {
                    	
                    	
                    	//Session::set('admintoken', md5($admin_user['id'].$admin_user['username'].$salt));
                    	
                    	//Session::set('admin_salt', $salt);
                        Session::set('admin_id', $admin_user['id']);
                        Session::set('admin_name', $admin_user['username']);
                        Db::name('admin_user')->update(
                            [
                                'last_login_time' => time(),
                                'last_login_ip'   => $this->request->ip(),
                                'id'              => $admin_user['id']
                            ]
                        );
                        return json(array('code' => 200, 'msg' => '登录成功'));
                      //  $this->success('登录成功', 'admin/index/index');
                    }
                } else {
                	return json(array('code' => 0, 'msg' => '用户名或密码错误'));
                    //$this->error('用户名或密码错误');
                }
         
        }
    }
public function locker(){
    $data            = $this->request->only(['username', 'password']);
    $salt=Db::name('admin_user')->where(array('username'=>$data['username']))->value('salt');
    $where['username'] = $data['username'];
    $where['password'] = md5($data['password'] . $salt);
    $admin_user = Db::name('admin_user')->field('id,username,status')->where($where)->find();
                
                
                if (!empty($admin_user)) {
                     Session::set('admin_id', $admin_user['id']);
                        Session::set('admin_name', $admin_user['username']);
                       
                    return json(array('code' => 200, 'msg' => '登录成功'));
                     
                    
                } else {
                	return json(array('code' => 0, 'msg' => '密码错误'));
                   
                }
}
    /**
     * 退出登录
     */
    public function logout()
    {
        Session::delete('admin_id');
        Session::delete('admin_name');
       // $this->success('退出成功', 'admin/login/index');
        return json(array('code' => 200, 'msg' => '退出成功'));
        //return NULL;
    }
}
