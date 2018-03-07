<?php
namespace addons\attachlink\controller;
use think\Db;
class Attachlink
{
    public function get_curl($url, $post = 0, $referer = 0, $cookie = 0, $header = 0, $ua = 0, $nobaody = 0)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        if ($post) {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        }
        if ($header) {
            curl_setopt($ch, CURLOPT_HEADER, true);
        }
        if ($cookie) {
            curl_setopt($ch, CURLOPT_COOKIE, $cookie);
        }
        if ($referer) {
            curl_setopt($ch, CURLOPT_REFERER, $referer);
        }
        if ($ua) {
            curl_setopt($ch, CURLOPT_USERAGENT, $ua);
        }
        if ($nobaody) {
            curl_setopt($ch, CURLOPT_NOBODY, 1);
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $ret = curl_exec($ch);
        curl_close($ch);
        return $ret;
    }

    public function openlink()
    {
        $id = input('id');
        $linkid = input('linkid');
        $analyze = input('analyze');
        $linkinfo = Db::name('attachlink')->where('id',$id)->value('linkinfo');
        $arr = explode(';', $linkinfo);
        $arr2 = explode('|', $arr[$linkid]);
        $name=$arr2[0];
        $link=$arr2[1];
        $passwd=(count($arr2)==3)?$arr2[2]:'';
        $local=0;
        if (strpos($link, 'http') === false) {
            $local=1;
            $link=WEB_URL.$link;
        }
        $msg='链接地址获取成功';
        if($analyze){
            if (strpos($link, "lanzou") != -1) {
                $link = $this->lzy($link);
                $msg='蓝奏云直链解析成功';
            }
        }
        return array('code' => 200,'msg'=>$msg, 'path' => $link,'name' => $name,'local'=>$local);
    }

    public function lzy($url)
    {

        $curldata = $this->get_curl($url, 0, $url);
        preg_match_all('|<iframe(.+?)src="(.+?)"(.+?)frameborder="0"|i', $curldata, $datarr);
        $fndata = $datarr[2][0];

        $url1 = "https://pan.lanzou.com" . $fndata;
        $curldata2 = $this->get_curl($url1);
        preg_match_all("|'down_process';([\s\S]*)\.ajax|i", $curldata2, $datarr2);
        $fndata2 = str_replace(";\n\t\t", "", $datarr2[1][0]);
        preg_match_all("|var i([\s\S]*)= '(.*)'([\s\S]*)= '(.*)'([\s\S]*)= '(.*)'([\s\S]*)|i", $fndata2, $datarr3);
        $file_id = $datarr3[2][0];
        $t = $datarr3[4][0];
        $k = $datarr3[6][0];

        $post_data = array("action" => "down_process", "file_id" => $file_id, "t" => $t, "k" => $k);

        $action1 = "https://pan.lanzou.com/ajaxm.php";
        $jsondata = $this->get_curl($action1, $post_data, $url1);
        $downarr = json_decode($jsondata, true);
        $dom = $downarr['dom'];
        $file = $downarr['url'];
        return 'http://' . $dom . '/file/' . $file;
    }
}
