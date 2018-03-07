<?php
namespace addons\attachlink;

use app\common\controller\Addon;
use think\Config;
use think\Db;

class attachlink extends Addon
{
    private $error;

    public $info = array(
        'name' => 'attachlink',
        'title' => '附件链接',
        'description' => '用于添加附件资源，外链上传均可，配置后自动识别资源信息',
        'status' => 1,
        'author' => '云阳',
        'version' => '1.0',
        'url' => 'www.laysns.com',
    );

    public function install()
    {
        $this->getisHook('attachlinkadd', $this->info['name'], '添加附件链接钩子');
        $this->getisHook('attachlinksave', $this->info['name'], '保存附件链接数据钩子');
        $this->getisHook('attachlinkshow', $this->info['name'], '展示附件链接钩子');
        $db_config = array();
        $db_config['prefix'] = Config::get('database.prefix');
        $dirname = dirname(__FILE__);
        $sqldata = file_get_contents($dirname . '/install.sql');
        $sql_array = preg_split("/;[\r\n]+/", str_replace('ls_', $db_config['prefix'], $sqldata));
        foreach ($sql_array as $k => $v) {
            if (!empty($v)) {
                Db::query($v);
            }
        }
        return true;
    }

    public function uninstall()
    {
        $db_config = array();
        $db_config['prefix'] = Config::get('database.prefix');

        $dirname = dirname(__FILE__);
        $sqldata = file_get_contents($dirname . '/uninstall.sql');
        $sql_array = preg_split("/;[\r\n]+/", str_replace('ls_', $db_config['prefix'], $sqldata));
        foreach ($sql_array as $k => $v) {
            if (!empty($v)) {
                Db::query($v);
            }
        }
        return true;
    }

    public function attachlinkadd($param)
    {
        if (!empty($param) && $param['id'] > 0) {
            $map['fid'] = $param['id'];
            $map['type'] = $param['type'];
            $attachlink = Db::name('attachlink')->where($map)->find();

            $this->assign('attachlink', $attachlink);
        } else {
            $this->assign('attachlink', array('score' => 0, 'linkinfo' => ''));
        }
        echo $this->tplfetch('add');
    }
    public function attachlinksave($param)
    {
        $table = 'article';
        if ($param['type'] == 2) {
            $table = 'forum';
        }
        if ($param['edit'] == 1) {
            $map['fid'] = $param['id'];
            $map['type'] = $param['type'];

            if ($param['linkinfo'] != '') {
                if (Db::name('attachlink')->where($map)->find()) {
                    Db::name('attachlink')->where($map)->update(['score' => $param['score'], 'linkinfo' => $param['linkinfo']]);

                } else {
                    Db::name('attachlink')->insert(['fid' => $param['id'], 'type' => $param['type'], 'score' => $param['score'], 'linkinfo' => $param['linkinfo']]);

                }
                Db::name($table)->where('id', $param['id'])->update(['attach' => 1]);
            } else {

                Db::name($table)->where('id', $param['id'])->update(['attach' => 0]);
            }
        } else {
            if ($param['linkinfo'] != '') {
                Db::name($table)->where('id', $param['id'])->update(['attach' => 1]);
                $data['score'] = $param['score'];
                $data['fid'] = $param['id'];
                $data['type'] = $param['type'];
                $data['linkinfo'] = $param['linkinfo'];
                Db::name('attachlink')->insert($data);
            }
        }
    }
    public function attachlinkshow($parm)
    {
        $config = $this->getConfig('attachlink');
        $rule_arr = explode("\n", $config['linkanalyze']);
        for ($i = 0; $i < count($rule_arr); $i++) {
            $arr0 = explode('|', $rule_arr[$i]);
            $wparr[$i] = $arr0[1];
            $wp_arr[$arr0[1]]['name'] = $arr0[0];
            $wp_arr[$arr0[1]]['url'] = $arr0[1];
            $wp_arr[$arr0[1]]['img'] = $arr0[2];
            $wp_arr[$arr0[1]]['analyze'] = $arr0[3];
        }
        $downlinksarr = "";
        // $table = isset($parm['table']) ? $parm['table'] : 'forum';
        $type = $parm['type'];
        $a = Db::name('attachlink')->where("fid = {$parm['id']} and type = {$type}")->find();
        $linkinfo = $a['linkinfo'];
        $downlinksarr = array();
        if ($linkinfo != '') {
            $arr = explode(';', $linkinfo);

            for ($i = 0; $i < count($arr); $i++) {
                //初始化

                $downlinksarr[$i]['img'] = $wp_arr['default']['img'];
                $downlinksarr[$i]['analyze'] = $wp_arr['default']['analyze'];
                $arr2 = explode('|', $arr[$i]);
                foreach ($wparr as $vv) {
                    if (strpos($arr2[1], $vv)) {
                        $downlinksarr[$i]['img'] = $wp_arr[$vv]['img'];
                        $downlinksarr[$i]['analyze'] = $wp_arr[$vv]['analyze'];
                    }
                }
                $downlinksarr[$i]['name'] = $arr2[0];
                $downlinksarr[$i]['linkid'] = $i;
                $downlinksarr[$i]['passwd'] = '';
                if (count($arr2) == 3) {
                    $downlinksarr[$i]['passwd'] = $arr2[2];
                }

                // $downlinksarr[$i]['analyze'] = $wp_arr['default']['analyze'];
                // if (strpos($arr[$i], '#') !== false) {
                //     $arr2 = explode('#', $arr[$i]);
                //     foreach ($wparr as $vv) {
                //         if (strpos($arr2[0], $vv)) {
                //             $downlinksarr[$i]['name'] = $wp_arr[$vv]['name'];
                //             $downlinksarr[$i]['img'] = $wp_arr[$vv]['img'];
                //             $downlinksarr[$i]['analyze'] = $wp_arr[$vv]['analyze'];
                //         }
                //     }

                //     $downlinksarr[$i]['link'] = $arr2[0];
                //     $downlinksarr[$i]['passwd'] = $arr2[1];
                // } else {
                //     foreach ($wparr as $vv) {
                //         if (strpos($arr[$i], $vv)) {
                //             $downlinksarr[$i]['name'] = $wp_arr[$vv]['name'];
                //             $downlinksarr[$i]['img'] = $wp_arr[$vv]['img'];
                //             $downlinksarr[$i]['analyze'] = $wp_arr[$vv]['analyze'];
                //         }
                //     }
                //     $downlinksarr[$i]['link'] = $arr[$i];
                //     $downlinksarr[$i]['passwd'] = "";
                // }
            }
        }
        $static_path = WEB_URL . '/addons/attachlink/static';
        $this->assign('static_path', $static_path);
        $this->assign('downlinksarr', $downlinksarr);
        $this->assign('wpcontent', $a);

        echo $this->tplfetch('show' . $type);
    }
}
