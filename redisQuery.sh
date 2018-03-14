#!/bin/bash

#开启
function start()
{
    #先检测程序是否已经开启
    pid=`ps -ef | grep "php -f redisQuery.php" | grep -v grep | awk '{print $2}'`

    if [ "$pid" == "" ]
    then
        php -f redisQuery.php >> mailLog &
        echo "程序启动成功"
    else
        echo "程序已经开启过"
    fi

}

#关闭
function stop()
{
    pid=`ps -ef | grep "php -f redisQuery.php" | grep -v grep | awk '{print $2}'`

    if [ "$pid" == "" ]
    then
        echo "程序未开启"
    else
        kill -9 $pid
        echo "程序关闭成功"
    fi
}

#查看开启状态
function status()
{
    pid=`ps -ef | grep "php -f redisQuery.php" | grep -v grep | awk '{print $2}'`

    if [ "$pid" == "" ]
    then
        echo "程序未开启"
    else
        echo "程序运行中,pid: $pid"
    fi
}


#主程序
case "$1" in
"start")
    start
    ;;

"stop" )
    stop
    ;;
"status" )
    status
    ;;

* )
    echo "参数错误! Usage: redisQuery [start|stop|status]"
    ;;

esac