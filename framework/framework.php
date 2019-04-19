<?php
/**
 * Created by PhpStorm.
 * User: huanghailin
 * Date: 2019/4/18
 * Time: 下午2:09
 */

namespace framework;

defined('CORE_PATH') or define('CORE_PATH', __DIR__);
class FrameWork
{
    protected $config = [];

    public function __construct($config)
    {
        $this->config = $config;
    }

    public function run()
    {
        spl_autoload_register(array($this,'loadClass'));
        $this->setReporting();
        $this->route();
    }

    public function route()
    {
        $controllerName = $this->config['defaultController'];
        $actionName = $this->config['defaultAction'];

        $uri = $_SERVER['REQUEST_URI'];
        $pos = strpos($uri,'?');
        $uri = $pos === false ? $uri : substr($uri,0,$pos);
        $uri = trim($uri,'/');
        if($uri){
            $uriArray = array_filter(explode('/',$uri));
            if(count($uriArray) >= 2){
                $controllerName = ucfirst($uriArray[0]);
                $actionName = $uriArray[1];
            }
        }
        // 判断控制器和操作是否存在
        $controller = 'app\\controllers\\'. $controllerName . 'Controller';
        if (!class_exists($controller)) {
            exit($controller . '控制器不存在');
        }
        if (!method_exists($controller, $actionName)) {
            exit($actionName . '方法不存在');
        }
        $dispatch = new $controller($controllerName, $actionName);
        $methods = get_class_methods($dispatch);

        foreach ($methods as &$v){
            $v = strtolower($v);
        }
        if(in_array('init',$methods)){
            call_user_func_array(array($dispatch,'Init'),array());
        }
        call_user_func_array(array($dispatch, $actionName),array());
    }

    public function setReporting()
    {
        if(APP_DEBUG === true){
            error_reporting(E_ALL);
            ini_set('display_errors','On');
        }else{
            error_reporting(E_ALL);
            ini_set('display_errors','Off');
            ini_set('log_errors','On');
        }
    }

    public function loadClass($className)
    {
        $classMap = $this->classMap();

        if(isset($classMap[$className])){
            $file = $classMap[$className];
        }elseif (strpos($className,'\\') !== false){
            $file = APP_PATH.str_replace('\\','/',$className).'.php';
            if(!is_file($file)){
                return;
            }
        }else{
            return;
        }
        include $file;
    }

    protected function classMap()
    {
        return [
            'framework\base\Controller' => CORE_PATH . '/base/Controller.php',
        ];
    }
}