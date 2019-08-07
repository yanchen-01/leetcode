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
		
		//剩下的数字
		$this->s=$s;
		
		//数组长度
		$this->size=count($array)-1;
		
		//优化结果
		$this->opt=[];
	}
	
	//递归方式求解
	function rec_opt($k,$s){
		//递归出口
		if($s==0){
			//s已经相减为0了
			return true;
		}
		else if($k==0){
			//最后一个元素 恰好与给出的数字相同
			return $this->array[$k]==$s;
		}
		else if($this->array[$k]>$s){
			//数组元素比给出的数字大，跳过
			return $this->rec_opt($k-1,$s);
		}
		
		//两种分支
		else{
			//1情况，选择当前数组数字，给出的数字减
			$case_a=$this->rec_opt($k-1,$s-$this->array[$k]);
			
			//2情况，选择当前数组数字
			$case_b=$this->rec_opt($k-1,$s);
			
			return ($case_a || $case_b);
		}
	}
}

//下列数组 是否有相加的和 为另一个数字的可能
$array=[3,34,4,12,5,2];
$s=9;		//true
//$s=10;	//true
//$s=11;	//true
//$s=12;	//true
//$s=13;	//false

$obj=new DynamicProblem($array,$s);

//递归求解
$rec_rs=$obj->rec_opt(count($array)-1,$s);
debug::d($rec_rs);
debug::t();
exit;















































