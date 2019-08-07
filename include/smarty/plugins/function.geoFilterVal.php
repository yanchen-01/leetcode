<?php
/**
 * smarty 注册函数，直接在模板中读取根据地域信息处理过的内容
 * 
 * {%geoFilterVal name="assign_name"  data=$somedata%}
 *  在smarty中正常调用经过地理信息过滤后的　$assign_name 变量
 * 
 * $geoinfo = Array
(
    [continent_code] => NA
    [country_code] => US
    [country_code3] => USA
    [country_name] => United States
    [region] => 
    [city] => 
    [postal_code] => 
    [latitude] => 38
    [longitude] => -97
    [dma_code] => 0
    [area_code] => 0
)

$params['data'] = array(
	array(
		'name1'=>'...'
		'name2'=>'...'
		'name3'=>'...'
		'geocity'=>'fremont,union city'
		'geocountry'=>'CA,US'
	),
	...
);
 * 
 * @param array
 * @return string
 */
function smarty_function_geoFilterVal($params, &$smarty){
	if(empty($params['data'])) return;
	
	$geoinfo = func_initGeoInfo();
	
	//选项及与geoinfo对应的键值
	$funcs = array(
		'country'=>'country_code',
		'state'=>'region',
		'city'=>'city',
		'dmacode'=>'dma_code'
	);
	
	$result = array();
	foreach($params['data'] as $key=>$val){
		$checkpool = array();//可用的检测池
		foreach($funcs as $func=>$geokey){
			if( isset($val["geo{$func}"]) )
				$checkpool[] = array(
						'fieldname'=>"geo{$func}",
						'geokey'=>$geokey
				);
		}
		
		//没有设置地区检查时直接返回
		if(empty($checkpool)) {
			$result[$key] = $val;
			continue;
		}
		
		//如果设置了检查项目
		foreach($checkpool as $item){
			if(empty($val[$item['fieldname']])) continue;
			
			$tmp = strtolower($val[$item['fieldname']]);
			$arr = explode(',',$tmp);//数据中的地域信息设置
			$geovalue = strtolower($geoinfo[$item['geokey']]);//当前用户的地域信息
			
			if(in_array($geovalue,　$arr)) {
				$result[$key] = $val;
				break;
			}
		}
	}
	
	//向smarty输出$name = $result
	$smarty->assign($params['name'], $result);
}