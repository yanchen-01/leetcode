<?php
/**
* 使用elasticsearch实现搜索引擎功能
*
* 相同：
* 使用lucene内核，支持分布式部署，中文查询，可以使用php_curl内建查询接口
*
* 差异：
* elasticsearch 2.0: 不能动态修改主分片
* solr 5.0: 不支持结构化文档索引和查询，分片不能自动做负载平衡
*
* 文档参见
* http://www.elasticsearch.com/docs/elasticsearch/rest_api/
*/
class Search {

	public $search_host = "";
	public $search_port ="";
	public $showquery = false;

	protected $index;
	protected $type;
	
	function __construct(){
		$this->search_host = defined('SEARCH_NODE_HOST')?SEARCH_NODE_HOST:'127.0.0.1';
		$this->search_port = defined('SEARCH_NODE_PORT')?SEARCH_NODE_PORT:'9200';
		
		//开启web端查询语句调试
		if(debug::check("showquery")) 
			$this->showquery=true;
	}
	
	function curl($url, $data=null, $method="GET"){
		$baseUri = "http://{$this->search_host}:{$this->search_port}{$url}";
		
		$ci = curl_init();
	
		curl_setopt( $ci, CURLOPT_URL, $baseUri );
		curl_setopt( $ci, CURLOPT_PORT, $this->search_port );
		curl_setopt( $ci, CURLOPT_TIMEOUT, 200 );
		curl_setopt( $ci, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt( $ci, CURLOPT_FORBID_REUSE, 0 );
		curl_setopt($ci, CURLOPT_HTTPHEADER, array('Content-Type: application/json','Connection: Keep-Alive'));

	
		curl_setopt( $ci, CURLOPT_CUSTOMREQUEST, $method );
		if(!empty($data)) curl_setopt( $ci, CURLOPT_POSTFIELDS, json_encode($data) );
		if($this->showquery) debug::d(json_encode($data));
			
		$json =  curl_exec($ci);
		return  json_decode($json);
	}

	//分页
	public $page=array(
			'param1'=>'page',
			'param2'=>'pagenum',
			'group'=>5,
			'size'=>15, //默认每个分页的文档数量
			'now'=>0,//当前页面
			'all'=>0,
			'max'=>100
	);
	
	//分页结果
	public $pageNavInfo = array();
	
	//用户指定的分页模板
	public $pageNavTpl='';
	
	//搜索结果的分组信息, 与aggregations在一起生效
	public $groupInfo = array();
	
	/**
	 * 文档搜索接口
	 * https://www.elastic.co/guide/en/elasticsearch/reference/current/search-request-body.html
	 */
	function search( $data, $index=null, $type=null){
		$index = empty($index)? $this->index : $index;
		$type = empty($type)? $this->type : $type;
		$this->page['size'] = isset($_GET[$this->page['param2']])?intval($_GET[$this->page['param2']]):$this->page['size'];
		
		//获得当前页码
		if(isset($_GET[$this->page['param1']])) $this->page['now'] = intval($_GET[$this->page['param1']]);
		//针对搜索翻页，最多支持到100页即可，更多的页面内容除了增加系统消耗外，没有意义
		if( $this->page['now'] > $this->page['max']) $this->page['now']=$this->page['max'];
		//设置起始记录点
		$pagination = $this->page['size']*$this->page['now'];
		//es搜索请求链接
		$url = "/{$index}/$type/_search?size={$this->page['size']}&from={$pagination}";

		//处理搜索条件
		$data = $this->prequery($data);
		
		$result = array();
		$tmpobj = $this->curl($url,$data,"GET");
		//debug::d($tmpobj);
		
		if( isset($tmpobj->hits->total) ) $this->page['all'] = $tmpobj->hits->total;//记录总数
		if( isset($tmpobj->aggregations) ) $this->groupInfo = $tmpobj->aggregations; //记录搜索分组结果
		
		//结构化搜索结果
		if( !empty($tmpobj->hits->hits) ) {
			$resultObj = $tmpobj->hits->hits;
			if(is_array($resultObj)){
				foreach($resultObj as $item){
					$val = $item->_source; //标准内容
					if( isset($item->highlight) ) $val->__highlight = $item->highlight; //加载高亮结果
					$val->__index_id = $item->_id; //唯一ID
					
					$result[] = $val;
				}
			}
		}
		
		if(!empty($result)){
			$this->pageNavInfo = $this->__getNavData();
			$this->pageNavTpl = $this->__getNavTpl();
		}
		
		return $result;
	}
	
	/**
	 * 对查询条件进行预处理
	 * 
	 * 参见：
	 * https://www.elastic.co/guide/en/elasticsearch/reference/current/query-filter-context.html
	 * https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-bool-query.html
	 * 
	 下面是返回的标准$query
	 $query = array(
			'query'=>array(
				"bool" => array(
					//==简易写法的query参数, 数组内容部分 START======================================>>>>>>

					//可以匹配的字段	
					"should"=>array(
							Search::object(  array("match" => array("title"=>$_GET['q'])) ),
							Search::object(  array("match" => array("archive"=>'2011-12')) ), //对于有多项条件并列的情况，需要使用object对象封装数组
					),
						
					//必须匹配的字段，相关字段参与评分
					"must" =>array(
						"term" =>array('userid'=>612264), //对于只有单项条件的情况，直接使用数组即可
					),
					
					//必须匹配的字段，但相关字段不参与评分
					"filter"=>array(
						"term" =>array( 'userid'=>612264 )
					),
					
					//去掉满足这些条件的文档
					//https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-range-query.html
					"must_not" =>array(
						"range" =>array(
							"age" =>array( "gte" => 10, "lt" => 20, "boost"=>2.0 )
						)
					),
						
					"minimum_should_match" => 1,
					"boost" => 1.0
					
					//==简易写法的query参数, 数组内容部分 END======================================>>>>>>
				)
			),
			
			// 高亮设置参见
			// https://www.elastic.co/guide/en/elasticsearch/reference/current/search-request-highlighting.html
			"highlight" => array(
		        "pre_tags" => array( "<tag1>" ),
		        "post_tags" => array( "</tag1>" ),
		        "fields" => array(
		            "title" : Search::object(array( ... )), 
		            // title的值可以留空或可设置的选项： 
		            //{"type" : "plain"， "force_source" : true，"index_options" => "offsets"，"term_vector" : "with_positions_offsets"}
		        ),
		   ),
		   
		   // 对搜索结果按指定条件分组
	       // https://www.elastic.co/guide/en/elasticsearch/reference/current/search-aggregations.html
		   "aggs"=>array(
				//按标签分组：
				//https://www.elastic.co/guide/en/elasticsearch/reference/current/search-aggregations-bucket-children-aggregation.html
	// 			"my-tags"=>array(
	// 					"terms"=>array(
	// 							'field'=>'tags', //进行分组的字段
	// 							'size'=>10 //指定返回的最大分组数量
	// 					),
	// 			),
				
				//自动生成时间区段，并按时间区段对搜索结果进行分组
				//https://www.elastic.co/guide/en/elasticsearch/reference/current/search-aggregations-bucket-datehistogram-aggregation.html
	// 			"daytime" =>array(
	// 				"date_histogram" =>array(
	// 					"field" => "dateline",
	// 					"interval" => "1d",
	// 					"format" => "yyyy-MM-dd",
	// 				)
	// 			),
				
				//指定分组时间范围，并按范围区间对搜索结果进行分组
				//https://www.elastic.co/guide/en/elasticsearch/reference/current/search-aggregations-bucket-daterange-aggregation.html
	// 			"date_ranges"=>array(
	// 				"range"=>array(
	// 					"field"=> "dateline",
	// 					"format"=> "yyyy-MM-dd",
	// 					"ranges"=> array(
	// 						array("key"=>"早","to"=> "2011-12-12") ,
	// 						array("key"=>"中","from"=> "2011-12-02"),
	// 						array("key"=>"午","from"=> "2011-12-08","to"=>"2011-12-16")
	// 					)
	// 				)
	// 			),
				
				//指定数字范围，并按范围区间对搜索结果进行分组
				//https://www.elastic.co/guide/en/elasticsearch/reference/current/search-aggregations-bucket-range-aggregation.html
	// 			"price_ranges" =>array(
	// 				"range" =>array(
	// 					"field" => "price",
	// 					"ranges" =>array(
	// 						array( "key"=>"小于50","to" => 50 ),
	// 						array( "from" => 50, "to" => 100 ),
	// 						array( "from" => 100 )
	// 					)
	// 				)
	// 			)，
	
				//自动生成数字范围，并按范围区间对搜索结果进行分组
				//https://www.elastic.co/guide/en/elasticsearch/reference/current/search-aggregations-bucket-histogram-aggregation.html
	// 			"prices" =>array(
	// 				"histogram" =>array(
	// 					"field" => "price",
	// 					"interval" => 50, //指定每50为一个分组区间
	// 					"extended_bounds" =>array(
	// 						"min" => 1, //指定分组成员最少要有一个
	// 						"max" => 500 //指定分组成员最多不能超过500
	// 					),
	// 					"order" =>array( "_key" => "desc")//设置排序
	// 				)
	// 			)
			),
			
			// 对搜索结果进行排序
			// https://www.elastic.co/guide/en/elasticsearch/reference/current/search-request-sort.html
			"sort" => array(
		          "offer.price" =>array (
		             "mode" =>  "avg",
		             "order" => "asc",
		             "nested_path" => "offer",
		             "nested_filter" => array(
		                "term" =>array( "offer.color" => "blue" )
		             )
		          )
		    )
		);

	 * @param array $query  简易写法的query参数, 具体参见/search/demo/test.php
	 */
	private function prequery($query){
		$methods = array('should','must','must_not','filter');
		$checkmark = false;
		foreach( $methods as $method ){
			if(isset($query[$method])) {
				$checkmark = true;
				break;
			}
		}
		if(!$checkmark) return $query;//对没有使用复合查询条件的直接返回
		
		//创建基本查询结构
		// if( !isset($query['minimum_should_match']) ) $query['minimum_should_match'] = 1;
		// if( !isset($query['boost']) ) $query['boost'] = 1.0;
		
		$result = array(
			'query'=>array(
				"bool" => array(
						//will insert query here....
				)
			)
		);
		
		//提取各分类设置
		$action = array('highlight','aggs','sort');
		foreach( $action as $act ){
			if( !isset($query[$act]) ) continue;
			
			$result[$act] = $query[$act];
			unset($query[$act]);
		}
		
	    //设置基本查询参数
		foreach($query as $key=>$val) $result['query']['bool'][$key]=$val;
		
		if($this->showquery) debug::d($result);
		
		return $result;
	}
	
	private function __getNavData(){
		//导航数组
		$list=array();
		$numAll=empty($this->page['all'])?0:$this->page['all'];
		$pageSize=$this->page['size'];
		$pageGroup=$this->page['group'];
	
		$pageNow=empty($this->page['now'])?0:$this->page['now'];
		$pageAll = ceil($numAll/$pageSize);
	
		if ( $numAll > 0 && $pageNow<=$pageAll ){
			if($pageNow<0){$pageNow=0;}
				
			//当前页面组号，0为第一组
			$groupNow=ceil(($pageNow+1)/$pageGroup-1);
				
			$list["current"]=$pageNow+1;
			$list["totalpage"]=$pageAll;
			$list["per"]=$pageSize;
	
			$start=$pageNow*$pageSize;
			$list["first"] = $start + 1;
			$list["last"] = min($start+$pageSize, $pageAll);
	
			$list["group"]=$pageGroup;
			$list["total"]=$numAll;
	
	
			//prev group
			if ($groupNow>=1){
				$list["prevgroup"]=$this->__getLink($groupNow*$pageGroup-1);
			}else{
				$list["prevgroup"]="";
			}
	
			//prev page
			if($pageNow+1>1){
				$list["prevpage"]=$this->__getLink($pageNow-1);
			}else{
				$list["prevpage"]="";
			}
	
			//each page
			$listpage=array();
			for($i_page=($groupNow*$pageGroup+1);$i_page<=(($groupNow+1)*$pageGroup);$i_page++){
				if ($i_page>($pageAll)||$i_page>$this->page['max']) {
					break;
				}
				if ($i_page==($pageNow+1)){
					$listpage[]=array($i_page,"");
				}else{
					$listpage[]=array($i_page,$this->__getLink($i_page-1));
				}
			}
			$list["eachpage"]=$listpage;
	
			//next page
			if( $pageNow+1<$pageAll && $pageNow+1<$this->page['max'] ){
				$list["nextpage"]=$this->__getLink($pageNow+1);
			}else{
				$list["nextpage"]="";
			}
			//next group
			if ($i_page <= $pageAll && $i_page <= $this->page['max'] ){
				$list["nextgroup"]=$this->__getLink(($groupNow+1)*$pageGroup);
			}else{
				$list["nextgroup"]="";
			}
	
			//for page index
			$list["firstpage"]="";
			$list["lastpage"]="";
		}
		
		return $list;
	}
	
	private function __getNavTpl(){
		if(!empty($this->pageNavTpl)){
			$navtpl = $this->pageNavTpl;
		}else{
			$navtpl = DOCUROOT.'/include/template/searchnav/'.conf("global",'system.language').'.html';
			if(!is_file($navtpl)){
				$navtpl = DOCUROOT.'/include/template/searchnav/'.conf("global",'system.language').'.html';
			}
		}
		
		return $navtpl;
	}
	
	private function __getLink($pn){
		//基础地址
		static $url;
	
		if(!isset($url)){
			$url=$_SERVER['SCRIPT_NAME'];
			if( isset($_GET[$this->page['param1']]) ) unset($_GET[$this->page['param1']]);
	
			$query='';
			foreach($_GET as $key=>$val){
				$query.=empty($query)?'?':'&';
				$query.="{$key}={$val}";
			}
				
			$url.=$query.'&'.$this->page['param1'].'=';
		}
	
		$result = $url . $pn;
	
		return $result;
	}
	
	/*
	 * 内置通信函数，用于与搜索引擎直接通信
	 */
	protected function call( $url, $data, $method="GET" ){
		if(empty($this->index)) return array('error'=>"empty index!");
		if(empty($this->type)) return array('error'=>"empty type!");

		return $this->curl($url, $data, $method);
	}
	

	/*
	 * 索引文档
	 * 参见 https://www.elastic.co/guide/en/elasticsearch/reference/current/docs-index_.html
	 *
	 * $data = array(
		 "user" =>"kimchy",
		 "post_date" => "2009-11-15T14:12:12",
		 "message" => "trying out Elasticsearch"
		 ); //文档数据，支持多级数组
		 
	  $id 可以指定文档ID或让系统自动生成	 
	 */
	function add($data,$id=''){
		return $this->call( "/{$this->index}/{$this->type}/{$id}", $data, "PUT" );
	}
	
	function update($data,$id){
		$doc = array("doc"=>$data);
		return $this->call( "/{$this->index}/{$this->type}/{$id}/_update", $doc, "POST" );
	}
	
	function del($id){
		return $this->call( "/{$this->index}/{$this->type}/{$id}", array(), 'DELETE' );
	}
	
	//获取单一或多个文档,$id可以是单一字串或是数组
	function get($id,$source=true){
		$data = array();
		if(is_array($id)){
			$url = "/{$this->index}/{$this->type}/_mget";
			foreach($id as $docid){
				$data[]=array(
						"_id"=> $docid,
            			"_source"=>$source
				);
			}
		}else{
			$url = "/{$this->index}/{$this->type}/{$id}";
			if($source) $url = $url."/_source";
		}
		
		return $this->call( $url, $data );
	}
	
	//设置索引 
	//https://www.elastic.co/guide/en/elasticsearch/reference/current/indices-create-index.html
	function indexset($data){
		return $this->call( "/{$this->index}/", $data, "PUT" );
	}
	
	//https://www.elastic.co/guide/en/elasticsearch/reference/current/indices-delete-index.html
	function indexdel(){
		return $this->call( "/{$this->index}/", array(), 'DELETE' );
	}
	
	//https://www.elastic.co/guide/en/elasticsearch/reference/current/indices-create-index.html
	function indexmapping($data){
		return $this->call( "/{$this->index}", $data, "POST" );
	}
	
	/**
	 * 返回中文分词结果
	 * 参考网址：https://www.elastic.co/guide/en/elasticsearch/reference/current/indices-analyze.html
	 * 
	 * @param string $text
	 * @return mixed
	 */
	function analyzer($text){
		return $this->curl("/_analyze",array('analyzer'=>'ik','text'=>$text));
	}
	
	//将数组转换成对象
	static function object($array=null) {
		$object = new stdClass();
		
		if( $array===null ) return $object;
	    if (!is_array($array)) return $array;
	    
        foreach ($array as $name=>$value) {
            $name = trim($name);
            if (!empty($name)) $object->$name = self::object($value);
        }
        return $object; 
	}
	
	//将时间字串转成标准lucene时间格式
	static function time($timestr=null){
		if(empty($timestr)) $timestr = date("Y-m-d H:i:s",times::getTime());
		return str_replace(" ", "T", $timestr);
	}
	
	protected function debug($msg){
		echo "{$msg}\n";
		flush();
	}
}

//将数据转换成对象
class searchObject{
	function __construct($config=null){
		if(!empty($config)){
			foreach($config as $key=>$val) $this->$key=$val;
		}
	}
}