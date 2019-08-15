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

//核心思想：
class problem_1{
	function __construct() {
	}
	
	//核心：排序，两边指针往中间移动，小于值左指针+1，大于值右指针-1
	function start($nums,$target){
		$l=0;
		$r=count($nums)-1;
		
		//排序
		$s_nums=$nums;
		sort($s_nums);
		
		while($l<=$r){
			if($s_nums[$l]+$s_nums[$r]==$target){
				$rs_l = array_search ($s_nums[$l], $nums);
				$rs_r = array_search ($s_nums[$r], $nums);
				
				if($rs_l==$rs_r){
					return array_keys($nums, $s_nums[$l]);
				}else{
					return [array_search ($s_nums[$l], $nums),array_search ($s_nums[$r], $nums)];
				}
			}
			
			//小于值左指针+1
			else if($s_nums[$l]+$s_nums[$r]<$target){
				$l++;
			}
			
			//大于值右指针-1
			else if($s_nums[$l]+$s_nums[$r]>$target){
				$r--;
			}
		}
		
		return [];
	}
}

//数组里面的数字两个加起来为target
$nums = [2,7,11,15];
$target = 9;

$nums = [3,2,1,4];
$target = 6;

$obj=new problem_1();
$rs=$obj->start($nums,$target);
debug::d($rs);
debug::t();





































