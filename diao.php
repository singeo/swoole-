<?php
/**
 * Created by PhpStorm.
 * User: aiyunkeji
 * Date: 10/9/17
 * Time: 2:37 AM
 */
//ini_set("display_errors", "On");
//error_reporting(E_ALL | E_STRICT);
//echo 000;
$redis = new Redis;
echo 111;
echo $redis->connect('127.0.0.1', 9501);
echo 222;
$response = array(
    'type' => 'userLogin',    // 1代表系统消息，2代表用户聊天
    'user_id'=> '123123' ,
    'message' => 'dsadads'
);
$taskId = $redis->lpush("myqueue", json_encode($response));
echo $taskId ;
///

//phpinfo() ;