<?php
namespace app\user\controller;

use app\common\controller\HomeBase;
use app\common\model\User as UserModel;
use think\Cache;
use think\Db;

class Login extends HomeBase
{
    protected $site_config;
    protected $yzmarr;
    public function _initialize()
    {
        parent::_initialize();
        $this->site_config = Cache::get('site_config');

        $this->yzmarr = explode(',', $this->site_config['site_yzm']);

        if (in_array(1, $this->yzmarr)) {
            $regyzm = 1;
        } else {
            $regyzm = 0;
        }
        if (in_array(2, $this->yzmarr)) {
            $loginyzm = 1;
        } else {
            $loginyzm = 0;
        }
        if (in_array(3, $this->yzmarr)) {
            $forgetyzm = 1;
        } else {
            $forgetyzm = 0;
        }
        $qq_login= $this->site_config['open_qqlogin'];
        $this->assign('regyzm', $regyzm);
        $this->assign('loginyzm', $loginyzm);
        $this->assign('forgetyzm', $forgetyzm);
        $this->assign('qq_login', $qq_login);

    }
    public function index()
    {
        session('callbackurl', $_SERVER['HTTP_REFERER']);
        $site_config = Cache::get('site_config');
        $yzmarr = explode(',', $site_config['site_yzm']);

        if (in_array(2, $yzmarr)) {
            $loginyzm = 1;
        } else {
            $loginyzm = 0;
        }

        $this->assign('loginyzm', $loginyzm);
        $member = new UserModel();
        if (request()->isPost()) {

            if (in_array(2, $yzmarr)) {
                if (!captcha_check(input('code'))) {

                    return json(array('code' => 0, 'msg' => '验证码错误'));

                }

            }
            $data = input('post.');
            $status['status'] = array('gt', 0);
            $userstr = $data['username'];
            //用户名、邮箱、手机号均可登陆
            if (preg_match("/^1[34578]\d{9}$/", $userstr)) {
                $map['mobile'] = $userstr;
            } else {
                $map['username|usermail'] = $userstr;
            }
            $user = $member->where($status)->where($map)->find();

            if ($user) {

                $user = updategrade($user);
                if ($user['password'] == md5($data['pass'] . $user['salt'])) {

                    if ($user['userhead'] == '') {
                        $user['userhead'] = '/public/images/default.png';
                    }
                    session('userstatus', $user['status']);
                    session('grades', $user['grades']);
                    session('userhead', $user['userhead']);
                    session('username', $user['username']);
                    session('userid', $user['id']);
                    session('point', $user['point']);
                    session('developer', $user['developer']);
                    $cook = array('id' => $user['id'], 'status' => $user['status'], 'point' => $user['point'], 'username' => $user['username'], 'userhead' => $user['userhead'], 'grades' => $user['grades']);

                    //获取签到信息
                    if ($site_config['open_sign']) {
                        $result = action('user/indexsign/todayData');
                        if ($result['is_sign'] == 1) {
                            //更新缓存
                            session('signdate', date('Ymd'));
                        }else{
                            session('signdate', null);
                        }
                     }

                    systemSetKey($cook);

                    $member->update(
                        [
                            'last_login_time' => time(),
                            'last_login_ip' => $this->request->ip(),
                            'id' => $user['id'],
                        ]
                    );
                    point_note($site_config['jifen_login'], session('userid'), 'login');

                    return json(array('code' => 200, 'msg' => '登录成功'));
                } else {
                    return json(array('code' => 0, 'msg' => '密码错误'));
                }
            } else {
                return json(array('code' => 0, 'msg' => '账号错误或已禁用'));
            }
        }
        return view();
    }

    public function reg()
    {

        $regswitch = Cache::get('site_config');
        if (!$regswitch['user_reg']) {
            $this->error('网站已关闭会员注册功能', url('index/index'));
        }
        $member = new UserModel();
        if (request()->isPost()) {

            if (!$regswitch['user_reg']) {

                return json(array('code' => 0, 'msg' => '网站已关闭会员注册功能'));
            }

            $data = $this->request->post();

            if (!empty($regswitch['baoliu'])) {
                $arr = explode(',', $regswitch['baoliu']);

                foreach ($arr as $k => $v) {

                    if (strpos($data['username'], $v) !== false) {
                        return json(array('code' => 0, 'msg' => '你的昵称被禁止注册'));

                    }
                }
            }
            $password = input('password');
            if (in_array(1, $this->yzmarr)) {
                if (!captcha_check(input('code'))) {

                    return json(array('code' => 0, 'msg' => '验证码错误'));

                }

            }

            $validate_result = $this->validate($data, 'User');

            if ($validate_result !== true) {
                // $this->error($validate_result);
                return json(array('code' => 0, 'msg' => $validate_result));
            } else {

                $data['salt'] = generate_password(18);
                $data['regtime'] = time();
                $data['username'] = remove_xss($data['username']);
                $data['grades'] = 0;
                $data['status'] = 1; //config('web.WEB_REG');
                $data['point'] = $this->site_config['jifen_reg']; //config('point.REG_POINT');
                $data['userhead'] = '/public/images/default.png';
                $data['userip'] = $_SERVER['REMOTE_ADDR'];
                $data['password'] = md5($password . $data['salt']);

                if ($member->allowField(true)->save($data)) {

                    point_note($regswitch['jifen_reg'], $member->id, 'reg');

                    return json(array('code' => 200, 'msg' => '注册成功'));
                } else {

                    return json(array('code' => 0, 'msg' => '注册失败'));
                }

            }
        }

        //  $data = input('post.');

        return view();
    }

    public function resetpass()
    {

        $data = $this->request->param();
        if (in_array(3, $this->yzmarr)) {
            if (!captcha_check(input('code'))) {

                return json(array('code' => 0, 'msg' => '验证码错误'));

            }

        }

        if ($data['pass'] != $data['repass']) {
            return json(array('code' => 0, 'msg' => '两次密码输入不一致'));
        } else {
            $n = Db::name('user')->where('id', $data['id'])->find();
            Db::name('user')->where('id', $data['id'])->setField('password', md5($data['pass'] . $n['salt']));
            return json(array('code' => 200, 'msg' => '密码修改成功，请重新登录'));

        }

    }

    public function resetmima()
    {
        $data = $this->request->param();
        $n = Db::name('user')->where('id', $data['mod'])->find();
        if (md5($n['salt'] . $n['id'] . $n['usermail']) == $data['id']) {

            $this->assign('userid', $n['id']);
            $this->assign('username', $n['username']);
            return view();
        } else {
            $this->error('非法操作', url('login/forget'));
        }

    }

    public function forget()
    {

        if (request()->isPost()) {

            $datan = $this->request->param();

            if (in_array(3, $this->yzmarr)) {
                if (!captcha_check(input('code'))) {

                    return json(array('code' => 0, 'msg' => '验证码错误'));

                }

            }

            $n = Db::name('user')->where('usermail', $datan['usermail'])->find();

            if (empty($n) || ($n['status'] != 2 && $n['status'] != 5)) {
                return json(array('code' => 0, 'msg' => '邮箱未激活或邮箱未注册'));
            } else {

                $data['email'] = $n['usermail'];

                $data['title'] = '找回密码';
                $str = md5($n['salt'] . $n['id'] . $n['usermail']);

                $url = 'http://'.$_SERVER['HTTP_HOST'].url('login/resetmima') . '?mod=' . $n['id'] . '&id=' . $str;

                //邮件模板替换
                $data['body'] = str_replace(['{username}', '{site_title}', '{url}'], [$n['username'], $this->site_config['site_title'], $url], $this->site_config['mail_tpl_resetpwd']);
                $res = send_mail_local($data['email'], $data['title'], $data['body']);
                if ($res) {
                    return json(array('code' => 1, 'msg' => '邮件已发送，请到邮箱进行查收'));
                } else {
                    return json(array('code' => 0, 'msg' => '发送失败，请通知管理员检查邮件服务器配置'));
                }
            }

        } else {
            return view();
        }

    }
    public function logout()
    {
        session("userstatus", null);
        session("userid", null);
        session("username", null);
        session("usermail", null);
        session("kouling", null);
        session('developer', null);
        session('callbackurl',null);
        cookie('sys_key', null);
        return json(array('code' => 200, 'msg' => '退出成功'));
        //  return NULL;
    }
}
