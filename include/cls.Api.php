<?php
/**
 * API文档格式说明，参考以下 ===》后的说明
 * 自动生成文档的注释语法目前支持：access，param，response，post，formats, return
 *
 class sampleApiClass extends Api{
 
 * 这是一个测试
 * hay, how are you?  ===》无命名的多行注释对应函数的description
 *
 * @access PUT ===》access对应函数访问方式, 未声明的默认为GET
 *
 * @param string $name ===》param对应字段格式  "类型|名称|注释"
 * @param unknown $age
 * @param string $content | 具体的说明内容
 *
 * @response 请求结果参见  ===》response对应"Response Information" 支持多条
 * @response /data/api/sample/category.txt ===》response支持文本文件引用，不支持html标签
 *
 * @post age,content ===》post对应哪些函数参数是由post传递的，未声明的默认为url传递
 *
 * @formats /sample/Config/api/category-test.json ===》formats的json数据对应 "Response Formats"，必须是数组形式，每个记录有name,type,description(非必须)三个属性字段
 * @formats /sample/Config/api/category-test.json ===》formats支持多种返回结果说明，对于有formats定义的字段会覆盖return中的自动生成的说明信息
 *
 * 数据文件 /sample/Config/api/category-test.json 中json结构示例
 [
 {"name":"title","type":"string","description":"标题"},
 {"name":"datetime","type":"int","description":"时间戳"},
 {"name":"text","type":"string","description":"内容"}
 ...
 ]
 *
 * @return /sample/Config/api/category-list.json ===》return定义返回结果的详细字段，自动生成"Response Formats"中的说明
 * @return /sample/Config/api/category-list.json ===》return支持多种返回结果说明
 *
 function sampleFunction($name,$age=array(),$content=''){   ===》只有public函数会自动生成API文档
 $result=[];
 return $result;
 }
 
 //更多接口函数
 ....
 
 }
 
 ******************************
 *            DEMO            *
 ******************************
 示例1：
 ==========================================================================================
 $.ajax({
 type: "GET",
 url: "/api.php",
 data: "app=profile&class=ajax&func=saveAvatar&param1="+ val0+"&param2="+ val1+"&param3="+ val2+"&param4="+ val3,
 success:function( json ){object.functionX(json); }
 });
 
 示例2：
 ==========================================================================================
 var formData = new FormData();
 for ( var key in itemlist.data) formData.append(key, itemlist.data[key]);
 
 $.ajax({
 type: "POST",
 url: "/api/v1/sample/test/testfunc/",
 data: {
 nickname: 'adsfkjkalsf',
 someparam: 'customerValue',
 uid: 120
 }, //直接赋值或者是使用formData
 success:function( result ){ $("#somewhere").html(result.html); }
 });
 
 示例3：
 ==========================================================================================
 Apache2 =>
 RewriteRule ^api/v([0-9]+)/docs/([A-Za-z0-9-_]+)/([A-Za-z0-9-_]+)/$ /api.php?version=$1&app=$2&class=$3&func=apidocs [QSA,L]
 RewriteRule ^api/v([0-9]+)/docs/([A-Za-z0-9-_]+)/([A-Za-z0-9-_]+)/([A-Za-z0-9-_]+)/$ /api.php?version=$1&app=$2&class=$3&func=$4&api_document_$4_view=true [QSA,L]
 RewriteRule ^api/v([0-9]+)/([A-Za-z0-9-_]+)/([A-Za-z0-9-_]+)/([A-Za-z0-9-_]+)/$ /api.php?version=$1&app=$2&class=$3&func=$4 [QSA,L]
 
 Nginx =>
 rewrite ^/api/v([0-9]+)/docs/([A-Za-z0-9_-]+)/([A-Za-z0-9_-]+)/$ /api.php?version=$1&app=$2&class=$3&func=apidocs last;
 rewrite ^/api/v([0-9]+)/docs/([A-Za-z0-9_-]+)/([A-Za-z0-9_-]+)/([A-Za-z0-9_-]+)/$ /api.php?version=$1&app=$2&class=$3&func=$4&api_document_$4_view=true last;
 rewrite ^/api/v([0-9]+)/([A-Za-z0-9_-]+)/([A-Za-z0-9_-]+)/([A-Za-z0-9_-]+)/$ /api.php?version=$1&app=$2&class=$3&func=$4 last;
 
 var formData = new FormData();
 for ( var key in itemlist.data) formData.append(key, itemlist.data[key]);
 
 $.ajax({
 type: "POST",
 url: "/api/v1/sample/category/getChannel/",
 data: {
 nickname: 'adsfkjkalsf',
 someparam: 'customerValue',
 uid: 120
 }, //直接赋值或者是使用formData
 success:function( result ){ $("#somewhere").html(result.html); }
 });
 */
class Api{
	
	public $sess=false;  // 是否启用SESSION
	public $admin=false; // 是否为管理后台
	public $space=false; // 是否为用户空间
	
	public $authPool = []; //设置哪些方法必须验证 key & secret
	
	private $classname;
	private $classCache;
	private $classFileName;
	
	public $purchased_package=false;
	
	/**
	 * 当$cacheStart为false时，只针对php内部调用
	 * @param boolean $cacheStart
	 */
	function __construct($cacheStart=true){
		//收到调试请求时判断是否启用SESSION
		if( isset($_GET["debug"]) || isset($_GET["clear"]) ) {
			$ip = http::getIP();
			if( substr($ip,0,strlen(DEBUGIP)) == DEBUGIP ) $this->startSession();
		}
		
		$_GET['version'] = empty($_GET['version'])?1:(int)$_GET['version'];
		
		if($cacheStart){
			$this->initCacheServer();
			$this->initClassCache();
		}
	}
	
	//检查学生隶属关系
	protected function studentAuthz($studentID){
		if(!$this->accessCheck($studentID)){
			$this->error("Student is not match!","User Access Forbidden!",0,'403');
		}
	}
	
	protected function accessCheck($studentID){
		if(empty($_SESSION['student'])) return false;
		
		foreach($_SESSION['student'] as $val){
			if( $val['id']==$studentID ) return true;
		}
		
		return false;
	}
	
	//检查专家隶属关系
	protected function expertAuthz($expertID){
		if(!$this->expertAccessCheck($expertID)){
			$this->error("Expert is not match!","User Access Forbidden!",0,'403');
		}
	}
	
	protected function expertAccessCheck($expertID){
		if(empty($_SESSION['user']['expert_id'])||empty($expertID)) return false;
		if($_SESSION['user']['expert_id']==$expertID) return true;
		
		//检查当前请求的expert是否是自己的下属
		$expertManagerObj = load("members_expert_manager");
		$where  = [
				'manager_id'=>$_SESSION['user']['expert_id'],
				'expert_id'=>$expertID,
				'pending'=>0
		];
		$rs = $expertManagerObj->getOne(['id'],$where);
		if(!empty($rs)) return true;
		
		return false;
	}
	
	function run( $method ){
		//启用session
		if( $this->sess || $this->admin || $this->space ) $this->startSession();
		
		//是否购买package
		if($this->purchased_package && empty($_SESSION['user']['purchased_package'])) $this->notfound();
		
		//是否存在
		if(!method_exists($this, $method)) $this->notfound();
		
		//检查权限
		if(in_array($method,$this->authPool)) $this->authz($method);
		if($this->space)
			if(empty($_SESSION['user']['id']))
				$this->error("User Is Not Login!","User Access Forbidden!",0,'403');
				if($this->admin)
					if(empty($_SESSION['UserLevel'])) $this->error("Administrator Is Not Loign!","User Access Forbidden!",0,'403');
					//显示文档
					if($method == 'apidocs') $this->apidocs();
					if(!empty($_GET["api_document_{$method}_view"])) $this->viewdoc();
					
					//执行api
					if(!$methodObj = $this->classCache->public_method->$method) $this->error('api',$method);
					
					//运行参数
					$parameters = [];
					$this->__initRequestPayload();
					//debug::D($_GET);
					//debug::D($_POST);
					if(isset($methodObj->request->get))$parameters = $this->__getParamValue($methodObj->request->get,$_GET,$parameters);
					if(isset($methodObj->request->post))$parameters = $this->__getParamValue($methodObj->request->post,$_POST,$parameters);
					
					//debug::D($methodObj->param);
					//debug::D($parameters);
					
					//按函数定义重排参数顺序
					$parameters = $this->__resortParameters($methodObj->param,$parameters);
					//echo "result=>";debug::D($parameters);exit;
					
					
					//获得结果
					$result = call_user_func_array(array($this, $method), $parameters);
					$this->display($result);
	}
	
	/**
	 * 按函数定义排序参数
	 * @param array $param
	 * @param array $parameters
	 *
	 * @return array
	 */
	private function __resortParameters($param,$parameters){
		$result = [];
		foreach($param as $paramObj){
			$key = $paramObj->name;
			if(isset($parameters[$key])){
				$result[$key]=$parameters[$key];
			}else{
				$result[$key]=isset($paramObj->defaultValue)?$paramObj->defaultValue:null;
			}
		}
		return $result;
	}
	
	private function __getParamValue($dstData,$srcData,$parameters){
		if(empty($dstData)) return $parameters;
		
		foreach($dstData as $param){
			if(!isset($srcData[$param->name])) {
				if(isset($param->default)){
					continue;
				}else{
					$this->error('param',$param->name); //必要参数不能为空
				}
			}
			$parameters[$param->name]=$srcData[$param->name];
		}
		
		return $parameters;
	}
	
	/**
	 * 初始化传递过来的JSON数据
	 */
	private function __initRequestPayload(){
		$request_body = file_get_contents('php://input');
		if(!empty($request_body)){
			$data = json_decode($request_body,true);
			if(!empty($data)){
				foreach($data as $key=>$value){
					if(!isset($_POST[$key]))$_POST[$key] = $value;
				}
			}
		}
	}
	
	/**
	 * 加载session
	 * 默认的SESSION为 UserID,UserName,NickName,UserLevel,UserPower
	 */
	private function startSession(){
		func_initSession();
		if(!$this->sess) $this->sess = true;
		
		//自动登录
		if(empty($_SESSION['user']['id'])){
			$obj_members_passport=load("members_passport");
			$obj_members_passport->check_auto_login();
		}
	}
	
	protected function initClassCache(){
		$this->classname = get_class($this);
		$this->classFileName = DOCUROOT."/".AppName."/api/".$this->classname.".php";
		$this->cacheID = systemVersion.'-api-'.AppName.'-'.$this->classname;
		
		//检查缓存
		if( $this->checkCache() ) return;
		
		$this->classCache = [
				'name'=>$this->classname,
				'access'=>"/api/v{$_GET['version']}/".AppName."/{$this->classname}",
				'document'=>"/api/v{$_GET['version']}/docs/".AppName."/{$this->classname}/",
				'pathinfo'=> "/".AppName."/api/".$this->classname.".php",
				'description'=>'',
				'updatetime'=>time(),
				'lastmodify'=>filemtime($this->classFileName),
				];
		
		//读取php中的文档信息
		$this->getReflectInfo();
		
		//写入缓存
		$this->classCache = json_decode(json_encode($this->classCache));
		$this->setCache();
		
		//debug::d($this->classCache);
		$this->debug( "Class Cache Has Been Rebuild!");
	}
	
	private function getReflectInfo(){
		$obj = new ReflectionClass( $this->classname );
		
		//处理主类文档
		$docComment = $this->parseDoc($obj->getDocComment());
		$this->classCache['description'] = $docComment['description'];
		if(!empty($docComment['docs']))
			foreach($docComment['docs'] as $key=>$item) $this->classCache[$key] = $item;
			
			//处理函数文档
			$methodList = $obj->getMethods();
			if(!empty($methodList)){
				foreach($methodList as $method){
					if($this->isSkipedMethod($method->name)) {
						//$this->debug("skip this method name:".$method->name);
						continue;//跳过保留函数
					}
					
					$methodObj = new ReflectionMethod($this->classname, $method->name);
					if(!$methodObj->isPublic()) continue;//跳过私有函数
					
					//读取函数文档
					$docs = $this->parseDoc($methodObj->getDocComment());
					
					$methodInfo = [];
					$methodInfo['name'] = $method->name;
					$methodInfo['description'] = isset($docs['description'])?trim($docs['description']):'';
					
					//生成api访问接口
					$accessType = empty($docs['docs']['access'][0])?'GET':$docs['docs']['access'][0];
					$methodInfo['access'] = "{$accessType} {$this->classCache['access']}/{$method->name}";
					
					//函数详细文档查看地址
					$methodInfo['url'] = $this->classCache['document'].$method->name."/";
					
					//请求参数
					$methodInfo['param'] = $this->getParam($methodObj->getParameters());
					
					//请求参数说明
					$methodInfo['request'] = $this->parseParam($methodObj->getParameters(),$docs);
					
					//返回结果说明
					$methodInfo['response'] = isset($docs['docs']['response'])?$docs['docs']['response']:null;
					
					//读取结果字段定义
					$methodInfo['formats'] = $this->parseReturn($docs);
					
					//返回结果字段说明
					$methodInfo['formats'] = $this->parseFormats($docs, $methodInfo['formats']);
					
					$this->classCache['public_method'][$method->name] = $methodInfo;
				}
			}
	}
	
	private function getParam($paramArray){
		$param = [];
		if(!empty($paramArray)){
			foreach($paramArray as $paramObj){
				$define = ['name'=>$paramObj->name ];
				if($paramObj->isOptional()){
					$define['defaultValue'] = $paramObj->getDefaultValue();
				}
				$param[]= $define;
			}
		}
		
		return $param;
	}
	
	private function isSkipedMethod($methodName){
		if(substr($methodName,0,2)=='__') return true;
		if(in_array($methodName, ['run','notfound'])) return true;
		
		return false;
	}
	
	private function parseParam($paramArray,$docs){
		if(empty($paramArray)) return;//debug::d($docs);
		
		//分析文档注释，获得参数类型入说明
		$paramDocsPool=[];
		if(!empty($docs['docs']['param'])){
			foreach($docs['docs']['param'] as $val){
				$tmp = [];
				if(strstr($val,"|")){
					$tmp['description']=trim(substr($val,strpos($val,"|")+1));
					$val = substr($val,0,strpos($val,"|"));
				}else{
					$tmp['description']='';
				}
				
				$paramDefinedArr = explode("$",$val);
				if(isset($paramDefinedArr[1])){
					$paramName = trim($paramDefinedArr[1]);
					$tmp['type'] = trim($paramDefinedArr[0]);
					$paramDocsPool[$paramName] = $tmp;
				}
			}
		}
		
		//读取post传递的字段说明
		$paramPostPool=[];
		if(!empty($docs['docs']['post'][0])){
			$postArr = explode(",",$docs['docs']['post'][0]);
			if(!empty($postArr)){
				foreach($postArr as $paramName){
					$paramName = trim($paramName);
					if(isset($paramDocsPool[$paramName]))$paramDocsPool[$paramName]['post']=true;
				}
			}
		}
		
		$param = [];
		if(!empty($paramArray)){
			foreach($paramArray as $paramObj){
				if($paramObj->isDefaultValueAvailable()){
					$defaultValue = $paramObj->getDefaultValue();
					$defaultValue = is_string($defaultValue)?$defaultValue:strtoupper(gettype($defaultValue));
				}else{
					$defaultValue = null;
				}
				
				
				$paramtype = empty($paramDocsPool[$paramObj->name]['post'])?'get':'post';
				$param[$paramtype][]=[
						'name'=>$paramObj->name,
						'description'=>isset($paramDocsPool[$paramObj->name]['description'])?$paramDocsPool[$paramObj->name]['description']:'',
						'type'=>isset($paramDocsPool[$paramObj->name]['type'])?$paramDocsPool[$paramObj->name]['type']:'',
						'default'=>$defaultValue
				];
			}
		}
		
		return $param;
	}
	
	//定义返回结果的特殊说明
	private function parseFormats($docs,$result){
		$formats = isset($docs['docs']['formats'])?$docs['docs']['formats']:null;
		if(empty($formats)) return $result;
		
		foreach($formats as $key=>$format){
			if(!isset($result[$key]))continue;
			if(empty($format)||!is_array($format))continue;
			
			$pool = [];
			foreach($format as $fields){
				if(empty($fields->name))continue;
				$pool[$fields->name]=[
						'type'=>empty($fields->type)?'unknown':$fields->type,
						'description'=>empty($fields->description)?'':$fields->description,
				];
			}
			
			//对有定义的返回结果字段，重新格式化输出
			foreach($result[$key] as $item=>$value){
				if(!isset($pool[$value['name']]))continue;
				
				$definedValue = $pool[$value['name']];
				if($definedValue['type']!='unknown') $value['type'] = $definedValue['type'];
				$value['description'] = $definedValue['description'];
				
				$result[$key][$item]=$value;
			}
		}
		
		return $result;
	}
	
	private $result;//函数返回结果模板
	
	private function parseReturn($docs){
		$return = isset($docs['docs']['return'])?$docs['docs']['return']:null;
		if(empty($return)) return;
		
		$result = [];
		foreach($return as $item=>$returnObj){
			if(!is_object($returnObj))continue;
			foreach ($returnObj as $key=>$val) {
				$result[$item][]=[
						'name'=>$key,
						'type'=>gettype($val),
						'demo'=>$val,
						'description'=>'',
				];
			}
		}
		
		return $result;
	}
	
	private function parseDoc($doc){
		if(empty($doc)) return;
		
		$doc_block = new doc($doc);
		$result = [
				'description'=>$doc_block->description,
				'docs'=>$doc_block->all_params
		];
		unset($doc_block);
		
		//处理描述
		if(!empty($result['description'])) {
			$result['description'] = trim($result['description']);
			$result['description'] =str_replace("*", " ", $result['description']);
		}
		
		//处理参数
		if(!empty($result['docs'])){
			foreach($result['docs'] as $key=>$param){
				if(empty($param)||!is_array($param)) continue;
				foreach($param as $itemkey=>$item){
					$item=trim($item);
					if(substr($item,-4)=='.txt'){
						$filename = DOCUROOT.$item;
						if(file_exists($filename)){
							$txt = file_get_contents($filename);//加载复杂格式文本
							$result['docs'][$key][$itemkey] = $txt;
						}
					}
					
					if(substr($item,-5)=='.json'){
						$filename = DOCUROOT.$item;
						if(file_exists($filename)){
							$json = file_get_contents($filename);//加载复杂格式数据
							$result['docs'][$key][$itemkey] = json_decode($json);
						}
					}
				}
			}
		}
		
		return $result;
	}
	
	private $cacheID;
	private $cacheObj;
	
	private function initCacheServer(){
		global $_GlobalConfig;
		$this->cacheObj = func_initMemcached($_GlobalConfig['host'],$_GlobalConfig['port']);
	}
	
	private function getCache(){
		return $this->cacheObj->get($this->cacheID);
	}
	
	private function setCache(){
		return $this->cacheObj->set($this->cacheID, $this->classCache,false, 0);
	}
	
	protected function checkCache(){
		$this->classCache = $this->getCache();
		
		if( empty($this->classCache) ) return false;
		if( empty($this->classCache->updatetime) ) return false;
		if( $this->classCache->updatetime < filemtime($this->classFileName) ) return false;
		if( $this->classCache->updatetime < filemtime(DOCUROOT."/include/cls.Api.php") ) return false;
		
		if(isset($_GET["clear"])){
			if( !empty($_SESSION['UserLevel'])) return false;
			if( http::getIP() == DEBUGIP ) return false;
		}
		
		return true;
	}
	
	//验证字段定义
	public $credentials=['key'=> 'access_key','secret'=> 'access_secret'];
	
	/**
	 * 检查API访问权限
	 * @param string $app
	 * @param string $func
	 */
	private function authz($func){
		if(!defined("AppName")) $this->error('app',$this->classname);
		
		//class
		$app = strstr(AppName,"/")?substr(AppName,strpos(AppName,"/")+1):AppName;
		$app = $app."_".$this->classname;
		
		//credentials
		$credential = $this->authzInfo();
		$access_key = isset($credential['key'])?$credential['key']:null;
		$access_secret = isset($credential['secret'])?$credential['secret']:null;
		
		$authzfile = DOCUROOT."/inc.authz.php";
		if(!file_exists($authzfile)) return;
		
		//SIMPLE REST
		$authzConf = include $authzfile;
		
		//检查用户名
		if(!isset($authzConf[$access_key])) $this->error('access_key');
		
		//检查密码
		if($authzConf[$access_key]['ACCESS_SECRET']!=$access_secret) $this->error('access_secret');
		
		//检查应用
		if(!isset($authzConf[$access_key]['APP'][$app])) $this->error('app',$app);
		
		//检查API
		if(is_array($authzConf[$access_key]['APP'][$app])){
			if(!in_array($func,$authzConf[$access_key]['APP'][$app])) $this->error('api');
		}
	}
	
	/**
	 * GET User's credentials
	 * sequence for credentials: Headers > Get > Post
	 */
	private function authzInfo(){
		$key = defined('CREDENTIAL_ACCESS_KEY') ? CREDENTIAL_ACCESS_KEY:$this->credentials['key'];
		$secret = defined('CREDENTIAL_ACCESS_SECRET') ? CREDENTIAL_ACCESS_SECRET:$this->credentials['secret'];
		
		$headerKey = 'HTTP_'.strtoupper($key);
		$headerSecret = 'HTTP_'.strtoupper($secret);
		
		$info = [];
		if(isset( $_SERVER[$headerKey] )&&isset( $_SERVER[$headerSecret]) ){
			$info['key'] = $_SERVER[$headerKey];
			$info['secret'] = $_SERVER[$headerSecret];
		}
		
		if(empty($info)){
			$info['key'] = empty($_GET[$key])?empty($_POST[$key])?'none':$_POST[$key]:$_GET[$key];
			$info['secret'] = empty($_GET[$secret])?empty($_POST[$secret])?'none':$_POST[$secret]:$_GET[$secret];
		}
		
		return $info;
	}
	
	protected function error($result=null,$msg=null,$code=0,$status='404'){
		$result = empty($result)?["name"=>"Not Found","message"=>"Page not found.","code"=>0,"status"=>404]:$result;
		
		$error = [
				'access_key'=>["name"=>"Not Access Key Found","message"=>"Access Key can not be matched.","code"=>0,"status"=>500],
				'access_secret'=>["name"=>"Not Access Secret Found","message"=>"Access Secret can not be matched.","code"=>0,"status"=>500],
				'app'=>["name"=>"APP Not Found","message"=>"App `{$msg}` is not found.","code"=>0,"status"=>404],
				'api'=>["name"=>"API Not Found","message"=>"API `{$msg}` is not found.","code"=>0,"status"=>404],
				'param'=>["name"=>"Parameters Error","message"=>"Required parameter `{$msg}` can not be empty.","code"=>0,"status"=>500],
				];
		
		if(is_string($result)) $result = isset($error[$result])?$error[$result]:["name"=>$result,"message"=>$msg,"code"=>$code,"status"=>$status];
		$this->display($result);
	}
	
	private function display($result){
		
		header("Access-Control-Allow-Origin: *");
		header("Content-Type: application/json; charset=UTF-8");
		
		if(isset($_GET['format'])){
			if($_GET['format']=='debug') {
				debug::D($result);
				exit;
			}
			
			if($_GET['format']=='php') {
				echo $result;
				exit;
			}
		}
		
		echo json_encode($result, JSON_NUMERIC_CHECK);
		exit;
	}
	
	private function debug($msg){
		//echo "{$msg}<br>";
	}
	
	function notfound(){
		$this->error();
	}
	
	/**
	 * 显示api 类文档
	 */
	private function apidocs(){
		$this->__displayDoc("/include/template/docs.html",$this->classCache);
	}
	
	/**
	 * 显示api 函数文档
	 */
	private function viewdoc(){
		$method = $_GET['func'];
		if(!isset($this->classCache->public_method->$method)) $this->notfound();
		
		$value = $this->classCache->public_method->$method;
		
		if(!empty($value->request)){
			$param=[];
			if(isset($value->request->get))
				foreach($value->request->get as $val)$param[]=$val->name;
				
				if(isset($value->request->post))
					foreach($value->request->post as $val)$param[]=$val->name;
					
					$value->paramStr = implode(", ", $param);
		}
		
		$this->__displayDoc("/include/template/docview.html",$value);
	}
	
	private function __displayDoc($tpl,$value){
		$value->classname = $this->classname;
		$value->document = $this->classCache->document;
		$value->lastmodify = times::getTime($this->classCache->lastmodify);
		
		if(isset($_GET['debug'])) {
			debug::D($value);
			exit;
		}
		
		$smarty = func_getSmarty( AppName );
		$smarty->assign("docs",$value);
		$smarty->display(DOCUROOT.$tpl);
		
		exit;
	}
	
	protected function objToArray($obj, &$arr=array())
	{
		if(!is_object($obj) && !is_array($obj)){
			$arr = $obj;
			return $arr;
		}
		
		foreach ($obj as $key => $value)
		{
			if (!empty($value))
			{
				$arr[$key] = array();
				$this->objToArray($value, $arr[$key]);
			}
			else
			{
				$arr[$key] = $value;
			}
		}
		return $arr;
	}
}