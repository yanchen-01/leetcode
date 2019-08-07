<?php
//处理内容中的链接，格式化输出带标准标签的广告链接
function smarty_modifier_taglink($content){

	//取默认的utf8 字符编码
	if(substr($content,0,5)!='<meta'){
		$content = '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />'.$content;
	}
	
	$adlinks = load("ad_taglink");
	
	$dom = new DOMDocument;
	$dom->loadHTML($content);
	foreach ($dom->getElementsByTagName('a') as $node) {
		$originURL = $node->getAttribute('href');
		$newURL = $adlinks->format($originURL);
		
		$node->setAttribute('href',$newURL);
	}
	
	$html = $dom->saveHTML();
	$html = strings::findMe($html, "<body>", "</body>");
	
	return $html;

}