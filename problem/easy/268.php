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

//求和在一个一个相减，剩下的就是缺的
class problem_268{
	function __construct($array) {
		//给出的数组
		$this->array=$array;
	}
	
	function start(){
		$max_array=max($this->array);
		$max_value=($max_array*($max_array+1))/2;
		
		foreach($this->array as $v){
			$max_value-=$v;
		}
		
		//丢失两边的数字
		if($max_value==0){
			if(min($this->array)==0){
				//丢失最后一个数字
				return max($this->array)+1;
			}else{
				//丢失第一个数字
				return min($this->array)-1;
			}
		}else{
			return $max_value;
		}
	}
}

//数组中间缺了一个数字，找出来，也可能两边缺数字
//$array=[2,1,0,4];	//3
//$array=[0];			//1
//$array=[0,1];		//2
$array=[1,2];		//0

$obj=new problem_268($array);
$rs=$obj->start();
debug::d($rs);
debug::t();





































