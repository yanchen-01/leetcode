<?php
class video{
	
	function init($config){
		if(empty($config['link'])) return '';
		
		$w=empty($config['w'])?480:$config['w'];
		$h=empty($config['h'])?400:$config['h'];
		
		
		
		return ;
	}
	
	/**
	 * 处理http的视频源文件
	 * http://www.mediaelementjs.com/
	 */
	private function youtube(){
		
	}
	
	/**
	 * 处理http的视频源文件
	 * http://www.mediaelementjs.com/
	 */
	private function dailymotion(){
		
	}
	
	/**
	 * 处理http的视频源文件
	 * http://www.mediaelementjs.com/
	 */
	private function sourcefile(){
		
	}

	/**
	 * 使用mplayer进行视频截图
	 * 使用示例 video::capture("/tmp/4b6d045f776c373a.mp4",DOCUROOT."/cache/",5);
	 * 使用示例 video::capture("http://domain.com/demo/nature.mp4",DOCUROOT."/cache/",5);
	 * 
	 * 
	 * @param $video 视频地址
	 * @param $path 截图存储路径
	 * @param $start 视频开始多久后截图，以秒为单位
	 * @param $os 服务器的操作系统 
	 * 
	 * mac 下安装：port install MPlayer
	 * ubuntu 下安装: apt-get install mplayer
	 * 
	 * /root/.mplayer/config
	 * ===============================
	 * //设置mplayer默认使用ipv4
	 * prefer-ipv4 = yes
	 * 
	 * 
	 * 详细参数可参见man mplayer
	 *  jpeg
              Output each frame into a JPEG file in the current directory.  Each file takes the frame  number  padded
              with leading zeros as name.
                 [no]progressive
                      Specify standard or progressive JPEG (default: noprogressive).
                 [no]baseline
                      Specify use of baseline or not (default: baseline).
                 optimize=<0-100>
                      optimization factor (default: 100)
                 smooth=<0-100>
                      smooth factor (default: 0)
                 quality=<0-100>
                      quality factor (default: 75)
                 outdir=<dirname>
                      Specify the directory to save the JPEG files to (default: ./).
                 subdirs=<prefix>
                      Create  numbered  subdirectories  with the specified prefix to save the files in instead of the
                      current directory.
                 maxfiles=<value> (subdirs only)
                      Maximum number of files to be saved per subdirectory.  Must be equal to or larger than  1  (de‐
                      fault: 1000).
                      
       TODO 增加视频多个截图支持
	 */
	static function capture($video, $path, $start=5, $os = "linux" ){
		$start = intval($start);
		$cmd = $os=='linux'?"/usr/bin/mplayer":"/opt/local/bin/mplayer";
		
		$id = md5($video);
		$outdir = "/tmp/mplayer_{$id}";
		if(!is_dir($outdir)) mkdir($outdir);//临时存储的文件位置
		
		$cmd = "{$cmd} -nosound -vo jpeg:outdir={$outdir} -frames 1 -ss {$start} {$video} -loop 1";
		shell_exec($cmd);
		
		$filename = $outdir."/00000001.jpg";
		if(!file_exists($filename)) {
			if(is_dir($outdir)) rmdir($outdir);
			return false;
		}
		
		$fullfilepath = $path."/{$id}.jpg";
		rename($filename,$fullfilepath);
		if(is_dir($outdir)) rmdir($outdir);
		
		$url = substr($fullfilepath,strlen(DOCUROOT));
		
		return $url;
	}
	
}
?>