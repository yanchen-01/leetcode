<?php
/**
 * mssql防止预期外注入的措施：
 *
 * 简单说就是不要给用户传入特殊操作符的机会。
 * 1，所有使用框架中数组定义的方式进行的查询都是安全的
 * 2，使用用户输入的数据拼接SQL时，对数字类型的参数要使用Type Casting:(int)||(float)进行类型验证，对字符类型的参数要使用dbtools::escape进行过滤，并在SQL中使用引号包括用户输入的数据
 * 3，使用框架或自己接拼接SQL尽量不要引用用户数据生成表名，如一定需要这样做，程序员要严格限定输入的参数格式
 *
 * @author weiqiwang
 */
class MsSQL
{
    public $conn;//当前链接
  public $silent = false;//是否不抛出异常信息
  public $mssqlMasterSlave=false; //是否使用mssql数据库主从结构

  public $master;//主库,可读写
  public $slave;//从库，只读

  public $fields = array(); //当前数据对象的结构定义,随数据对象切换改变
  public $config=array();//当前链接的配置
  public $sql=array();//累计执行的sql语句
  public $querynum=0;//累计查询的数量
  private $prevStmt;
    public function init()
    {
        if (!$this->mssqlMasterSlave) {
            //独立库
            $this->conn = $this->connect($this->config);
        } else {
            //主从库，自动打开到从库链接
            $this->conn = $this->slave = $this->connect($this->config['slave']);
        }
    }
    /**
     * 使用mssqli进行连接
     * @return pdo connection
     */
    public function connect($config)
    {
        try {
            $conn= new \PDO(sprintf("dblib:host=%s;dbname=%s;charset=%s", $config['server'], $config['database'], $config['charset']), $config['user'], $config['password']);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            func_throwException("Failed to connect to MSSQL: (" . $e->getMessage() . ") ");
        }
        if (empty(dbtools::$conn)|| $this->ping(dbtools::$conn)) {
            dbtools::$conn = $conn;
        }
        return $conn;
    }
    public function ping($conn)
    {
        try {
            $conn->query('SELECT 1');
        } catch (PDOException $e) {
            return false;           // Don't catch exception here, so that re-connect fail will throw exception
        }
        return true;
    }

    /**
     * 执行sql语句
     * @param string sql语句
     * @param string 默认为空，可选值为 CACHE UNBUFFERED
     * @param int Cache以秒为单位的生命周期
     * @return resource
     */
    public function query($sql)
    {
        if (!$this->mssqlMasterSlave) {
            //对于使用mssql-proxy实现主从结构或没有主从结构的情况直接调用主库即可
            if (empty($this->conn)) {
                $this->conn=$this->connect($this->config);
            }
        } else {
            //根据查询类型选择使用主库还是从库
            if (strtolower(substr(trim($sql), 0, 6))=='select') {
                $this->conn = $this->slave;
            } else {
                if (empty($this->master)) {
                    if ($this->config['master']['server'] == $this->config['slave']['server']) {
                        $this->master = $this->slave;
                    } else {
                        $this->master = $this->connect($this->config['master']);
                    }
                }
                $this->conn = $this->master;
            }
        }

        //执行数据内容操作
        if (substr($sql, -1)!=';') {
            $sql = $sql.';';
        }
        if (!($result = $this->conn->query($sql)) && !$this->silent) {
            $mes = 'MSSQL Query Error:'.$sql;
            func_throwException($mes);
        } else {
            $this->sql[]=$sql;//记录执行语句
        }

        $this->querynum++;
        return $result->fetchAll(PDO::FETCH_ASSOC);
    }
    /**
     * 执行sql语句，快捷方法
     * @return int or bool
     */
    public function exec($sql)
    {
        if (!$this->mssqlMasterSlave) {
            //对于使用mssql-proxy实现主从结构或没有主从结构的情况直接调用主库即可
            if (empty($this->conn)) {
                $this->conn=$this->connect($this->config);
            }
        } else {
            //根据查询类型选择使用主库还是从库
            if (strtolower(substr(trim($sql), 0, 6))=='select') {
                $this->conn = $this->slave;
            } else {
                if (empty($this->master)) {
                    if ($this->config['master']['server'] == $this->config['slave']['server']) {
                        $this->master = $this->slave;
                    } else {
                        $this->master = $this->connect($this->config['master']);
                    }
                }
                $this->conn = $this->master;
            }
        }
        return $this->conn->exec($sql);
    }

    /**
     *
     * 执行sql语句，返回记录集行数
     * @param string $sql
     * @return number
     */
    public function rows($sql)
    {
        $result = $this->query($sql);
        return count($result);
    }

    /**
     * 执行sql语句，只得到一条记录
     * @return array
     */
    public function getOne($fields, $where = null, $table=null, $order=null)
    {
        $result=$this->getAll($fields, $where, $table, $order, 1);
        if (!empty($result)) {
            foreach ($result as $rs) {
                return $rs;
                break;
            }
        }
    }


    /**
     * 执行sql语句，得到所有的记录
     * @return array
     */
    public function getAll($fields, $where = null, $table=null, $order=null, $limit=null)
    {
        if (empty($fields)) {
            return false;
        }
        /* SQL绑定分析 */
        $this->prepareSQL($fields, $table, $where, $order, $limit);

        /* prepare SQL binding */
        if (!$stmt = $this->conn->prepare($this->querySQL)) {
            func_throwException("Failed to prepare Statement");
        }

        /* bind variables and get result*/
        $stmt = $this->stmtExec($stmt);
        // $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        /* clean up */
        // $stmt->closeCursor();
        // $this->stmtSqlClean();

        $list = array();
        // if (!empty($result)) {
        $i=0;
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $i++;
            $list[] = $row;
            //为防止取出过多的查询结果导致内存溢出，此处对超过100条输出的结果做内存监控
            if ($i>100) {
                //默认查询数据库的内存上限为16M，可以通过设置SqlMaxMemorySize调整
                static $maxMemory;
                if (empty($maxMemory)) {
                    $maxMemory = defined("SqlMaxMemorySize")?SqlMaxMemorySize:16777216;
                }

                $memory_usage=memory_get_usage();
                if ($memory_usage>$maxMemory) {
                    func_throwException("数据库存查询结果过大，导致内存溢出!");
                }
            }
        }
        // }
        $stmt->closeCursor();
        $this->stmtSqlClean();

        return $list;
    }
    public function prepareSQL($condition, $table, $where, $order=null, $limit=null)
    {
        $sql="";
        if ($table!=null) {
            //处理where内部常量
            if ($ualist = isset($where['UA'])?$where['UA']:false) {
                unset($where['UA']);
            }
            if ($order = isset($where['order'])?$where['order']:$order) {
                unset($where['order']);
            }
            if ($limit = isset($where['limit'])?$where['limit']:$limit) {
                unset($where['limit']);
            }

            $tmp_order=$this->parseOrder($order);

            //debug::D('limit0');
            //debug::D($limit);
            $sql="SELECT ". $this->parseSelect($condition, $limit, $tmp_order) ." FROM [{$table}] ".$sql;

            //debug::D($sql);
            //where
            $whereSQL = $this->parseWhere($where);
            if (!empty($whereSQL)) {
                $sql.= "WHERE " . $whereSQL;
            }
            //debug::D($whereSQL);

            //union all
            if (!empty($ualist)) {
                $sql = $this->parseUnionAll($sql, $whereSQL, $ualist);
            }


            //order
            $sql.=$tmp_order;

            //select & table

            //limit
            $sql.= $this->parseLimit($limit, $tmp_order);
        } else {
            $sql=$condition; //直接使用SQL
        }

        $this->querySQL = $sql;
    }
    private function parseSelect($condition, $limit, $order)
    {

        //limit, if limit is array, build a key to order by, else use top
        $sql='';
        if (!empty($limit)) {
            if (is_numeric($limit)) {
                $sql=" TOP ".$limit." ";
            }
            if (is_array($limit)&&(empty($order))) {
                $sql=" 0 as TempSort, ";
            }
        }
        if (is_string($condition)) {
            return $sql.$condition;
        }
        $list=array();
        foreach ($condition as $key=>$val) {
            $selectField = $this->paresSelectItem($key, $val);
            if (empty($selectField)) {
                continue;
            }
            $list[] = $selectField;
        }
        return $sql.implode(', ', $list);
    }

    private function paresSelectItem($key, $val)
    {
        if ($key==="SQL") {
            return empty($val)?"":$val;
        }
        return "[".$val."]";
    }

    /**
     * 分析查询条件
     *
     * @param str||arr $condition
     * @return string where
     */
    private function parseWhere($condition)
    {
        if (empty($condition)) {
            return "";
        }
        if (is_string($condition)) {
            return $condition;
        }

        //以下两项用于直接调用parseWhere的代码时使用
        if ($order = isset($condition['order'])?$condition['order']:false) {
            unset($condition['order']);
        }
        if ($limit = isset($condition['limit'])?$condition['limit']:false) {
            unset($condition['limit']);
        }

        $sql='';
        $list=array();
        foreach ($condition as $key=>$val) {
            $tmp=$this->paresWhereItem($key, $val);
            // debug::D($tmp);
            if (!empty($tmp)) {
                $list[]=$tmp;
            }
        }

        $sql = implode(' AND ', $list);
        $tmp_order=$this->parseOrder($order);
        //order
        if ($order) {
            $sql.= $tmp_order;
        }

        //limit
        if ($limit) {
            $sql.= $this->parseLimit($limit, $tmp_order);
        }

        return $sql;
    }

    /**
     * 处理复合条件
     *
     * @param string $key
     * @param array $val
     * @return string
     */
    private function paresWhereItem($key, $val)
    {
        //处理复合条件SQL
        if ($key=="SQL") {
            return empty($val)?"":"({$val})";
        }

        //处理复合条件OR
        if ($key=="OR") {
            $tmpSQL=array();
            foreach ($val as $k=>$v) {
                if (!isset($this->fields[$k])) {
                    continue;
                }
                foreach ($v as $item) {
                    $tmpSQL[]= "[{$k}]=".$this->readfields($k, $item)."";
                }
                break;//仅取一级
            }

            return empty($tmpSQL)?"":"(".implode(" OR ", $tmpSQL).")";
        }

        //处理其他单一条件
        if (strstr($key, ",")) {//非等条件,如 !=,>,<,>=,<= 等
          $arr=explode(",", $key);
            $key=$arr[0];
            $delimiter=$arr[1];
        } else {
            $delimiter="=";
        }
        $rf=$this->readfields($key, $val);
        $res="([{$key}]{$delimiter}".$rf.")";
        return $res;
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
    private function parseUnionAll($sql, $whereSQL, $ualist)
    {
        $sql.=(empty($whereSQL))?"WHERE ":" AND ";

        $tmpSQL=array();
        foreach ($ualist as $key=>$value) {
            if (!isset($this->fields[$key])) {
                continue;
            }
            foreach ($value as $val) {
                $tmpSQL[]= "({$sql}([{$key}]=".$this->readfields($key, $val)."))";
            }

            break;//仅取一级
        }

        //使用UNION ALL重新连接SQL语句
        return empty($tmpSQL)?"":implode(" UNION ALL ", $tmpSQL);
    }


    /**
     * 处理 Order
     *
     * @param array $condition
     * @return string $orderstr
     */
    private function parseOrder($condition)
    {
        if (empty($condition)||!is_array($condition)) {
            return "";
        }

        $tmp=array();
        foreach ($condition as $key=>$orderStr) {
            if (empty($key)) {
                if (!isset($this->fields[$orderStr])) {
                    continue;
                }
                $tmp[]=$orderStr;
            } else {
                if (!isset($this->fields[$key])) {
                    continue;
                }
                $orderStr = strtoupper($orderStr);
                if (!in_array($orderStr, array('ASC','DESC'))) {
                    continue;
                }

                $tmp[]="[".$key."] ".$orderStr;
            }
        }
        return empty($tmp)?"":" ORDER BY ". implode(" , ", $tmp);
    }
    /**
     * 处理Limit, Limit与mssql不同，使用Top方式
     *
     * @param int $condition=25  array $condition=array(from,cellnum)
     * @return str $limit
     */
    private function parseLimit($condition, $order)
    {
        if (empty($condition)) {
            return "";
        }

        $limit="";
        //if limit is numeric then top is already used
        if (is_numeric($condition)) {
            return $limit;
        }
        //if limit is array, use offset with fetch, add TempSort if order is empty
        if (is_array($condition)) {
            if (empty($order)) {
                $limit.=' Order By TempSort ';
            }
            $limit.=" OFFSET ".$condition[0]." ROWS FETCH NEXT ".($condition[1]-$condition[0])." ROWS ONLY";
        }

        return $limit;
    }

    //记数
    public function Count($where, $table)
    {
        if (!strstr($table, "[")) {
            $table = "[{$table}]";
        }
        $fields='count(*) as [n]';
        if (is_array($where)) {
            //去重查询
            if (isset($where['distinct'])&&isset($this->fields[$where['distinct']])) {
                $fields="count( DISTINCT [{$where['distinct']}] ) as [n]";
                unset($where['distinct']);
            }
        }

        $wherestr=empty($where)?"":" WHERE ".$this->parseWhere($where);
        $count=$this->getOne("SELECT {$fields} FROM {$table}{$wherestr}");
        return $count["n"];
    }

    private function parseData($data)
    {
        if (is_string($data)) {
            return $data;
        }

        $value = array();
        foreach ($data as $k => $v) {
            $dataItem = $this->parseDataItem($k, $v);
            if (!empty($dataItem)) {
                $value[] = $dataItem;
            }
        }

        return implode(', ', $value);
    }

    private function parseDataItem($key, $val)
    {
        if ($key=="SQL") {
            return empty($val)?"":$val;
        }
        return "[{$key}]=".$this->readfields($key, $val);
    }
    //执行
    public function Execute($sql)
    {
        return $this->exec($sql);
    }

    // 对单表进行优化 SQL Server have no such function
    // public function OptimizeTB($tbn)
    // {
    //     if (!empty($tbn)) {
    //         $this->exec("OPTIMIZE TABLE [{$tbn}]");
    //     }
    // }

    //删除
    public function Remove($where, $table)
    {
        if (!strstr($table, "[")) {
            $table = "[{$table}]";
        }

        /* prepare SQL binding */
        $this->querySQL = "DELETE FROM {$table} WHERE ". $this->parseWhere($where);
        if (!$stmt = $this->conn->prepare($this->querySQL)) {
            func_throwException("Failed to prepare");
        }

        /* bind variables and get result*/
        $stmt = $this->stmtExec($stmt);
        $result = $stmt->rowCount();

        /* clean up */
        $stmt->closeCursor();
        $this->stmtSqlClean();

        return $result;
    }

    //对数据库中的全部表进行优化
    // public function OptimizeDB($dbconf)
    // {
    //     $tblist = func_getAllTbList($dbconf);
    //     if (empty($tblist)) {
    //         return false;
    //     }
    //
    //     $conn = $this->connect(func_getDbSetting($dbconf));
    //     $sql='OPTIMIZE TABLE ['.implode("],[", $tblist).']';
    //
    //     if ($query = $this->conn->exec($sql)) {
    //         $this->sql[]=$sql;//记录执行语句
    //     } else {
    //         $mes = 'MsSQL Query Error:'.$sql;
    //         func_throwException($mes);
    //     }
    // }
    //插入
    public function Insert($data, $table, $command='INSERT')
    {
        if (empty($table) && !is_array($data)) {
            return false;
        }
        if (!strstr($table, "[")) {
            $table = "[{$table}]";
        }

        $fields = array();
        $values = array();
        if (!empty($data["key"]) && !empty($data["valuelist"])) {//多行插入
            $fields = $data["key"];
            $values = $data["valuelist"];
        } else {//单行插入
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
            $fieldsArr[] = "[{$v}]";
            $valuesArr[] = "?";
        }
        $this->querySQL = "{$command} INTO {$table} ( " . implode(', ', $fieldsArr) . ") VALUES ( " . implode(', ', $valuesArr) . " )";
        /* prepare SQL binding */
        if (!$stmt = $this->conn->prepare($this->querySQL)) {
            func_throwException("Failed to prepare mssqli_stmt");
        }

        /* bind variables and get result*/
        $insertIDs = array();
        foreach ($values as $val) {
            $this->queryParameters = array();
            foreach ($val as $k => $v) {
                $this->readfields($fields[$k], $v);
            }
            $stmt = $this->stmtExec($stmt);
            $insertIDs[] = $this->conn->lastInsertId();
        }

        /* clean up */
        $stmt->closeCursor();
        $this->stmtSqlClean();

        if (empty($insertIDs)) {
            return false;
        }
        return count($insertIDs)>1?$insertIDs:$insertIDs[0];
    }

    //替换
    public function Replace($data, $table)
    {
        return $this->Insert($data, $table, 'INSERT');
    }

    //更新
    public function Update($data, $where, $table)
    {
        if (empty($table) || empty($data)) {
            return false;
        }
        if (!strstr($table, "[")) {
            $table = "[{$table}]";
        }

        /* prepare SQL binding */
        $this->querySQL = "UPDATE {$table} SET " . $this->parseData($data) . " WHERE " . $this->parseWhere($where);
        if (!$stmt = $this->conn->prepare($this->querySQL)) {
            func_throwException("Failed to prepare mssqli_stmt");
        }

        /* bind variables and get result*/
        $stmt = $this->stmtExec($stmt);
        $result = $stmt->rowCount();

        /* clean up */
        $stmt->closeCursor();
        $this->stmtSqlClean();

        return $result;
    }

    private $ruleMS=[
      'i'=>PDO::PARAM_INT,
      'd'=>PDO::PARAM_STR,
      'b'=>PDO::PARAM_LOB,
      's'=>PDO::PARAM_STR
    ];
    private $querySQL;
    private $queryParameters;

    private function stmtExec($stmt, $stmtDebug=false)
    {
        if (!empty($this->queryParameters)) {
            /* bind parameters for markers */
            $format = '';//绑定格式
            foreach ($this->queryParameters as $param) {
                $format.= $param['format'];
            }
            $bind_items=[];
            // $bind_items = array($format);//绑定数据,此处只能传递变量实体，不能直接使用变量值 => http://php.net/manual/en/mssqli-stmt.bind-param.php
            $i=0;
            $j=1;
            $bind_name_pool = array();
            foreach ($this->queryParameters as $key=>$param) {
                $mssqli_prepare_unique_bind_name = in_array("bind_{$param['name']}", $bind_name_pool)?"bind_{$param['name']}_{$i}":"bind_{$param['name']}";//防止UA/OR条件下重复命名
                $$mssqli_prepare_unique_bind_name = $param['value'];
                $bind_items[] = [
                  $j,
                  $param['format']=='d'?$param['value'].'':$param['value'],
                  $this->ruleMS[$param['format']]
                ];
                $bind_name_pool[]=$mssqli_prepare_unique_bind_name;
                $i++;
                $j++;
            }
            foreach ($bind_items as $item) {
                call_user_func_array(array($stmt,'bindValue'), $item);
            }
            //触发绑定
            //debug::d($bindResult);
        }
        $stmt->execute();
        if ($stmtDebug) {
            debug::displayValue($stmt);
        }
        return $stmt;
    }
    private function stmtSqlClean()
    {
        $this->sql[] = $this->querySQL;
        $this->querynum++;

        $this->querySQL = '';
        $this->queryParameters = array();
    }

    private function readfields($key, $value)
    {
        if (!isset($this->fields[$key])) {
            func_throwException("Failed on mssql fields validate: {$key} !");
        }
        $this->queryParameters[] = array("format"=>$this->fields[$key], "name"=>$key, "value"=>$value);

        return "?";
    }

    //挂起
    private function halt($message = '', $sql = '')
    {
        $out ="MsSQL Query:$sql <br>";
        $out.="MsSQL Error:".$this->conn->errorInfo." <br>";
        $out.="MsSQL Error No:".$this->conn->erroCode." <br>";
        $out.="Message:$message";
        exit($out);
    }

    //日志
    private function log($mes, $n)
    {
        $path = DOCUROOT."/data/logs/mssql";
        if (!is_dir($path)) {
            files::mkdirs($path);
        }
        file_put_contents($path."/error_".$n.".log", $mes);
    }
}
