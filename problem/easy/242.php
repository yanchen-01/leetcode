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

//用字符串的ASCII码做hashtable
class problem_242{
	function __construct($s1,$s2) {
		//给出的字符串1
		$this->s1=$s1;
		
		//给出的字符串2
		$this->s2=$s2;
		
		//字符串1长度
		$this->len_s1=strlen($s1);
		
		//字符串2长度
		$this->len_s2=strlen($s2);
	}
	
	function start(){
		$pool1=[];
		$pool2=[];
		for($i=0;$i<$this->len_s1;$i++){
			if(empty($pool1[$this->s1[$i]])){
				$pool1[$this->s1[$i]]=1;
			}
			else{
				$pool1[$this->s1[$i]]++;
			}
		}
		
		for($i=0;$i<$this->len_s2;$i++){
			if(empty($pool2[$this->s2[$i]])){
				$pool2[$this->s2[$i]]=1;
			}
			else{
				$pool2[$this->s2[$i]]++;
			}
		}
		
		return $pool2==$pool1;
	}
}

//两个字符串，出现的字母都一样
$s1="test";
$s2="estt";

$obj=new problem_242($s1,$s2);
$rs=$obj->start();
debug::d($rs);
debug::t();





































