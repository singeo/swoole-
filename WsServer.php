<?php
/**
 * Created by PhpStorm.
 * User: aiyunkeji
 * Date: 11/6/17
 * Time: 2:32 AM
 */
class WsServer
{
    private $server;

    public function __construct()
    {
        $this->server = new swoole_websocket_server("0.0.0.0", 9506);
        $this->server->set(array(
//            'task_worker_num' => 32,
//            'worker_num' => 1,
//            'heartbeat_check_interval' => 5,
//            'heartbeat_idle_time' => 10,
        ));

        $this->server->on('open', function (swoole_websocket_server $server, $request) {

            $stats = $server->stats();
            echo(date("Y-m-d H:i:s", time()) . '  ' . $request->fd . "当前" . $stats['connection_num'] . "人在线\n");
        });

        $this->server->on('message', function (swoole_websocket_server $server, $frame) {

            $getData = json_decode($frame->data,true) ;
            $conn_list = $server->connection_list();
            echo print_r($conn_list) . "\n" ;
            switch ($getData['type']){
                case 'Login':
                    $server->bind($frame->fd,$getData['user_id']) ;
                    $putData['type'] = 'userLogin' ;
                    $putData['user_id'] = $getData['user_id'];
                    foreach ($conn_list AS $fd){
                        if($frame->fd != $fd){
                            $server->push($fd,json_encode($putData)) ;
                        }
                    }
                    break ;
                case 'type':
                //case 'pong' :

            }

        });

        $this->server->on('close', function ($ser, $fd) {
            echo "client {$fd} closed\n";
        });

        $tcpServer = $this->server->listen("127.0.0.1", 9503, SWOOLE_SOCK_TCP) ;
//        $tcpServer->set([
//                'open_length_check' => false,
//                'package_length_type' => 'c',
//                'package_length_offset' => 0,
//                'package_max_length' => 800000,]
//        );
        $tcpServer->on('connect', function ($serv, $fd){
            echo "Client:Connect.\n";
        });

        $tcpServer->on('receive', function ($serv, $fd, $from_id, $data) {
            echo '当期链接=='.$fd ."\n" ;
            $serv->push($fd, $data);
            $serv->close($fd);
        });

        $tcpServer->on('close', function ($serv, $fd) {
            echo "Client: Close.\n";
        });

        $this->server->start();
    }


    private function bindUid($uid,$fd){
        //$conn_list = $this->server->connection_list();
        $redis = new Swoole\Redis;
        $redis->connect('127.0.0.1', 6379, function ($redis, $result) {
            $redis->set('test_key', 'value', function ($redis, $result) {
                $redis->get('test_key', function ($redis, $result) {
                    var_dump($result);
                });
            });
        });

    }
}

new WsServer() ;