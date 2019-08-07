<?php
/**
 * MySQL防止预期外注入的措施：
 *
 * 简单说就是不要给用户传入特殊操作符的机会。
 * 1，所有使用框架中数组定义的方式进行的查询都是安全的
 * 2，使用用户输入的数据拼接SQL时，对数字类型的参数要使用Type Casting:(int)||(float)进行类型验证，对字符类型的参数要使用dbtools::escape进行过滤，并在SQL中使用引号包括用户输入的数据
 * 3，使用框架或自己接拼接SQL尽量不要引用用户数据生成表名，如一定需要这样做，程序员要严格限定输入的参数格式
 *
 * @author weiqiwang
 */
class MySQL{
	var $conn;//当前链接
	var $silent = false;//是否不抛出异常信息
	var $mysqlMasterSlave=false; //是否使用mysql数据库主从结构

	var $master;//主库,可读写
	var $slave;//从库，只读

	var $fields = array(); //当前数据对象的结构定义,随数据对象切换改变
	var $config=array();//当前链接的配置
	var $sql=array();//累计执行的sql语句
	var $querynum=0;//累计查询的数量

	function init(){
		if(!$this->mysqlMasterSlave){
			//独立库
			$this->conn = $this->connect( $this->config );
		}else{
			//主从库，自动打开到从库链接
			$this->conn = $this->slave = $this->connect( $this->config['slave'] );
		}
	}
	
	/**
	 * 使用mysqli进行连接
	 * @return mysqli connection
	 */
	function connect($config){
		$conn = new mysqli($config['server'], $config['user'], $config['password'], $config['database']);
		
		if ( $conn->connect_errno ) func_throwException("Failed to connect to MySQL: (" . $conn->connect_errno . ") " . $conn->connect_error);
		if ( !mysqli_set_charset ( $conn , $config['charset'] ) ) func_throwException("Error loading character set {$config['charset']}: {$conn->error}");
		if ( empty(dbtools::$conn)||!mysqli_ping(dbtools::$conn) ) dbtools::$conn = $conn;
		
		return $conn;
	}

	/**
	 * 执行sql语句
	 * @param string sql语句
	 * @param string 默认为空，可选值为 CACHE UNBUFFERED
	 * @param int Cache以秒为单位的生命周期
	 * @return resource
	 */
	public function query($sql){
		if(!$this->mysqlMasterSlave){
			//对于使用mysql-proxy实现主从结构或没有主从结构的情况直接调用主库即可
			if(empty($this->conn)){
				$this->conn=$this->connect( $this->config );
			}
		}else{
			//根据查询类型选择使用主库还是从库
			if(strtolower(substr(trim($sql),0,6))=='select'){
				$this->conn = $this->slave;
			}else{
				if(empty($this->master)){
					if($this->config['master']['server'] == $this->config['slave']['server']){
						$this->master = $this->slave;
					}else{
						$this->master = $this->connect( $this->config['master'] );
					}
				}
				$this->conn = $this->master;
			}
		}

		//执行数据内容操作
		if(substr($sql,-1)!=';') $sql = $sql.';';
		if( !($result = mysqli_query($this->conn,$sql)) && !$this->silent){
			$mes = 'MySQL Query Error:'.$sql;
			func_throwException($mes);
		}else{
			$this->sql[]=$sql;//记录执行语句
		}

		$this->querynum++;
		return $result;
	}

	/**
	 * 执行sql语句，快捷方法
	 * @return int or bool
	 */
	public function exec( $sql ){
		$this->query( $sql );
		return $this->conn->affected_rows;
	}
	
	/**
	 * 
	 * 执行sql语句，返回记录集行数
	 * @param string $sql
	 * @return number
	 */
	public function rows( $sql ){
		$result = $this->query( $sql );
		return mysqli_num_rows ( $result );
	}

	/**
	 * 执行sql语句，只得到一条记录
	 * @return array
	 */
	public function getOne( $fields, $where = null, $table=null, $order=null) {
		$result=$this->getAll( $fields, $where, $table, $order, 1);
		if(!empty($result)){
			foreach($result as $rs){
				return $rs;
				break;
			}
		}
	}

	/**
	 * 执行sql语句，得到所有的记录
	 * @return array
	 */
	public function getAll( $fields, $where = null, $table=null, $order=null, $limit=null) {
		if (empty($fields)) return false;
		
		/* SQL绑定分析 */
		$this->prepareSQL( $fields, $table, $where, $order, $limit );
		
		/* prepare SQL binding */
		if (!$stmt = $this->conn->prepare( $this->querySQL )) func_throwException("Failed to prepare mysqli_stmt");
        
		/* bind variables and get result*/
		$stmt = $this->stmtExec($stmt);
		$result = $stmt->get_result();
		
		/* clean up */
		$stmt->close();
		$this->stmtSqlClean();
		
		$list = array(); 
		if(!empty($result)){
			$i=0;
			while( $row = mysqli_fetch_array( $result, MYSQLI_ASSOC ) ) {
				$i++;
				$list[] = $row;
				
				//为防止取出过多的查询结果导致内存溢出，此处对超过100条输出的结果做内存监控
				if($i>100){
					//默认查询数据库的内存上限为16M，可以通过设置SqlMaxMemorySize调整
					static $maxMemory;
					if( empty($maxMemory) ) $maxMemory = defined("SqlMaxMemorySize")?SqlMaxMemorySize:16777216;
					
					$memory_usage=memory_get_usage();
					if( $memory_usage>$maxMemory ) func_throwException("数据库存查询结果过大，导致内存溢出!");
				}
			}
		}
		
		return $list;
	}
	
	private function prepareSQL($condition, $table, $where, $order=null, $limit=null){
		$sql="";
		if( $table!=null ){
			//处理where内部常量
			if( $ualist = isset($where['UA'])?$where['UA']:false ) unset($where['UA']);
			if( $order = isset($where['order'])?$where['order']:$order ) unset($where['order']);
			if( $limit = isset($where['limit'])?$where['limit']:$limit ) unset($where['limit']);
				
			//select & table
			$sql="SELECT ". $this->parseSelect($condition) ." FROM `{$table}` ";
	
			//where
			$whereSQL = $this->parseWhere($where);
			if(!empty($whereSQL)) $sql.= "WHERE " . $whereSQL;
	
			//union all
			if(!empty($ualist)) $sql = $this->parseUnionAll($sql, $whereSQL, $ualist);
				
			//order
			$sql.= $this->parseOrder($order);
	
			//limit
			$sql.= $this->parseLimit($limit);
	
		}else{
			$sql=$condition; //直接使用SQL
		}
	
		$this->querySQL = $sql;
	}
	
	private function parseSelect($condition){
		if(is_string($condition)) return $condition;
	
		$list=array();
		foreach($condition as $key=>$val) {
			$selectField = $this->paresSelectItem($key,$val);
			if(empty($selectField)) continue;
			$list[] = $selectField;
		}
		return implode(', ', $list);
	}
	
	private function paresSelectItem($key,$val){
		if($key==="SQL") return empty($val)?"":$val;
		return "`".$val."`";
	}
	
	
	/**
	 * 分析查询条件
	 *
	 * @param str||arr $condition
	 * @return string where
	 */
	private function parseWhere($condition){
		if( empty($condition) ) return "";
		if( is_string($condition) ) return $condition;
	
		//以下两项用于直接调用parseWhere的代码时使用
		if( $order = isset($condition['order'])?$condition['order']:false ) unset($condition['order']);
		if( $limit = isset($condition['limit'])?$condition['limit']:false ) unset($condition['limit']);
	
		$sql='';
		$list=array();
		foreach($condition as $key=>$val){
			$tmp=$this->paresWhereItem($key,$val);
			if(!empty($tmp)) $list[]=$tmp;
		}
	
		$sql = implode(' AND ', $list );
	
		//order
		if($order) $sql.= $this->parseOrder($order);
	
		//limit
		if($limit) $sql.= $this->parseLimit($limit);
	
		return $sql;
	}
	
	/**
	 * 处理复合条件
	 *
	 * @param string $key
	 * @param array $val
	 * @return string
	 */
	private function paresWhereItem($key,$val){
		//处理复合条件SQL
		if($key=="SQL")
			return empty($val)?"":"({$val})";
	
		//处理复合条件OR
		if($key=="OR"){
			$tmpSQL=array();
			foreach($val as $k=>$v){
				if(!isset($this->fields[$k])) continue;
				foreach($v as $item){
					$tmpSQL[]= "`{$k}`=".$this->readfields( $k, $item )."";
				}
				break;//仅取一级
			}

			return empty($tmpSQL)?"":"(".implode(" OR ",$tmpSQL).")";
		}
			
		//处理其他单一条件
		if(strstr($key,",")){//非等条件,如 !=,>,<,>=,<= 等
			$arr=explode(",",$key);
			$key=$arr[0];
			$delimiter=$arr[1];
		}else{
			$delimiter="=";
		}

		return "(`{$key}`{$delimiter}".$this->readfields( $key, $val ).")";
	}
	
	/**
	 * 联合查询
	 * $tmp = array('4f1g2j3','u8e9o2','p2o3i4');
	 * $this->getAll("*",array('UA'=>array('regCode'=>$tmp)),"m:{$this->userID}");
	 *
	 * @param 已经解析的查询语句 $sql
	 * @param 已经解析的条件语句 $whereSQL
	 * @param 做合并的条件 $ualist //此处示例为 array('regCode'=>$tmp)
	 * @return string
	 */
	private function parseUnionAll($sql,$whereSQL,$ualist){
		$sql.=(empty($whereSQL))?"WHERE ":" AND ";
	
		$tmpSQL=array();
		foreach($ualist as $key=>$value){
			if(!isset($this->fields[$key])) continue;
			foreach($value as $val){
				$tmpSQL[]= "({$sql}(`{$key}`=".$this->readfields( $key, $val )."))";
			}
			
			break;//仅取一级
			
		}
	
		//使用UNION ALL重新连接SQL语句
		return empty($tmpSQL)?"":implode( " UNION ALL ", $tmpSQL );
	}
	
	
	/**
	 * 处理 Order
	 *
	 * @param array $condition
	 * @return string $orderstr
	 */
	private function parseOrder($condition){
		if(empty($condition)||!is_array($condition)) return "";
	
		$tmp=array();
		foreach($condition as $key=>$orderStr) {
			if(empty($key)){
				if(!isset($this->fields[$orderStr])) continue;
				$tmp[]=$orderStr;
			}else{
				if(!isset($this->fields[$key])) continue;
				$orderStr = strtoupper($orderStr);
				if(!in_array($orderStr, array('ASC','DESC'))) continue;
	
				$tmp[]="`".$key."` ".$orderStr;
			}
		}
		return empty($tmp)?"":" ORDER BY ". implode(" , ",$tmp);
	}
	
	/**
	 * 处理Limit
	 *
	 * @param int $condition=25  array $condition=array(from,cellnum)
	 * @return str $limit
	 */
	private function parseLimit( $condition ){
		if(empty($condition)) return "";
	
		$limit="";
		if( is_numeric($condition)) $limit = " LIMIT ".$condition;
		if( is_array($condition)) $limit = " LIMIT ".intval($condition[0]).",".intval($condition[1]);
			
		return $limit;
	}

	//插入
	public function Insert( $data, $table, $command='INSERT') {
		if( empty($table) && !is_array($data) ) return false;
		if( !strstr($table,"`") ) $table = "`{$table}`";
		
		$fields = array();
		$values = array();
		if( !empty($data["key"]) && !empty($data["valuelist"]) ){//多行插入
			$fields = $data["key"];
			$values = $data["valuelist"];
		}else{//单行插入
			$tmpValue = array();
			foreach ($data as $k => $v) {
				$fields[] = $k;
				$tmpValue[] = $v;
			}
			$values[] = $tmpValue;
		}
		
		//插入整理获得数据结构
		$fieldsArr = array();
		$valuesArr = array();
		foreach ($fields as $k => $v) {
			$fieldsArr[] = "`{$v}`";
			$valuesArr[] = "?";
		}
		$this->querySQL = "{$command} INTO {$table} ( " . implode(', ', $fieldsArr) . ") VALUES ( " . implode(', ', $valuesArr) . " )";
		
		/* prepare SQL binding */
		if (!$stmt = $this->conn->prepare( $this->querySQL )) func_throwException("Failed to prepare mysqli_stmt");
		
		/* bind variables and get result*/
		$insertIDs = array();
		foreach($values as $val){
			$this->queryParameters = array();
			$i=0;
			foreach ($val as $k => $v) {
				$this->readfields( $fields[$i], $v ); $i++;
			}
			$stmt = $this->stmtExec($stmt);
			$insertIDs[] = $stmt->insert_id;
		}
		
		/* clean up */
		$stmt->close();
		$this->stmtSqlClean();
		
		if(empty($insertIDs)) return false;
		return count($insertIDs)>1?$insertIDs:$insertIDs[0];
	}

	//替换
	public function Replace( $data, $table ) {
		return $this->Insert($data, $table, 'REPLACE');
	}

	//更新
	public function Update($data, $where, $table) {
		if (empty ($table) || empty($data)) return false;
		if(!strstr($table,"`")) $table = "`{$table}`";
		
		/* prepare SQL binding */
		$this->querySQL = "UPDATE {$table} SET " . $this->parseData($data) . " WHERE " . $this->parseWhere( $where );
		if (!$stmt = $this->conn->prepare( $this->querySQL )) func_throwException("Failed to prepare mysqli_stmt");
		
		/* bind variables and get result*/
		$stmt = $this->stmtExec($stmt);
		$result = $stmt->affected_rows;
		
		/* clean up */
		$stmt->close();
		$this->stmtSqlClean();
		
		return $result;
	}
	
	private function parseData($data){
		if( is_string($data) ) return $data;
		
		$value = array();
		foreach( $data as $k => $v ) {
			$dataItem = $this->parseDataItem( $k , $v );
			if( !empty($dataItem) ) $value[] = $dataItem;
		}
		
		return implode(', ', $value);
	}
	
	private function parseDataItem($key,$val){
		if($key=="SQL") return empty($val)?"":$val;
		return "`{$key}`=".$this->readfields($key, $val);
	}

	//执行
	public function Execute($sql){
		return $this->exec($sql);
	}

	//删除
	public function Remove($where,$table){
		if(!strstr($table,"`")) $table = "`{$table}`";
		
		/* prepare SQL binding */
		$this->querySQL = "DELETE FROM {$table} WHERE ". $this->parseWhere( $where );
		if (!$stmt = $this->conn->prepare( $this->querySQL )) func_throwException("Failed to prepare mysqli_stmt");
		
		/* bind variables and get result*/
		$stmt = $this->stmtExec($stmt);
		$result = $stmt->affected_rows;
		
		/* clean up */
		$stmt->close();
		$this->stmtSqlClean();
		
		return $result;
	}
	
	//记数
	public function Count($where, $table){
		if(!strstr($table,"`")) $table = "`{$table}`";
		$fields='count(*) as `n`';
		if(is_array($where)){
			//去重查询
			if(isset($where['distinct'])&&isset($this->fields[$where['distinct']])){
				$fields="count( DISTINCT `{$where['distinct']}` ) as `n`";
				unset($where['distinct']);
			}
		}
		
		$wherestr=empty($where)?"":" WHERE ".$this->parseWhere($where);
		$count=$this->getOne("SELECT {$fields} FROM {$table}{$wherestr}");
		return $count["n"];
	}
	
	//对单表进行优化
	public function OptimizeTB($tbn){
		if(!empty($tbn)) $this->exec("OPTIMIZE TABLE `{$tbn}`");
	}
	
	//对数据库中的全部表进行优化
	public function OptimizeDB($dbconf){
		$tblist = func_getAllTbList($dbconf);
		if(empty($tblist)) return false;
		
		$conn = $this->connect( func_getDbSetting($dbconf) );
		$sql='OPTIMIZE TABLE `'.implode("`,`", $tblist).'`';
		
		if($query = mysqli_query($this->conn,$sql )){
			$this->sql[]=$sql;//记录执行语句
		}else{
			$mes = 'MySQL Query Error:'.$sql;
			func_throwException($mes);
		}
	}
	


	private $querySQL;
	private $queryParameters;
	
	private function stmtExec( $stmt, $stmtDebug=false ){
		if(!empty($this->queryParameters)){
			/* bind parameters for markers */
			$format = '';//绑定格式
			foreach( $this->queryParameters as $param ) $format.= $param['format'];
	
			$bind_items = array($format);//绑定数据,此处只能传递变量实体，不能直接使用变量值 => http://php.net/manual/en/mysqli-stmt.bind-param.php
			$i=0;
			$bind_name_pool = array();
			foreach( $this->queryParameters as $key=>$param ) {
				$mysqli_prepare_unique_bind_name = in_array("bind_{$param['name']}",$bind_name_pool)?"bind_{$param['name']}_{$i}":"bind_{$param['name']}";//防止UA/OR条件下重复命名
				$$mysqli_prepare_unique_bind_name = $param['value'];
				$bind_items[] = &$$mysqli_prepare_unique_bind_name;
	
				$bind_name_pool[]=$mysqli_prepare_unique_bind_name;
				$i++;
			}
	
			$bindResult = call_user_func_array(array($stmt,'bind_param'), $bind_items);//触发绑定
			//debug::d($bindResult);
		}
	
		/* execute query */
		$stmt->execute();
		if( $stmtDebug ) debug::displayValue($stmt);
	
		return $stmt;
	}
	
	//记录并清理SQL绑定数据
	private function stmtSqlClean(){
		$this->sql[] = $this->querySQL;
		$this->querynum++;
		
		$this->querySQL = '';
		$this->queryParameters = array();
	}
	
	private function readfields($key, $value){
		if(!isset($this->fields[$key]))func_throwException("Failed on mysql fields validate: {$key} !");
		$this->queryParameters[] = array("format"=>$this->fields[$key], "name"=>$key, "value"=>$value);
	
		return "?";
	}

	//挂起
	private function halt($message = '', $sql = ''){
		$out ="MySQL Query:$sql <br>";
		$out.="MySQL Error:".$this->conn->error." <br>";
		$out.="MySQL Error No:".$this->conn->errno." <br>";
		$out.="Message:$message";
		exit($out);
	}

	//日志
	private function log( $mes, $n ){
		$path = DOCUROOT."/data/logs/mysql";
		if(!is_dir($path)) files::mkdirs($path);
		file_put_contents( $path."/error_".$n.".log",$mes);
	}
}

?>