<?php
require("httpserver.php");
require("websocket.php");
require("server.php");
require("backendclient.php");

$params = parseArgvs($argv);

if (!extension_loaded('swoole')) {
    die('swoole extension was not found' . PHP_EOL);
}

/* 加载swoole配置文件 */
if(isset($params["c"]) && file_exists($params["c"])){
    echo "loading swoole server special config:".$params["c"].PHP_EOL;
    require_once ($params["c"]);
}else if(file_exists(dirname(__FILE__)."/conf.php")){
    echo "load default swoole server conf.php file.".PHP_EOL;
    require_once(dirname(__FILE__)."/conf.php");
}else{
    echo ( "swoole server config not found.".PHP_EOL);
    helpDom();
    return;
}

if(empty($config)){
    die("swoole server config not found.".PHP_EOL);
}
$server = new Server($config);
$server->start();
/*
 * 接收一个数组
 * */
function parseArgvs($argv)
{
    $params = getopt('c:hp:');
    $count = count($argv);
    if($argv[$count - 1] == '&') {
        $params['action'] = $argv[$count - 2];
    } else {
        $params['action'] = $argv[$count - 1];
    }
    return $params;
}

