<?php
//{%"No Result"|lang:'CH':'application_deadline.no_result'%}
function smarty_modifier_lang($string, $code, $key) {
	static $cache;

	if(empty($code)) return $string;

	$arr = explode(".", $key);

	if (is_array($arr)) {
		$module = $arr[0];
		$submodule = $arr[1];
	}
	else
		return $string;
	

	$cacheID = $code."_".$module;

	if(empty($cache[$cacheID])) {
		$memcache = func_initMemcached("cache01");
		$cache[$cacheID] = json_decode($memcache->get($cacheID), true); 

		if ($cache[$cacheID]) {

			foreach($cache[$cacheID] as $k=>$v) {
				if (($k==$submodule)&&empty($v)) return $string;
				if ($k==$submodule)
					return $v;
			}

			return $string;
		}

		else {
			$obj = load("localization_localization_setting");

			$cache[$cacheID] = $obj->getMem($code, $module);

			foreach($cache[$cacheID] as $k=>$v) {
				if (($k==$submodule)&&empty($v)) return $string;
				if ($k==$submodule)
					return $v;
			}

			return $string;
		}
	}

	else {
		foreach($cache[$cacheID] as $k=>$v) {
			if (($k==$submodule)&&empty($v)) return $string;
			if ($k==$submodule)
				return $v;
		}

		return $string;
	}
}
