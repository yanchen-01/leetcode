<?php
/*
 * Created on 2008-4-3 By Weiqi
 * Model类与基础的db类的本质区别
 *
 * 1、可以针对表为单元进行编程，并缓存当前表
 * 2、MVC架构，更好理解和调用
 */
abstract class Model{
	// 数据库连接
	public $conn=null;
	
	// 分页信息
	public $pageconf=array('static'=>false,'pagesign'=>'page');
	// 数据模块缓存，以主键为ID的生命周期内缓存
	public static $cache;
	
	// 是否启用分库
	protected $DB_split=false;
	
	// 分表后缀截取长度
	protected $subTbLen=1;

	// 数据库配置信息
	protected $dbinfo=array("config"=>"master","type"=>"MySQL");
	
	// 数据表名字
	protected $tableName = '';
	
	// 数据库表信息,包括字段名，字段类型，是否为空，是否有默认值
	protected $fields = null;
	
	// 数据库表主键信息
	public $tablePK = 'id';
	
	// 开关，决定GetList是否使用简单主键作为计算条件
	public $isCountPK = false;
	
	// 映射表验证规则信息
	private $rule=array();

	private $mysqlDataType = array(
			'tinyint'=>'i',
			'smallint'=>'i',
			'mediumint'=>'i',
			'int'=>'i',
			'bigint'=>'i',
			'boolean'=>'i',
			'serial'=>'i',
	
			'float'=>'d',
			'decimal'=>'d',
			'double'=>'d',
			'real'=>'d',
	
			'tinyblob'=>'b',
			'mediumblob'=>'b',
			'blob'=>'b',
			'longblob'=>'b',
	);
	
	/**
	 * @param  $config=array('debug'=>false,'clean'=>false,'mustMasterConn'=>true);
	 * @return void
	 */
	function __construct($config=null){
		//分库
		$this->dbinfo=$this->formatDBInfo($this->dbinfo);
		
		// 获取数据库操作对象
		$this->conn=func_getDB( $this->dbinfo["config"], $this->dbinfo["type"] , $config);
		
		//只对mysql/mssql数据库进行下面的操作
		if($this->dbinfo['type']!='MySQL'&&$this->dbinfo['type']!='MsSQL') return;

		// 清理表结构
		if(!empty($_SESSION['UserLevel'])&&!empty($_GET["clear"])){
			if($_GET["clear"]=='tb') $this->flushTableInfo();
		}

		// 数据表字段检测
		if(!empty($this->tableName)){
			$this->getTableInfo();
		}else{
			echo "<h2>没有设置数据表名！<h2>";exit;
		}

	}
	
	//判断是否分库
	private function formatDBInfo($dbinfo){
		//分库
		if(!empty($this->DB_split)){
			$DbLabel = Cache::get( "DbLabel" );
			if($this->DB_split=='lang') $dbinfo['config']=$dbinfo['config'].'_'.(empty($DbLabel)?LANGUAGE:$DbLabel);
		}
		
		return $dbinfo;
	}

	##为模型类创建快速的操作函数
	
	/**
	 * 返回主健标识数据
	 * 
	 * @param int $id
	 */
	public function getResult($id){
		if(empty($id)) return;
		
		if(empty($this->cache[$id])) $this->cache[$id]=$this->getOne("*",array( $this->tablePK=>$id ));
		return $this->cache[$id];
	}
	
	// 在外部程序中使用主键设定缓存
	public function setResult($id,$result){
		$this->cache[$id] = $result;
	}

	/**
	 * 返回带有分页信息的数据数组
	 *
	 * @param string||array $where
	 * @param string||array $condition
	 * @param int $num
	 * @param string $table
	 * @return array
	 * TODO 此函数未完
	 */
	public function getList( $field='*', $where = null, $num=25, $table=null, $debug=false, $pages=5){
		$this->syncTableInfo();
		
		$page = new page($this->conn,Action::$tplobj);
		if( !empty($this->tablePK) && $this->isCountPK )$page->tablePK=$this->tablePK;
		
		$page->static = $this->pageconf['static'];
		$page->pagesign = $this->pageconf['pagesign'];
		$page->pageinfo = isset($this->pageconf['pageinfo'])?$this->pageconf['pageinfo']:true;
		$page->dbtype = $this->dbinfo['type'];
		
		$result = $page->readList($this->parseFields($field,"field"), $this->parseFields($where), $this->getTbName($table), (int)$num, (int)$pages);
		return $result;
	}

	/**
	 * 获得单条或多条记录
	 * ig: getOne($sql); getOne($fields,$where,$table);
	 *
	 * @param string||array $condition
	 * @param string||array $where
	 * @param string $table
	 */
	public function getOne($condition, $where = null, $table = null){
		$this->syncTableInfo();
		
		if(!is_array($condition) && trim($condition)!="*"){
			return $this->conn->getOne($condition);//正常执行SQL语句
		}

		$rs=$this->conn->getOne($this->parseFields($condition,'field'), $this->parseFields($where), $this->getTbName($table) );
		return $rs;

	}
	public function getAll($condition, $where = null, $table = null){
		$this->syncTableInfo();
		
		if(!is_array($condition) && trim($condition)!="*"){
			return $this->conn->getAll($condition);//正常执行SQL语句
		}
		$rs=$this->conn->getAll($this->parseFields($condition,'field'), $this->parseFields($where), $this->getTbName($table) );
		return $rs;
	}

	/**
	 * 执行sql命令，简写方法名，exec(), PDO和Mysql都支持
	 *
	 * @param string $sql
	 */
	public function exec($sql){
		$this->checkDbType('MySQL');
		return $this->conn->exec($sql);
	}
	public function Execute($sql){
		$this->checkDbType('MySQL');
		return $this->conn->exec($sql);
	}
	
	//对数据库中的全部表进行优化
	public function OptimizeDB($dbconf){
		$this->checkDbType('MySQL');
		return $this->conn->OptimizeDB( $dbconf );
	}
	
	//对单表进行优化
	public function OptimizeTB($tbn){
		$this->checkDbType('MySQL');
		return $this->conn->OptimizeTB( $tbn );
	}

	//常用数据操作
	/**
	* 向数据库插入数据
	*
	* @param array $array
	* @param string $table
	*/
	public function Insert($array, $table=null){
		$this->syncTableInfo();
		return $this->conn->Insert( $this->parseFields($array,'field'), $this->getTbName($table));
	}

	/**
	 * 向数据库更新数据
	 *
	 * @param array $array
	 * @param array $where
	 * @param string $table
	 */
	public function Update($array, $where, $table=null){ 
		$this->syncTableInfo();
		return $this->conn->Update( $this->parseFields($array,'field'), $this->parseFields($where), $this->getTbName($table) );
	}

	/**
	 * 从数据库删除数据
	 *
	 * @param array $where
	 * @param string $table
	 */
	public function Remove($where, $table=null){
		$this->syncTableInfo();
		return $this->conn->Remove( $this->parseFields($where),$this->getTbName($table) );
	}

	/**
	 * 更新记录集
	 *
	 * @param array $array
	 * @param string $table
	 */
	public function Replace($array, $table=null){
		$this->syncTableInfo();
		return $this->conn->Replace( $this->parseFields($array,'field'), $this->getTbName($table));
	}

	/**
	 * 返回记录集数量
	 *
	 * @param string||array $where
	 * @param string $table
	 * @return int
	 */
	public function Count($where=array(),$table=null){
		$this->syncTableInfo();
		$table=$this->getTbName($table);
		return $this->conn->Count($where, $table);
	}

	/**
	 * 设置记录集输出时的排序信息
	 *
	 * @param 要操作的记录 $id
	 * @param 用于排序的字段
	 * @return void;
	 */
	function setOrder($id,$order="order", $table=null){
		$this->syncTableInfo();
		$this->checkDbType('MySQL');
		$this->exec("UPDATE ".$this->getTbName($table)." SET `{$order}`=`id` WHERE id={$id}");
	}

	//排序操作
	function ord($fields=array('order'=>'order','id'=>'id'), $table=null, $debug=false){
		$this->checkDbType('MySQL');
		if($debug) debug::d($_POST);
		
		//获得当前选项
		for($i=1;$i<=$_POST["allnum"];$i++){
			$basenum = empty($_POST['basepagenum'])?0:intval($_POST['basepagenum']);
			
			$sn=$basenum+$i;
			if(!isset($_POST["flag".$sn])) break;
			//仅对发生改变的项目执行操作
			if($_POST["flag".$sn]=='Y'){
				$rid = empty($_POST["rid".$sn])?null:$_POST["rid".$sn];
				$ord = empty($_POST["ord".$sn])?null:$_POST["ord".$sn];				
					
				//被选中的记录
				$rid=intval($rid);if($debug){echo $rid;echo '|';}
				$ord=intval($ord);if($debug){echo $ord;echo '<br>';}
				if(!empty($rid) && !empty($ord)){
					$this->Update(array($fields["order"]=>$ord),array($fields["id"]=>$rid),$table);
				}
			}

		}
		if($debug) exit;

	}
	
	/**
	 * 根据id, 获取分表参数
	 * 
	 * @param  $id
	 * @param  $num
	 */
	function getSuffix($id){
		$id = '00000'.$id;
		$num = -1*$this->subTbLen;
		$str = substr($id, $num);
		
		return $str;
	}
	
	/**
	 * 获取常规分表名称
	 * @param $id  分表主键
	 */
	function getSubTbName($id){
		$id = '00000'.$id;
		$subTbName = empty($this->subTbLen)?$this->tableName : $this->tableName .'_'. $this->getSuffix($id);
		$subTbName = dbtools::escape($subTbName);
		
		return $subTbName;
	}
	
	/**
	 * 得到表名称
	 * @param string $table
	 */
	function getTbName($table=null){
		//使用默认名称
		if( empty($table) ) 
			return $this->tableName;
		
		//抛出异常
		if(!is_string($table)) func_throwException( "表名称有误!" );
		
		//启用分表
		if( substr($table,0,2)=='m:'){
			if( empty($this->conn->config['multiTb']) ){
				return $this->tableName;
			}else{
				return $this->getSubTbName(substr($table,2));
			}
		}
		
		//使用指定名称	
		return $table;
	}
	
	/**
	 * 使用redis获得自动增长的唯一ID
	 */
	function autoid($host=null,$port=6379){
		static $rdb;
		
		if(empty($host)) $host = defined('REDIS_HOST')?REDIS_HOST:'127.0.0.1';
		if(empty($rdb)) $rdb = func_initRedis($host,$port);
		
		$cacheid = "model-{$this->dbinfo['config']}-{$this->tableName}-autoid";
		
		$checkpos = $rdb->get($cacheid);
		if(empty($checkpos)){
			$rs = $this->getOne(array($this->tablePK),array('order'=>array($this->tablePK=>'DESC')));
			$defaultID = empty($rs[$this->tablePK])?0:$rs[$this->tablePK];
			$rdb->set($cacheid,$defaultID);
		}
		
		return $rdb->incr($cacheid);
		
	}
	
	/**
	 * 重置redis中存储的自动增长唯一ID
	 */
	function resetAutoid($id=0,$host=null,$port=6379){
		static $rdb;
		
		if(empty($host)) $host = defined('REDIS_HOST')?REDIS_HOST:'127.0.0.1';
		if(empty($rdb)) $rdb = func_initRedis($host,$port);
		
		if(empty($id)){
			$rs = $this->getOne(array($this->tablePK),array('order'=>array($this->tablePK=>'DESC')));
			$id = empty($rs[$this->tablePK])?0:$rs[$this->tablePK];
		}
		
		$cacheid = "model-{$this->dbinfo['config']}-{$this->tableName}-autoid";
		$result = empty($rdb->set($cacheid,$id))?0:$id;
		
		return $result;
	}

	/**
	 * 分析数组是否满足当前操作的表条件
	 *
	 * @param string||array $condition
	 * @param string $type
	 * @return array
	 */
	protected function parseFields($condition,$type='other'){
		//过滤mongo的数据结构
		if($this->dbinfo['type']=='Mongo') return $this->__mongoEscape($condition, $type);
		
		if(empty($condition)) return;
		if(is_string($condition)) return $condition;

		//针对多行插入的情况 
		if(!empty($condition["key"]) && !empty($condition["valuelist"])) return $condition;

		//验证字段,并返回可能经过修正的目标字段数据
		$list = array();
		foreach($condition as $key=>$val){
			if($type=='field'){
				//数字时是select的字段，其它键值时是insert/update字段
				$fieldname = is_numeric($key)?$val:$key;
				$bs = $this->validator($fieldname);
			}else{ 
				$bs = $this->validator($this->formatKey($key));
			}
			if(!empty($bs)) $list[$key]=$val;
		}

		return $list;
	}
	
	/*
	 * 判断输入mongo db 的数据结构是否符合预期
	 */
	private function __mongoEscape($document,$type){
		if( !is_array($document) ) return $document; //只对有结构的document进行验证
		if( $type!='field' ) return $document; //对于where中的操作只能是由程序员进行输入验证
		if( empty($this->fields) ) func_throwException("没有定义Mongo Collection的结构!");
		
		return $this->__mongoEscapeValidate($document, $this->fields);
	}
	
	//用于循环验证mongo collection结构
	private function __mongoEscapeValidate($document, $struct){
		$pool = array('int','bool','float','string','array','object');
		$map = array('int'=>'intval', 'float'=>'floatval', 'string'=>'strval');
		
		if(empty($document)) return $document;
		
		foreach($document as $key=>$val){
			//数字时是select的字段，其它键值时是insert/update字段
			$fieldname = is_numeric($key)?$val:$key;
			
			//对insert/update情况下，验证默认_id
			if($key==='_id') {
				if(is_object($val)) continue;
				if(is_string($val)) {
					$document['_id'] = func_getMongoID($val);
					continue;
				}
				unset($document['_id']);
				continue;
			}
			
			//select情况下，直接跳过
			if($fieldname=='_id'){continue;}
			
			//处理子级更新时的特殊设定
			if(strstr($fieldname,'.')){
				$tmpkeyarr = explode('.',$fieldname);
				if($struct[$tmpkeyarr[0]]=='array') continue;
			}
			
			//没有定义的字段不能使用
			if(!isset($struct[$fieldname])){
				unset($document[$key]);
				continue; 
			}
			
			//对insert/update方法操作的数据进行格式验证
			if(!is_numeric($key)){
				//对定义的字段进行格式验证
				$type = is_array($struct[$fieldname])?$struct[$fieldname]['type']:$struct[$fieldname];
				$method = in_array( $type, $pool )?$type:'int';
				$func = "is_{$method}";
				if(!$func($val)){
					 $val= isset($map[$method])?$map[$method]($val):null;
					 $document[$key] = $val;
				}
				
				//TODO更多验证
			}
			
			//对有子级结构的数据，进行进一步的验证, 此项文档是否有子集设置
			if( is_array($val) && $struct[$fieldname]!='array') unset($document[$key]);
		}
		
		return $document;
	}
	
	
	/**
	 * 验证数据是否满足条件
	 *
	 * @param 要验证的目标数据 $data
	 * @param array $rule  目标
	 * @return bool
	 */
	private function validator($data){
		if(empty($this->rule)) $this->initRule();
		return isset($this->rule[$data])?$data:false;
	}

	/**
	 * 过滤加了复合查询条件的key,如  $value=array('id,!='=>24)中的'id,!=';
	 *
	 * @param string $key
	 */
	private function formatKey($key){
		if(strstr($key,",")){//非等条件,如 !=,>,<,>=,<= 等
			$arr=explode(",",$key);
			$key=$arr[0];
		}
		return $key;
	}

	private function initRule()
    {
        //默认有效的调用字段
        $this->rule["SQL"]=true;
        $this->rule["OR"]=true;//使用 OR 做为联合查询条件
        $this->rule["UA"]=true;//使用 UNION ALL 做为联合查询条件
        $this->rule["limit"]=true;
        $this->rule["order"]=true;

        foreach ($this->fields as $key=>$val) {
            $this->rule[$key]=true;
        }
    }
	
	private function checkDbType($type){
		if($this->dbinfo['type']!=$type) func_throwException("当前操作只对 '{$type}' 数据库有效！");
	}

	/**
	 * 强制刷新数据表信息，缓存当前表结构
	 *
	 * @access public
	 * @return void
	 */
	public function flushTableInfo() {
		if ($this->dbinfo['type']=='MySQL') {
			$sql = 'SHOW COLUMNS FROM `'.$this->tableName.'`';
		} 
		
		if ($this->dbinfo['type']=='MsSQL') {
			$temp=conf('db');
			$db_name=$temp['database'][$this->dbinfo['config']]['db'];
			$sql = 'SELECT K.COLUMN_NAME, K.DATA_TYPE FROM ['.$db_name.'].INFORMATION_SCHEMA.[COLUMNS] AS K WHERE K.TABLE_NAME = \''.$this->tableName.'\'';
			$pk_list=$this->getPKs();
		}
		$result = $this->conn->getAll( $sql );
		$info   =   array();
		$pks = array();
		foreach ($result as $key => $val) {
			if ($this->dbinfo['type']=='MySQL') {
                if (is_object($val)) {
                    $val=get_object_vars($val);
                }

                //字段类型
                $dataType = $val['Type'];
                if (strstr($val['Type'], "(")) {
                    $dataTypeArr = explode("(", $val['Type']);
                    $dataType = $dataTypeArr[0];
                }
                $dataType = isset($this->mysqlDataType[$dataType])?$this->mysqlDataType[$dataType]:'s';

                $info[$val['Field']] = $dataType;
                if (strtolower($val['Key']) == 'pri') {
                    $pks[] = $val['Field'];
                }
            } elseif ($this->dbinfo['type']=='MsSQL') {
                if (is_object($val)) {
                    $val=get_object_vars($val);
                }
                $dataType = $val['DATA_TYPE'];
                if (strstr($val['DATA_TYPE'], "(")) {
                    $dataTypeArr = explode("(", $val['DATA_TYPE']);
                    $dataType = $dataTypeArr[0];
                }
                $dataType = isset($this->mysqlDataType[$dataType])?$this->mysqlDataType[$dataType]:'s';

                $info[$val['COLUMN_NAME']] = $dataType;
                if (!empty($pk_list[$val['COLUMN_NAME']])) {
                    $pks[]=$val['COLUMN_NAME'];
                }
            }
		}
		$this->fields = $info;
		if( !empty( $pks ) ){
			$this->tablePK = implode( ',',$pks );
			$info['pk'] = $this->tablePK;
		}
		// 永久缓存数据表信息		
		$folder=DOCUROOT.'/cache/table';
		if(!file_exists($folder)){
			files::mkdirs($folder);
		}
		$cacheFile = $folder.'/'.$this->dbinfo["config"].'.'.$this->tableName.'.php';
		if( is_writable( $folder ) ){
			$content = 'return '.var_export( $info,true ).";\n";
			$content  = "<?php\n".$content."\n?>";
			file_put_contents( $cacheFile,$content );
		}

	}
	
	/**
	 * 数据表字段检测, Primary Key(SQL Server)
	 *
	 * @access private
	 * @return void
	 */
	private function getPKs()
	{
		$temp=conf('db');
		$db_name=$temp['database'][$this->dbinfo['config']]['db'];
		$sql='SELECT C.CONSTRAINT_TYPE, K.COLUMN_NAME FROM ['.$db_name.'].[INFORMATION_SCHEMA].TABLE_CONSTRAINTS AS C
				 JOIN ['.$db_name.'].INFORMATION_SCHEMA.KEY_COLUMN_USAGE AS K ON C.TABLE_NAME = K.TABLE_NAME AND C.CONSTRAINT_NAME = K.CONSTRAINT_NAME AND C.CONSTRAINT_CATALOG = K.CONSTRAINT_CATALOG
				AND C.CONSTRAINT_SCHEMA = K.CONSTRAINT_SCHEMA WHERE K.TABLE_NAME = \''.$this->tableName.'\'';
		$result=$this->conn->getAll($sql);
		$map=[];
		foreach ($result as  $val) {
			if ($val['CONSTRAINT_TYPE']=='PRIMARY KEY') {
				$map[$val['COLUMN_NAME']]=$val['CONSTRAINT_TYPE'];
			}
		}
		return $map;
	}

	/**
	 * 数据表字段检测
	 *
	 * @access protected
	 * @return void
	 */
	protected function getTableInfo(){
		if( !empty( $this->fields ) || empty( $this->tableName ) )
		return;

		$cache = DOCUROOT.'/cache/table/'.$this->dbinfo["config"].'.'.$this->tableName.'.php';
		if( file_exists( $cache ) ){
			$info = include $cache;
			if( isset( $info['pk'] ) ){
				$this->tablePK = $info['pk'];
				unset( $info['pk'] );
			}
			$this->fields = $info;
		}else{
			$this->flushTableInfo();
		}
	}
	
	/**
	 * 向MySQL数据库传递当前数据对象的表结构定义
	 */
	protected function syncTableInfo(){
		$this->conn->fields = $this->fields;
	}
	
	/**
	 * 检查字段是否有效
	 * @return boolean
	 */
	public function isField($field){
		$this->checkDbType('MySQL');
		foreach($this->fields as $val) if($field==$val) return true;
		return false;
	}

	/**
	 * 检查当前连接是否因为长时间不活动而失效
	 * @return void
	 */
	public function checkConn(){
		if(empty($this->conn)){
			$this->resetConn();
			return;
		}

		if( $this->dbinfo['type']=='MySQL' && !mysqli_ping( $this->conn->conn )) $this->resetConn();
	}

	/**
	 * 防止长时间执行占用大量内存，强制重置当前mod的数据连接
	 *
	 */
	public function resetConn(){
		$this->conn=null;
		$this->conn=func_getDB( $this->dbinfo["config"], $this->dbinfo["type"], array('clean'=>true));
	}
}
?>