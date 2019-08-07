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

//动态规划
//核心为: 比较 选择当前情况与选择后一种情况
class DynamicProblem{
	function __construct($array,$s) {
		//给出的数组
		$this->array=$array;
		
		//数组长度
		$this->size=count($array)-1;
	}
}



$array=[2,1,2,1,2,1,3,4,1,9,1,1,1];
$pool=[];
foreach($array as $k=>$v){
	if(empty($pool)){
		$pool[]=$v;
	}
	else if($pool[0]==$v){
		$pool[]=$v;
	}
	else{
		array_pop($pool);
	}
	debug::d($pool);
}






































