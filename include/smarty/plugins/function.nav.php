<?php
/**
* smarty 注册函数，判断给定地址是否是当前页面地址
* {%nav url="/abc/" result="style='....'" default=""%} 
*
* @param array
* @return string
*
*/
function smarty_function_nav($params){
	static $urlinfo;
	if(empty($urlinfo)) $urlinfo = parse_url($_SERVER['REQUEST_URI']);
	
	$url = "";
	$result = "";
	$default = "";
	foreach($params as $_key => $_val) {
		if($_key=='url') $url=$_val;
		if($_key=='result') $result=$_val;
		if($_key=='default') $default=$_val;
	}
	
	if($url == $urlinfo['path']) return $result;

	return $default;
}