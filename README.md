beanstalk ＋　echarts实现的队列实时统计图
==============


效果截图
======
![queue_runtime](https://github.com/wjcgithub/web-msg-sender/blob/master/web/img/queue.png)

后端服务启动停止
======
### 启动服务
php start.php start -d
### 停止服务
php start.php stop
### 服务状态
php start.php status

如果启动不成功请参考 [Workerman手册](http://doc3.workerman.net/install/requirement.html) 配置环境

常见问题：
====
如果通信不成功检查防火墙   
/sbin/iptables -I INPUT -p tcp --dport 2120 -j ACCEPT   
/sbin/iptables -I INPUT -p tcp --dport 2121 -j ACCEPT   
/sbin/iptables -I INPUT -p tcp --dport 2123 -j ACCEPT    

