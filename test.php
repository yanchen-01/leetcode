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
class problem_386{
	function __construct() {
	}
	
	//核心：
	function start($s,$k){
		$sring=$s;
		
		if(empty($s)){
			return 0;
		}
		
		$s=str_split($s);
		
		//左指针
		$l=0;
		
		//右指针
		$r=0;
		
		//答案左指针
		$rs_l=-1;
		
		//答案右指针
		$rs_r=-1;
		
		//窗口
		$window=[];
		
		//完成额度
		$count_t=[];
		
		//完成个数
		$c=0;
		
		//始初化窗口
		for($i=0;$i<=256;$i++){
			$window[$i]=$count_t[$i]=0;
		}
		
		//初始化完成额度
		foreach($s as $v){
			$count_t[ord($v)]=1;
		}
		
		//设定
		for($l=0;$l<count($s);$l++){
			//r辅指针往前走 且未达到预期目标c
			while($r<count($s) && $c<=$k){
				//添加窗口值
				$window[ord($s[$r])]++;
				
				//判断是否达到标准
				if($window[ord($s[$r])] == $count_t[ord($s[$r])]){
					$c++;
				}
				
				$r++;
			}
			
			debug::d(substr($sring,$l,$r-$l)."__".$l."=>".$r);
			
			//满足目标
			if($c==$k){
				if($rs_l==-1 || ($rs_r-$rs_l)<($r-$l)){
					$rs_r=$r;
					$rs_l=$l;
				}
			}
			
			//移除窗口
			$window[ord($s[$l])]--;
			
			//判断是否达到标准
			if($window[ord($s[$l])] < $count_t[ord($s[$l])]){
				$c--;
			}
		}
		
		if($rs_l==-1){
			return count($s);
		}
		
		$test=substr($sring,$rs_l,$rs_r-$rs_l);
		foreach(str_split("jjmxutystqdfhuMblWbylgjxsxg") as $v){
			$test1[$v]=1;
		}
		
		debug::d($sring);
		debug::d(count($test1));
		debug::d(strlen("jjmxutystqdfhuMblWbylgjxsxg"));
		exit;
		
		return $rs_r-$rs_l;
	}
}

//找出“最长”的包含"K"个"distinct"元素的字符串
$s = "eqgkcwGFvjjmxutystqdfhuMblWbylgjxsxgnoh";	// jjmxutystqdfhuMblWbylgjxsxg
$k = 16; //27

//$s="eceba";
//$k=3;

$obj=new problem_386();
$rs=$obj->start($s,$k);
debug::d($rs);
debug::t();





































