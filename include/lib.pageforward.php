<?php
/**
 * 使用curl 在页面间传递 post, files, cookie
 * 
 * 支持post多维数组
 * 支持files一维数组
 * 支持session和cookie传递
 * 支持目标页面二次跳转
 * 
 * 使用场景：
 * =============================================================================
 * 用户在A页面填写表单并上传文件
 * 表单提交到页面B，B页面对用户的数据进行缓存，之后进行登录或注册逻辑
 * 用户在B,C,D ...页面间跳转，最后在Y页面重新提交A页面填写的表单及上传文件到Z
 * 
 * B: setcachePage($cacheID, Z页面的URL);
 * Y: gotoCachePage($cacheID) //为避免路径调用问题，Y页面最好与Z页面在同一路径下
 * 
 * 在用户没有关闭浏览器，没有超时的情况下，随时可以使用 getCachePage($cacheID) 调用缓存
 */

/*
A.html:
=============================================================================
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>page forward demo</title>
</head>
<body>
	<form  method="post"  id="fabuForm" action="./B.php"  enctype="multipart/form-data">
	<input type="text" name="subject" value="hello world"><br>
	<input type="text" name="test[]" value="123"><br>
	<input type="text" name="test[]" value="456"><br>
	<input type="text" name="test[]" value="789"><br>
	<input type='file' name="file1[]" ><br>
	<input type='file' name="file1[]" ><br>
	<input type='file' name="pic" > <br><br><br>
	<input type="submit"  name="fabu1"  value="立即发布"  />
</form>
</body>
</html>

B.php
=============================================================================
<?php
include "inc.comm.php";

$obj = new pageforward();
$obj->setCachePage("test","/D.php");

echo "<h1>This is Page C!</h1>";
echo "<a href='./C.php' target='_blank'>Goto Page C</a>";

debug::d($_POST);
debug::d($_FILES);
debug::d($_SERVER);
debug::d($_COOKIE);
?>

C.php
=============================================================================
<?php
include "inc.comm.php";

if(!empty($_GET['go'])){
	$obj = new pageforward();
	$obj->gotoCachePage("test");
}

echo "<h1>This is Page C!</h1>";
echo "<a href='./E.php' target='_self'>Goto Page E</a><br>";
echo "<a href='./C.php?go=1' target='_blank'>Goto Page D</a>";

debug::d($_POST);
debug::d($_FILES);
debug::d($_SERVER);
debug::d($_COOKIE);
?>

E.php
=============================================================================
<?php
include "inc.comm.php";

echo "<h1>This is Page E!</h1>";
echo "<a href='./C.php' target='_self'>Goto Page C</a>";
?>

D.php
=============================================================================
<?php
include "inc.comm.php";

echo "<h1>This is Page D!</h1>";

debug::d($_POST);
debug::d($_FILES);
debug::d($_SERVER);
debug::d($_COOKIE);
?>
 */
if (!function_exists('curl_file_create')) {
	//兼容php5.5之前的函数，使用"@/path/to/filename"
	function curl_file_create($filename, $mimetype = '', $postname = '') {
		return "@$filename;filename="
		. ($postname ?: basename($filename))
		. ($mimetype ? ";type=$mimetype" : '');
	}
}

class pageforward{
	
	public $cacheServer = "cache01"; //缓存服务器
	public $cacheServerPort = "11211";
	
	public $prefixID = "pageforward";//缓存目录及memcache缓存标识
	public $lefttime = 3600; //数据缓存时间
	
	public $filetype = array("jpg","gif","jpeg","png"); //接受的上传文件类型
	
	private $tmppost=array();
	private $tmpfiles=array();
	
	/**
	 * 缓存页面提交的$_POST和$_FILES,
	 *
	 * @param string $cacheID
	 * @param string $url
	 */
	function setCachePage($cacheID,$url){
		if(!empty($_POST)) $this->initPost( $_POST );
		
		if(!empty($_FILES)){
			//创建缓存目录
			$cacheFolder = DOCUROOT."/data/".$this->prefixID."/".date("Ymd")."/".$cacheID;
			if(!file_exists($cacheFolder)) files::mkdirs($cacheFolder);
			$this->initFiles( $cacheFolder, $_FILES );
		}
		
		$data = array( 'url'=>$url, 'post'=>$this->tmppost, 'files'=>$this->tmpfiles );
		$memObj = func_initMemcached($this->cacheServer,$this->cacheServerPort);
		
		return $memObj->set($this->prefixID."_".$cacheID, $data, false, $this->lefttime);
	}
	
	function initPost($post,$key=""){
		if(empty($post)) return;
		
		foreach($post as $k=>$v){
			$resultKey = empty($key)?$k:$key."[{$k}]";
			
			if(is_array($v)){
				$this->initPost($v,$resultKey);
			}else{
				$this->tmppost[$resultKey]=$v;
			}
		}
	}
	
	function initFiles($cacheFolder, $files){
		foreach($files as $name=>$file){
			if(is_array($file['name'])){//处理文件数组
				foreach($file['name'] as $k=>$v){
					$ext = files::getExt( $v );
					if(!in_array(strtolower($ext),$this->filetype)) continue;
						
					move_uploaded_file($file['tmp_name'][$k], $cacheFolder."/".$v );
					$this->tmpfiles[$name."[{$k}]"] = $cacheFolder."/".$v;
				}
			}else{//处理单一文件
				$ext = files::getExt( $file['name'] );
				if(!in_array(strtolower($ext),$this->filetype)) continue;
			
				move_uploaded_file($file['tmp_name'], $cacheFolder."/".$file['name'] );
				$this->tmpfiles[$name] = $cacheFolder."/".$file['name'];
			}
		}
	}
	
	/**
	 * 读取页面缓存的$_POST和$_FILES
	 * @param string $cacheID
	 */
	function getCachePage($cacheID){
		$memObj = func_initMemcached($this->cacheServer,$this->cacheServerPort);
		return $memObj->get($this->prefixID."_".$cacheID);
	}
	
	/**
	 * 前往缓存页面的最终目标页
	 *
	 * @param string $cacheID
	 * @param array $post 新增加或要替换的POST值
	 * @param array $files 新增加或要替换的FILES值
	 * @param string $url 要去的目标页面，默认使用setCachePage时设置的url
	 */
	function gotoCachePage($cacheID, $post=array(), $files=array(), $url=null ){
		$memObj = func_initMemcached($this->cacheServer,$this->cacheServerPort);
		$data = $memObj->get($this->prefixID."_".$cacheID);
		if(empty($data)) exit("Data Not Found!");
		
		//目标页面
		$url = substr($data['url'],0,1)=='/'?"http://{$_SERVER["HTTP_HOST"]}{$data['url']}":$data['url'];
		if(empty($data['post'])) go($url);
		
		//合并所有的post
		if(!empty($post)){
			$this->initPost($post);
			foreach($this->tmppost as $k=>$v){
				$data['post'][$k]=$v;
			}
		}
		
		//curl 提交的数据
		$postfields = $data['post'];
		
		$files= array();
		if(!empty($data['files'])){
			foreach($data['files'] as $key=>$filename) {
				if(!file_exists($filename)) continue;
				$postfields[$key] = curl_file_create($filename);
			}
		}
		
		$ch = curl_init();
	
		//传递cookie
		if(!empty($_COOKIE)){
			$cookie=array();
			foreach($_COOKIE as $key=>$val) $cookie[]="{$key}={$val}";
			curl_setopt($ch, CURLOPT_COOKIE, implode("; ", $cookie));
		}
		
		curl_setopt($ch, CURLOPT_URL, $url );
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POST, true);	
		curl_setopt($ch, CURLOPT_POSTFIELDS, $postfields);
	
		$html = curl_exec($ch);
		$info = curl_getinfo($ch);
	
		curl_close($ch);
		
		//缓存只使用一次
		if(!empty($info)) $memObj->set($this->prefixID."_".$cacheID, array('url'=>$data['url']), false, $this->lefttime);
	
		if(empty($info['redirect_url'])){
			//直接显示目标页面内容
			echo $html;
			exit;
		}else{
			//重定向到新的目标页面
			go($info['redirect_url']);
		}
	}
	
	//TODO 每天运行，清理前一天的中间缓存文件
	function cleanup($now=null){
		if( empty($now) ) $now = time();
		$day = 3600*24;
		
		for($i=2;$i<7;$i++){
			$timestamp = $now-$i*$day;
			$cacheFolder = DOCUROOT."/data/".$this->prefixID."/".date("Ymd",$timestamp);
			if(file_exists($cacheFolder)){
				files::rmdirs($cacheFolder);
				
			}echo "Delete Folder {$cacheFolder} ...\n";
		}
		
		echo "Complete!\n";
	}
}