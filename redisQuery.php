<?php
/**
 * Created by PhpStorm.
 * User: aiyunkeji
 * Date: 3/13/18
 * Time: 11:42 PM
 */
$log_file = __DIR__ . '/log.txt' ;
$redis = new Redis();
$redis->connect('127.0.0.1',6379) ;
while (true){
    $task = $redis->rPop('testList') ;
    if($task){
        $fp = fopen($log_file,'a+') ;
        fwrite($fp,$task."\n") ;
        fclose($fp) ;
    }
}