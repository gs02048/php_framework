ps -ef|grep base_worker|grep -v grep|cut -c 9-15|xargs kill -9
