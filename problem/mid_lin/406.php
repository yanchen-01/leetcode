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

//核心思想：  右指针+1，直到遇到大于S的数字后，前面左指针+1，直到S小于数字，在右指针+1
class problem_406{
	function __construct($array,$s) {
		//给出的数组
		$this->array=$array;
		
		//滑动窗口
		$this->window=[0];
		
		//记录长度
		$this->length=[];
		
		//给出的最大值
		$this->s=$s;
		
		//左指针
		$this->l_index=0;
		
		//右指针
		$this->r_index=0;
	}
	
	function start(){
		foreach($this->array as $k=>$v){
			if($v>=$this->s){
				return 1;
			}
			
			//记录窗口
			$tmp_window=$this->window[$this->l_index];
			$this->window[$this->l_index]+=$v;
			
			//超出窗口情况
			if($tmp_window+$v >= $this->s){
				//记录长度
				$this->length[]=$this->r_index-$this->l_index+1;
				
				//记录前指针前面所有值的和
				for($i=$this->l_index+1;$i<=$this->r_index;$i++){
					//记录窗口
					$this->window[$i]=$this->window[$i-1]-$this->array[$i-1];
					
					//右指针+1
					$this->l_index++;
					
					//小于值跳过
					if($this->window[$i]<$this->s){
						break;
					}else{
						//记录长度
						$this->length[]=$this->r_index-$i+1;
					}
				}
			}
			
			//前指针+1
			$this->r_index++;
		}
		
		if(empty($this->length)){
			return -1;
		}
		
		return min($this->length);
	}
}

//一个数组中，找出“最短”连续数组 大于等于一个数字
//$array=[4,8,6,4,2,3,1,2,5,2,4];$s=11;			//2
//$array=[100,50,99,50,100,50,99,50,100,50];$s=250;		//4
//$array=[2,3,1,2,4,3];$s=7;			//2
$array=[1,2,3,4,5,6,1,1,1,1];$s=15;				//3

$obj=new problem_406($array,$s);
$rs=$obj->start();
debug::d($rs);
debug::t();





































