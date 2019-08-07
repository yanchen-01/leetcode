<?php
/**
 * reCAPTCHA人机验证码
 * 
 * $siteKey = '6Lfeg6EUAAAAAHXEYaVYQjcjS43iThGVWW3pknLK';
 * $secretKey = '6Lfeg6EUAAAAAHOPUSybdgOW6iGk_fjD2Kt2hyzL';
 * 
 * 部署 reCAPTCHA 人机识别
 * 
 * 1, 在</head>前加载google api
 * 	  <script src='https://www.google.com/recaptcha/api.js'></script>
 * 
 * 2, 在<form>中需要的地方嵌入这行代码
 * 	  <div class="g-recaptcha" data-sitekey="6Lfeg6EUAAAAAHOPUSybdgOW6iGk_fjD2Kt2hyzL"></div>
 * 
 * 3, 在php程序中,先定义 RECAPTCHA_SECRET_KEY 然后使用 recapcha::check($_POST['g-recaptcha-response']); 验证人机识别结果
 */
class recapcha {
	
	/**
	 * 验证人机识别结果
	 * @param string $recaptcha_resp 取自 $_POST['g-recaptcha-response']
	 * @return bool $result;
	 */
	public static function check($recaptcha_resp=''){
		$remoteIP = http::getIP();
		$secretKey = defined("RECAPTCHA_SECRET_KEY")?RECAPTCHA_SECRET_KEY:'6Lfeg6EUAAAAAHOPUSybdgOW6iGk_fjD2Kt2hyzL';
		$url = "https://www.google.com/recaptcha/api/siteverify?secret={$secretKey}&response={$recaptcha_resp}&remoteip={$remoteIP}";
		
		$ch = curl_init($url);//使用curl
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$result = curl_exec($ch);
		curl_close($ch);
		
		$result = json_decode($result,true); //返回jason数据
		$result = empty($result['success'])?false:true;
		
		return $result;
	}
}