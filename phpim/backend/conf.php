<?php

$config =  array(
    "redis" => array(
        "host"=>"127.0.0.1",
        "port"=>6379,
    ),
    //主服务，选主服务 建议按 websocket（http） > http > udp || tcp 顺序创建 ,websocket只能作为主进程
    "server" => array(
        "server_name" => "tal_backend",
        "host" => "0.0.0.0",
        "port" => 9588,
        "class" => "swoole_websocket_server",//可选项：swoole_websocket_server/swoole_http_server/swoole_server
        "socket" => SWOOLE_SOCK_TCP,
        "tablesize" => 1024,
        "processtimeout" => 10,//进程超时时间，超过10秒自动kill
    ),


    "httpserver" => array(
        "host" => "0.0.0.0",
        "port" => 9573,
        "socket" => SWOOLE_SOCK_TCP,
        'domain'     => '172.16.187.219',
        "protocol"   => array(
            'open_http_protocol'   => 1,
            'open_tcp_nodelay'          => 1,
            'backlog'                   => 3000,
        ),
    ),


    "swoole" => array(
        'user' => 'nobody',
        'group' => 'nobody',
        'dispatch_mode' => 2,
        'package_max_length' => 2097152, // 1024 * 1024 * 2,
        'buffer_output_size' => 3145728, //1024 * 1024 * 3,
        'pipe_buffer_size'   => 33554432, //1024 * 1024 * 32,

        'backlog'                   => 30000,
        'open_tcp_nodelay'          => 1,
        'heartbeat_idle_time'       => 180,
        'heartbeat_check_interval'  => 60,

        'open_cpu_affinity' => 1,
        'worker_num'        => 5,
        'task_worker_num'   => 1,
        'max_request'       => 100000,
        'task_max_request'  => 10000,
        'discard_timeout_request' => false,
        'log_level'         => 2, //swoole 日志级别 Info
        'log_file'          => '/tmp/tal_backend.log',//swoole 系统日志，任何代码内echo都会在这里输出
        'task_tmpdir'       => '/dev/shm/',//task 投递内容过长时，会临时保存在这里，请将tmp设置使用内存
        'pid_file'          => '/tmp/tal_backend.pid',//进程pid保存文件路径，请写绝对路径
        'daemonize'         => 0,
    ),

);
