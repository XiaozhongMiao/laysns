<?php
namespace app\user\controller;

use app\common\controller\HomeBase;
use app\common\model\User as UserModel;
use think\Db;
use think\Cache;
class Index extends HomeBase
{
    public function _initialize()
    {
        parent::_initialize();
        $this->assign('uid', session('userid'));
    }
    public function index()
    {
        if (!session('userid') || !session('username')) {
            $this->error('亲！请登录', url('user/login/index'));
        } else {
            $member = new UserModel();
            $uid = session('userid');
            $tptc = $member->where(array('id' => $uid))->find();
            $this->assign('tptc', $tptc);
            return view();
        }
    }
    public function activate()
    {
      
            return view();

    }

    public function topic()
    {
        if (!session('userid') || !session('username')) {
            $this->error('亲！请登录', url('user/login/index'));
        } else {
            $forum = Db::name('forum');
            $uid = session('userid');
            $count = $forum->where("uid = {$uid}")->count();

            $this->assign('uid', $uid);
            $this->assign('count', $count);

            //收藏的帖子
            $collect = Db::name('collect');
            $count_collect = $collect->where("uid = {$uid} and type = 1")->count();
            $this->assign('count_collect', $count_collect);
            return view();
        }
    }
    public function article()
    {
        if (!session('userid') || !session('username')) {
            $this->error('亲！请登录', url('user/login/index'));
        } else {
            $article = Db::name('article');
            $uid = session('userid');
            $count = $article->where("uid = {$uid}")->count();

            $this->assign('uid', $uid);
            $this->assign('count', $count);

            //收藏的文章
            $collect = Db::name('collect');
            $count_collect = $collect->where("uid = {$uid} and type = 3")->count();
            $this->assign('count_collect', $count_collect);
            return view();
        }
    }

    public function getmyarticle()
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();
        } else {
            $data = $this->request->param();
        }
        $limit = $data['limit'];
        $pre = ($data['page'] - 1) * $limit;
        $uid = session('userid');
        $article = Db::name('article');
        $uid = session('userid');
        $count = $article->where("uid = {$uid}")->count();
        $tptc = $article->where("uid = {$uid}")->order('id DESC')->limit($pre, $limit)->select();
        foreach($tptc as $k=>$v){
            $tptc[$k]['title']= strip_tags($v['title']);
        }
        return json(array('code' => 0, 'msg' => '', 'count' => $count, 'data' => $tptc));
    }
    public function getmyforum()
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();
        } else {
            $data = $this->request->param();
        }
        $limit = $data['limit'];
        $pre = ($data['page'] - 1) * $limit;
        $uid = session('userid');
        $forum = Db::name('forum');
        $uid = session('userid');
        $count = $forum->where("uid = {$uid}")->count();
        $tptc = $forum->where("uid = {$uid}")->order('id DESC')->limit($pre, $limit)->select();
        foreach($tptc as $k=>$v){
            $tptc[$k]['title']= strip_tags($v['title']);
        }

        return json(array('code' => 0, 'msg' => '', 'count' => $count, 'data' => $tptc));
    }
    public function getmycollect()
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();
        } else {
            $data = $this->request->param();
        }
        $type = $data['ctype'];
        $limit = $data['limit'];
        $pre = ($data['page'] - 1) * $limit;
        $uid = session('userid');
        $forum = Db::name('collect');
        $uid = session('userid');
        $count = $forum->where("uid = {$uid}")->count();
        $tptc = $forum->alias('c')->join('forum f', 'c.sid=f.id', 'LEFT')->field('c.*,f.id as fid,f.title')->where("c.uid = {$uid} and c.type = {$type}")->order('id DESC')->limit($pre, $limit)->select();
        foreach($tptc as $k=>$v){
            $tptc[$k]['title']= strip_tags($v['title']);
        }
        return json(array('code' => 0, 'msg' => '', 'count' => $count, 'data' => $tptc));
    }
    public function getmypoint()
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();
        } else {
            $data = $this->request->param();
        }
      //  $type = $data['ctype'];
        $limit = $data['limit'];
        $pre = ($data['page'] - 1) * $limit;
        $uid = session('userid');
        $model = Db::name('point_note');
        $uid = session('userid');
        $count = $model->where("uid = {$uid}")->count();
        $tptc = $model->alias('p')->join('point_refer r', 'p.controller=r.alias', 'LEFT')->field('p.*,r.title')->where("p.uid = {$uid}")->order('id DESC')->limit($pre, $limit)->select();
        foreach($tptc as $k=>$v){
            $tptc[$k]['title']= strip_tags($v['title']);
        }
        return json(array('code' => 0, 'msg' => '', 'count' => $count, 'data' => $tptc));
    }

    public function message()
    {
        if (!session('userid') || !session('username')) {
            $this->error('亲！请登录', url('user/login/index'));
        } else {

            //$readmessage = Db::name('readmessage');
            $uid = session('userid');

            $arr = Db::name('readmessage')->alias('rm')->where(array('uid' => $uid))->column('mid');

            if (!empty($arr)) {
                //    $arrimplode=implode(',', $arr);
                $tptc = Db::name('message')->alias('me')->join('user u', 'me.uid=u.id', 'LEFT')->field('me.*,u.id as userid,u.username')
                    ->where('me.touid', ['=', 0], ['=', $uid], 'or')
                    ->where('me.id', 'not in', $arr)
                    ->order('me.time desc')->paginate(5);
            } else {

                $tptc = Db::name('message')->alias('me')->join('user u', 'me.uid=u.id', 'LEFT')->field('me.*,u.id as userid,u.username')->where('me.touid', $uid)->whereOr('me.touid', 0)->order('me.time desc')->paginate(5);
            }

            $this->assign('tptc', $tptc);
            $this->assign('uid', $uid);

            return view();
        }
    }

    public function delallmessage()
    {

        $uid = session('userid');
        $tptc = Db::name('message')->where(array('touid' => 0))->column('id');
        $tptc1 = array();
        $tptc1 = Db::name('readmessage')->where(array('uid' => $uid))->column('mid');

        if (Db::name('message')->where(array('touid' => $uid))->count() > 0) {
            if (Db::name('message')->where(array('touid' => $uid))->delete()) {
                if (!empty($tptc)) {
                    foreach ($tptc as $k => $v) {
                        if (!in_array($v, $tptc1)) {
                            $messdata['uid'] = $uid;
                            $messdata['mid'] = $v;
                            Db::name('readmessage')->insert($messdata);
                        }

                    }
                }
                return json(array('code' => 200, 'msg' => '删除成功'));
            } else {
                return json(array('code' => 0, 'msg' => '删除失败'));
            }
        } else {

            if (!empty($tptc)) {

                if (count($tptc) != count($tptc1)) {

                    foreach ($tptc as $k => $v) {
                        if (!in_array($v, $tptc1)) {
                            $messdata['uid'] = $uid;
                            $messdata['mid'] = $v;
                            Db::name('readmessage')->insert($messdata);
                        }

                    }
                    return json(array('code' => 200, 'msg' => '删除成功'));
                } else {
                    return json(array('code' => 0, 'msg' => '您无任何消息可删除'));
                }
            } else {
                return json(array('code' => 0, 'msg' => '您无任何消息可删除'));

            }
        }

    }

    public function delsysmessage($id)
    {

        $uid = session('userid');
        $messdata['uid'] = $uid;
        $messdata['mid'] = $id;

        if (Db::name('readmessage')->insert($messdata)) {

            return json(array('code' => 200, 'msg' => '删除成功'));
        } else {
            return json(array('code' => 0, 'msg' => '删除失败'));
        }

    }
    public function delmessage($id)
    {

        if (Db::name('message')->delete($id)) {
            //$this->success('删除成功');
            return json(array('code' => 200, 'msg' => '删除成功'));
        } else {
            // $this->error('删除失败');
            return json(array('code' => 0, 'msg' => '删除失败'));
        }
    }
    public function comment()
    {
        if (!session('userid') || !session('username')) {
            $this->error('亲！请登录', url('user/login/index'));
        } else {
            $comment = Db::name('comment');
            $uid = session('userid');
            $tptc = $comment->alias('c')->join('forum f', 'f.id=c.fid')->field('c.*,f.title')->where("c.uid = {$uid}")->order('c.id desc')->paginate(5);
            $this->assign('tptc', $tptc);
            $this->assign('uid', $uid);
            return view();
        }
    }
   
    public function home()
    {
        $id = input('id');
        if (empty($id)) {
            return $this->error('亲！你迷路了');
        } else {
            $member = new UserModel();
            $m = $member->where("id = {$id}")->find($id);
            if ($m) {
                $this->assign('m', $m);

                $type = input('type');
                $uid = session('userid');
                $self = 0;
                if ($id == $uid) {
                    $self = 1;
                }

                if ($type == 'post' || $type == '') {
                    $map['open'] = 1;
                    $map['uid'] = $id;
                    $tptcs = Db::name('forum')->where($map)->order('id desc')->paginate(10);
                } elseif ($type == 'reply') {
                    $tptcs = Db::name('comment')->alias('c')->join('forum f', 'f.id=c.fid')->field('c.*,f.title')->where("c.uid = {$id}")->order('c.id desc')->paginate(10);

                } elseif ($type == 'collect') {
                    $tptcs = Db::name('collect')->alias('c')->join('forum f', 'c.sid=f.id')->join('user u', 'u.id=f.uid')->field('f.id as fid,f.title,f.time,u.username,u.id')->where('c.uid=' . $id . ' and c.type=1')->order('c.time desc')->paginate(10);
                } elseif ($type == 'zan') {
                    $tptcs = Db::name('zan')->alias('z')->join('forum f', 'z.sid=f.id')->join('user u', 'u.id=f.uid')->field('f.id as fid,f.title,f.time,u.username,u.id')->where('z.uid=' . $id . ' and z.type=1')->order('z.time desc')->paginate(10);
                } elseif ($type == 'guanzhu') {
                    $tptcs = Db::name('collect')->alias('c')->join('user u', 'c.sid=u.id')->field('u.*,c.time')->where('c.uid=' . $id . ' and c.type=0')->order('c.time desc')->paginate(30);
                } elseif ($type == 'shang') {
                    $tptcs = Db::name('point_note')->alias('p')->join('forum f', 'p.pointid=f.id')->join('user u', 'u.id=p.uid')->field('f.title,f.id,p.score,p.add_time,u.username')->where('f.uid=' . $id . ' and p.score<0 and p.controller="tipauthor"')->order('p.add_time desc')->paginate(10);
                } else {
                    $tptcs = Db::name('point_note')->alias('p')->join('forum f', 'p.pointid=f.id')->join('user u', 'u.id=f.uid')->field('f.title,f.id,p.score,p.add_time,u.username')->where('p.uid=' . $id . ' and p.score<0 and p.controller="tipauthor"')->order('p.add_time desc')->paginate(10);
                }

                $this->assign('tptcs', $tptcs);
       
                $this->assign('self', $self);

                $this->assign('type', $type);
                $this->assign('id', $id);
                $this->assign('m', $m);
                return view();
            } else {
                return $this->error('亲！你迷路了');
            }
        }
    }
    public function set()
    {
        if (!session('userid') || !session('username')) {
            $this->error('亲！请登录', url('user/login/index'));
            //return json(array('code' => 0, 'msg' => '亲！请登录','url'=>url('user/login/index')));
        } else {
            $member = new UserModel();
            $uid = session('userid');
            $tptc = $member->where(array('id' => $uid))->find();

            if (request()->isPost()) {
                $data = $this->request->post();

                $data['id'] = $uid;

                $validate_result = $this->validate($data, 'User');

                if ($validate_result !== true) {
                    // $this->error($validate_result);
                    return json(array('code' => 0, 'msg' => $validate_result,

                    ));
                } else {
                    $data['userhome'] = remove_xss($data['userhome']);
                    $data['description'] = remove_xss($data['description']);

                    if ($member->save($data, ['id' => $uid])) {
                        return json(array('code' => 200, 'msg' => '修改成功'));
                    } else {
                        return json(array('code' => 0, 'msg' => '修改失败'));
                    }
                }
            }
            $uid = session('userid');
            $m = Db::name('qqconnect')->where('uid', $uid)->find();
            if (!empty($m)) {
                $this->assign('userinfo', $m);
            } else {
                $this->assign('userinfo', 0);
            }
            $this->assign('tptc', $tptc);
            $this->assign('uid', $uid);
            return view();
        }
    }
    public function setedit()
    {
        if (!session('userid') || !session('username')) {

            $this->error('亲！请登录', url('user/login/index'));
        } else {
            $member = new UserModel();
            $uid = session('userid');
            $tptc = $member->find($uid);

            if (request()->isPost()) {
                $data = $this->request->post();

                $validate_result = $this->validate($data, 'User.passwordedit');

                if ($validate_result !== true) {
                    // $this->error($validate_result);
                    return json(array('code' => 0, 'msg' => $validate_result,

                    ));
                } else {
                    if ($data['password'] == $data['nowpass']) {
                        return json(array('code' => 0, 'msg' => '密码未修改'));

                    }

                    if ($data['nowpass'] == 0) {
                        $salt = generate_password(18);
                        $datam['password'] = md5($data['password'] . $salt);
                        $datam['salt'] = $salt;
                        if ($member->save($datam, ['id' => $uid])) {
                            return json(array('code' => 200, 'msg' => '修改成功'));
                        } else {
                            return json(array('code' => 0, 'msg' => '修改失败'));
                        }
                    } else {
                        if ($tptc['password'] != md5($data['nowpass'] . $tptc['salt'])) {
                            return json(array('code' => 0, 'msg' => '原始密码错误'));
                        } else {
                            $datam['password'] = md5($data['password'] . $tptc['salt']);
                            if ($member->save($datam, ['id' => $uid])) {
                                return json(array('code' => 200, 'msg' => '修改成功'));
                            } else {
                                return json(array('code' => 0, 'msg' => '修改失败'));
                            }
                        }
                    }

                }

            }

            $this->assign('tptc', $tptc);
            return view();
        }
    }
    public function headedit()
    {
        if (!session('userid') || !session('username')) {
            $this->error('亲！请登录', url('user/login/index'));
        } else {
            $member = new UserModel();
            $uid = session('userid');
            if (request()->isPost()) {
                $data = $this->request->post();

                if ($member->allowField(['userhead'])->save($data, ['id' => $uid])) {

                    session('userhead', $data['userhead']);

                    return json(array('code' => 200, 'msg' => '修改成功'));
                } else {
                    return json(array('code' => 0, 'msg' => '修改失败'));
                }
            }
            $tptc = $member->find($uid);
            $this->assign('tptc', $tptc);
            return view();
        }
    }
    public function yzemail($id)
    {
        if (!session('userid') || !session('username')) {

            $this->error('亲！请登录', url('user/login/index'));
        } else {
            $uid = session('userid');
            $user = db('user')->where(array('id' => $uid))->find();

            if ($id == md5($user['salt'] . $uid . $user['usermail'])) {
                if ($user['status'] == 1) {

                    db('user')->where(array('id' => $uid))->setField('status', 2);

                } else {
                    db('user')->where(array('id' => $uid))->setField('status', 5);

                }

                point_note($this->site_config['jifen_email'], $uid, 'yzemail');
                $this->success('验证成功', url('user/index/set'));

            } else {
                $this->error('非法验证', url('user/index/set'));
            }

        }

    }
}
