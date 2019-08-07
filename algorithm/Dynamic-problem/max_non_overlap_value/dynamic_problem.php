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
//核心为: 比较 前一个最优任务值 与 当前任务值+前一个可能最优任务值
class DynamicProblem{
	function __construct($array) {
		//给出的数组
		$this->array=$array;
		
		//每个任务的最优值
		$this->opt=[];
	}
	
	//获取前一种可能任务
	function get_possible($k){
		//时间不重合
		for($i=$k;$i>=0;$i--){
			if($this->array[$i]['endtime']<=$this->array[$k]['starttime']){
				return $i;
			}
		}
		
		return -1;
	}
	
	//递归方式求解
	function rec_opt($k){
		//数组第0个元素为本身
		if($k==0){
			return $this->array[$k]['value'];
		}
		//数组第1个元素为第0个与第1个最大值
		else if($k==1){
			return max($this->array[$k]['value'],$this->array[$k-1]['value']);
		}
		else{
			//1情况  不相邻的最优值与本身
			$possible_id=$this->get_possible($k);	//获取前一种可能任务
			
			if(!empty($this->array[$possible_id])){
				$current=$this->rec_opt($possible_id)+$this->array[$k]['value'];
			}else{
				$current=$this->array[$k]['value'];
			}
			
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
				$this->opt[]=$this->array[$k]['value'];
			}
			//数组第1个元素为第0个与第1个最大值
			else if($k==1){
				$this->opt[]=max($this->array[$k]['value'],$this->array[$k-1]['value']);
			}
			else{
				//1情况  不相邻的最优值与本身
				$possible_id=$this->get_possible($k);	//获取前一种可能任务
				if(!empty($this->array[$possible_id])){
					$current=$this->opt[$possible_id]+$this->array[$k]['value'];
				}else{
					$current=$this->array[$k]['value'];
				}
				
				//2情况	前一个最优值
				$prev=$this->opt[$k-1];
				
				$this->opt[]=max($current,$prev);
			}
		}
		
		return max($this->opt);
	}
}

//在不重合的时间内，选出最大value
$array=[
		['starttime'=>1,'endtime'=>4,'value'=>5],
		['starttime'=>3,'endtime'=>5,'value'=>1],
		['starttime'=>0,'endtime'=>6,'value'=>8],
		['starttime'=>4,'endtime'=>7,'value'=>4],
		['starttime'=>3,'endtime'=>8,'value'=>6],
		['starttime'=>5,'endtime'=>9,'value'=>3],
		['starttime'=>6,'endtime'=>10,'value'=>2],
		['starttime'=>8,'endtime'=>11,'value'=>4],
];	//13

$obj=new DynamicProblem($array);
//递归求解
$rec_rs=$obj->rec_opt(count($array)-1);
debug::d($rec_rs);

//循环求解
$for_rs=$obj->for_opt();
debug::d($for_rs);

debug::t();
exit;















































