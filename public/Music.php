<?php
//ini_set("display_errors", "On");
//error_reporting(E_ALL | E_STRICT);

define('HTTPS', false);    // 如果您的网站启用了https，请将此项置为“true”，如果你的网站未启用 https，建议将此项设置为“false”
define('DEBUG', true);      // 是否开启调试模式，正常使用时请将此项置为“false”

if(!defined('DEBUG') || DEBUG !== true) error_reporting(0); // 屏蔽服务器错误
$source = getParam('source', 'netease');  // 歌曲源
$types = getParam('types');
switch($types)   // 根据请求的 Api，执行相应操作
{
    case 'url':   // 获取歌曲链接
        $id = getParam('id');  // 歌曲ID
        $br = 320;
        //case 'netease':
            $api = array(
                'method' => 'GET',
                'url' => 'http://music.163.com/song/media/outer/url?id='.$id,
                //'url'    => 'http://music.163.com/api/song/enhance/player/url',
                'body'   => array(
                    'ids' => array($id),
                    'br'  => $br * 1000,
                ),
                'encode' => 'netease_AESCBC',
                'decode' => 'netease_url',
            );
        $data = get_headers($api['url'],1);
        header("Location: {$data['Location']}");        
        break;
        
    case 'pic':   // 获取歌曲链接
     	$size = 300;
        $id = getParam('id');  // 歌曲ID
        $url = 'https://p3.music.126.net/'.netease_encryptId($id).'/'.$id.'.jpg?param='.$size.'y'.$size;
        $data = json_encode(array('url' => $url));        
        echojson($data);
        break;
    case 'detail':   // 获取歌曲详情
        $id = getParam('id');  // 歌曲ID
        $api = array(
                'method' => 'GET',
                'url' => 'http://music.163.com/api/song/detail/?id='.$id.'&ids=['.$id.']',
            );        
        $data = my_exec($api);               
        echojson($data);
        break;
    case 'lyric':       // 获取歌词
        $id = getParam('id');  // 歌曲ID 
        //case 'netease':
            $api = array(
                'method' => 'GET',
                'url'    => 'http://music.163.com/api/song/lyric?os=pc&id='.$id.'&lv=-1&kv=-1&tv=-1',
                //'url'    => 'http://music.163.com/api/song/lyric',
                'body'   => array(
                    'id' => $id,
                    'os' => 'pc',
                    'lv' => -1,
                    'kv' => -1,
                    'tv' => -1,
                ),
                'encode' => 'netease_AESCBC',
                'decode' => 'netease_lyric',
            );       
        $data = my_exec($api);               
        echojson($data);
        break;
        
    case 'download':    // 下载歌曲(弃用)
        $fileurl = getParam('url');  // 链接        
        header('location:$fileurl');
        exit();
        break;
    
    case 'userlist':    // 获取用户歌单列表
        $uid = getParam('uid');  // 用户ID
        
        $url= 'https://music.163.com/api/user/playlist/?offset=0&limit=1001&uid='.$uid;
        $data = file_get_contents($url);        
        echojson($data);
        break;
        
    case 'playlist':    // 获取歌单中的歌曲
        $id = getParam('id');  // 歌单ID  
        //case 'netease':
            $api = array(
                'method' => 'GET',
                'url'    => 'http://music.163.com/api/playlist/detail?id='.$id,
                //'url'    => 'http://music.163.com/api/v6/playlist/detail',
                'body'   => array(
                    's'  => '0',
                    'id' => $id,
                    'n'  => '1000',
                    't'  => '0',
                ),
                'encode' => 'netease_AESCBC',
                'format' => 'playlist.tracks',
            );
        $data = my_exec($api);     
        echojson($data);
        break;
     
    case 'search':  // 搜索歌曲
        $keyword = getParam('name');  // 歌名
        $limit = getParam('count', 20);  // 每页显示数量
        $page = getParam('page', 1);  // 页码     
        $offset =  isset($page) && isset($limit) ? ($page - 1) * $limit : 0;
        $api = array(
                'method' => 'GET',
                'url'    => 'http://music.163.com/api/search/get?s='.urlencode($keyword).'&type=1&limit='.$limit.'&offset='.$offset,
            );
        $data = my_exec($api);
        echojson($data);
        break;
        
    default:
        echo '<!doctype html><html><head><meta charset="utf-8"><title>信息</title><style>* {font-family: microsoft yahei}</style></head><body><br>';
        if(!defined('DEBUG') || DEBUG !== true) {   // 非调试模式
            echo '<p>Api 调试模式已关闭</p>';
        } else {
            echo '<p><font color="red">您已开启 Api 调试功能，正常使用时请在 api.php 中关闭该选项！</font></p><br>';
            
            echo '<p>PHP 版本：'.phpversion().' （本程序要求 PHP 5.4+）</p><br>';
            
            echo '<p>服务器函数检查</p>';
            echo '<p>curl_exec: '.checkfunc('curl_exec',true).' （用于获取音乐数据）</p>';
            echo '<p>file_get_contents: '.checkfunc('file_get_contents',true).' （用于获取音乐数据）</p>';
            echo '<p>json_decode: '.checkfunc('json_decode',true).' （用于后台数据格式化）</p>';
            echo '<p>hex2bin: '.checkfunc('hex2bin',true).' （用于数据解析）</p>';
            echo '<p>openssl_encrypt: '.checkfunc('openssl_encrypt',true).' （用于数据解析）</p>';
        }
        
        echo '</body></html>';
}
function my_exec($api)
    {    

        if ($api['method'] == 'GET') {            
           $data = file_get_contents($api['url']);
         }
        return $data;
    }
function netease_encryptId($id)
    {
        $magic = str_split('3go8&$8*3*3h0k(2)2');
        $song_id = str_split($id);
        for ($i = 0; $i < count($song_id); $i++) {
            $song_id[$i] = chr(ord($song_id[$i]) ^ ord($magic[$i % count($magic)]));
        }
        $result = base64_encode(md5(implode('', $song_id), 1));
        $result = str_replace(array('/', '+'), array('_', '-'), $result);

        return $result;
    }
/**
 * 创建多层文件夹 
 * @param $dir 路径
 */
function createFolders($dir) {
    return is_dir($dir) or (createFolders(dirname($dir)) and mkdir($dir, 0755));
}

/**
 * 检测服务器函数支持情况
 * @param $f 函数名
 * @param $m 是否为必须函数
 * @return 
 */
function checkfunc($f,$m = false) {
	if (function_exists($f)) {
		return '<font color="green">可用</font>';
	} else {
		if ($m == false) {
			return '<font color="black">不支持</font>';
		} else {
			return '<font color="red">不支持</font>';
		}
	}
}

/**
 * 获取GET或POST过来的参数
 * @param $key 键值
 * @param $default 默认值
 * @return 获取到的内容（没有则为默认值）
 */
function getParam($key, $default='')
{
    return trim($key && is_string($key) ? (isset($_POST[$key]) ? $_POST[$key] : (isset($_GET[$key]) ? $_GET[$key] : $default)) : $default);
}

/**
 * 输出一个json或jsonp格式的内容
 * @param $data 数组内容
 */
function echojson($data)    //json和jsonp通用
{
    header('Content-type: application/json');
    $callback = getParam('callback');
    
    if(defined('HTTPS') && HTTPS === true && !defined('NO_HTTPS')) {    // 替换链接为 https
        $data = str_replace('http:\/\/', 'https:\/\/', $data);
        $data = str_replace('http://', 'https://', $data);
    }else if(defined('HTTPS') && HTTPS === false){
        $data = str_replace('https:\/\/', 'http:\/\/', $data);
        $data = str_replace('https://', 'http://', $data);
    }
    
    if($callback) //输出jsonp格式
    {
        die(htmlspecialchars($callback).'('.$data.')');
    } else {
        die($data);
    }
}
