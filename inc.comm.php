<?php
define( 'SKIP_ADMIN_IP_CHECK', true);
define( 'systemVersion', 'leetcode' );

//设置用于命令行调用的服务器变量
if(empty($_SERVER['HTTP_HOST'])) $_SERVER['HTTP_HOST']='local.leetcode.com';

//根路径
define( 'DOCUROOT', dirname( __FILE__ ) );

// 加载公共系统函数
require_once( DOCUROOT."/vendor/autoload.php" );
require_once( DOCUROOT."/include/_functions.php" );

//数据库配置
define( 'DBSETTING', DOCUROOT.'/inc.db.php' );

// 定义当前服务器默认时区
define( 'TIMEZONE', 'UTC-0800' );

// 全局变量存储参数
$_GlobalConfig = array( 'host' => '127.0.0.1', 'port' => '11211' );

// 加载程序配置文件
require_once( DOCUROOT."/include/_init.php" );

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);














