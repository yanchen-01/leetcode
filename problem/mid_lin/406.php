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
		
		//记录长度
		$this->length=[];
		
		//给出的最大值
		$this->s=$s;
		
		//后指针
		$this->b_index=0;
		
		//前指针
		$this->f_index=0;
		
		//最大值key
		$this->maxValue_key=[];
		
		//最短数组key
		$this->minLength_key=[];
	}
	
	function start(){
		foreach($this->array as $k=>$v){
			//在滑动窗口范围内
			if($this->window[$this->b_index]+$v < $this->s){
				//重新窗口
				$this->window[$this->b_index]+=$v;
				
				//记录指针
				$this->pool[$this->b_index]=[$this->b_index,$this->f_index];
			}
			//超出窗口
			else{
				//记录长度
				$this->length[$this->b_index]=$this->f_index-$this->b_index;
				
				//后指针为前指针
				$this->b_index=$this->f_index-1;
				
				//重新窗口
				$this->window[$this->b_index]=$this->array[$this->b_index]+$v;
				
				//记录指针
				$this->pool[$this->b_index]=[$this->b_index,$this->f_index];
			}
			
			//前指针+1
			$this->f_index++;
		}
		
		//获取靠近值的key
		foreach($this->window as $k=>$v){
			if($v < $this->s){
				continue;
			}
			if(empty($sum_max)){
				$sum_max=$v;
			}
			if($sum_max>$v){
				$sum_max=$v;
			}
		}
		
		//所有数组值总和小于s
		if(empty($sum_max)){
			return -1;
		}
		
		foreach($this->window as $k=>$v){
			if($v==$sum_max){
				$this->maxValue_key[]=$k;
			}
		}
		
		//选出最短的数字key
		foreach($this->maxValue_key as $v){
			$this->minLength_key[$v]=$this->pool[$v][1]-$this->pool[$v][0];
		}
		$min_key = array_keys($this->minLength_key, min($this->minLength_key));
		
		//返回长度
		return $this->pool[$min_key[0]][1]-$this->pool[$min_key[0]][0]+1;
		
		//选出区间数组值
		foreach($this->pool[$min_key[0]] as $v){
			$rs[]=$this->array[$v];
		}
		return $rs;
	}
}

//一个数组中，找出“最短”连续数组 大于等于一个数字
//$array=[1,3,1,2,5,6,5];			//[5,6]
//$array=[1,3,1,2,5,8,6,7];				//[5,8]
//$array=[1,2,3,4];			//-1

$s=11;

$obj=new problem_406($array,$s);
$rs=$obj->start();
debug::d($rs);
debug::t();





































