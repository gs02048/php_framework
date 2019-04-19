<?php
/**
 * Created by PhpStorm.
 * User: huanghailin
 * Date: 2019/4/18
 * Time: 下午2:10
 */

define('APP_PATH',__DIR__.'/');

define('APP_DEBUG',true);

require(APP_PATH.'framework/framework.php');

$config = require(APP_PATH.'config/config.php');

(new framework\FrameWork($config))->run();