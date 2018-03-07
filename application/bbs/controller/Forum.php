<?php
namespace app\bbs\controller;

use app\bbs\model\Forum as ForumModel;
use app\common\controller\HomeBase;
use think\Cache;
use think\Db;
use think\Session;

class Forum extends HomeBase
{
    public function _initialize()
    {
        parent::_initialize();
        if(CBOPEN==1) $this->redirect(url('index/index/index'));
    }

    public function add()
    {
        $site_config = Cache::get('site_config');

        if (!session('userid') || !session('username')) {
            $this->error('亲！请登录', url('user/login/index'));
        } else {
            //发帖防灌水检测
            $res = hook('watercheck', array('type' => 1, 'tid' => @input('tid')), true, 'type');
            if ($res && isset($res['code'])) {
                if ($res['code'] == 0) {
                    $this->error($res['msg'], $_SERVER["HTTP_REFERER"], '', 10);
                }
            }

            $forum = new ForumModel();
            if (request()->isPost()) {
                if (session('userstatus') != 2 && session('userstatus') != 5 && $site_config['email_sh'] == 0) {
                    return json(array('code' => 0, 'msg' => '您的邮箱还未激活'));
                }

                $data = input('post.');

                if ($data['tid'] == 0) {
                    return json(array('code' => 0, 'msg' => '版块为空'));
                }

                if ($data['content'] == '') {
                    return json(array('code' => 0, 'msg' => '内容为空'));
                }
                $data['time'] = time();

                if (session('userstatus') > 0) {
                    $data['open'] = $site_config['forum_sh'];
                } else {
                    $data['open'] = session('userstatus');
                }

                $data['view'] = 1;
                $data['uid'] = session('userid');
                $data['description'] = mb_substr(remove_xss($data['content']), 0, 200, 'utf-8');

                $data['title'] = strip_tags($data['title']);

                $data['content'] = remove_xss($data['content']);
                if (!empty($data['coverpic'])) {
                    $data['coverpic'] = remove_xss($data['coverpic']);
                }
                //$member = Db::name('user');
                // $member->where('id', session('userid'))->setInc('point', $site_config['jifen_add']);

                if ($forum->add($data)) {

                    point_note($site_config['jifen_add'], session('userid'), 'forumadd', $forum->id);
                    if (!empty($data['viewtype'])) {
                        if ($data['viewtype'] > 0) {
                            $res = hook('threadfee', array('score' => $data['fee'], 'id' => $forum->id, 'edit' => 0, 'type' => $data['viewtype']));
                        }
                    }
                    //附件链接信息
                    if (!empty($data['linkinfo'])) {
                        $data['linkinfo'] = remove_xss($data['linkinfo']);
                        if (!empty($data['score'])) {
                            $data['score']=0;$data['otherinfo']='';
                        }
                        $res = hook('attachlinksave', array('score' => $data['attachscore'], 'linkinfo' => $data['linkinfo'], 'id' => $forum->id, 'otherinfo' => $data['otherinfo'],'edit' => 0, 'type' => 2));
                    }

                    return json(array('code' => 200, 'msg' => '添加成功'));
                } else {
                    return json(array('code' => 0, 'msg' => '添加失败'));
                }
            }
            $cid = 0;
            $requesurl = @$_SERVER["HTTP_REFERER"];
            if ($requesurl) {
                //print_r(parse_url($requesurl)) ;
                //从帖子跳转过来的
                if (strpos($requesurl, 'cate') !== false) {
                    preg_match('/cate\/(.*).html?/', $requesurl, $match);
                    // print_r($match[1]);
                    $cid = db('forumcate')->where('alias', $match[1])->value('id');
                    //echo $cid;
                } else if (strpos($requesurl, "thread") !== false) {
                    preg_match('/thread\/(.*).html?/', $requesurl, $match);
                    // print_r($match[1]);
                    $cid = db('forum')->where('id', $match[1])->value('tid');
                    //echo $cid;
                }
            }

            $category = Db::name('forumcate');
            $tptc = $category->where(array('show' => 1))->select();
            $this->assign('cid', $cid);
            $this->assign('tptc', $tptc);
            $tags = $site_config['site_keyword'];
            $tagss = explode(',', $tags);
            $this->assign('tagss', $tagss);

            $this->assign('title', '发布帖子');

            return view();
        }
    }
    public function edit()
    {
        $site_config = Cache::get('site_config');
        if (!session('userid') || !session('username')) {
            $this->error('亲！请登录', url('user/login/index'));
        } else {
            $wcres = hook('watercheck', array('type' => 1, 'id' => input('id')), true, 'type');
            if ($wcres && isset($wcres['code'])) {
                if ($wcres['code'] == 0) {
                    $this->error($wcres['msg'], $_SERVER["HTTP_REFERER"], '', 10);
                }

            }
            $id = input('id');
            session('editid', $id);

            $uid = session('userid');
            $forum = new ForumModel();
            $a = $forum->find($id);
            if (empty($id) || $a == null || $a['uid'] != $uid) {
                $this->error('亲！您迷路了');
            } else {
                if (request()->isPost()) {

                    $data = input('post.');
                    $data['id'] = session('editid');

                    // if (isset($data['downlinks'])) {
                    //     $data['outlink'] = remove_xss($data['outlink']);
                    //     $data['downlinks'] = remove_xss($data['downlinks']);
                    //     if ($data['outlink'] && $data['content'] == "") {
                    //         $data['content'] = '外部链接';
                    //     }

                    // }

                    session('editid', null);
                    if ($data['content'] == '') {
                        return json(array('code' => 0, 'msg' => '内容为空'));
                    }
                    $data['description'] = mb_substr(remove_xss($data['content']), 0, 200, 'utf-8');
                    $data['title'] = strip_tags($data['title']);
                    // $data['title']=  hook('trigtitle',array('title'=>$data['title'],'id'=>$data['id']),true,'title');

                    //$data['coverpic']= remove_xss($data['coverpic']);
                    $data['content'] = remove_xss($data['content']);

                    if ($forum->edit($data)) {

                        if (!empty($data['fee'])) {
                            $res = hook('threadfee', array('score' => $data['fee'], 'id' => $data['id'], 'edit' => 1));
                        }
                        //附件链接信息
                        if (!empty($data['linkinfo'])) {
                            $data['linkinfo'] = remove_xss($data['linkinfo']);
                            if (!empty($data['score'])) {
                                $data['score']=0;$data['otherinfo']='';
                            }
                            $res = hook('attachlinksave', array('score' => $data['attachscore'], 'linkinfo' => $data['linkinfo'],'otherinfo' => $data['otherinfo'], 'id' => $data['id'], 'edit' => 1, 'type' => 2));
                        }
                        return json(array('code' => 200, 'msg' => '修改成功'));
                    } else {
                        return json(array('code' => 0, 'msg' => '主体内容未做更改！'));
                    }
                }

                $category = Db::name('forumcate');
                $tptc = $forum->find($id);
                $tptc['title'] = strip_tags($tptc['title']);
                $tptcs = $category->where(array('show' => 1))->select();
                $this->assign(array('tptcs' => $tptcs, 'tptc' => $tptc));
                $tags = $site_config['site_keyword'];
                $tagss = explode(',', $tags);
                $this->assign('tagss', $tagss);
                $this->assign('title', '编辑帖子');
                return view();
            }
        }
    }

}
