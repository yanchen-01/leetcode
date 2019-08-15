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
class problem_384{
	function __construct($s) {
		//给出的数字
		$this->s=$s;
		
		//记录长度
		$this->length=[];
		
		//数组
		$this->array=str_split($s);
		
		//前指针
		$this->b_index=0;
		
		//后指针
		$this->f_index=0;
		
		//记录有无重复
		$this->pool=[];
	}
	
	function start(){
		if(empty($this->array)){
			return 0;
		}
		
		foreach($this->array as $k=>$v){
			//无重复
			if(!in_array($v, $this->pool)){
				$this->pool[]=$v;
				
				//记录长度
				$this->length[]=$this->f_index-$this->b_index+1;
			}
			//有重复
			else{
				$this->pool[]=$v;
				while($this->array[$this->b_index]!=$v){
					array_shift($this->pool);
					$this->b_index++;
				}
				array_shift($this->pool);
				$this->b_index++;
				//记录长度
				$this->length[]=$this->f_index-$this->b_index+1;
			}
			
			$this->f_index++;
		}
		
		return max($this->length);
	}
}

//在字符串中找到最长的没有重复字符的字串长度
//$s="abcbac";		//3
//$s="bpfbhmipx";			//7
//$s="lxhgxjyazitnxgrepl";			//14
//$s="bbbbbb";

$obj=new problem_384($s);
$rs=$obj->start();
debug::d($rs);
debug::t();





































