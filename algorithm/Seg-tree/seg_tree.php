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

//线段树，二叉树的建立与更新，获取子数组集的和
class SegTree{
	function __construct($array) {
		//给出的数组
		$this->array=$array;
		
		//数组大小
		$this->size=count($array);
		
		//树
		$this->tree=[];
	}
	
	/**
	 * 建立树
	 * @param $array	初始数组
	 * @param $tree		树
	 * @param $node		根节点
	 * @param $start	左节点
	 * @param $end		右节点
	 */
	function build_tree($array,$tree,$node,$start,$end){
		//递归出口，左节点与右节点相同，为末枝
		if($start==$end){
			//数组赋值给树
			$this->tree[$node]=$this->array[$start];	
		}
		//创建树
		else{
			//初始中间值
			$mid=intval(($start+$end)/2);
			
			//初始左节点
			$left_node=2*$node+1;
			
			//初始右节点
			$right_node=2*$node+2;
			
			//创建左树
			$this->build_tree($this->array,$this->tree,$left_node,$start,$mid); 
			
			//创建右树
			$this->build_tree($this->array,$this->tree,$right_node,$mid+1,$end);
			
			//树赋值
			$this->tree[$node]=$this->tree[$left_node]+$this->tree[$right_node];
			
			//排序
			ksort($this->tree);
		}
	}
	
	/**
	 * 更新树
	 * @param $array	初始数组
	 * @param $tree		树
	 * @param $node		根节点
	 * @param $start	左节点
	 * @param $end		右节点
	 * @param $idx		数组指针
	 * @param $val		更改的值
	 */
	function update_tree($array,$tree,$node,$start,$end,$idx,$val){
		//递归出口，左节点与右节点相同，为末枝
		if($start==$end){
			//更新数组
			$this->array[$idx]=$val;
			
			//修改树
			$this->tree[$node]=$val;
		}
		else{
			//初始中间值
			$mid=intval(($start+$end)/2);
			
			//初始左节点
			$left_node=2*$node+1;
			
			//初始右节点
			$right_node=2*$node+2;
			
			//更新左分支 从左节点开始 起始到中间
			if($start<=$idx && $idx<=$mid){
				$this->update_tree($this->array, $tree, $left_node, $start, $mid, $idx, $val);
			}
			//更新右分支 从右节点开始 中间+1到结束
			else{
				$this->update_tree($this->array, $tree, $right_node, $mid+1, $end, $idx, $val);
			}
			
			//树赋值
			$this->tree[$node]=$this->tree[$left_node]+$this->tree[$right_node];
			
			//排序
			ksort($this->tree);
		}
	}
	
	/**
	 * 求子数组集
	 * @param $array	初始数组
	 * @param $tree		树
	 * @param $node		根节点
	 * @param $start	左节点
	 * @param $end		右节点
	 * @param $L		数组左指针
	 * @param $R		数组右指针
	 */
	function select_tree($array,$tree,$node,$start,$end,$L,$R){
		//递归出口，区间以外返回0			[L,R, --- [start,end], --- ,L,R] 
		if($R<$start || $end<$L){
			return 0;
		}
		//递归出口，区间以内返回树节点		[L, ---  [start,end], ---  R]
		else if($L<=$start && $end<=$R){
			return $this->tree[$node];
		}
		//递归出口，末枝返回树节点值		
		else if ($start==$end){
			return $this->tree[$node];
		}
		
		//初始中间值
		$mid=intval(($start+$end)/2);
		
		//初始左节点
		$left_node=2*$node+1;
		
		//初始右节点
		$right_node=2*$node+2;
		
		//左边和
		$sum_left=$this->select_tree($this->array,$this->tree,$left_node,$start,$mid,$L,$R);
		
		//右边和
		$sum_right=$this->select_tree($this->array,$this->tree,$right_node,$mid+1,$end,$L,$R);
		
		//相加左边与右边
		return $sum_left+$sum_right;
	}
	
}

//给出一个数组，创建二叉树，更新二叉树，求子数组集
$array=[1,3,5,7,9,11];				
$obj=new SegTree($array);

//创建二叉树
$obj->build_tree($obj->array,$obj->tree,0,0,$obj->size-1);
debug::d($obj->array);
debug::d($obj->tree);
echo "============\n";

//更新二叉树 数组指针4改为6
$obj->update_tree($obj->array,$obj->tree,0,0,$obj->size-1,4,6);
debug::d($obj->array);
debug::d($obj->tree);
echo "============\n";

//求子数组集 数组指针2到5
$rs=$obj->select_tree($obj->array,$obj->tree,0,0,$obj->size-1,2,5);
debug::d($obj->array);
debug::d($obj->tree);
debug::d($rs);
debug::t();
exit;


















































