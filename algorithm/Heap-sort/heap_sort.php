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

//堆的概念为，1.完整二叉树 2.父节点比子节点大
//堆排序
class HeapSort{
	function __construct($array) {
		//给出的数组
		$this->array=$array;
		
		//树
		$this->tree=$array;
		
		//总节点
		$this->size=count($array);
		
		//创建堆
		$this->build_heap($this->array);
	}
	
	//交换树的节点的值
	function swap($node,$max_node){
		$tmp=$this->tree[$node];
		$this->tree[$node]=$this->tree[$max_node];
		$this->tree[$max_node]=$tmp;
	}
	
	//单节点排序，三个点转一下，最大的在上面
	function heapify($node){
		//超出树范围的点返回空
		if($node>=$this->size){
			return;
		}
		
		$left_node=2*$node+1;
		$right_node=2*$node+2;
		$max_node=$node;
		
		//在树的节点范围内
		if($left_node<$this->size && $this->tree[$left_node]>$this->tree[$max_node]){
			//左节点更大
			$max_node=$left_node;
		}
		if($right_node<$this->size && $this->tree[$right_node]>$this->tree[$max_node]){
			//右节点更大
			$max_node=$right_node;
		}
		
		//如果父节点不是最大
		if($max_node!=$node){
			//交换树的节点的值
			$this->swap($node,$max_node);
			
			//因为树结构被破坏，递归对下面两个子节点再次排序
			$this->heapify($max_node);
		}
	}
	
	//创建堆
	function build_heap(){
		//最后一个节点的父节点排序，节点依次往上排
		$last_node=$this->size-1;
		$root_last_node=($last_node-1)/2;
		for($i=$root_last_node;$i>=0;$i--){
			$this->heapify($i);
		}
	}
	
	//堆排序
	function sort_heap(){
		for($i=$this->size-1;$i>=0;$i--){
			//1.堆的第一个点与最后一个点交换
			$this->swap(0,$i);
			
			//2.砍掉堆的最后一个点，无需直接砍掉，只要重新排序少一个数组长度即可
			$this->size--;
			
			//3.重新构建堆
			$this->heapify(0);
		}
	}
}

//父节点比字节点大，且叶子是完整形态
$array=[4,10,3,5,1,2]; 		//tree=[10,5,3,4,1,2]
$obj=new HeapSort($array);
$obj->sort_heap();
debug::d($obj->tree);
debug::t();
















































