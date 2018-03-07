<?php
namespace app\index\controller;

use app\common\controller\HomeBase;
use app\common\model\Forumcate as ForumcateModel;
use app\common\model\User as UserModel;
use org\Http;
use think\Cache;
use think\Controller;
use think\Db;
use think\Session;

class Index extends HomeBase
{
    protected $site_config;
    public function _initialize()
    {
        parent::_initialize();
        if(CBOPEN==2) $this->redirect(url('bbs/index/index'));
        $this->site_config = Cache::get('site_config');
    }
   
    
    public function index()
    {
        $article_new = Db::name('article')->alias('a')->join('user u', 'u.id=a.uid')->join('articlecate c', 'c.id=a.tid')->where('a.open', 1)->field('u.userhead,u.username,a.id,a.uid,a.title,a.time,c.template')->order('a.settop desc,a.time desc')->limit(28)->select();
        $this->assign('article_new', $article_new);

        //下面的分类展示
        $artbycatelist=Db::name('articlecate')->where('hometextshow=1')->select();
        foreach($artbycatelist as $k=>$v){

            $artbycatelist[$k]['artlists']=get_articles_by_cid($v['id']);
        }
        $this->assign('artbycatelist', $artbycatelist);
        //图片区
        $article_pic=Db::name('articlecate')->where('homepicshow=1')->select();
        foreach($article_pic as $k=>$v){

            $article_pic[$k]['artlists']=get_articles_by_cid($v['id'],20);
        }
       // $article_pic = Db::name('article')->alias('a')->join('articlecate c', 'c.id=a.tid')->where('a.open', 1)->field('a.*,c.template')->order('a.time desc')->limit(20)->select();
        $this->assign('article_pic', $article_pic);


        return view();

    }

  
    public function search()
    {
        $ks = input('ks');
        $kss = urldecode(input('ks'));
        if (empty($ks) || $kss==' ') {
            return $this->error('亲！你没有输入关键字');
        } else {
            $article = Db::name('article');
            $open['open'] = 1;

            $map['f.title|f.keywords|f.description|f.content'] = ['like', "%{$kss}%"];

            $tptc = $article->alias('f')->join('articlecate c', 'c.id=f.tid')->join('user m', 'm.id=f.uid')->field('f.*,c.id as cid,m.id as userid,m.userhead,m.username,c.name,c.template')->order('f.id desc')->where($open)->where($map)->paginate(5, false, $config = ['query' => array('ks' => $ks)]);
            $this->assign('tptc', $tptc);
            return view();
        }
    }
    public function getemotion()
    {
        $path = WEB_URL . '/public/plugins/wangEditor/emotion/';
        $dir = ROOT_PATH . 'public'.DS.'plugins'.DS.'wangEditor'.DS.'emotion'.DS;
      
        $array = array();
        foreach (glob($dir . '*', GLOB_ONLYDIR) as $files) {
            $files1 = iconv('GB2312', 'UTF-8', $files);
            $filename = basename($files1);
            $k = $filename;
            $array[$k]['title'] = $filename;
            if (is_dir($files)) {
                $array[$k]['data'] = array();
                if ($dh = opendir($files)) {
                    while (($file = readdir($dh)) !== false) {
                        if ($file != "." && $file != "..") {
                            $result = pathinfo($file);
                            $file = iconv('GB2312', 'UTF-8', $file);
                            $n = str_replace('.' . $result['extension'], '', $file);
                            array_push($array[$k]['data'], array('icon' =>  $path. $filename .'/'. $file, 'value' => $n));
                        }
                    }
                    closedir($dh);
                }
            }
        }
        return json_encode($array);
    }
    public function downinfo()
    {

        $data = request()->param();
        Db::name('attach')->where('id', $data['id'])->setInc('download');
        $info = Db::name('attach')->where('id', $data['id'])->find();
        return json(array('code' => 200, 'msg' => '开始下载', 'url' => $info['savepath']));
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
    
    public function savebanquan($url)
    {

        $data['url'] = $url;
        $res = Db::name('domain')->where($data)->find();
        $arr = array();
        $arr['	sqstatus'] = 0;
        $arr['	version'] = '2.2.0版-Build20180208';
        if ($res) {
            Db::name('domain')->where($data)->setInc('num');
            if ($res['status'] == 1) {
                $arr['	sqstatus'] = 1;
                $arr['	msg'] = '您的程序已授权！如需下载插件免费，请联系278198348升级为超级会员';
            } else {
                $arr['	msg'] = '您的程序还未授权！如需授权或下载插件免费，请联系278198348授权。';
            }
        } else {
            $data['num'] = 0;
            Db::name('domain')->insert($data);
            $arr['	msg'] = '您的程序还未授权！如需授权或下载插件免费，请联系278198348授权。';
        }

        return json_encode($arr);
    }

    public function article()
    {
        $id=input('id');
       // echo $id.'444';
        //$id = 1;
        if (empty($id)) {
            return $this->error('亲！你迷路了');
        } else {
            $article = Db::name('article');
            $a = $article->find($id);
            if ($a) {
                	//外链页面直接跳转
				if($a['outlink']) $this->success('正在跳转到外部页面',$a['outlink'],null,1);
                $article->where("id",$id)->setInc('view', 1);
                $t = $article->alias('a')->join('articlecate c', 'c.id=a.tid')->join('user m', 'm.id=a.uid')->field('a.*,c.id as cid,c.name,c.template,c.alias,m.id as userid,m.grades,m.point,m.userhead,m.username,m.status')->where('a.id',$id)->find();
            //   print_r($t);
                $this->assign('t', $t);
                //阅读排行
                $artphb=$article->where('tid' ,$t['tid'])->order('view desc')->limit(15)->select();
                $this->assign('artphb', $artphb);
                //文章推荐
               
                $choice['tid']=$t['tid'];
                $choice['choice']=1;
                $artchoice=$article->where($choice)->order('id desc')->select();
                $this->assign('artchoice', $artchoice);
                //畅言配置
                $site_config = Cache::get('site_config');
				$is_open_changyan = $site_config['open_changyan'];
				$changyan_config=0;
                if ($is_open_changyan) {
                    $changyan_config = Db::name('system')->field('value')->where('name', 'changyan')->find();

                    $changyan_config = unserialize($changyan_config['value']);
                    
				}
				
			    
            
                
				$this->assign('changyan', $changyan_config);
                return view();
            } else {
                return $this->error('亲！你迷路了');
            }
        }
    }
    public function soft($id)
    {
        $id = input('id');
        if (empty($id)) {
            return $this->error('亲！你迷路了');
        } else {
            $article = Db::name('article');
            $a = $article->where("id = {$id}")->find();
            if ($a) {
                $article->where("id = {$id}")->setInc('view', 1);
                $t = $article->alias('a')->join('articlecate c', 'c.id=a.tid')->join('user m', 'm.id=a.uid')->field('a.*,c.id as cid,c.name,c.template,c.alias,m.id as userid,m.grades,m.point,m.userhead,m.username,m.sex,m.status')->where('a.id',$id)->find();
           // print_r($t);
                //阅读排行
               $artphb=$article->where("tid = {$t['tid']}")->order('view desc')->limit(10)->select();
               $this->assign('artphb', $artphb);
                //畅言配置
                $site_config = Cache::get('site_config');
				$is_open_changyan = $site_config['open_changyan'];
				$changyan_config=0;
                if ($is_open_changyan) {
                    $changyan_config = Db::name('system')->field('value')->where('name', 'changyan')->find();
                    $changyan_config = unserialize($changyan_config['value']);             
				}
				
			    $this->assign('t', $t);
		
				$this->assign('changyan', $changyan_config);
                return view();
            } else {
                return $this->error('亲！你迷路了');
            }
        }
    }
}
