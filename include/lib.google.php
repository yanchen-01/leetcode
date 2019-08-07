<?php
/**
 * google api 接口
 * 使用前需要到google console设置允许调用的Server IP, 检查KEY的类型和授权
 * @author weiqiwang
 *
 */
class google{
	
	//参见 https://cloud.google.com/translate/v2/using_rest#query-params
	static function translate($text,$from='en',$to='zh-CN'){
		$key = defined("GOOGLE-API-KEY")?GOOGLE-API-KEY:'AIzaSyAluCxH7mkYXphlPu6j-uYmxvgV1TwJsLk';
		$text = rawurlencode($text);
		$url = "https://www.googleapis.com/language/translate/v2?key={$key}&source={$from}&target={$to}&q={$text}";
		
		//使用curl获取内容
		$ch = curl_init();
		$timeout = 15;
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
		$json = curl_exec($ch);
		curl_close($ch);
		
		//解码
		$result = empty($json)?'':json_decode($json);
		if(isset($result->data->translations[0]->translatedText)) $result = $result->data->translations[0]->translatedText;
		return $result;
	}
}