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
//核心为: 比较 前一个最优任务值 与 当前任务值+前一个"可能"最优任务值
class DynamicProblem{
	function __construct($array) {
		//给出的数组
		$this->array=$array;
		
		//数组长度
		$this->size=count($array);
		
		//优化结果
		$this->opt=[];
	}
	
	//获取前一种可能任务
	function get_possible($k){
		//不相邻数字
		return $k-2;
	}
	
	//递归方式求解
	function rec_opt($k){
		//递归出口
		if($k==0){
			//数组第0个元素返回 本身
			return $this->array[$k];
		}
		else if($k==1){
			//数组第1个元素返回 第0个与第1个最大值
			return max($this->array[$k-1],$this->array[$k]);
		}
		
		//比较两可能 1.本身+前一个可能最大值 2.前一个最大值
		else{
			//1情况  不相邻的最优值与本身
			$possible_id=$this->get_possible($k);	//获取前一种可能任务
			$current=$this->rec_opt($possible_id)+$this->array[$k];
			
			//2情况	前一个最优值
			$prev=$this->rec_opt($k-1);
			
			return max($current,$prev);
		}
	}
	
	//循环方式求解
	function for_opt(){
		foreach($this->array as $k=>$v){
			//数组第0个元素为本身
			if($k==0){
				$this->opt[$k]=$this->array[$k];
			}
			//数组第1个元素为第0个与第1个最大值
			else if($k==1){
				$this->opt[$k]=max($this->array[$k],$this->array[$k-1]);
			}
			else{
				//1情况  不相邻的最优值与本身
				$possible_id=$this->get_possible($k);	//获取前一种可能任务
				$current=$this->opt[$possible_id]+$this->array[$k];
				
				//2情况	前一个最优值
				$prev=$this->opt[$k-1];
				
				$this->opt[$k]=max($current,$prev);
			}
		}
		
		return max($this->opt);
	}
}

//选出不相邻数字的最大和
$array=[1,2,4,1,7,8,3];	//15
$obj=new DynamicProblem($array);

//递归求解
$rec_rs=$obj->rec_opt(count($array)-1);
debug::d($rec_rs);

//循环求解
$for_rs=$obj->for_opt();
debug::d($for_rs);
debug::t();
exit;















































