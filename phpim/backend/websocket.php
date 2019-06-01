<?php

class websocket
{
    protected $_config = null;

    public function __construct($server)
    {
        $this->_config = $server->getConfig();
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
        print_r($frame);
    }

    public function onClose(swoole_server $server, $fd, $reactorId)
    {

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
