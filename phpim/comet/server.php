<?php

class Server
{
    private $_server;
    private $_subserver;
    private $_config;

    public function __construct($config)
    {
        $this->_config = $config;
    }
    public function getConfig()
    {
        return $this->_config;
    }

    public function start()
    {
        $this->_server = new Swoole\WebSocket\Server($this->_config["server"]["host"],
            $this->_config["server"]["port"], SWOOLE_PROCESS, $this->_config["server"]["socket"]);
        //创建1024个swoole table 连接进来按房间号取模
        for($i=0;$i<1024;$i++){
            $this->_server->{'room_table_'.$i} = new swoole_table(1024);
            $this->_server->{'room_table_'.$i}->column("uid",swoole_table::TYPE_INT,8);
            $this->_server->{'room_table_'.$i}->column("roomid",swoole_table::TYPE_INT,8);
            $this->_server->{'room_table_'.$i}->column("time",swoole_table::TYPE_STRING,30);
            $this->_server->{'room_table_'.$i}->create();
        }
        $this->_server->set($this->_config["swoole"]);

        $this->_server->on('Start',function(swoole_server $server){
            swoole_set_process_name($this->_config["server"]["server_name"].'_master');
            //服务注册
            $r = new \Redis();
            $ips = swoole_get_local_ip();
            $ip = $ips[0];
            $r->connect($this->_config['redis']['host'],$this->_config['redis']['port']);
            $r->hset('comet_server_list',$ip.':'.$this->_config['server']['port'],$this->_config['server']['serverid']);
            $r->hset('http_server_list',$ip.":".$this->_config['httpserver']['port'],$this->_config['server']['serverid']);
            echo "server start";
        });

        $this->_server->on('Shutdown',function (){
            $r = new \Redis();
            $ips = swoole_get_local_ip();
            $ip = $ips[0];
            $r->connect($this->_config['redis']['host'],$this->_config['redis']['port']);
            $r->hDel('comet_server_list',$ip.':'.$this->_config['server']['port']);
            $r->hDel('http_server_list',$ip.":".$this->_config['httpserver']['port']);

            //服务注销
            echo "server shutdown";
        });

        $this->_server->on('WorkerStart', function(swoole_server $server,$worker_id){
            swoole_set_process_name($this->_config["server"]["server_name"].'_worker');
        });

        $this->_server->on('ManagerStart', function(swoole_server $server){
            swoole_set_process_name($this->_config["server"]["server_name"].'_manager');
        });

        $this->_server->on('Task', function(swoole_server $server,$task_id,$src_worker_id,$data){
            echo $task_id;
        });
        $this->_server->on('Finish', function(swoole_server $server,$task_id,$data){
            echo $task_id;
        });

        $websockerserver = new websocket($this,$this->_server);
        $this->_server->on('Open', array($websockerserver, 'onOpen'));
        $this->_server->on('Message', array($websockerserver, 'onMessage'));
        $this->_server->on('Close', array($websockerserver, 'onClose'));


        //create http new listen
        $config = $this->_config['httpserver'];
        $this->_subserver = $this->_server->addListener($config["host"], $config["port"], $config["socket"]);

        //http
        $httpserver = new httpserver($this,$this->_server);
        if (isset($config["protocol"]["open_http_protocol"]) && $config["protocol"]["open_http_protocol"] && $config["socket"] == SWOOLE_SOCK_TCP) {
            $this->_subserver->on('Request', array($httpserver, 'onRequest'));
        }
        $this->_server->start();
    }
}
