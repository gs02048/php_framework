<?php
/**
 * Created by PhpStorm.
 * User: huanghailin
 * Date: 2019/4/19
 * Time: 下午7:10
 */

namespace framework\base;

class Controller
{
    protected $_controller;
    protected $_action;

    public function __construct($controller,$action)
    {
        $this->_controller = $controller;
        $this->_action = $action;
    }
}