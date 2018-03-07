<?php
namespace app\common\controller;

use Captcha\Captcha;
use think\Cache;
use think\Config;
use think\Controller;
use think\Db;
use think\Session;

class HomeBase extends Controller
{

    protected function _initialize()
    {
        parent::_initialize();
        $this->getSystem();   
        $this->systemlogin();

        $nav=$this->getNav('nav');
        $this->assign('nav', $nav);

        $cmsnav=$this->getNav('nav_cms');
        $this->assign('cmsnav', $cmsnav);
  
        $tpto = Db::name('forumcate')->where(array('tid'=>0,'show'=>1))->order('sort desc')->limit(6)->select();
        $this->assign('tpto', $tpto);

        $this->assign('type', 'all');
        $this->assign('name', '');
        
        $action = $this->request->action();
        $this->assign('action', $action);
    }
    protected function systemlogin()
    {
        if (!session('userid') || !session('username')) {

            $user = unserialize(decrypt(cookie('sys_key')));
            if ((empty($user['id'])) || (empty($user['username']))) {

            } else {
                systemSetKey($user);
                if ($user['userhead'] == '') {
                    $user['userhead'] = '/public/images/default.png';
                }
                session('userstatus', $user['status']);
                session('grades', $user['grades']);
                session('userhead', $user['userhead']);
                session('username', $user['username']);
                session('userid', $user['id']);
                session('point', $user['point']);

                Db::name('user')->update(
                    [
                        'last_login_time' => time(),
                        'last_login_ip' => $this->request->ip(),
                        'id' => $user['id'],
                    ]
                );

            }

        }

    }
    /**
     * 添加邮件到队列
     */
    protected function _mail_queue($to, $subject, $body, $priority = 1, $bool = false)
    {
        $to_emails = is_array($to) ? $to : array($to);
        $mails = array();
        $time = time();
        foreach ($to_emails as $_email) {
            $mails[] = array(
                'mail_to' => $_email,
                'mail_subject' => $subject,
                'mail_body' => $body,
                'priority' => $priority,
                'add_time' => $time,
                'lock_expiry' => $time,
            );
        }
        $user = model('MailQueue');
        $user->addAll($mails);

        //异步发送邮件
        $this->db_send_mail($bool);
    }
    /**
     * 发送邮件
     */
    public function db_send_mail($is_sync = true)
    {
        if (!$is_sync) {
            //异步
            session('async_sendmail', true);
            return true;
        } else {
            //同步
            session('async_sendmail', null);
            $user = model('MailQueue');
            return $user->send();
        }
    }
    /**
     * 获取站点信息
     */
    protected function getSystem()
    {
        if (Cache::has('site_config')) {
            $site_config = Cache::get('site_config');
        } else {
            $site_config = Db::name('system')->field('value')->where('name', 'site_config')->find();
            $site_config = unserialize($site_config['value']);
            Cache::set('site_config', $site_config);
        }

        if (empty($site_config['jifen_name'])) {
            $site_config['jifen_name'] = '积分';
        }
        if (!defined('CBOPEN')) { 
            define('CBOPEN', $site_config['db_open']?$site_config['db_open']:3);
            }
        if (!defined('JF_NAME')) { 
        define('JF_NAME', $site_config['jifen_name']);
        }
        if (!defined('DEF_COVER')) { 
        define('DEF_COVER', WEB_URL.'/public/images/default_cover.png');
        }
        $this->assign('site_config', $site_config);
    }

    /**
     * 获取前端导航列表
     */
    protected function getNav($table)
    {
        if (Cache::has($table)) {
            $nav = Cache::get($table);
        } else {
            $nav = Db::name($table)->where(['status' => 1])->order(['sort' => 'ASC'])->select();

            if (!empty($nav)) {
                Cache::set($table, $nav);
            }
        }

       return $nav; 

    }
    public function captcha()
    {

        $m = new Captcha(Config::get('captcha'));
        $img = $m->entry();
        return $img;
    }

}
