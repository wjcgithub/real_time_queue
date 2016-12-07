<?php
use Workerman\Worker;
use Workerman\WebServer;
use Workerman\Lib\Timer;
use PHPSocketIO\SocketIO;

include __DIR__ . '/vendor/autoload.php';
include __DIR__ . '/services/BeansTalkdService.php';

// 全局数组保存uid在线数据
$uidConnectionMap = array();
// 记录最后一次广播的在线用户数
$last_online_count = 0;
// 记录最后一次广播的在线页面数
$last_online_page_count = 0;
//beanstalkd链接
$bsObj = new BeanstalkdService();

// PHPSocketIO服务
$sender_io = new SocketIO(2120);
// 当$sender_io启动后监听一个http端口，通过这个端口可以给任意uid或者所有uid推送数据
$sender_io->on('workerStart', function(){
    // 监听一个http端口
    $inner_http_worker = new Worker('http://0.0.0.0:2121');
    // 当http客户端发来数据时触发
    $inner_http_worker->onMessage = function($http_connection, $data){
        global $uidConnectionMap;
        $_POST = $_POST ? $_POST : $_GET;
        // 推送数据的url格式 type=publish&to=uid&content=xxxx
        switch(@$_POST['type']){
            case 'publish':
                global $sender_io;
                $to = @$_POST['to'];
                $_POST['content'] = htmlspecialchars(@$_POST['content']);
                // 有指定uid则向uid所在socket组发送数据
                if($to){
                    $sender_io->to($to)->emit('new_msg', $_POST['content']);
                // 否则向所有uid推送数据
                }else{
                    $sender_io->emit('new_msg', @$_POST['content']);
                }
                // http接口返回，如果用户离线socket返回fail
                if($to && !isset($uidConnectionMap[$to])){
                    return $http_connection->send('offline');
                }else{
                    return $http_connection->send('ok');
                }
        }
        return $http_connection->send('fail');
    };
    // 执行监听
    $inner_http_worker->listen();

    // 一个定时器，定时向所有uid推送当前uid在线数及在线页面数
    Timer::add(1, function(){
        global $sender_io, $bsObj;
        $tubArr = $bsObj->getTubeList();
        $tubehtml = $bsObj->tubsArrToHtml($tubArr);
        $sender_io->emit('update_tubes_info', $tubehtml);
        $sender_io->emit('update_tubes_graph', $tubArr);
    });
});

if(!defined('GLOBAL_START'))
{
    Worker::runAll();
}
