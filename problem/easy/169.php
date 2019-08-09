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

class problem_169{
	function __construct($array) {
		//给出的数组
		$this->array=$array;
		
		//数组长度
		$this->size=count($array)-1;
	}
	
	function start(){
		$pool=[];
		foreach($this->array as $k=>$v){
			//栈为空+1
			if(empty($pool)){
				$pool[]=$v;
			}
			//栈底与数组元素相同+1
			else if($pool[0]==$v){
				$pool[]=$v;
			}
			//其他情况-1
			else{
				array_pop($pool);
			}
		}
		
		return $pool[0];
	}
}

//数组中，求出出现频率最多数字，大于2/1数组长度
$array=[2,1,1,1,1,3,4];		//1
$obj=new problem_169($array);

$rs=$obj->start();
debug::d($rs);
debug::t();





































