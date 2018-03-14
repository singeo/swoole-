<?php
/**
 * Created by PhpStorm.
 * User: aiyunkeji
 * Date: 11/6/17
 * Time: 12:08 AM
 */
$redis = new Redis() ;
$redis->connect('127.0.0.1',6379) ;
//$redis->select(1) ;
//$redis->set('color','yellow') ;
$redis->lPush('testList','hello') ;

$redis->lPush('testList','hello2') ;
$redis->lPush('testList','hello3') ;
$redis->lPush('testList','hello4') ;
$redis->lPush('testList','hello5') ;
$redis->lPush('testList','hello6') ;
$redis->lPush('testList','hello7') ;
$redis->lPush('testList','hello8') ;

echo 'redis is ok' ;