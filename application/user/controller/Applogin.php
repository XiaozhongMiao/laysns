<?php
namespace app\user\controller;

use app\common\controller\HomeBase;
use qqconnect\QC;
use think\Cache;
use think\Db;

class Applogin extends HomeBase
{
    protected $config;
    protected function _initialize()
    {
        parent::_initialize();
        $qqlogin_config = Db::name('system')->field('value')->where('name', 'qqlogin')->find();
        $config = unserialize($qqlogin_config['value']);
        $config['scope'] = 'get_user_info,add_share,list_album,add_album,upload_pic,add_topic,add_one_blog,add_weibo,check_page_fans,add_t,add_pic_t,del_t,get_repost_list,get_info,get_other_info,get_fanslist,get_idolist,add_idol,del_idol,get_tenpay_addr';
        $config['errorReport'] = true;
        $this->config = $config;
        session('qqconnect', $config);
    }
    public function qqcb()
    {

        $qc = new QC();
        $askurl = getenv("HTTP_REFERER");
        $access_token = $qc->qq_callback(); // openid
        $openid = $qc->get_openid(); // openid
        $qc = new QC($access_token, $openid);
        $info = $qc->get_user_info(); // access_token
        if ($info['ret'] < 0) {
            $this->error('获取用户信息失败');

        } else {

            $uid = session('userid');
            if ($uid > 0) {
                //绑定的动作
                $map['openid'] = $openid;
                $m = Db::name('qqconnect')->where($map)->find();
                if (!empty($m)) {

                    Db::name('qqconnect')->where($map)->setField('uid', $uid);

                } else {
                    $data['head'] = $info['figureurl_2'];
                    $data['nickname'] = $info['nickname'];
                    $data['status'] = 1;
                    $data['errorcode'] = 0;
                    $data['add_time'] = time();
                    $data['openid'] = $openid;
                    $data['uid'] = $uid;
                    Db::name('qqconnect')->insert($data);
                }

                $this->success('绑定成功', 'user/index/set');

            } else {
                $map['openid'] = $openid;
                $m = Db::name('qqconnect')->where($map)->find();
                if (!empty($m)) {

                    $usermap['id'] = $m['uid'];

                } else {

                    if (empty($info['figureurl_2']) || $info['figureurl_2'] == '') {
                        $info['figureurl_2'] = $info['figureurl_qq_1'];
                    }
                    $data['head'] = $info['figureurl_2'];
                    $data['nickname'] = $info['nickname'];
                    $data['status'] = 1;
                    $data['errorcode'] = 0;
                    $data['add_time'] = time();
                    $data['openid'] = $openid;

                    $data1['username'] = $info['nickname'];
                    $count = Db::name('user')->where($data1)->count();
                    if ($count > 0) {
                        $data1['username'] = $info['nickname'] . ($count + 1);
                    }

                    $data1['password'] = 0;
                    $data1['userip'] = $this->request->ip();
                    //$data1['usermail']=$openid;
                    $data1['userhead'] = $info['figureurl_2'];
                    $data1['regtime'] = time();
                    $data1['status'] = 1;
                    $id = Db::name('user')->insertGetId($data1);
                    $data['uid'] = $id;
                    Db::name('qqconnect')->insert($data);
                    $usermap['id'] = $id;

                }
                $site_config = Cache::get('site_config');

                $user = Db::name('user')->where($usermap)->find();
                point_note($site_config['jifen_login'], $user['id'], 'login');
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

                $this->success('登录成功', session('callbackurl'));

            }

        }

    }
    public function unbind()
    {

        $data = request()->param();

        $uid = session('userid');
        $info = Db::name('user')->where('id', $uid)->find();
        if ($info['password'] == 0) {
            return array('code' => 0, 'msg' => '请先设置密码，然后再解除绑定');
        } else {
            $pattern = "/^([0-9A-Za-z\\-_\\.]+)@([0-9a-z]+\\.[a-z]{2,3}(\\.[a-z]{2})?)$/i";

            if (!preg_match($pattern, $info['usermail'])) {
                return array('code' => 0, 'msg' => '请先设置邮箱，然后再解除绑定');
            } else {
                $map['openid'] = $data['openid'];
                Db::name('qqconnect')->where($map)->setField('uid', 0);
                return array('code' => 200, 'msg' => '解除绑定成功');
            }

        }

    }

    /////下一步是绑定qq号，而且现在没有密码，也没有密码的随机码，怎么弄
    public function qqlogin()
    {    
        $qc = new QC();
        $this->redirect($qc->qq_login());
    }

}
