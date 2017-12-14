<?php
//phpinfo() ;
//require __DIR__ . "/WsClient.class.php";
//
//$client = new WsClient('127.0.0.1', '9512');
//
//$data = $client->connect();
//
//$client->send('hello Server!!');
//
//$tmp = $client->recv();
//echo $tmp ;

//echo $data ;
//error_reporting(E_ALL) ;
$client = new swoole_client(SWOOLE_SOCK_TCP);

//连接到服务器
if (!$client->connect('127.0.0.1', 9506, 0.5)) {
    die("connect failed.");
}
//向服务器发送数据
if (!$client->send("hello world")) {
    die("send failed.");
}
//从服务器接收数据
$data = $client->recv();
if (!$data) {
    die("recv failed.");
}
echo $data;
//关闭连接
$client->close();