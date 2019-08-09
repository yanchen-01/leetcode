<?php
/**
 * 计数器原理
 * 变实时更新实时显示为着时更新实时显示。
 * 如果中间出现memcache死机的情况，最多损失一个统计周期的数据，但可以有效的减少服务器的负载
 * 循环更新时，要判断目标值和原始值的大小，再判断使用相加更新还是替换更新
 * 
 * 1、使用memcache实时显示及记录用户访问数
 * 2、使用mysql在各相关的类型表中永久记录统计结果
 * 3、在各程序的调用环节使用getNum或setNum
 * 4、定时执行当前类，汇总临时数据到永久表中
 * 
 * 汇总时执行：
 * 1、转移数据到临时表 count_tmp
 * 2、为了尽量减少对用户访问的影响，立即重建数据记录表 count
 * 3、对临时表count_tmp加索引
 * 4、遍历临时表count_tmp
 * 5、在遍历的过程中先执行更新，后清除缓存
 * 6、最后删除临时表count_tmp
 * 
 * @author weiqi
 * site_count.php
 */
class counter {
	
}