<?php
set_time_limit(0);

$system = new \Think\Db();
$config = new \Think\Config();

//$rec = $_REQUEST['rec'] ? $_REQUEST['rec'] : 'default';
//参数1：访问的URL，参数2：post数据(不填则为GET)，参数3：提交的$cookies,参数4：是否返回$cookies
function curl_request($url,$post='',$cookie='', $returnCookie=0){
	$curl = curl_init();
	curl_setopt($curl, CURLOPT_URL, $url);
	curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 10.0; Windows NT 6.1; Trident/6.0)');
	curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
	curl_setopt($curl, CURLOPT_AUTOREFERER, 1);
	curl_setopt($curl, CURLOPT_REFERER, "http://XXX");
	if($post) {
		curl_setopt($curl, CURLOPT_POST, 1);
		curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($post));
	}
	if($cookie) {
		curl_setopt($curl, CURLOPT_COOKIE, $cookie);
	}
	curl_setopt($curl, CURLOPT_HEADER, $returnCookie);
	curl_setopt($curl, CURLOPT_TIMEOUT, 10);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	$data = curl_exec($curl);
	if (curl_errno($curl)) {
		return curl_error($curl);
	}
	curl_close($curl);
	if($returnCookie){
		list($header, $body) = explode("\r\n\r\n", $data, 2);
		preg_match_all("/Set\-Cookie:([^;]*);/", $header, $matches);
		$info['cookie']  = substr($matches[1][0], 1);
		$info['content'] = $body;
		return $info;
	}else{
		return $data;
	}
}

//先检测

$version = $system::name('system')->where('name', 'version')->value('value');
    if (empty($version)) {
        $data['name'] = 'version';
        $data['value'] = 1;
        $system::name('system')->insert($data);
        $version = 1;
	}
	
    $url = 'http://www.laysns.com/update.php?v=' . $version;
    $data = curl_request($url);
    $arr = json_decode($data,true);
    if ($arr['code'] == 200) {
		$newver=$arr['version'];
        if ($newver == $version) {

            return json(array('code' => 0, 'msg' => '您已经是最新版本，不需升级'));

        } else {			
			$dirname = dirname(__FILE__);
			$install_sql = $dirname . '/update'+$newver+'.sql';
			if (file_exists($install_sql)) {
				$db_config = array();
				$db_config['prefix'] = $config::get('database.prefix');
				$sqldata = file_get_contents($install_sql);
				$sql_array = preg_split("/;[\r\n]+/", str_replace('ls_', $db_config['prefix'], $sqldata));
				foreach ($sql_array as $k => $v) {
					if (!empty($v)) {
						$system::query($v);
					}
				}
			}
			$system::name('system')->where('name', 'version')->setField('value', $newver);
			return json(array('code' => 200, 'msg' => '更新完毕，请清理缓存并重新登录'));
        }
    } else {
        return json(array('code' => 0, 'msg' => '远程服务器连接失败，请稍后再试'));
	}
	
    

    

