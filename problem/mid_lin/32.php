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
class problem_32{
	function __construct() {
	}
	
	function start($source,$targe){
		//选择字符串
		$s=str_split($source);
		
		//目标字符串
		$t=str_split($targe);
		
		if(empty($t)){
			return "";
		}
		
		//窗口记录字符
		$count_s=[];
		
		//目标字符个数
		$count_t=[];
		for($i=0;$i<=256;$i++){
			$count_s[$i]=$count_t[$i]=0;
		}
		
		$tmp_pool=[];
		foreach($t as $v){
			if(empty($tmp_pool[$v])){
				$tmp_pool[ord($v)]=$v;
				$count_t[ord($v)]++;
			}
		}
		
		//总目标个数
		$k=count($tmp_pool);
		
		//当前目标个数
		$c=0;
		
		//左指针
		$l=0;
		
		//右指针
		$r=0;
		
		//目标左指针
		$rs_l=-1;
		
		//目标右指针
		$rs_r=-1;
		
		//左指针为主，往右走
		for($l;$l<count($s);$l++){
			
			//没有达到目标，循环至到达目标
			while($r<count($s) && $c<$k){
				//记录窗口字符
				$count_s[ord($s[$r])]++;
				
				//窗口字符与目标字符相同,完成目标
				if($count_s[ord($s[$r])] == $count_t[ord($s[$r])]){
					$c++;
				}
				
				//右指针+1
				$r++;
			}
			
			//已经达到目标，记录答案
			if($c==$k){
				//如果现有指针比答案指针小
				if($rs_l==-1 || ($r-$l)<($rs_r-$rs_l)){
					$rs_l=$l;
					$rs_r=$r;
				}
			}
			
			//移除左边一个窗口元素
			$count_s[ord($s[$l])]--;
			
			//重新检查窗口
			if($count_s[ord($s[$l])] == $count_t[ord($s[$l])]-1){
				$c--;
			}
		}
		
		if($rs_l==-1){
			return "";
		}
		
		return substr($source, $rs_l , $rs_r);
	}
}

//给定字符串A和B，找到A中最短的字串，其中包含能组成B的所有字符
$source="abczdedf";
$targe="acdd";

$obj=new problem_32();
$rs=$obj->start($source,$targe);
debug::d($rs);
debug::t();





































