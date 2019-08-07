<?php
/**
    代码同步 包含svn github
    
    inc.comm.php
        特殊位置需要加入更新root目录 //define( 'REPOROOT', '');
        angular build 项目名单
    
    本机单节点
        inc.updateid.php    inc.webnodes.php
    
    调用方法
        include "inc.comm.php";
        $obj_repository=new repository();
     
    同步 sync.php
     	$obj_repository->init_sync();
     	
    更新svn update_svn.php	
    	echo $obj_repository->update_svn();
    	
    更新git update_git.php
    	echo $obj_repository->update_git();
    	
    后台更新svn svnTask.php $1 $2
        php svnTask.php 
            
    后台更新git gitTask.php $1 $2
        php gitTask.php 
*/

class repository
{
	//报错信息
	public $msg =array(
			'fail'=>"同步失败！",
			'lock'=>": <h1 style='color:red'>请看： 服务器同步锁定中 请稍候在发布!</h1>\n",
			'unlock'=>": 解除锁定\n",
	        'unlock_error'=>"所有服务器已经全部解锁\n",
			'auth'=>'权限检测失败，请以管理员身份在当前站点重新登录!',
			'parent'=>"请选择父目录\n",
			'parentroot'=>'无法同步根目录',
			'syncPath'=>": 目录与文件位置 \n\n",
			'syncServer'=>"同步服务器中 ...\n",
	        'removeServer_token'=>"错误 ...\n",
	);
	
	public $publish;	//线上同步
	public $servername;		
	public $serveroot;
	public $ngBuildFolder;
	
	public $parent;		//父目录
	public $path;		//子目录
	public $version;	//版本库代码编号
	public $unlock;		//解除锁定
	
	public $memCache;
	public $repo_type;  //版本库种类
	public $depolyCacheFile = DOCUROOT."/cache_angular/angularDeploy.json";
	
	function __construct($repo_type='svn')
	{
		$this->memCache=func_initMemcached("sourceNode");
		
		//服务器信息
		$this->servername=empty($_SERVER["SERVER_NAME"])?"":$_SERVER["SERVER_NAME"];
		$this->serveroot=defined('REPOROOT')?REPOROOT:DOCUROOT;
		$this->ngBuildFolder="angular";
		
		//版本库信息
		$this->repo_type=$repo_type;
		func_initSession();
	}
	
	//生成同步模板
	function init_sync()
	{
		//检查权限
		if(!defined('SKIP_SVN_ADMIN')) 
			if(!$this->checkAuth())	go("/admin/");
		
		$smartyObj=func_getSmarty('include');
		$tpl=DOCUROOT."/include/template/sync_{$this->repo_type}.html";
		
		$smartyObj->assign("servername",$_SERVER["SERVER_NAME"]);
		$smartyObj->assign("scriptname",$_SERVER["SCRIPT_NAME"]);
		$smartyObj->assign("target",$this->get_fileList());
		
		//解除锁定
		if(!empty($_GET['unlock']))
		{
			$rs=$this->unlock();
			$smartyObj->assign("rs",$rs);
		}
		
		//同步
		if(!empty($_POST))
		{
			$this->parent=empty($_POST['parent'])?"":$_POST['parent'];
			$this->path=empty($_POST['path'])?"":$_POST['path'];
			$this->version=empty($_POST['version'])?"":$_POST['version'];
			$this->publish=empty($_POST['publish'])?"":$_POST['publish'];
			
			$rs=$this->sync();
		
			$smartyObj->assign("parent",$this->parent);
			$smartyObj->assign("path",$this->path);
			$smartyObj->assign("version",$this->version);
			$smartyObj->assign("rs",$rs);
		}
		
		echo $smartyObj->fetch($tpl);
	}
	
	//同步
	function sync()
	{
		//初始化
		if(!isset($_SESSION))
			func_initSession();
		
		//检查权限
		if(!defined('SKIP_SVN_ADMIN')) 
			if(!$this->checkAuth())	return $this->msg['auth'];
		
		//根目录无法同步
		if(empty($this->parent)) return	$this->msg['parent'];
		
		//同步程序
		if(!empty($this->publish))
			$rs_sync=$this->sync_publish();
		else
			$rs_sync=$this->sync_local();
		
		return $rs_sync;
	}
	
	//同步本机
	private function sync_local()
	{
		//获取同步路径
		$path=$this->get_path();
		$msg=$path.$this->msg['syncPath'];
		
		//获取本地IP
		if(!is_file( DOCUROOT."/inc.updateid.php" ))exit();
		$localip=include DOCUROOT."/inc.updateid.php";
		
		//检查锁
		$checkval = $this->memCache->get(systemVersion."_".$this->repo_type."_".$localip);
		$checkcron = $this->memCache->get(systemVersion."cron_lock");
		
		if(!empty($checkval) || !empty($checkcron))
			$msg.=$localip.$this->msg['lock'];
		else 
		{
			//同步
		    $this->memCache->add(systemVersion."_".$this->repo_type."_".$localip,$path,false,0);
			
			//回复版本库指定版本
			$version=intval($this->version);
			if(!empty($version))
			    $this->memCache->add(systemVersion."_".$this->repo_type."_ver_".$localip,$version,false,0);
			
			$msg .= $localip.$this->msg['syncServer'];
		}
		
		return $msg;
	}
	
	//同步线上
	private function sync_publish()
	{
		//获取同步路径
		$path=$this->get_path();
		$msg=$path.$this->msg['syncPath'];
		
		//同步服务器池
		$serverList=func_getNodes();
		
		foreach($serverList as $v)
		{
		    //发布代码
		    $data=[
		        "token"=>"pmc_2018_git update",
		        "path"=>$path,
		        "localip"=>$v,
		    ];
		    
		    $rs_api=http::sendpost( $data,$v);
		    $msg.=$rs_api;
		}
		
	    return $msg;
	}
	
	//远程发布
	function remote_sync()
	{
	    $token=empty($_POST['token'])?"":urldecode($_POST['token']);
	    $localip=empty($_POST['localip'])?"":urldecode($_POST['localip']);
	    $path=empty($_POST['path'])?"":urldecode($_POST['path']);
	    
	    if($token!="pmc_2018_git update"){
	        debug::d($this->msg['removeServer_token']);
	    }
	    
	    //检查锁
	    $checkPos = false;
	    $checkval = $this->memCache->get(systemVersion."_".$this->repo_type."_".$localip);
        
        //锁定
        $msg="";
        if(!empty($checkval))
        {
            $msg.=$this->msg['lock'];
            $checkPos=true;
            debug::d($this->msg['lock']);
        }else{
            $this->memCache->add(systemVersion."_".$this->repo_type."_".$localip,$path,false,0);
        }
	    
	    exit;
	}
	
	//后台shell同步
	function svnTask($argv)
	{
		//同步服务器池
		$serverList=func_getNodes();
		
		//参数
		$path=empty($argv[1])?"":$argv[1];
		$version=empty($argv[2])?"":$argv[2];
			
		$msg="";
		if(empty($path))
			$msg .= $this->msg['parent'];
		else
		{
			$msg="";
			foreach($serverList as $v)
			{
			    $this->memCache->add(systemVersion."_".$this->repo_type."_".$v,$path,false,0);
			
				//回复版本库指定版本
				$version=intval($version);
				if(!empty($version))
				    $this->memCache->add(systemVersion."_".$this->repo_type."_ver_".$v,$version,false,0);
			
				$msg .= $v.$this->msg['syncServer'];
			}
		}
		
		echo $msg;
	}
	
	//权限认证
	private function checkAuth($level=1,$ids=array(0,1))
	{
		//是否登录
		if(empty($_SESSION ["UserID"]))
			return false;
		
		if($level==1)
			return true;
		
		//是否为管理员
		if( $_SESSION["UserLevel"]!=1 )
			return false;
		
		if($level==2)
			return true;
		
		if( !in_array($_SESSION ["UserID"], $ids))
			return false;
	
		return true;
	}
	
	//获取同步路径
	private function get_path()
	{
	    if($this->parent=='.'){
	        $path=$this->parent;
	    }
	    else{
	        $path = "/".$this->parent;
	    }
	    
		if(!empty($this->path))
		{
			if(substr($this->path,0,1)=='/')
				$path .= $this->path;
			else
				$path .= '/'.$this->path;
		}
		
		return $path;
	}
	
	
	//解除服务器svn锁定
	function unlock()
	{
		$localip=include DOCUROOT."/inc.updateid.php";
		
		//同步服务器池
		$serverList=func_getNodes();
		$serverList[]=$localip;
		$msg="";
		
		if(!empty($serverList))
		{
			foreach($serverList as $v)
			{
				if(empty($v))
					continue;
				
				$check=$this->memCache->get(systemVersion."_".$this->repo_type."_".$v);
				if(empty($check))
				    $msg=$this->msg['unlock_error'];
				else 
				{
				    $status=$this->memCache->delete(systemVersion."_".$this->repo_type."_".$v);
				    $msg.=$v.$this->msg['unlock'];
				}
			}
		}
		
		//解锁gitlock
		$memCache=func_initMemcached("cache01");
		$memCache->delete(systemVersion."cron_lock");
		
		return $msg;
	}
	
	//更新代码svn
	function update_svn()
	{
		//获取本地IP
		if(!is_file( DOCUROOT."/inc.updateid.php" ))exit();
		$localip=include DOCUROOT."/inc.updateid.php";
		
		$val = $this->memCache->get(systemVersion."_svn_".$localip);
		$version = $this->memCache->get(systemVersion."_svn_ver_".$localip);
		
		//更新
		$log="";
		if(!empty($val))
		{
			$this->memCache->delete(systemVersion."_svn_".$localip);
			if(!empty($version))$this->memCache->delete(systemVersion."_svn_ver_".$localip);
		
			if($val=='root')
			    $path = $this->serveroot;
			else
			{
				if(substr($val,0,1)!='/') $val = "/".$val;
				if(substr($val,0,strlen($this->serveroot))!=$this->serveroot) $val = $this->serveroot . $val;
				$path = $val;
			}
		
			$log = "";
			if(empty($version))
			{
				$log.= "/usr/bin/svn update {$path}\n";
				$log.= shell_exec("/usr/bin/svn update {$path}");
			}
			else
			{
				$log.= "/usr/bin/svn -r {$version} update {$path}\n";
				$log.= shell_exec("/usr/bin/svn -r {$version} update {$path}");
			}
		
			if(!empty($log)) file_put_contents("/pub/log/svnUpdate.log", $log);
		}
		
		return $log;
	}
	
	//更新代码git
	function update_git()
	{
	    if(!is_file( DOCUROOT."/inc.updateid.php" ))exit();
	    $localip=include DOCUROOT."/inc.updateid.php";
	    
		$val = $location = $this->memCache->get(systemVersion."_git_".$localip);
		$version = $this->memCache->get(systemVersion."_git_ver_".$localip);
		
		//更新
		$log="";
		if(!empty($val))
		{
			if($val=='root')
			    $path = $this->serveroot;
			else
			{
				if(substr($val,0,1)!='/') $val = "/".$val;
				if(substr($val,0,strlen($this->serveroot))!=$this->serveroot) $val = $this->serveroot . $val;
				$path = $val;
			}
			
			//angular path
			if(str_replace("/", "", $location)==$this->ngBuildFolder){
			    $path=substr($this->serveroot, 0, -4) . $location;
			}
			
			$log = "";
			if(empty($version))
			{
				//git fetch origin
				//$log.= shell_exec("/usr/bin/git fetch origin > /pub/log/resultGitUpdate.log 2>&1");
				//$log.= shell_exec("/usr/bin/git checkout FETCH_HEAD -- {$path} >> /pub/log/resultGitUpdate.log 2>&1");
				
			    $log.= shell_exec("/usr/bin/git pull");
			}
			else
			{
			    //git fetch reverse
			}
			
			if(str_replace("/", "", $location)==$this->ngBuildFolder){
			    $dist_name=str_replace("/", "", $location);
			    $log.= shell_exec("cd {$path} && npm install >> /pub/log/resultGitUpdate.log 2>&1");
			    $log.= shell_exec("cd {$path} && ng build --prod >> /pub/log/resultGitUpdate.log 2>&1");
			    
			    $dist_exists=substr($this->serveroot, 0, -4).$location."/dist";
			    if (file_exists($dist_exists)) {
			        //创建dist包
			        $dist_name=str_replace("/", "", $location);
			        $log.= shell_exec("cd {$path} && cp -rf {$path}/dist ../www/cache_angular/_{$dist_name}");
			        $log.= shell_exec("cd {$path} && cd ../www/cache_angular && mv {$dist_name} {$dist_name}_rm");
			        $log.= shell_exec("cd {$path} && cd ../www/cache_angular && mv _{$dist_name} {$dist_name}");
			        $log.= shell_exec("cd {$path} && cd ../www/cache_angular && rm -rf {$dist_name}_rm");
			        
			        //删除链接
			        $depolyCache = [];
			        if(is_file($this->depolyCacheFile)) $depolyCache = json_decode(file_get_contents($this->depolyCacheFile));
			        if(!empty($depolyCache)){
			            foreach($depolyCache as $link){
			                if(in_array($link,['index.html','assets','favicon.ico']))continue;
			                unlink(DOCUROOT."/".$link);
			            }
			        }
			        
			        //建立链接
			        $distpath = $this->serveroot."/cache_angular".$location;
			        $target = files::fileAll($distpath);
			        if(!is_link(DOCUROOT.'/assets')){
			            symlink($distpath."/index.html", DOCUROOT.'/angular.html');
			        }
			        foreach($target as $link){
			            if(is_link(DOCUROOT.'/'.$link)||is_file(DOCUROOT.'/'.$link)) continue;
			            if(in_array($link,['favicon.ico','index.html'])) continue;
			            if(substr($link,0,1)=='.')continue;
			            
			            symlink($distpath."/".$link, DOCUROOT.'/'.$link );
			        }
			        
			        //记录angular文件
			        file_put_contents($this->depolyCacheFile, json_encode($target));
			    }
			}
			
			if(!empty($log)) file_put_contents("/pub/log/gitUpdate.log", $log);
			
			$this->memCache->delete(systemVersion."_git_".$localip);
			if(!empty($version))$this->memCache->delete(systemVersion."git_ver_".$localip);
		}
	
		return $log;
	}
	
	//替换磁盘文件
	function replace_angular_index($location)
	{
	    $index_path=substr($this->serveroot, 0, -4).$location."/dist/index.html";
	    
	    $content=file_get_contents($index_path);
	    $content=str_replace('/assets/css/', $location.'/assets/css/', $content);
	    $content=str_replace('<base href="/">', '<base href="'.$location.'/">', $content);
	    
	    file_put_contents($index_path,$content);
	}
	
	//获取同步文件目录
	function get_fileList()
	{
		$list = files::fileList( $this->serveroot );
		$check_exist=$this->serveroot;
		
		$target = array();
		$target[]=".";
		
		foreach($list as $value){
			if(in_array($value,array('mysql','include','data','upload','cache','.svn','cache_angular','cache_data','cache_upload','.settings','.git','.mage','vendor','.well-known','.sh'))) continue;
			if( is_dir( $check_exist."/".$value ) ){
				if(isset($skip)){
					if(!in_array($value,$skip)) $target[] = $value;
				}else{
					$target[] = $value;
				}
			}
		}
		
		//angular
		$target[]=$this->ngBuildFolder;
		
		return $target;
	}
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
}
