<?php
/**
 * Created by PhpStorm.
 * User: aiyunkeji
 * Date: 12/4/17
 * Time: 12:00 AM
 */

$redis = new Redis() ;

$redis->connect('127.0.0.1',6379) ;

$storeKeys = $redis->keys('*') ;

$res = $redis->llen('goods_store');
echo $res ;

//$redis->flushAll() ;

print_r($storeKeys) ;