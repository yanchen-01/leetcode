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

//双指针滑动窗口
class SlidingWindow{
	function __construct($array,$k) {
		//预先数组
		$this->array=$array;
		
		//需要靠近的数字
		$this->k=$k;
		
		//滑动窗口
		$this->window=[];
		
		//记录连续数组池
		$this->pool=[];
		
		//后指针
		$this->b_index=0;
		
		//前指针
		$this->f_index=0;
	}
	
	//建立滑块窗口
	function build_window(){
		foreach($this->array as $k=>$v){
			//前指针连续+1
			$this->f_index=$k;
			
			//初始化窗口
			if($k==0){
				$this->window=['sum'=>[$v],'length'=>[1],'pool'=>[[$v]]];
				continue;
			}
			
			//窗口在范围内
			if( ($this->window['sum'][$k-1]+$v)<=$this->k ){
				$this->window['sum'][]=$this->window['sum'][$k-1]+$v;
				
				if($k==count($this->array)-1){
					$this->window['length'][$k]=$k;
				}else{
					$this->window['length'][$k]=$k+1;
				}
			}
			//相加在窗口范围外 后滑块指针推进1 长度为当前长度减滑块指针长度
			else if( ($this->window['sum'][$k-1]+$v)>$this->k ){
				$this->window['sum'][]=($this->window['sum'][$k-1]+$v)-$this->array[$this->b_index];
				$this->window['length'][$k]=$k-$this->b_index;
				$this->b_index+=1;
			}
			
			//记录连续数组 后指针与前指针的中间元素相加   或  只记录前指针与后指针，挑出最优结果后在相加
			for($i=$this->b_index;$i<=$this->f_index;$i++){
				$this->window['pool'][$k][]=$this->array[$i];
			}
		}
	}
	
	//遍历滑块窗口找出最佳答案
	function find_array(){
		//1.选出最佳数组和
		$sum_max=$this->window['sum'][0];
		$sum_index_pool=[];
		foreach($this->window['sum'] as $k=>$v){
			//大于数字不考虑
			if($v>$this->k){
				continue;
			}
			if($sum_max<=$v){
				$sum_max=$v;
				$sum_index_pool[]=$k;
			}
		}
		
		//2.选出最佳长度
		$length_max=$this->window['length'][0];
		$length_index=0;
		foreach($sum_index_pool as $k=>$v){
			if($length_max<=$v){
				$length_index=$v;
			}
		}
		
		//3.选出最佳数组
		$rs=$this->window['pool'][$length_index];
		return $rs;
	}
	
	function start(){
		//建立滑块窗口
		$this->build_window();
		
		//遍历滑块窗口找出最佳答案
		$rs=$this->find_array();
		
		return $rs;
	}
}



//选出数组中元素 “相加之和最靠近一个数子” 的 “最长” “子数组集”
//$array=[3,1,2,1];				//[1,2,1]
//$array=[1,2,1,3,1,1,1];			//[1,2,1]
$array=[1,1,1,9,9,1,1,1,1];		//[1,1,1,1]
$k=4;

$obj=new SlidingWindow($array,$k);
$rs=$obj->start();

debug::d($rs);
debug::t();
exit;


















































