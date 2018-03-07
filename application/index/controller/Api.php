<?php
namespace app\index\controller;

use app\common\controller\HomeBase;
use org\Http;
use think\Cache;
use think\Controller;
use think\Db;
use think\Session;

class Api extends HomeBase
{
    protected $site_config;
    public function _initialize()
    {
        parent::_initialize();
        $this->site_config = Cache::get('site_config');
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

    public function zan_collect()
    {
        $data = $this->request->param();
        $id = $data['id'];
        $uid = session('userid');
        if (!session('userid') || !session('username')) {

            return json(array('code' => 0, 'msg' => '登录后才能操作'));
        } else {

            //状态:
            // 0 用户 1 帖子 2 评论
            $zan_collect = $data['zan_collect'];

            $msgsubject = '';
            $zan_collect == 'zan' ? $msgsubject = '点赞' : $msgsubject = '收藏';
            $tablename = '';
            $type = $data['type'];
            switch ($type) {
                case 1:
                    $tablename = 'forum';

                    break;

                case 2:

                    $tablename = 'comment';
                    break;

                default:
                    $msgsubject = '关注';
                    $tablename = 'user';
                    break;
            }
            $zuid = $id;
            if ($type != '0') {
                $zuid = Db::name($tablename)->where('id', $id)->value('uid');

            }
            if ($zuid == $uid) {
                return json(array('code' => 0, 'res' => '减', 'msg' => '不可以孤芳自赏哦'));

            }

            $insertdata['type'] = $type;
            $insertdata['uid'] = $uid;
            $insertdata['sid'] = $id;

            $n = Db::name($zan_collect)->where($insertdata)->find();
            if (empty($n)) {
                $insertdata['time'] = time();
                if (Db::name($zan_collect)->insert($insertdata)) {

                    Db::name($tablename)->where('id', $id)->setInc($zan_collect);

                    return json(array('code' => 200, 'res' => '加', 'msg' => $msgsubject . '成功'));

                } else {
                    return json(array('code' => 0, 'res' => '加', 'msg' => $msgsubject . '失败'));

                }
            } else {
                if (Db::name($zan_collect)->where('id', $n['id'])->delete()) {
                    Db::name($tablename)->where('id', $id)->setDec($zan_collect);
                    return json(array('code' => 200, 'res' => '减', 'msg' => $msgsubject . '成功'));

                } else {
                    return json(array('code' => 0, 'res' => '减', 'msg' => $msgsubject . '失败'));
                }
            }

        }
    }
    
}
