<?php
namespace app\common\model;

use app\common\model\System as SystemModel;
use org\Http;
use org\Upload as Uploadext;
use think\Cache;
use think\Db;
use think\File;
use think\Log;
use think\Model;

class Upload extends Model
{

    public function initialize()
    {
        parent::initialize();
    }

    public function upfile($type, $filename = 'file', $table = 'file', $isthumb = false, $is_water = false)
    {
        $file = request()->file($filename);
        if (empty($file)) {
            return array('code' => 0, 'msg' => '文件大小超出服务器限制，管理员需要在php.ini中设置post_max_size和upload_max_filesize的值');
        }
        //$filemode = new FileModel();
        $filemode = Db::name($table);
        $md5 = $file->hash('md5');
        $n = $filemode->where('md5', $md5)->find();

        if (empty($n)) {
            //本地上传判断条件
            $validate['size'] = 15 * 1024 * 1024;
            $validate['ext'] = 'jpg,png,bmp,rar,zip';

            $sysconfig = Cache::get('site_config');
            if ($sysconfig['open_7niu']) {
                $sys_mode = new SystemModel();

                $q = $sys_mode->where('name', 'qiniu')->find();
                $config = unserialize($q['value']);
                if ($config['allowExt']) {
                    $setting['exts'] = explode(',', $config['allowExt']);
                }
                $setting['maxSize'] = eval("return {$config['maxSize']};");
                $setting['rootPath'] = './';
                $setting['saveName'] = array('uniqid', '');
                $setting['hash'] = true;

                $driverConfig = array(
                    'secrectKey' => $config['SecretKey'],
                    'accessKey' => $config['AccessKey'],
                    'domain' => $config['domain'],
                    'bucket' => $config['bucket'],
                );

                $File = $file->getInfo();
                $Upload = new Uploadext($setting, 'Qiniu', $driverConfig);
                $info = $Upload->uploadOne($File);
                if ($info) {
                    $data['sha1'] = $info['sha1'];
                    $data['md5'] = $info['md5'];
                    $data['create_time'] = time();
                    $data['size'] = $info['size'];
                    $data['name'] = $info['name'];
                    $data['ext'] = $info['ext'];
                    $data['savepath'] = $info['url'];
                    $data['savename'] = $info['savename'];
                    $data['mime'] = $info['type'];
                    $map['md5'] = $info['md5'];
                    $data['location'] = 0;
                    $mmn = Db::name($table)->where($map)->find();
                    if (empty($mmn)) {
                        Db::name($table)->insert($data);
                        $res = Db::name($table)->getLastInsID();
                        if ($res > 0) {
                            return array('code' => 200, 'msg' => '上传成功', 'ext' => $data['ext'], 'id' => $res, 'path' => $data['savepath'], 'headpath' => $data['savepath'], 'md5' => $data['md5'], 'savename' => $data['savename'], 'filename' => $data['name'], 'info' => $info);
                        } else {
                            return array('code' => 0, 'msg' => '上传失败');
                        }
                    } else {
                        return array('code' => 200, 'msg' => '上传成功', 'ext' => $mmn['ext'], 'id' => $mmn['id'], 'path' => $mmn['savepath'], 'headpath' => $mmn['savepath'], 'md5' => $mmn['md5'], 'savename' => $mmn['savename'], 'filename' => $mmn['name'], 'info' => $mmn);
                    }
                } else {
                    return array('code' => 0, 'msg' => $Upload->getError());
                }

            } else {
                $info = $file->validate($validate)->move(ROOT_PATH . DS . 'uploads');

                if ($info) {

                    $path = DS . 'uploads' . DS . $info->getSaveName();
                    Log::record('savename ' . $info->getSaveName(), 'info');
                    // if($type=='images' && $isthumb = true){
                    //     $path_arr= explode(DS,$info->getSaveName());
                    //     $tmpdir=ROOT_PATH . 'uploads' . DS .'thumb';
                    //     $datedir=$tmpdir.DS.$path_arr[0];
                    //     Log::record('目录 ' . $datedir, 'info');
                    //     if(!is_dir($datedir))
                    //     {
                    //         @mkdir($datedir);
                    //     }
                    //     $smallway=$tmpdir.DS.$info->getSaveName();

                    //     $image=Image::open(request()->file($filename));// 按照原图的比例生成一个最大为150*150的缩略图并保存为thumb.jpg
                    //     $image->thumb(150, 150)->save($smallway);
                    //     $path = DS .'uploads' . DS .'thumb'.DS.$info->getSaveName();
                    // }
                    $path = str_replace("\\", "/", $path);
                    $realpath = WEB_URL . $path;
                    $data['sha1'] = $info->sha1();
                    $data['md5'] = $info->md5();
                    $data['create_time'] = time(); //;

                    $data['ext'] = $info->getExtension();
                    $data['size'] = $info->getSize();
                    $data['savepath'] = $path;
                    $data['savename'] = $info->getFilename();
                    $data['download'] = 0;
                    $fileinfo = $info->getInfo();
                    $data['name'] = $fileinfo['name'];
                    $data['mime'] = $fileinfo['type'];
                    Db::name($table)->insert($data);
                    $res = Db::name($table)->getLastInsID();
                    if ($res > 0) {
                        return array('code' => 200, 'msg' => '上传成功', 'hasscore' => 0, 'ext' => $data['ext'], 'id' => $res, 'path' => $path, 'headpath' => $realpath, 'md5' => $data['md5'], 'savename' => $info->getSaveName(), 'filename' => $fileinfo['name'], 'info' => $info->getInfo());
                    } else {
                        return array('code' => 0, 'msg' => '上传失败');
                    }
                } else {
                    return array('code' => 0, 'msg' => $file->getError());
                }
            }

        } else {

           $path = $n['savepath'];
           $realpath = WEB_URL . $path;
            return array('code' => 200, 'msg' => '上传成功', 'hasscore' => 1, 'ext' => $n['ext'], 'id' => $n['id'], 'path' => $path, 'headpath' => $realpath, 'md5' => $md5, 'savename' => $n['savename'], 'filename' => $n['name'], 'info' => $n);
        }

    }

/**
 * 获得content里的外部资源
 *
 * @access    public
 * @param     string  $content  文档内容
 * @return    string
 */
    public function getCurContent($content)
    {
        //匹配图片地址
        preg_match_all('/((http|https):\/\/)+(\w+\.)+(\w+)[\w\/\.\-]*(jpg|gif|png)/', $content, $img_array);
        //移除重复的Url
        $img_array = array_unique($img_array[0]);
        if ($img_array != '') {
            $imgUrl = DS . 'uploads' . DS . date("Ymd") . DS;
            $imgPath = ROOT_PATH . $imgUrl;
            $htd = new Http();
            foreach ($img_array as $key => $value) {

                //获取hash
                $md5 = strtolower($htd->getHash($value, true));
                $filemode = Db::name('file');
                $n = $filemode->where('md5', $md5)->find();
                $realpath = '';
                if (empty($n)) {
                    $itype = substr(strrchr($value, '.'), 1);
                    $milliSecondN = md5(microtime(true));

                    $rndFileName = $imgPath . $milliSecondN . '.' . $itype;
                    $fileurl = $imgUrl . $milliSecondN . '.' . $itype;

                    $rs = $htd->http_down($value, $rndFileName);
                    //抓取成功
                    if ($rs) {
                        $path = str_replace("\\", "/", $fileurl);
                        $realpath = WEB_URL . $path;
                        //写入数据库
                        $data['sha1'] = sha1($rndFileName);
                        $data['md5'] = md5($rndFileName);
                        $data['create_time'] = time();
                        $data['size'] = filesize($rndFileName);
                        $data['name'] = $milliSecondN . '.' . $itype;
                        $data['ext'] = $itype;
                        $data['savepath'] = $realpath;
                        $data['savename'] = $milliSecondN . '.' . $itype;
                        $data['mime'] = mime_content_type($rndFileName);

                        Db::name('file')->insert($data);
                    }
                } else {
                    $realpath = $n['savepath'];
                }
                $content = str_replace($value, $realpath, $content);
            }
        }
        return $content;
    }

}
