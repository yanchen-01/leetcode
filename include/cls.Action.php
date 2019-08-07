<?php
/*
 * Created on 2008-4-3 By Weiqi
 */
abstract class Action {
    // 模板对象
    public static $tplobj=null;
    // 语言包对象
    public $lang;
    
    // 当前模板设置
    public $tpl=null;
    // 当前action运行时环境
    public $env=null;
    // 用户授权类型
    public $authz="user";//superadmin:超管,admin:授权用户,user:普通用户
    
    // 当前方法名
    public $method;
    
    // 程序内置缓存时间
    public $cacheTime=0;
    
    // 当前页面的访问位置
    protected $self;
    
    // 初始化
    function __construct(){
        // 获取数据库操作对象
        if( self::$tplobj == null ){
            //是否使用集中式模板结构，默认采用分项目模板结构
            if( !defined( 'centreModel' ) ) define( 'centreModel', false );
            
            self::$tplobj = func_getSmarty( AppName , centreModel);
        }
        if(!empty($_SERVER)){
            $this->self=$_SERVER["SCRIPT_NAME"];
        }
    }
    // 析构
    function __destruct(){
        if(empty($_SESSION['UserLevel']))return;
        if(!empty($_GET['debug'])){
            if($_GET['debug']=='showtpl') {
                $tpl=array();
                $tpl['ScriptName'] = $_SERVER['SCRIPT_NAME'];
                $tpl['TemplateFolder']=str_replace(DOCUROOT,'',self::$tplobj->template_dir[0]);
                $tpl['TemplateFile']=str_replace(DOCUROOT,'',$this->tpl);
                $tpl['includeTpl']=isset(self::$tplobj->tpl_vars['includeTpl'])?str_replace(DOCUROOT,'',self::$tplobj->tpl_vars['includeTpl']):'';
                
                echo '<pre style="clear:both;"><h3>The Script Tpl: </h3></pre><hr>';
                debug::d($tpl);
            }
            if($_GET['debug']=='showfields') {
                $vars=self::$tplobj->tpl_vars;
                if(isset($_GET['field']))
                    $vars=empty($vars[$_GET['field']])?$vars:$vars[$_GET['field']];
                    
                    echo '<pre style="clear:both;"><h3>All Assign Vars: </h3></pre><hr>';
                    debug::d($vars);
            }
            
            if($_GET['debug']=='showserver') {
                $lst=array('PATH','DOCUMENT_ROOT','SERVER_SIGNATURE');
                $result=array();
                foreach($_SERVER as $key=>$val){
                    if(in_array($key,$lst)) continue;
                    $val = str_replace( DOCUROOT,'/DOCUROOT',$val );
                    $result[$key]=$val;
                }
                echo '<pre style="clear:both;"><h3>The Server Vars: </h3></pre><hr>';
                debug::d($result);
            }
        }
    }
    
    /**
     * 检查页面权限
     * @param Array $role
     */
    public function checkRole($role){
    	if(!isset($_SESSION["role"])||!in_array($_SESSION["role"],$role))
    		$this->tpl="template/pmc/admin.html";
    }
    
    // 附值
    public function assign($name, $value){
        return self::$tplobj->assign($name, $value);
    }
    
    // 注销
    public function unsign($name){
        $arr=explode('.',$name);
        $num=count($arr);
        switch($num){
            case 1:
                unset(self::$tplobj->tpl_vars[$name]);break;
            case 2:
                unset(self::$tplobj->tpl_vars[$arr[0]]->value[$arr[1]]);break;
            case 3:
                unset(self::$tplobj->tpl_vars[$arr[0]]->value[$arr[1]][$arr[2]]);break;
            case 4:
                unset(self::$tplobj->tpl_vars[$arr[0]]->value[$arr[1]][$arr[2]][$arr[3]]);break;
            case 5:
                unset(self::$tplobj->tpl_vars[$arr[0]]->value[$arr[1]][$arr[2]][$arr[3]][$arr[4]]);break;
            default:
                ;
        }
    }
    
    // 获取当前服务器的缓存对象
    function getMemObj(){
        $configstr = conf('global','server.worknode');
        if(empty($configstr)) {
            $cachePools = array("cache01");
        }else{
            $cachePools = explode(",",$configstr);
        }
        
        foreach($cachePools as $k=>$v)$cachePools[$k]=trim($v);
        
        $host = in_array( $_SERVER['SERVER_ADDR'], $cachePools )?$_SERVER['SERVER_ADDR']:"cache01";
        if(debug::check("showserver")) debug::d( "memcache server: ".$host );
        
        return func_initMemcached($host);
    }
    
    // 设置缓存, 多主机下删除cache使用 func_delCache()
    function setCache($cacheID,$cache,$cacheTime=0){
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
            if( $obj=func_initMemcached($host) ) $obj->set($cacheID,$cache,false, $cacheTime);
    }
    
    //检测缓存设置
    function checkCache($cache){
        //没有缓存
        if(empty($cache)) return false;
        
        //超时
        if(isset($cache['datetime'])){
            $now = time();
            $cachePoint = intval( $cache['datetime'] );
            if( $now - $cachePoint > $this->cacheTime ) return false;
        }
        
        //管理员手动清除缓存
        if(!empty($_GET['clear'])) {if(!empty($_SESSION['UserLevel'])) return false;}
        
        return true;
    }
    
    // 显示指定模板
    public function show($tpl){
        self::$tplobj->display( $tpl );
        exit;
    }
    
    // 显示
    public function display(){
        $html = "";
        $this->initSystemTemplate();
        
        //是否需要语言转换
        if(!empty($_COOKIE['lang_big5'])){
            $obj = new big5();
            if($_COOKIE['lang_big5']==1) $html = $obj->c2t($this->fetch());//简转繁
            if($_COOKIE['lang_big5']==2) $html = $obj->t2c($this->fetch());//繁转简
        }
        
        //输出文档类型
        $this->getContentType();
        
        if(empty($html)){
            self::$tplobj->display( $this->tpl );
        }else{
            echo $html;
        }
    }
    
    private function getContentType(){
        if(empty($this->outformat)){
            $pos=strpos($_SERVER['REQUEST_URI'],"?");
            $filename=empty($pos)?$_SERVER['REQUEST_URI']:substr($_SERVER['REQUEST_URI'],0,$pos);
            $ext=strtolower(substr(strrchr($filename, "."), 1));
        }else{
            $ext = $this->outformat;
        }
        $ext = strtolower($ext);
        if($ext=="php") return;
        
        $pools=array(
            "html"=>"text/html",
            "htm"=>"text/html",
            "css"=>"text/css",
            "js"=>'application/x-javascript',
            "png"=>"image/png",
            "jpg"=>"image/jpeg",
            "jpeg"=>"image/jpeg",
            "gif"=>"image/gif",
            "xml"=>"text/xml",
            "json"=>"application/json",
        );
        if(!empty($pools[$ext])) header("Content-Type:".$pools[$ext]);
    }
    
    // json输出
    public function json($value,$options=null){
        echo json_encode($value,$options);
        exit;
    }
    
    // 抓取
    public function fetch($tpl=null){
        $tpl=empty($tpl)?$this->tpl:$tpl;
        $html=self::$tplobj->fetch( $tpl );
        return $html;
    }
    
    // 启用smarty调试
    public function smartyDebug(){
        self::$tplobj->debugging=true;
    }
    
    // 注册函数块
    public function register_block($name, $value){
        return self::$tplobj->register_block($name, $value);
    }
    
    // 注册修饰函数
    public function register_modifier($name, $value){
        return self::$tplobj->register_modifier($name, $value);
    }
    
    /**
     * 多站点环境下，对于设置了特定模板的页面，加载针对该站点的模板
     * 如没有相关文件，直接使用默认路径
     */
    public function getTpl(){
        $siteTpl = conf("global","tpl");
        $className = get_class($this);
        $act = $_GET['act'];
        
        $templateFile = DOCUROOT."/".AppName."/Tpl/{$className}/{$siteTpl}/{$act}.html";
        if(file_exists($templateFile)) {
            if($this->env=='space'){
                $this->assign("includeTpl",$templateFile);
            }else{
                $this->tpl = $templateFile;
            }
        }
    }
    
    /**
     * 判断母板设置并获取页面模板
     */
    private function initSystemTemplate(){
        //无母板时使用默认模板
        if(empty($this->parentTpl)) return;
        
        //如果文件存在，自动将模板文件转换内母板内置文件
        if( file_exists( DOCUROOT."/".AppName."/Tpl/".$this->tpl )||file_exists($this->tpl) ) $this->assign("includeTpl", $this->tpl);
        
        //将页面模板设置成母板
        $this->tpl = $this->parentTpl;
    }
    
    /**
     * 加载空间菜单信息
     * Enter description here ...
     * @param unknown_type $columnTab
     */
    function initSpaceMenu($columnTab){
        $sitetpl = conf("global","uid");
        $spacemenu = conf("plugins.{$sitetpl}.spacemenu");
        
        $currentColumnTab = "space";//默认是空间首页
        foreach($spacemenu as $item){
            if(empty($item['id']))continue;
            if($item['id'] == $columnTab){
                $this->assign("spaceTitle",$item['title']);
                $currentColumnTab = $columnTab;
                break;
            }
        }
        
        //输出space菜单和项目定位标识
        $this->assign("spaceMenu", $spacemenu);
        $this->assign("columnTab", $currentColumnTab);
    }
    
    /**
     * 加载不同模式下的tpl路径
     * 返回类似
     *
     * /template/default/article/view
     * 或
     * /article/Tpl/view/default
     * 的模板路径
     *
     * @param 应用 $appname
     * @param 页面 $act
     * @param 是否分站 $siteFolder
     *
     * @return string $path
     */
    public function getTypeBaseTpl($app, $act='', $siteFolder=false){
        
        //集中模式
        $centrePath = "/template/".conf('global','tpl')."/{$app}";
        if(!empty($act)) $centrePath .= "/{$act}";
        
        //项目模式
        $selfPath= "/{$app}/Tpl";
        if(!empty($act)) $selfPath .= "/{$act}";
        if( $siteFolder ) $selfPath .= "/".conf('global','tpl');
        
        //项目目录下的模板的调用优先级高于集中式的模板
        $path = file_exists(DOCUROOT.'/'.$selfPath)? $selfPath : $centrePath;
        
        return $path;
    }
    
}
?>