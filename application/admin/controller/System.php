<?php
namespace app\admin\controller;

use app\common\controller\AdminBase;
use app\common\model\Upload as UploadModel;
use think\Cache;
use think\Db;

/**
 * 系统配置
 * Class System
 * @package app\admin\controller
 */
class System extends AdminBase
{
    public function _initialize()
    {
        parent::_initialize();

    }

    /**
     * 站点配置
     */
    public function siteConfig()
    {
        $site_config = Db::name('system')->field('value')->where('name', 'site_config')->find();
        $site_config = unserialize($site_config['value']);
        return $this->fetch('site_config', ['site_config' => $site_config]);
    }

    /**
     * 更新配置
     */
    public function updateSiteConfig()
    {
        if ($this->request->isPost()) {
            $site_config = $this->request->post('site_config/a');
            $site_config['site_tongji'] = htmlspecialchars_decode($site_config['site_tongji']);
            $data['value'] = serialize($site_config);
            $path = 'application/extra/web.php';
            $file = include $path;
            $config = array(
                'WEB_TPL' => $site_config['site_tpl'],
                'WEB_BTPL' => $site_config['site_tpl_bbs']

            );
            $res = array_merge($file, $config);
            $str = '<?php return [';

            foreach ($res as $key => $value) {
                $str .= '\'' . $key . '\'' . '=>' . '\'' . $value . '\'' . ',';
            };
            $str .= ']; ';
            file_put_contents($path, $str);

            $path = 'application/config.php';
            $str = '<?php return [';

            if ($site_config['site_wjt'] == 1) {
                $str .= "'app_debug'           => true,";
            } else {
                $str .= "'app_debug'           => false,'log' =>['level' => ['error']]";
            }

            $str .= ']; ';
            file_put_contents($path, $str);

            //写入CMS/BBS开关
            $cbstr="<?php return ["."'cb_open'=>".$site_config['cb_open']."]; ";
            file_put_contents('application/extra/cbopen.php', $cbstr);

            if (Db::name('system')->where('name', 'site_config')->update($data) !== false) {
                Cache::set('site_config', null);

                return json(array('code' => 200, 'msg' => '提交成功'));
            } else {
                return json(array('code' => 200, 'msg' => '提交失败'));
            }
        }
    }

    /**
     * 清除缓存
     */
    public function clear()
    {
        delete_dir_file(CACHE_PATH);
        array_map('unlink', glob(TEMP_PATH . '/*.php'));
        if (!file_exists(TEMP_PATH)) {
            return json(array('code' => 200, 'msg' => '暂无缓存'));
        } else {
            rmdir(TEMP_PATH);
            return json(array('code' => 200, 'msg' => '更新缓存成功'));
        }

    }
    public function doUploadPic()
    {
        $uploadmodel = new UploadModel();
        $info = $uploadmodel->upfile('images', 'FileName');
        echo $info['headpath'];
    }
    public function ajax_mail_test()
    {
        $data = $this->request->param();

        if (!$data['email']) {
            return json(array('code' => 0, 'msg' => '邮箱地址为空'));
        } else {

            $data['body'] = '测试邮件内容';
            $data['title'] = '测试邮件标题';

         $res = send_mail_local($data['email'], $data['title'], $data['body']);
         if ($res) {
             return json(array('code' => 1, 'msg' => '邮件已发送，请到邮箱进行查收'));
         } else {
             return json(array('code' => 0, 'msg' => '发送失败，请检查邮件服务器配置'));
         }
          
        }
    }

    public function signrule()
    {
        $rules = Db::name('user_signrule')->select();
        $this->assign('rules', $rules);
        return $this->fetch('signrule');
    }
    public function updatesignrule()
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();
            $daysarr = $data['days'];
            $scorearr = $data['score'];
            Db::name('user_signrule')->where('id', '>', 0)->delete();
            $arr = [];
            foreach ($daysarr as $i => $days) {
                $score = $scorearr[$i];
                $arr[$i] = ['days' => $days, 'score' => $score];
            }
            Db::name('user_signrule')->insertAll($arr);
            return json(array('code' => 200, 'msg' => '提交成功'));
        }
    }
    public function qqlogin()
    {
        $qqlogin_config = Db::name('system')->field('value')->where('name', 'qqlogin')->find();

        $qqlogin_config = unserialize($qqlogin_config['value']);
        $this->assign('qqlogin', $qqlogin_config);
        return $this->fetch('qqlogin');
    }
    public function updateqqlogin()
    {
        if ($this->request->isPost()) {
            $site_config = $this->request->post('qqlogin/a');
            // $site_config['site_tongji'] = htmlspecialchars_decode($site_config['site_tongji']);
            $data['value'] = serialize($site_config);

            if (Db::name('system')->where('name', 'qqlogin')->update($data) !== false) {
                //  Cache::set('site_config', null);
                session('qqconnect', null);
                return json(array('code' => 200, 'msg' => '提交成功'));
            } else {
                return json(array('code' => 200, 'msg' => '提交失败'));
            }
        }

    }
    public function qiniu()
    {
        $qiniu_config = Db::name('system')->field('value')->where('name', 'qiniu')->find();

        $qiniu_config = unserialize($qiniu_config['value']);
        $this->assign('qiniu', $qiniu_config);
        return $this->fetch('qiniu');
    }
    public function updateqiniu()
    {
        if ($this->request->isPost()) {
            $site_config = $this->request->post('qiniu/a');
            $data['value'] = serialize($site_config);

            if (Db::name('system')->where('name', 'qiniu')->update($data) !== false) {
                return json(array('code' => 200, 'msg' => '提交成功'));
            } else {
                return json(array('code' => 200, 'msg' => '提交失败'));
            }
        }

    }
    public function changyan()
    {
        $changyan_config = Db::name('system')->field('value')->where('name', 'changyan')->find();

        $changyan_config = unserialize($changyan_config['value']);
        $this->assign('changyan', $changyan_config);
        return $this->fetch('changyan');
    }
    public function updatechangyan()
    {
        if ($this->request->isPost()) {
            $site_config = $this->request->post('changyan/a');
            $data['value'] = serialize($site_config);

            if (Db::name('system')->where('name', 'changyan')->update($data) !== false) {
                return json(array('code' => 200, 'msg' => '提交成功'));
            } else {
                return json(array('code' => 200, 'msg' => '提交失败'));
            }
        }

    }
}
