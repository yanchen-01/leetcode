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

//hashtable
class problem_136{
	function __construct($array) {
		//给出的数组
		$this->array=$array;
	}
	
	function start(){
		$pool=[];
		foreach($this->array as $k=>$v){
			if(empty($pool[$v])){
				$pool[$v]=1;
			}else{
				$pool[$v]++;
			}
		}
		
		return array_keys($pool, min($pool))[0];
	}
	
}

//数组中两个数为一对，找出缺失的那一对
$array=[4,1,2,1,2];		//4

$obj=new problem_136($array);
$rs=$obj->start();
debug::d($rs);
debug::t();





































