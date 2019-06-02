<?php

/**
 * Class standard
 *
 * @property  swoole_http_server _webserver
 */
class httpserver
{
    protected $_config = null;
    protected $redisserver = null;

    public function __construct($server)
    {
        $this->_config = $server->getConfig();
        $r = new \Redis();
        $r->connect($this->_config['redis']['host'],$this->_config['redis']['port']);
        $r->ping();
        $this->redisserver = $r;
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
    /*
     * 注册cometserver
     */
    public function registerCometServer()
    {
        $res = array('stat'=>0);
        $ip = isset($_REQUEST['ip']) ? $_REQUEST['ip'] : '';
        $websocket_port = isset($_REQUEST['websocket_port']) ? $_REQUEST['websocket_port'] : '';
        $http_port = isset($_REQUEST['http_port']) ? $_REQUEST['http_port'] : '';
        $server_id = isset($_REQUEST['server_id']) ? $_REQUEST['server_id'] : '';
        if(empty($uri)){
            $res['msg'] = 'param err';
            echo json_encode($res);
            return;
        }
        $this->redisserver->hSet('comet_server_list',$ip.':'.$websocket_port,$server_id);
        $this->redisserver->hSet('http_server_list',$ip.":".$http_port,$server_id);
        $res['stat'] = 1;
        $res['msg'] = "success";
        echo json_encode($res);
        return;
    }

    public function unRegisterCometServer()
    {
        $res = array('stat'=>0);
        $ip = isset($_REQUEST['ip']) ? $_REQUEST['ip'] : '';
        $websocket_port = isset($_REQUEST['websocket_port']) ? $_REQUEST['websocket_port'] : '';
        $http_port = isset($_REQUEST['http_port']) ? $_REQUEST['http_port'] : '';
        $server_id = isset($_REQUEST['server_id']) ? $_REQUEST['server_id'] : '';
        if(empty($uri)){
            $res['msg'] = 'param err';
            echo json_encode($res);
            return;
        }
        $this->redisserver->hDel('comet_server_list',$ip.':'.$websocket_port,$server_id);
        $this->redisserver->hDel('http_server_list',$ip.":".$http_port,$server_id);
        $res['stat'] = 1;
        $res['msg'] = "success";
        echo json_encode($res);
        return;
    }
    /*
     * 获取cometserver列表
     */
    public function getCometServerList()
    {
        $res = array('stat'=>0);
        $uid = isset($_REQUEST['uid']) ? $_REQUEST['uid'] : '';
        $roomid = isset($_REQUEST['roomid']) ? $_REQUEST['roomid'] : '';
        $list = $this->redisserver->hGetAll('comet_server_list');
        $cometserverlist = array();
        foreach ($list as $k=>$v){
            $cometserverlist[] = $k;
        }
        $res['stat'] = 1;
        $res['msg'] = "success";
        $res['list'] = $cometserverlist;
        echo json_encode($res);
        return;
    }
    /*
     * 连接时做的处理
     */
    public function connect()
    {
        $uid = isset($_REQUEST['uid']) ? $_REQUEST['uid'] : '';
        $roomid = isset($_REQUEST['roomid']) ? $_REQUEST['roomid'] : '';
        $serverid = isset($_REQUEST['serverid']) ? $_REQUEST['serverid'] : '';
        $fd = isset($_REQUEST['fd']) ? $_REQUEST['fd'] : '';

        $this->redisserver->hIncrBy("room_stu_nums",$roomid,1);
        $this->redisserver->hIncrBy("room_server_".$roomid,$serverid,1);
        $this->redisserver->hSet("room_detail_".$roomid,$uid,json_encode(
            array('uid'=>$uid,'roomid'=>$roomid,'serverid'=>$serverid,'fd'=>$fd)
        ));
        echo json_encode(['stat'=>1]);
        return;
    }

    /**
     * @throws Exception
     * 房间推送
     */
    public function pushRoom()
    {
        $uid = isset($_REQUEST['uid']) ? $_REQUEST['uid'] : '';
        $roomid = isset($_REQUEST['roomid']) ? $_REQUEST['roomid'] : '';
        $msg = isset($_REQUEST['msg']) ? $_REQUEST['msg'] : '';

        if(empty($uid) || empty($roomid) || empty($msg)){
            echo json_encode(['stat'=>0,'msg'=>'param err']);
            return;
        }
        $servermaps = $this->redisserver->hGetAll("room_server_".$roomid);
        $serverids = [];
        foreach ($servermaps as $serverid=>$count){
            $serverids[] = $serverid;
        }
        $serverlist = $this->redisserver->hGetAll("http_server_list");
        foreach ($serverlist as $uri=>$serverid){
            if(in_array($serverid,$serverids) && isset($servermaps[$serverid]) && $servermaps[$serverid] > 0){
                $res = httpClient::get($uri.'/pushRoom',array(
                    'roomid'=>$roomid,
                    'msg'=>$msg
                ));
            }
        }
    }
    /*
     * 关闭时做的处理
     */
    public function close()
    {
        $uid = isset($_REQUEST['uid']) ? $_REQUEST['uid'] : '';
        $roomid = isset($_REQUEST['roomid']) ? $_REQUEST['roomid'] : '';
        $serverid = isset($_REQUEST['serverid']) ? $_REQUEST['serverid'] : '';
        $fd = isset($_REQUEST['fd']) ? $_REQUEST['fd'] : '';

        $this->redisserver->hIncrBy("room_stu_nums",$roomid,-1);
        $this->redisserver->hIncrBy("room_server_".$roomid,$serverid,-1);
        $this->redisserver->hDel("room_detail_".$roomid,$uid,json_encode(
            array('uid'=>$uid,'roomid'=>$roomid,'serverid'=>$serverid,'fd'=>$fd)
        ));
    }
    /*
     * 获取房间用户数
     */
    public function getRoomUserNum()
    {

    }
}
