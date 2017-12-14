<?php
/**
 * Created by PhpStorm.
 * User: aiyunkeji
 * Date: 10/10/17
 * Time: 8:06 PM
 */

use Swoole\Redis\Server;

$taskServ = new Server("127.0.0.1", 9501, SWOOLE_BASE);

$taskServ->set(array(
    'task_worker_num' => 32,
    'worker_num' => 1,
));

$taskServ->setHandler('LPUSH', function ($fd, $data) use ($taskServ) {
    echo 12233 ."\n";
    echo print_r($data) ."\n";

    $taskId = $taskServ->task($data);
    if ($taskId === false)
    {
        return Server::format(Server::ERROR);
    }
    else
    {
        return Server::format(Server::INT, $taskId);
    }
});

$taskServ->on('Finish', function() {

});

$taskServ->on('Task', function ($serv, $taskId, $workerId, $data) {
    require __DIR__ . "/WsClient.class.php";

    $client = new WsClient('127.0.0.1', '9503');

    $data = $client->connect();

    if(!$client->send('hello Server!!')){
        echo 'send failed!'."\n" ;
    }

    $tmp = $client->recv();
    if(!$tmp){
        die('recv failed!'."\n" ) ;
    }
    echo 'Server Response:'.$tmp."\n" ;
    //处理任务
    //    echo print_r($serv)."\n" ;
    //    echo print_r($taskId)."\n" ;
    //    echo print_r($workerId)."\n" ;
    //    echo print_r($data)."\n" ;
});

$taskServ->start();