<?php

class websocket
{
    protected $_config = null;
    private $curser = null;
    public $table = array();

    public function __construct($server,$currentserver)
    {
        $this->_config = $server->getConfig();
        $this->curser = $currentserver;
    }

    /**
     * 当WebSocket客户端与服务器建立连接并完成握手后会回调此函数。
     * @param swoole_websocket_server $svr
     * @param swoole_http_request $req
     */
    public function onOpen(swoole_websocket_server $svr, swoole_http_request $req)
    {
        print_r($req);
    }

    /**
     * 当服务器收到来自客户端的数据帧时会回调此函数。
     * @param swoole_server $server
     * @param swoole_websocket_frame $frame
     */
    public function onMessage(swoole_server $server, swoole_websocket_frame $frame)
    {
        if(empty($frame->data)){
            return;
        }
        $data = @json_decode($frame->data,true);
        if(!is_array($data)){
            return;
        }
        $userfd = $frame->fd;
        switch ($data['type']){
            case 'connect':
                if(!isset($data['uid']) || !isset($data['roomid'])){
                    $this->curser->push($userfd,'param err');
                    return;
                }
                $uid = $data['uid'];
                $roomid = $data['roomid'];
                $table_key = intval($roomid) % 1024;
                $this->curser->{'room_table_'.$table_key}->set($userfd,['uid'=>$uid,'roomid'=>$roomid,'time'=>time()]);
                $this->curser->push($userfd,'join room success!');
                return;

        }

    }

    public function onClose(swoole_server $server, $fd, $reactorId)
    {
        for($i=0;$i<1024;$i++){
            $info = $this->curser->{'room_table_'.$i}->get($fd);
            if(empty($info)){
                continue;
            }
            $uid = $info['uid'];
            $roomid = $info['roomid'];

            httpClient::get('/close',['uid'=>$uid,'roomid'=>$roomid]);
            break;
        }
    }

    /**
     * 事件在Worker进程/Task进程终止时发生
     * @param swoole_server $server
     * @param               $worker_id
     */
    public function onWorkerStop(swoole_server $server, $worker_id)
    {
    }


}
