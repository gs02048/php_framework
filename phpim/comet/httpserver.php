<?php

/**
 * Class standard
 *
 * @property  swoole_http_server _webserver
 */
class httpserver
{
    protected $_config = null;
    private $curser;

    public function __construct($server,$currentserver)
    {
        $this->_config = $server->getConfig();
        $this->curser = $currentserver;
    }
    /**
     * 处理 http_server 服务器的 request 请求
     * @param $request
     * @param $response
     * @tutorial 获得 REQUEST_URI 并解析，通过 Fend_Acl 路由到指定的 controller
     */
    public function onRequest($request, $response)
    {
        $GLOBALS['_GET']        = !empty($request->get) ? $request->get : array();
        $GLOBALS['_POST']       = !empty($request->post) ? $request->post : array();
        $GLOBALS['_REQUEST']    = array_merge($GLOBALS['_GET'],$GLOBALS['_POST']);
        $GLOBALS['_COOKIE']     = !empty($request->cookie) ? $request->cookie : array();
        $GLOBALS['_HTTPDATA']   = !empty($request->data) ? $request->data : array();
        $GLOBALS['_FD']         = !empty($request->fd) ? $request->fd : '';
        $GLOBALS['_HEADER']     = !empty($request->header) ? $request->header : array();

        if (!empty($request->server)) {
            $_SERVER = array_merge($_SERVER, $request->server,array_change_key_case($request->server,CASE_UPPER) );
        }
        $_SERVER['HTTP_USER_AGENT']  = !empty($request->header['user-agent'])?$request->header['user-agent']:'';
        //debug info show
        if(isset($GLOBALS['_GET']["wxdebug"]) && $GLOBALS['_GET']["wxdebug"]==1){
            $GLOBALS["__DEBUG"]=1;
        }else{
            $GLOBALS["__DEBUG"]=0;
        }
        $host           = parse_url($request->header['host']);
        $_SERVER['HTTP_HOST']   = !empty($host['path'])?$host['path']:$host['host'];
        $_SERVER["REMOTE_ADDR"] = !empty($request->server["remote_addr"])?$request->server["remote_addr"]:'';
        $response->header("Content-Type", "text/html; charset=utf-8;");
        $response->header("Server-Version", "6.0");

        if (!isset($_SERVER['request_uri'])) {
            $response->end("请求非法");
            return;
        }
        if(stristr($_SERVER['request_uri'],"/health")){
            $response->end("ok");
            return;
        }
        $strurl = strtok($_SERVER['request_uri'], '?');
        $strurl = str_replace(array('.php', '.html', '.shtml'), '', $strurl);
        $module = explode('/', $strurl);
        $func = trim($module[1]);
        if(!method_exists($this,$func)){
            $response->end("请求非法");
            return;
        }
        $string = '';
        try{
            ob_start();//打开缓冲区
            call_user_func_array(array($this,$func),array());
            $string = ob_get_contents();//获取缓冲区内容
        }catch (Exception $e){
            print_r($e);
        }
        ob_end_clean();//清空并关闭缓冲区

        $response->end($string);

        //clean up last error befor
        error_clear_last();
        clearstatcache();
    }

    public function status()
    {
        echo "success";
    }

    /**
     *向当前comet server的room发送消息 收到push消息 根据相应房间id找到swoole table里面的fd进行推送
     */
    public function pushRoom()
    {
        $res = array('stat'=>0);
        $roomid = isset($_REQUEST['roomid']) ? $_REQUEST['roomid'] : 0;
        $msg = isset($_REQUEST['msg']) ? $_REQUEST['msg'] : 0;
        if($roomid <= 0 || empty($msg)){
            $res['msg'] = "param err";
            echo json_encode($res);
            return;
        }
        $table_key = intval($roomid) % 1024;
        foreach ($this->curser->{'room_table_'.$table_key} as $fd=>$data){
            if($data['roomid'] == $roomid){
                $this->curser->push($fd,json_encode(['type'=>PUSH_MSG,'msg'=>$msg]));
            }
        }
        $res = array('stat'=>1,'msg'=>'send success');
        echo json_encode($res);
        return;
    }

    /**
     * 向单个用户推送消息
     */
    public function pushUser()
    {

    }
}
