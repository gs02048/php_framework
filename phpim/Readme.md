##基于swoole实现的聊天室服务，使用swoole table在comet server存储用户信息，使用redis保存信息。

###1、comet 长连接服务器
####1.1服务启动自动向redis注册ip:port 
####1.2提供websocket服务，用户连接，加入聊天室。
####1.3提供http服务，用于backend服务器对房间或者人进行推送。
####1.4提供pushRoom、pushUser接口

###2、backend 后端服务器，提供http接口
####2.1、获取comet服务器列表
####2.2、用户连接connect处理接口
####2.3、用户连接close处理接口
####2.4、聊天push接口
####2.5、统计房间人数接口