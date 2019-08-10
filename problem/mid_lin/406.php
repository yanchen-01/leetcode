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

//核心思想：  找到靠近的数组后，后指针为前指针，前指针+1
class problem_406{
	function __construct($array,$s) {
		//给出的数组
		$this->array=$array;
		
		//滑动窗口
		$this->window=[0];
		
		//记录指针
		$this->pool=[];
		
		//给出的最大值
		$this->s=$s;
		
		//后指针
		$this->b_index=0;
		
		//前指针
		$this->f_index=0;
	}
	
	function start(){
		foreach($this->array as $k=>$v){
			//在滑动窗口范围内
			if($this->window[$this->b_index]+$v<$this->s){
				$this->window[$this->b_index]+=$v;
				
				//前指针+1
				$this->f_index++;
			}
			//超出窗口
			else{
				//记录指针
				$this->pool[$this->b_index]=[$this->b_index,$this->f_index-1];
				
				//后指针为前指针
				$this->b_index=$this->f_index-1;
				
				//前指针+1
				$this->f_index++;
				
				//重新窗口
				$this->window[$this->b_index]=$this->array[$this->b_index]+$v;
			}
		}
		
		//获取最大值的key
		$max=array_keys($this->window, max($this->window));
		return $this->pool[$max[0]];
	}
	
}

//一个数组中，找出“最短”连续数组 最靠近一个数字
$array=[1,3,1,2,5,6,2];		//[5,6]
$s=11;

$obj=new problem_406($array,$s);
$rs=$obj->start();
debug::d($rs);
debug::t();





































