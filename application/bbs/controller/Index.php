<?php
namespace app\bbs\controller;

use app\common\controller\HomeBase;
use app\common\model\Forumcate as ForumcateModel;
use app\common\model\User as UserModel;
use org\Http;
use think\Cache;
use think\Controller;
use think\Db;
use think\Request;
use think\Session;

class Index extends HomeBase
{
    protected $site_config;
    public function _initialize()
    {
        parent::_initialize();
        $this->site_config = Cache::get('site_config');
        if(CBOPEN==1) $this->redirect(url('index/index/index'));
    }

  

    public function index()
    {
        $forum = Db::name('forum');
        $open['open'] = 1;

        $tptch = $forum->field('id,title,reply')->where($open)->order('reply desc')->limit(15)->select();
        $this->assign('tptch', $tptch);

      //发帖Top 12
        $tptm = Db::name('forum')->alias('f')->join('user u', 'f.uid=u.id')->field('u.*,count(*) as forumnum')->group('f.uid')->order('forumnum desc')->limit(12)->select();
        $this->assign('tptm', $tptm);
        //热评帖子
        $tptcm = $forum->field('id,title')->where('memo', 1)->order('id asc')->select();
        $this->assign('tptcm', $tptcm);

        $forum = Db::name('forum');
        $open['open'] = 1;
        $settop['settop'] = 1;
        $nosettop['settop'] = 0;
        $tptc = $forum->alias('f')->join('forumcate c', 'c.id=f.tid')->join('user m', 'm.id=f.uid')->field('f.*,c.id as cid,m.id as userid,m.userhead,m.username,c.name')->where($open)->where($settop)->order('f.id desc')->limit(5)->select();
        $this->assign('tptc', $tptc);
        $tptcs = $forum->alias('f')->join('forumcate c', 'c.id=f.tid')->join('user m', 'm.id=f.uid')->field('f.*,c.id as cid,m.id as userid,m.userhead,m.username,c.name')->where($open)->where($nosettop)->order('f.id desc')->paginate(15);
        $this->assign('tptcs', $tptcs);
        return view();
    }

   
    public function cate()
    {
        $cate = input('cate_alias');
        //  echo $cate;die();
        $types = input('type');
        session('forumcate_alias', $cate);
        if (empty($cate)) {
            return $this->error('亲！你迷路了');
        } else {
            $forum = Db::name('forum');

            $category = Db::name('forumcate');

            if ($cate == 'all') {
                $children = implode(',', $category->column('id'));

                $name = '全部';
            } else {
                $c = $category->where('alias', $cate)->find();
                if ($c) {
                    $id = $c['id'];
                    $name = $c['name'];

                    $catemodel = new ForumcateModel();
                    $children = $catemodel->getchilrenid($id);
                    array_push($children, $id);
                } else {
                    $this->error("亲！你迷路了！");
                }

            }
            $forum = Db::name('forum');
            $open['open'] = 1;
            switch ($types) {
                case 'newreply':
                    $tptc = $forum->alias('f')->join('forumcate c', 'c.id=f.tid')->join('comment t', 'f.id=t.fid')->join('user m', 'm.id=t.uid')->field('f.*,f.time as ftime,c.id as cid,t.time,m.id as userid,m.userhead,m.username,c.name')->where('f.tid', 'in', $children)->where('f.open', 1)->group('f.id')->order('t.time desc,f.id desc')->paginate(15);

                    break;
                case 'hot':
                    $tptc = $forum->alias('f')->join('forumcate c', 'c.id=f.tid')->join('user m', 'm.id=f.uid')->field('f.*,c.id as cid,m.id as userid,m.userhead,m.username,c.name')->where('f.tid', 'in', $children)->where('f.open', 1)->order('f.reply desc,f.id desc')->paginate(15);

                    break;
                case 'choice':
                    // $choice['choice'] = 1;
                    $tptc = $forum->alias('f')->join('forumcate c', 'c.id=f.tid')->join('user m', 'm.id=f.uid')->field('f.*,c.id as cid,m.id as userid,m.userhead,m.username,c.name')->where('f.tid', 'in', $children)->where('f.open', 1)->where('f.choice', 1)->order('f.settop desc,f.id desc')->paginate(15);

                    break;
                default:

                    $tptc = $forum->alias('f')->join('forumcate c', 'c.id=f.tid')->join('user m', 'm.id=f.uid')->field('f.*,c.id as cid,c.alias as alias,m.id as userid,m.userhead,m.username,m.grades,c.name')->where('f.tid', 'in', $children)->where('f.open', 1)->order('f.settop desc,f.id desc')->paginate(15);
            }
            $tptch = $forum->field('id,title,reply')->where($open)->where('tid', 'in', $children)->order('reply desc')->limit(15)->select();
            $this->assign('tptch', $tptch);
            $this->assign('tptcs', $tptc);
            $this->assign('cate_alias', $cate);
            $this->assign('type', $types);
            $this->assign('name', $name);
            return view();

        }
    }

    
    

    public function search()
    {
        $ks = input('ks');
        $kss = urldecode(input('ks'));
        if (empty($ks)) {
            return $this->error('亲！你迷路了');
        } else {
            $forum = Db::name('forum');
            $open['open'] = 1;

            $map['f.title|f.keywords'] = ['like', "%{$kss}%"];

            $tptc = $forum->alias('f')->join('forumcate c', 'c.id=f.tid')->join('user m', 'm.id=f.uid')->field('f.*,c.id as cid,m.id as userid,m.userhead,m.username,c.name')->order('f.id desc')->where($open)->where($map)->paginate(8, false, $config = ['query' => array('ks' => $ks)]);
            $tptcm = $forum->field('id,title')->where('memo', 1)->order('id asc')->select();
        
            $tptch = $forum->field('id,title,reply')->where($open)->order('reply desc')->limit(15)->select();
         $this->assign('tptch', $tptch);
         // //发帖Top 12
         $tptm = Db::name('forum')->alias('f')->join('user u', 'f.uid=u.id')->field('u.*,count(*) as forumnum')->group('f.uid')->order('forumnum desc')->limit(12)->select();
         $this->assign('tptm', $tptm);
            // //热评帖子
         $tptcm = $forum->field('id,title')->where('memo', 1)->order('id asc')->select();
         $this->assign('tptcm', $tptcm);

            $this->assign('keyword', $ks);
            $this->assign('tptcss', $tptc);
            return view();
        }
    }
    public function guan()
    {

        $id = 59;
        $down = Db::name('attach')->where('id', $id)->find();
        $b = 1000;
        $c = 10000;
        $hits = $down['download'] + 13300;

        if ($hits > $b) {
            if ($hits < $c) {

                $down['download'] = floor($hits / $b) . '千';
            } else {

                $down['download'] = (floor(($hits / $c) * 10) / 10) . '万';
            }
        } else {
            $down['download'] = $hits;
        }
        $down['download'] = $down['download'] . '+';
        $this->assign('down', $down);

        return view();
    }
    public function downinfo()
    {

        $data = request()->param();
        Db::name('attach')->where('id', $data['id'])->setInc('download');
        $info = Db::name('attach')->where('id', $data['id'])->find();
        return json(array('code' => 200, 'msg' => '开始下载', 'url' => $info['savepath']));
    }

    public function forum()
    {
        return view();
    }

    public function zhuanti()
    {

        return view();
    }

    public function errors()
    {
        return view();
    }
    public function download($url, $name, $local)
    {
        $down = new Http();
        if ($local == 1) {
            $down->download($url, $name);
        } else {

            //echo     $down->curlDownload($url,$name);

        }

    }
   
    public function thread()
    {
        $id = input('id');
        if (empty($id)) {
            return $this->error('亲！你迷路了');
        } else {
            $forum = Db::name('forum');
            $a = $forum->where("id = {$id}")->find();
            if ($a) {
                $forum->where("id = {$id}")->setInc('view', 1);
                $t = $forum->alias('f')->join('forumcate c', 'c.id=f.tid')->join('user m', 'm.id=f.uid')->field('f.*,c.id as cid,c.alias,c.name,m.id as userid,m.grades,m.point,m.userhead,m.username,m.status')->find($id);
                $this->assign('t', $t);
                if ($t['keywords'] != '') {
                    $keywordarr = explode(',', $t['keywords']);
                    $this->assign('keywordarr', $keywordarr);
                }
                $comment['uid'] = array('not in', Db::name('user')->where('status', 'elt', 0)->column('id'));

                if ($t['status'] <= 0) {
                    $content = '<font color="#FF5722">该用户已被禁用或禁言</font>';

                } else {
                    $content = $t['content'];

                    $content = hook('threadfeecontent', array('content' => $content, 'id' => $t['id'], 'uid' => session('userid'), 'zuid' => $t['userid']), true, 'content');

                }
                //安装社区功能扩展插件的请解下面三行
                $order = input('see_desc') ? 'c.id desc' : 'c.id asc';
                $onlylz['uid'] = input('see_lz') ? $a['uid'] : array('>', 0);
                $tptc = Db::name('comment')->alias('c')->join('user m', 'm.id=c.uid')->where("fid = {$id}")->where($comment)->where($onlylz)->order($order)->field('c.*,m.id as userid,m.grades,m.point,m.userhead,m.username')->paginate(15, false, ['query' => Request::instance()->param()]);
                //没有安装社区功能扩展插件的请解开下面一行
                // $tptc = Db::name('comment')->alias('c')->join('user m', 'm.id=c.uid')->where("fid = {$id}")->where($comment)->order('c.id asc')->field('c.*,m.id as userid,m.grades,m.point,m.userhead,m.username')->paginate(15);

                $this->assign('tptc', $tptc);
                $tptch = Db::name('forum')->field('id,title,reply')->where('open', 1)->order('reply desc')->limit(15)->select();
                $this->assign('tptch', $tptch);

                $this->assign('content', $content);

                //查询当前用户是否收藏该帖子
                $iscollect = 0;
                $commentzan = array();
                $uid = session('userid');
                if ($uid) {
                    $collect = Db::name('collect')->where(array('uid' => $uid, 'sid' => $id, 'type' => 1))->find();
                    if ($collect) {
                        $iscollect = 1;
                    }
                    //查询用户点赞过的评论
                    $commentzan = Db::name('zan')->where(array('uid' => $uid, 'type' => 2))->column('sid');

                }
                $this->assign('iscollect', $iscollect);
                $this->assign('commentzan', $commentzan);

                return view();
            } else {
                return $this->error('亲！你迷路了');
            }
        }
    }
    public function getmsgnum()
    {
        $number = 0;

        if (!session('userid') || !session('username')) {
            return json(array('code' => 0, 'msg' => '请先登录'));
        } else {
            $uid = session('userid');
            $arr = Db::name('readmessage')->where(array('uid' => $uid))->column('mid');

            if (!empty($arr)) {
                //    $arrimplode=implode(',', $arr);
                $number = Db::name('message')
                    ->where('touid', ['=', 0], ['=', $uid], 'or')
                    ->where('id', 'not in', $arr)
                    ->count();
            } else {

                $number = Db::name('message')->where('touid', $uid)->whereOr('touid', 0)->count();
            }

        }
        return json(array('code' => 200, 'msg' => '获取成功', 'count' => $number));
    }
}
