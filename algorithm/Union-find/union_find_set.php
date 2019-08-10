<?php
class debug {
	static public function d($arr, $debug = false){
		debug::displayValue($arr, $debug = false);
	}
	static public function displayValue($arr, $debug = false) {
		echo ("<pre>");
		print_r($arr);
		echo ("</pre>");
		if ($debug) {
			exit;
		}
	}
	
	static public function t(){
		$time=getrusage();
		echo "user time: ".$time['ru_utime.tv_usec']. "<br>";
		echo "syst time: ".$time['ru_stime.tv_usec'];
	}
}

//并查集
class DisjointSet{
	function __construct($array) {
		//给出的数组
		$this->array=$array;
		
		//总元素个数
		$this->size=count($array);
		
		//相连元素的高度
		$this->rank=[];
		
		//树
		$this->tree=[];
		
		//赋值树 初始根全部为-1
		foreach($array as $v){
			foreach($v as $vv){
				if(empty($this->tree[$vv])){
					$this->tree[$vv]=-1;
					$this->rank[$vv]=0;
				}
			}
		}
	}
	
	//找出跟节点
	function find_root($value){
		$root=$value;
		
		//如果元素在树中指向的不是-1，那么循环找出-1，并重新复制父节点给此元素
		while($this->tree[$root]!=-1){
			$root=$this->tree[$root];
		}
		
		return $root;
	}
	
	
	//合并数组
	function union_set($x,$y){
		//找出根节点
		$root_x=$this->find_root($x);
		$root_y=$this->find_root($y);
		
		//跟节点相同，为一个环
		if($root_x==$root_y){
			return false;
		}
		else{
			//矮的树接在高的树上，并指向高的树
			if($this->rank[$root_x]<$this->rank[$root_y]){
				$this->tree[$root_x]=$root_y;
			}
			else if($this->rank[$root_y]<$this->rank[$root_x]){
				$this->tree[$root_y]=$root_x;
			}
			//等级相同，x接到y上面，并且y高度+1
			else if($this->rank[$root_x]==$this->rank[$root_y]){
				$this->tree[$root_x]=$root_y;
				$this->rank[$root_y]++;
			}
		}
		
		return true;
	}
	
	//检查圈
	function check_circle(){
		foreach($this->array as $v){
			if(!$this->union_set($v[0],$v[1])){
				return $v;
			}
		}
		
		return "No circle";
	}
}

//当数组前后数字连成一个环了，返回第一个出现环的数组
//$array=[[0,1],[1,2],[1,3],[2,4],[3,4],[2,5]];		//[3,4]
$array=[[1,2], [2,3], [3,4], [1,4], [1,5]]; 		//[1,4]
//$array=[[1,2], [1,3], [2,3]];						//[2,3]

$obj=new DisjointSet($array);

$rs=$obj->check_circle($obj->array);
debug::d($rs);
debug::t();
exit;


















































