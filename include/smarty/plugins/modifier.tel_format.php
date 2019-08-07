<?php
function smarty_modifier_tel_format($number, $countrycode='1',$delimiter="-"){
	return strings::tel_format($number, $countrycode, $delimiter);
}