<?php
/**
 * First Created on 2008-4-3 By Weiqi
 *
 * 系统URL开关：
 * clear:
 *   true 			//清除当前页面的静态缓存
 *   cacheDebug 	//清除并显示缓存信息
 *   tb				//清理数据表缓存文件
 *   label			//重新生成标签
 *   config			//重新生成站点全局变量
 *   struct			//清理app映射表
 *   forums			//清除论坛版面导航缓存
 *   forumcfg		//清除论坛版面配置缓存
 *
 * debug:
 *   true           //打开错误调试
 *   showsql		//显示所有执行的sql语句
 *   showfields		//显示所有smarty输出的字段
 *   showfile		//下载文件时显示临时文件地址
 *   showtpl		//显示当前调用的模板
 *   showcache      //显示缓存信息
 *   showget        //显示url参数
 *   showpost       //显示post参数
 *   showquery      //显示查询条件
 *   showserver     //显示服务器信息
 *   showappconfig  //page相关系统下，显示应用程序配置信息
 *   pageAppConf    //显示page中全部模块的配置信息
 *   showIncData    //显示页面内嵌模板的内容
 *   showLoadInfo	//显示所有自动加载的类路径
 *
 * other:
 *   field			//在debug=showfields的前提下，指定显示的field
 */

/**
 * 类自动加载函数
 * @param string $class_name
 */
function autoload( $class_name ) {
    //加载环境
    $path_cls = DOCUROOT . "/include/cls." . $class_name . ".php";
    if( file_exists( $path_cls ) ){
        require_once  $path_cls;
        return true;
    }
    
    //加载公共库
    $path_lib = DOCUROOT . "/include/lib." . $class_name . ".php";
    if( file_exists( $path_lib ) ){
        require_once  $path_lib;
        return true;
    }
    
    //加载app
    if(strstr($class_name, '_')){
        $arr=explode("_",$class_name);
        $appname = conf( 'appname',$arr[0] );
        
        $class_path = DOCUROOT . "/{$appname}/Lib/{$class_name}.php";
        if( file_exists( $class_path ) ){
            require_once  $class_path;
            return true;
        }
    }
    
    //针对命名空间的调用方式
    if(strstr($class_name, "\\")){
        $parts = explode("\\", $class_name);
        $all = count($parts);
        
        $path = [];
        if(in_array($parts[0],array('admin','space','service','office'))){
            for($i=2;$i<$all;$i++)$path[]=$parts[$i];
            $class_path = "/{$parts[0]}/{$parts[1]}/Lib/".implode("/", $path).'.php';
        }else{
            for($i=1;$i<$all;$i++)$path[]=$parts[$i];
            $class_path = "/{$parts[0]}/Lib/".implode("/", $path).'.php';
        }
        $class_path = DOCUROOT.$class_path;
        
        if( file_exists( $class_path ) ){
            if(!class_exists($class_name)) require_once $class_path;
            return true;
        }
    }
    
    return false;
}

//兼容composer, 启动框架自动加载函数
spl_autoload_register("autoload");

/**
 * 取得数据库联接对象
 * @return object $db
 */
function func_getDB($dbconfig="master", $param="MySQL", $config=null){
    // 数据库连接池
    static $pool = array();
    
    // 调试状态时返回全部连接池内的对象
    if(!empty($config['debug']))return $pool;
    
    // 大量操作时为了避免内存溢出，清除连接池缓存
    if( !empty($config['clean']) && !empty($pool[$dbconfig]) ) $pool[$dbconfig]=null;
    
    // 检测是否有可用的连接
    $conn = empty($pool[$dbconfig])?false:($pool[$dbconfig]);
    
    if($param=="MySQL"){
        if( empty($conn->conn)||!mysqli_ping($conn->conn)){
            $db = new MySQL();
            if(empty($config['mustMasterConn'])) $mysqlMasterSlave=conf('db','mysqlMasterSlave');
            if(!empty($mysqlMasterSlave)) $db->mysqlMasterSlave = true;
            
            //加载数据库配置信息
            $db->config = $db->mysqlMasterSlave ? array('master'=>func_getDbSetting($dbconfig,'master'),'slave'=>func_getDbSetting($dbconfig,'slave')):func_getDbSetting($dbconfig,'master');
            
            //转载多表配置信息
            $db->config['multiTb'] = func_getDbSetting($dbconfig,'multiTb');
            
            //立即建立连接，避免缓存失效及数据链接重置
            $db->init();
            
            $pool[$dbconfig] = $db;
        }
    }
    
    if($param=="Mongo"){
        if( empty($conn->conn)){
            $db = new MongoD();
            $db->config = func_getDbSetting($dbconfig);
            $db->init();
            
            $pool[$dbconfig] = $db;
        }
    }
    
    if ($param=="MsSQL") {
    	if (empty($conn->conn)) {
    		$db = new MsSQL();
    		if (empty($config['mustMasterConn'])) {
    			$mssqlMasterSlave=conf('db', 'mysqlMasterSlave');
    		}
    		if (!empty($mssqlMasterSlave)) {
    			$db->mssqlMasterSlave = true;
    		}
    
    		//加载数据库配置信息
    		$db->config = $db->mssqlMasterSlave ? array('master'=>func_getDbSetting($dbconfig, 'master'),'slave'=>func_getDbSetting($dbconfig, 'slave')):func_getDbSetting($dbconfig, 'master');
    
    		//转载多表配置信息
    		$db->config['multiTb'] = func_getDbSetting($dbconfig, 'multiTb');
    		$db->init();
    
    		$pool[$dbconfig] = $db;
    	}
    }
    
    return $pool[$dbconfig];
}

/**
 * 获取数据库信息
 *
 */
function func_getDbSetting($dbconfig,$type='master'){
    // 完整的数据库配置信息数组
    static $CONFIG;
    
    if(empty($CONFIG)){
        //加载配置
        $temp=conf('db');
        
        //初始化数据配置变量
        $CONFIG=array();
        $CONFIG['system']=array('prefix'=>$temp['prefix']);
        
        foreach($temp['database'] as $key=>$val){
            //数据库名
            $dbname=empty($val['db'])?'_'.$key:$val['db'];
            $dbname=(substr($dbname,0,1)=='_')?$temp['prefix'].$dbname:$dbname;
            
            //是否启用分表
            $multiTb = empty($val['multiTb'])?0:1;
            
            //主库信息
            $master=array();
            if(!empty($temp['server'][$val['master']])){
                $master=$temp['server'][$val['master']];
                $master['database']=$dbname;
            }
            //生成只读库信息
            $slave=array();
            if( !empty($val['slave']) && $temp['mysqlMasterSlave'] ){
                foreach($val['slave'] as $v){
                    if(!empty($temp['server'][$v])){
                        $slavetemp=$temp['server'][$v];
                        $slavetemp['database']=$dbname;
                        $slave[]=$slavetemp;
                    }
                }
            }
            $CONFIG[$key]=array( 'master'=>$master, 'slave'=>$slave, 'multiTb'=>$multiTb );
        }
    }
    
    // 处理由请求标识名和主库信息自动生成的数据链接
    if(empty($CONFIG[$dbconfig])){
        $tmp=$CONFIG['main'];
        $tmp['master']['database']=$CONFIG['system']['prefix'].'_'.$dbconfig;
        if(!empty($tmp['slave'])){
            foreach($tmp['slave'] as $k=>$v){
                $v['database']=$tmp['master']['database'];
                $tmp['slave'][$k]=$v;
            }
        }
        $CONFIG[$dbconfig]=$tmp;
    }
    
    //debug::d($CONFIG);exit;
    
    // 得到当前请求的数据链接内容
    $config=$CONFIG[$dbconfig];
    
    //返回多表配置参数
    if( $type=='multiTb' ) return $config['multiTb'];
    
    //根据type类型返回master或随机的slave
    if($type=='master'){
        return $config['master'];
    }else{
        if(empty($config['slave'])) return $config['master'];
        return func_getRandArr($config['slave']);
    }
}

//返回当前服务的全部有效节点
function func_getNodes(){
    static $webnodes;
    if(empty($webnodes)){
        global $_GlobalConfig;
        $webnodes = include DOCUROOT."/inc.webnodes.php";
    }
    
    return $webnodes;
}

/**
 * ## 对多表操作要小心 ##
 * 根据数据库配置信息，获得指定数据库下的全部表
 *
 * @param string $dbconfig
 */
function func_getAllTbList($dbconfig){
    $conf = func_getDbSetting($dbconfig);
    
    $conn = mysqli_connect($conf['server'], $conf['user'], $conf['password'], $conf['database']);
    $result = mysqli_query($conn,"SHOW TABLES");
    
    $list = array();
    if(!empty($result)){
        while ( $row = mysqli_fetch_array($result) ) $list[] = $row[0];
        mysqli_free_result($result);
    }
    return $list;
}

/**
 * 获得smarty对象
 * @return smarty templates object
 */
function func_getSmarty($appName,$centreModel=false){
    $smarty = new Smarty;//此处的Smarty是由composer自动加载的
    
    if(empty($smarty)){
        if(ERRORDEBUG){
            func_throwException(lang("default","initSmartyFaild"));
        }
        return false;
    }
    $cacheDir = DOCUROOT.'/cache/smarty';
    $compile_deep=defined('COMPILEDIR')?COMPILEDIR:'';
    
    $smarty->setTemplateDir( $centreModel?DOCUROOT.'/template/'.$appName.'/':DOCUROOT.'/'.$appName.'/Tpl/' )
    ->addPluginsDir(DOCUROOT.'/include/smarty/plugins')
    ->setCacheDir( $cacheDir.'/cache/'.$appName.'/' )
    ->setConfigDir( $cacheDir.'/configs/'.$appName.'/' )
    ->setCompileDir( $cacheDir.'/compile/'.$appName.'/'.$compile_deep );
    
    $workingDir = array(
        $cacheDir.'/cache/'.$appName.'/',
        $cacheDir.'/configs/'.$appName.'/',
        $cacheDir.'/compile/'.$appName.'/'.$compile_deep
    );
    
    foreach( $workingDir as $key=>$value )
        if( !is_dir( $value )) files::mkdirs( $value, 0777 );
        
        $smarty->left_delimiter = "{%";
        $smarty->right_delimiter = "%}";
        $smarty->compile_check = true;
        $smarty->caching = false;
        $smarty->cache_lifetime = 7200;
        $smarty->debugging = false;
        return $smarty;
}

/**
 * 异常处理
 *
 * @param string $msg 错误信息
 * @param int $code
 * @return void
 */
function func_throwException($msg,$code=0){
    static $status;
    if(empty($status)){
        $status=true;
        set_exception_handler( array("Exceptions","appException"));
    }
    throw new Exceptions($msg,$code,false);
}

/**
 * 如果用户是通过 *.eefocus.com 之类的域名访问的社区
 * 此函数会设置cookie_domain 的作用域，用于 session 同步,完成初始化工作
 */
function func_initSession(){
    if(!isset($_SESSION)){
        $global=conf();
        
        //用于本地测试
        if(!empty($global['system']['serverid'])){
            if($global['system']['serverid']=='svn'){
                ini_set('session.cookie_domain', null);
                ini_set('session.cookie_path', '/');
                @session_start();
                return;
            }
            
        }
        
        //生产环境
        $sess=conf("global","session");
        $domain=empty($sess["sessiondomain"])?null:$sess["sessiondomain"];
        $path=empty($sess["sessionpath"])?null:$sess["sessionpath"];
        
        if(!empty($domain)){
            if( is_numeric($domain) ){//根据访问域名设置动态的session域
                $domainstr=substr($_SERVER['HTTP_HOST'],strpos($_SERVER['HTTP_HOST'],"."));//目标域名字符串
                ini_set('session.cookie_domain', $domainstr);
            }else{
                ini_set('session.cookie_domain', $domain);
            }
        }
        
        if(!empty($sess["sessionpath"])){
            ini_set('session.cookie_path', $path);
        }
        
        if(!empty($sess['type'])){
            if($sess['type']=='files'){
                if(!empty($sess['savepath'])) ini_set("session.save_path",$sess['savepath']);
            }
        }
        
        @session_start();
    }
}

/**
 * 初始化地理信息
 * $geoinfo = Array
 (
 [continent_code] => NA
 [country_code] => US
 [country_code3] => USA
 [country_name] => United States
 [region] => CA
 [city] => San Jose
 [postal_code] => 95132
 [latitude] => 37.429901123047
 [longitude] => -121.77829742432
 [dma_code] => 807
 [area_code] => 408
 )
 */
function func_initGeoInfo($ipaddress=null){
    //对于指定IP的情况, 不使用缓存直接返回
    if(!empty($ipaddress)) return @geoip_record_by_name( $ipaddress );
    
    static $geoinfo;
    if(empty($geoinfo)){
        //TODO 性能优化
        $ip = empty($_GET['ip'])?http::getIP():$_GET['ip'];
        $geoinfo = @geoip_record_by_name($ip);
        $geoinfo['ip'] = $ip;
        if(empty($geoinfo)) $geoinfo = array('area'=>'bayarea');//默认地区为旧金山湾区
    }
    
    return $geoinfo;
}

/**
 * 初始化Memcache服务器
 * @param $host
 * @param $port
 * @return resource
 */
function func_initMemcached($host='127.0.0.1',$port='11211'){
    static $cache;
    if(empty($port)) $port='11211';
    
    $id=$host.'_'.$port;
    if(!isset($cache[$id]))	{
        
        $obj=@memcache_connect($host, $port);
        
        if(empty($obj)) return false;
        
        $cache[$id]=$obj;
    }
    return $cache[$id];
}

/**
 * 初始化Redis服务器
 * https://github.com/phpredis/phpredis#get
 *
 * @param $host
 * @param $port
 * @return resource
 */
function func_initRedis($host='127.0.0.1',$port='6379'){
    static $cache;
    if(empty($port)) $port='6379';
    
    $id=$host.'_'.$port;
    if(!isset($cache[$id]))	{
        if(!class_exists("redis")) return false;
        
        $redis = new redis();
        $redis->connect($host, $port);
        if(empty($redis)) return false;
        
        $cache[$id]=$redis;
    }
    return $cache[$id];
}

/**
 * 初始化数值缓存类
 * @param $filename 缓存类型
 * @param $cacheID 主键
 * @return obj
 */
function func_initValueCache($filename=null,$cacheID=null,$config=null){
    static $obj;
    if(empty($filename) && empty($cacheID)) return null;
    
    if(!isset($obj[$cacheID])) {
        
        $VC = new valueCache($config);
        $VC->filename=$filename;
        $VC->cacheID=$cacheID;
        
        $obj[$cacheID]=$VC;
    }
    
    return $obj[$cacheID];
}


use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * 通用邮件发送函数
 * i.g.
 
 ### 使用sparkpost.com或其它第三方的邮件服务
 $config=array(
 //统计标识
 'headers'=>array('X-MSYS-API'=>'{"campaign_id" : "planmycollege_notification"}'),
 
 //服务器信息
 'host'=>'smtp.sparkpostmail.com',
 'Secure'=>'tls',
 'port'=> 2525,
 
 //验证信息
 'user'=>'SMTP_Injection',
 'pass'=>'sparkpost pass code',
 
 //显示信息
 'fromname'=>'管理员',
 'fromuser'=>'notification@planmycollege.com',
 
 //调试
 'debug'=>true
 );
 
 ### 使用标准邮件服务器
 $config=array(
 //邮件服务器信息
 'host'=>'mail.domain.com',
 'secure'=>'ssl',
 'port'=>465,
 
 //验证信息
 'user'=>'weiqi@domain.com',
 'pass'=>'123456',
 
 //用户看到的发件人信息
 'fromname'=>'weiqi',//发件人名称
 'fromuser'=>'admin@domain.com',//发件人邮箱
 
 //调试
 'debug'=>true,
 );
 
 $status=func_sendMail(
 '系统提示邮件',
 '<h1>Hello World! 这是一个测试邮件</h1><b><a href="http://www.google.com">访问网站</a>content</b>',
 'nothing',
 'someuser@somedomain.com',
 "the user's name",
 $config
 );
 
 ($status);
 
 *
 * @param string $title    邮件标题
 * @param string $html     Html信体
 * @param string $text     纯文本信体
 * @param string $email    收件人地址
 * @param string $user     收件人称谓
 * @param array  $config   邮件发送参数
 * @param array  $attachments   邮件附件
 * 		$attachments = [
 * 			[ 'filename'=>'new.jpg', 'filepath'=>'/tmp/image.jpg' ],
 * 			[ 'filepath'=>'/var/tmp/file.tar.gz' ]
 * 		];
 *
 * @return boolean         返回邮件发送情况
 */
function func_sendMail($title,$html,$text,$email,$user,$config=array(),$attachments=array()){
    //下面是几个不常用到的变量
    $charset=empty($config["charset"])?'UTF-8':$config["charset"];
    $encode=empty($config["encoding"])?'base64':$config["encoding"];
    $debug=empty($config["debug"])?false:$config["debug"];
    
    //初始化邮件类
    $mail = new PHPMailer();
    
    try {
        $mail->SMTPDebug   = $debug;               // 调试用的开关
        $mail->IsSMTP();                           // send via SMTP
        $mail->Host        = $config["host"];      // SMTP servers
        $mail->Port        = empty($config["port"])?80:$config["port"];
        $mail->SMTPAuth    = true;                 // turn on SMTP authentication 开启验证
        $mail->Username    = $config["user"];      // SMTP username  注意：普通邮件认证不需要加 @域名
        $mail->Password    = $config["pass"];      // SMTP password
        $mail->CharSet     = $charset;             // 这里指定字符集！
        $mail->Encoding    = $encode;              // 编码方式
        
        $mailFromAddr = empty($config["fromuser"])?$config["user"]:$config["fromuser"];      // 发件人邮箱
        $mailFromName = empty($config["fromname"])?$config["name"]:$config["fromname"];      // 发件人
        
        $mail->setFrom( $mailFromAddr, $mailFromName );
        $mail->AddReplyTo( $mailFromAddr, $mailFromName );
        
        //设置协议和端口
        if(!empty($config["Secure"])) $mail->SMTPSecure = $config["Secure"];
        if(!empty($config["port"])) $mail->Port = $config["port"];
        
        //Gmail设置
        if (!empty($config["gmail"])){
            $mail->SMTPSecure = empty($config["Secure"])?'ssl':$config["Secure"];
            $mail->Port       = empty($config["port"])?465:$config["port"];
        }
        
        //设置自定义头信息
        if(!empty($config["headers"]))
            foreach($config["headers"] as $k=>$v)
                $mail->addCustomHeader($k, $v);
                
                $mail->Subject = $title;
                if(!empty($config["istext"])){
                    $mail->IsHTML(false);
                    $mail->Body = $text;
                }else{
                    $mail->IsHTML(true);
                    $mail->AltBody = $text;  //不支持html格式时，显示的文本
                    $mail->Body = $html;//html信体
                }
                
                //处理附件
                if(!empty($attachments)){
                    foreach($attachments as $attachment){
                        if(!isset($attachment['filepath'])) continue;
                        if(!is_file($attachment['filepath'])) continue;
                        
                        if(isset($attachment['filename'])){
                            $mail->addAttachment($attachment['filepath'], $attachment['filename']);
                        }else{
                            $mail->addAttachment($attachment['filepath']);
                        }
                    }
                }
                
                //收件人
                $mail->AddAddress($email,$user);
                return $mail->Send();
                
    } catch (Exception $e) {
        $msg = 'Message could not be sent.';
        $msg .= 'Mailer Error: ' . $mail->ErrorInfo;
        func_throwException($msg);
    }
}

/**
 * 防止非法用户大量抓取网站内容,超过指定访问数量后，会自动封锁该用户IP24小时
 *
 * if( func_checkUserIP(5000,'192.168.110.11','11211') ){ echo "" ;exit();}
 *
 * @param 最大访问数  int $maxnum
 * @param memcache主机  string $host
 * @param memcache端口  string $port
 * @param 计数时间区间  int $expire
 */
function func_checkUserIP($maxnum=500,$host='127.0.0.1',$port=11211, $expire=86400){
    //命令行模式下退出检测
    global $argv;
    if(!empty($argv[0])) return false;
    
    //排序apache内部访问
    if(empty($_SERVER['REMOTE_ADDR'])) return false;
    
    $ip = http::getIP();
    if(empty($ip)) return false;
    
    //本地访问
    if(substr($ip,0,7)=='192.168') return false;
    if(substr($ip,0,3)=='10.') return false;
    
    $memcache = func_initMemcached($host, $port);
    $count = $memcache->increment($ip, 1);//使用原子加法对访问进行计数
    
    if(empty($count)){
        //第一次访问的IP
        $memcache->add($ip, 1, false, $expire);
    }
    
    if ($count > $maxnum){
        //仅记录一次所有超过访问上限的IP
        if($count==$maxnum+1){
            $obj=load('site_ipcount');
            $obj->Insert(array('ip'=>$ip,'browser'=>$_SERVER['HTTP_USER_AGENT'],'datetime'=>time()));
        }
        
        $whiteIP='white_'.$ip;
        $blackIP='black_'.$ip;
        
        $spider=$memcache->get($whiteIP);
        if(!empty($spider)) return false;//允许合法的搜索引擎收录
        
        $hacker=$memcache->get($blackIP);
        if(!empty($hacker)) return true;//拒绝非法的抓站程序
        
        //判断IP是否合法
        $spiderlist=include DOCUROOT.'/admin/site/Config/spiderDomain.php';
        $spiderIPlist=@include DOCUROOT.'/admin/site/Config/spiderIP.php';
        $ptr=http::getPTR($ip);
        
        if(in_array($ptr,$spiderlist)||in_array($ip,$spiderIPlist)){
            //被允许的搜索引擎
            $memcache->add($whiteIP, 1, false, 0);
            return false;
        }else{
            $memcache->add($blackIP, 1, false, 0);
            //非法用户
            return true;
        }
    }
    
    return false;
}


/**
 * 在多主机的环境下, 删除memcache
 */
function func_delCache($cacheID){
    $cachePools = array();
    
    //写入多点缓存
    if(defined('PRODUCTION')){
        if(PRODUCTION!='0'){
            $configstr = conf('global','server.worknode');
            if(empty($configstr)) {
                $cachePools = array("cache01");
            }else{
                $cachePools = explode(",",$configstr);
            }
        }
    }
    
    //测试环境单点
    if(empty($cachePools))$cachePools = array("cache01");
    
    foreach($cachePools as $host)
        if( $obj=func_initMemcached($host) ) $obj->delete($cacheID);
        
}

//检查脚本环境
function func_checkCliEnv(){
    global $argv;
    if(empty($argv[0])) exit("This script is running under CLI environment only!");
    
    //定义clie环境变量
    if(!defined( 'CliEenvironment' )) define( 'CliEenvironment', true);
}

/**
 * 用于设置用户指定范围内的积分
 *
 * @param int $uid  用户id
 * @param int $rid  当前项目的关联id，如在bbs中发贴产生积分，则此id为刚发的帖子id
 * @param int $score 为0时积分数值取决于管理员设定，其他分值时采用用户定义
 * @param string $item  所属项目，如bbs
 * @param string $act 何种行为的积分，如 addpost
 * @param string $pos 积分所属的范围，如飞思卡尔社区内则为 freescale
 * @return bool
 *
 * 示例：设置某网站内333这个用户发贴产生的积分 func_setScore(333,12,0,'bbs','addpost','freescale');
 */
function func_setScore($uid,$rid,$score=0,$item='all',$act='all',$pos='default',$log=false){
    $obj=load("score_score");
    $status=$obj->setScore($uid,$rid,$score,$item,$act,$pos,$log);
    return $status;
}

/**
 * 获取随机概率的数据
 * @param array $arr
 * @return array
 */
function func_getRandArr($arr){
    $i=0;
    $pool=array();
    $str='';
    foreach($arr as $v){
        $letter=chr($i+65);
        $pool[$letter]=$v;
        $str.=$letter;
        $i++;
    }
    $pos = strings::getRandom(1,'user',$str);
    return $pool[$pos];
}

/**
 * 用于获取用户指定范围内的积分
 *
 * @param int $uid  用户id
 * @param string $item  所属项目，如bbs
 * @param string $act 何种行为的积分，如 addpost
 * @param string $pos 积分所属的范围，如飞思卡尔社区内则为 freescale
 * @return int score
 *
 * 示例：某网站内333这个用户所有发贴产生的积分 $n=func_getScore(333,'bbs','addpost','freescale');
 */
function func_getScore($uid, $item='all',$act='all',$pos='default'){
    $obj=load("score_score");
    $score=$obj->getScore($uid, $item,$act,$pos);
    return $score;
}

/**
 * 用于添加用户在站点内产生的行为
 *
 * @param int $uid  用户id
 * @param string $itemid  所属项目id
 * @param string $type 隶属于什么项目,bbs,blog,comment...
 * @param string $act 何种行为如 addpost,addblog,updateblog....
 * @return boolean  是否成功
 *
 * 示例：某网站内用户发贴产生的行为 $status=func_addEvent( $_SESSION['UerID'],1568,'bbs','addpost' );
 */
function func_addEvent( $uid, $itemid, $type, $act='all' ){
    $obj=load("friends_event");
    return $obj->addEvent( $uid, $itemid, $type, $act );
}

/**
 * 获取全局唯一ID，用于数据记录ID
 */
function func_getUUID(){
    $salt = mt_rand(0, 65535);
    $serverID = defined("SERVERID") ? SERVERID : mt_rand(0, 65535);
    $AppName = defined("AppName") ? SERVERID : mt_rand(0, 65535);
    $timestamp = microtime();
    
    return md5( $salt.$serverID.$AppName.$timestamp );
}

/**
 * 获取 MongoDB 的ID对象
 */
function func_getMongoID($id=null){
    if(empty($id)) return new MongoId();
    if(is_object($id)||is_array($id)) return $id;
    if(!is_string($id)) {alert(404);}
    
    $id = trim($id);
    if(strlen($id)!=24) $id = substr( md5($id),4,24);
    
    $mongoID = new MongoId($id);
    return $mongoID;
}

/**
 * 输出基本授权信息
 *
 * @param string $user
 * @param string $pass
 */
function func_basicAuthz($user,$pass){
    if (!isset($_SERVER['PHP_AUTH_USER']) || $_SERVER['PHP_AUTH_USER'] != $user || $_SERVER['PHP_AUTH_PW'] != $pass) {
        header('WWW-Authenticate: Basic realm="User-Authenticate"');
        header('HTTP/1.0 401 Unauthorized');
        return false;
    }
    
    return true;
}

//检查管理环境
function func_checkAdmin(){
    return func_checkAuth('superadmin');
}

/**
 * 对没有使用框架运行的程序，按框架结构检测授权信息(如Ajax脚本)
 *
 * @param 要检查的目标模块 $mod
 * @param 要求的授权类型 $type
 * @return string 授权信息
 */
function func_checkAuth($mod, $type = "admin"){
    func_initSession();
    $passport=load('passport_passport');
    $val=$passport->checkAuth($mod, $type);
    return $val;
}

/**
 * 追加日志记录
 *
 * @param string $msg
 * @param string $type
 * @param string $level
 */
function func_logs($msg, $level=1){
    static $obj;
    if ( empty($obj) ) {
        $CONFIG=conf("global","system");
        $obj = new log4p();
        $obj->file=empty($CONFIG["log"]["path"])?DOCUROOT."/cache/error.log":$CONFIG["log"]["path"];
        $obj->type=empty($CONFIG["log"]["type"])?"file":$CONFIG["log"]["type"];
        
    }
    return $obj->appendLog($msg, $level);
}

/**
 * 递归读取数组
 *
 * @param array $key
 * @param array $value
 * @return string
 */
function func_getKey($key,$value,$n=0){
    if( empty($value) ) return;
    if( !is_array($value) ){return $value;}//没有下级数据,直接返回当前值
    if( empty($key[$n]) ) return $value;//取到指定的级，返回余下的数组
    
    $v=empty($value[$key[$n]])?null:$value[$key[$n]];
    if(empty($v)){
        return;//不存在的键值,返回null
    }else{
        $n++;
        return func_getKey($key,$v,$n);
    }
}

/**
 * 获取系统运行参数,并根据情况建立或调用缓存数据
 *
 * @param string $key
 * @param string $pointer
 * @return string
 */
function conf($pointer="global",$key=null,$clear=false){
    static $cache;
    
    //强制更新缓存
    if($clear) $cache[$pointer] = null;
    
    if(empty($cache[$pointer])){
        
        switch($pointer){
            
            /**
             * 返回数据库配置信息
             */
            case "db":
                $cache[$pointer]=include DBSETTING;
                break;
                
                /**
                 * 根据指定的key返回应用程序名
                 * conf('appname','analytics');
                 */
            case "appname":
                $obj=new System();
                
                $cache[$pointer]=$obj->getAppNameConfig();
                break;
                
                /**
                 * 框架中定义的有子项目的目录名
                 */
            case "system":
                global $_GlobalSystem;
                $pools = isset($_GlobalSystem)?$_GlobalSystem:array('admin','space','service','office');
                return $pools;
                break;
                
                /**
                 * 根据当前选定的sess域读取指定站点的全局配置
                 * conf('sess','global');
                 */
            case "sess":
                if(defined('GLOBALCONF')){
                    $cache[$pointer]=conf("global","session");
                }else{
                    $obj=load("site_configure");
                    $cache[$pointer]=$obj->getConfig($_SESSION['SiteDomain'],$clear);
                }
                break;
                
                /**
                 * 根据访问域名读取当前站点的全局配置
                 * conf('global','uid');
                 */
            case "global":
                if(defined('GLOBALCONF')){
                    $cache[$pointer]=include GLOBALCONF;
                }else{
                    $obj=load("site_configure");
                    $cache[$pointer]=$obj->getConfig(null,$clear);
                }
                break;
                
                /**
                 * 读取指定路径中的配置,
                 * conf('label.document','develop.php.framework');//系统通用标签
                 * conf('admin.document.lang.cn','document.title');//项目配置文件
                 */
            default:
                $path=explode('.',$pointer);
                
                if($path[0]=='label'){//调用全局标签
                    $cache[$pointer]=label($path[1],0);
                    
                }else{//调用项目目录下的配置文件
                    global $_GlobalSystem;
                    $pools = isset($_GlobalSystem)?$_GlobalSystem:array('admin','space','service','office','account','plugins');
                    
                    $cfgpath=array();
                    if(in_array($path[0],$pools)){
                        $pkg=$path[0].'/'.$path[1];
                        for($i=2;$i<count($path);$i++){
                            if(!empty($path[$i])) $cfgpath[]=$path[$i];
                        }
                    }else{
                        $pkg=$path[0];
                        for($i=1;$i<count($path);$i++){
                            if(!empty($path[$i])) $cfgpath[]=$path[$i];
                        }
                    }
                    $configFile=DOCUROOT.'/'.$pkg.'/Config/'.implode('/',$cfgpath).'.php';
                    if(!is_file($configFile)){
                        func_throwException("Can't Open '".$configFile."' .... ");
                        return;
                    }else{
                        $cache[$pointer]=include_once $configFile;
                    }
                }
                
                break;
        }
        
    }
    //重置针对标签调用时使用的key
    if(substr($pointer,0,5)=='label'){
        // 对于全局标签来说每一子级都是在sublist下面的,此处对录入的key重新设置
        $key=str_replace('.','.sublist.',$key);
    }
    
    if(!empty($key)){
        $key=explode('.',$key);
        $val=func_getKey($key,$cache[$pointer]);
    }else{
        $val=$cache[$pointer];
    }
    return $val;
}

/**
 * 系统计数函数
 * 利用memcache的高效访问性能实现实时统计计数，并大幅减少对数据库的修改操作
 *
 * @param string $type
 * @param int $id
 * @param int $default
 * @return int num
 */
function setNum($type,$id,$default){
    $conf = conf('global','memcached');
    $memcacheID =  $type.'_'.$id;
    
    $memcache = func_initMemcached($conf['count_host'], $conf['count_port']);
    $count = $memcache->increment($memcacheID, 1);//使用原子加法对访问进行计数
    
    if(empty($count)){
        //第一次对对象的访问
        $count = $default+1;
        $memcache->add($memcacheID, $count, false, 172800); //缓存时间48小时
        
        //记录到数据库，用于24小时后集中做更新处理
        $obj=load('site_count');
        $obj->init($type,$id);
    }
    
    return $count;
}

function getNum($type,$id,$default){
    $conf = conf('global','memcached');
    $memcacheID =  $type.'_'.$id;
    
    $memcache = func_initMemcached($conf['count_host'], $conf['count_port']);
    
    $count = $memcache->get( $memcacheID ) ;
    $count = empty($count)? $default : $count ;
    
    return $count;
}

/**
 * 语言包调用函数
 *
 * 示例: $this->assign("lang", lang('admin.document'));
 *
 * @param string $key //键值
 */
function lang($key,$item=null){
    if(empty($key)) return;
    
    $language = conf("global",'system.language');
    $result = conf("{$key}.lang.{$language}",$item);
    
    return $result;
}

/**
 * 记录系统日志，用于跟踪错误及定位问题
 * DEMO //func_addlog("login",'userid_123',array('username'=>'张三','test'=>true));
 *
 * @param unknown $item
 * @param unknown $keyid
 * @param array $extraData
 */
function func_addlog($item, $keyid, $extraData=array() ){
    $data = array(
        'item'=>$item,
        'keyID'=>$keyid,
        'datetime'=>time(),
        'logData'=>serialize(
            array(
                'get'=>empty($_GET)?null:$_GET,
                'post'=>empty($_POST)?null:$_POST,
                'session'=>empty($_SESSION)?null:$_SESSION,
                'cookie'=>empty($_COOKIE)?null:$_COOKIE,
                'server'=>$_SERVER,
                'extra'=>$extraData
            )
            ),
    );
    $logObj = load("site_logs");
    $rs = $logObj->getOne("*",array('keyID'=>$keyid));
    if(empty($rs)){
        @$logObj->Insert($data);
    }else{
        @$logObj->Update($data,array('keyID'=>$keyid));
    }
}



/**
 * 加载Model类库
 *
 * @param string $model    要加载的model
 * @param array $config    Model加载参数,其中appname为应用程序地址，forceLoad为不使用缓存强制加载，其余为目标类的参数
 * @param string $parent   加载起始目录
 *
 * @return object
 */
function load($model,$config=''){
    //用于缓存当前页面全部初始化过的对象
    static $ModelCache=array();
    
    if(is_array($config)){
        //应用程序目录
        $appname=empty($config['appname'])?'':$config['appname'];
        
        //处理类初始化变量
        if(isset($config['appname'])) unset($config['appname']);
    }else{
        //对于字符串参数直接设置$appname,不设置$config;
        $appname=$config;
        $config=null;
    }
    
    //缓存ID
    $CacheID=$model.$appname;
    $forceLoad=empty($config['forceLoad'])?false:true;
    
    if( empty($ModelCache[$CacheID]) || $forceLoad ){
        //判断model 路径
        if(strstr($model,"/")){
            $modelName = strtolower(basename($model));
        }else{
            $modelName = strtolower($model);
        }
        //根据model名自动提取appname
        if( strstr($modelName,"_") && empty($appname) ){
            $a=explode("_",$modelName);
            if(!empty($a[0])) $appname = conf( 'appname',$a[0] );
        }
        //如果没有设置也没有提取到appname,使用应用程序默认的AppName;
        if(empty($appname)) $appname = defined('AppName')? AppName : '';
        
        $modelfile=DOCUROOT."/".$appname."/Lib/".$model.".php";
        //debug信息
        if(!empty($_GET['debug'])){
            if($_GET['debug']=='showLoadInfo') {
                $classpath=str_replace(DOCUROOT,'',$modelfile);
                echo "<h2><strong>{$CacheID}</strong>: {$classpath}</h2><br\>";
            }
        }
        
        if(file_exists($modelfile)){
            if(!class_exists($modelName))include( $modelfile );
            
            if(empty($config)){
                $obj=new $modelName();
            }else{
                $obj=new $modelName($config);
            }
            
            if(empty($obj)){
                func_throwException(lang("default","autofailed"));
                return false;
            }
            $ModelCache[$CacheID]=$obj;//缓存对象
        }else{
            return false;
        }
    }else{
        $obj=$ModelCache[$CacheID];
    }
    return $obj;
}

/**
 * 标签读取函数,默认调用当前站点的标签
 * @param string $key  点号分隔参数
 * @param int $siteid  站点ID
 */
function label($key=null,$siteid='N/A'){
    $siteid=is_numeric($siteid)?$siteid:conf('global','lid');
    
    $obj=load('label_common');
    $result = $obj->getLabel($key,$siteid);
    
    return $result;
}

/**
 * 显示错误信息
 *
 * 调用系统内置提示DEMO:
 * alert('404');
 *
 * 调用自定义的提示DEMO:
 * $val=array(
 * 	  'title'=>'sth',
 * 	  'content'=>'sth',
 * 	  'tpl'=>'/somewhere/Tpl/somefile',
 * );
 * alert($val);
 *
 * @param string $n 错误编码或自定义的错误内容
 * @return void
 */
function alert($val){
    //对于数字类的提示信息，先尝试调用默认模板
    if(intval($val)>0){
        $sitetpl = conf('global','tpl');
        $tplfile = DOCUROOT . "/template/{$sitetpl}/{$val}.html";
        if(is_file($tplfile)){
            include $tplfile;
            exit;
        }
    }
    
    $tpl=func_getSmarty("message");
    if(!is_array($val)){
        //读取配置信息
        $messInfo=include( DOCUROOT."/include/config/message.php" );
        $info=empty($messInfo[$val])?$messInfo["errorID"]:$messInfo[$val];
        
        if(!empty($info['tpl'])){
            //调用站点错误页面
            if($info['tpl']=='site'){
                $info['tpl'] = "/template/".conf('global','tpl').'/msg/'.$val.'.html';
                
                //如果需要，可以加载动态信息到错误页面
                $filename = DOCUROOT . "/plugins/".conf('global','tpl')."/error_{$val}.php";
                if( file_exists($filename) ) include $filename;
            }
        }
    }else{
        //使用自定义错误页面,array('title'=>'...','content'=>'...','tpl'=>'...')
        $info=$val;
    }
    
    //加载站点配置文件
    $config = conf();
    $filter=array('uid','tpl','nav','link','global');
    $result=array();
    foreach($config as $k=>$v){
        if(in_array($k,$filter)) $result[$k]=$v;
    }
    $tpl->assign( "site",$result );
    
    //加载站点根目录
    $tpl->assign("root",DOCUROOT);
    
    //加载模板语言包文件
    if(defined( 'LANGUAGE')) $tpl->assign("lang",lang("admin.dashboard"));
    
    //输出变量
    $tpl->assign("rs",$info);
    
    //获取模板
    $tplpath = empty($info["tpl"]) ? DOCUROOT.'/include/template/message.html' : DOCUROOT . $info["tpl"];
    if( !is_file($tplpath) ) $tplpath = DOCUROOT.'/include/template/message.html';
    
    $tpl->display($tplpath);
    exit;
}

/**
 * 设置地址转向
 * @param string $url
 */
function go($url){
    header("location:".$url);
    exit;
}

?>