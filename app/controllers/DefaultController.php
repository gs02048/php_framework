<?php
/**
 * Created by PhpStorm.
 * User: huanghailin
 * Date: 2019/4/19
 * Time: 下午8:57
 */

namespace app\controllers;

use framework\base\Controller;

class DefaultController extends Controller
{
    public function Init()
    {
        echo "run init";
    }

    public function index()
    {
        $data = array(
            'stat'=>0,
            'msg'=>'controller:'.$this->_controller.' action:'.$this->_action,
        );
        echo json_encode($data);
        return;
    }
}