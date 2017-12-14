<?php

class ClientTest{
    private $cServ ;
    private $serverAddr = '127.0.0.1' ;
    private $serverPort = '9512' ;
    public function __construct()
    {
        echo 132 ;
        $this->cServ = new swoole_client(SWOOLE_SOCK_TCP,SWOOLE_SOCK_ASYNC);
        $this->cServ->on("connect", function($serv){
            $this->onConnect($serv) ;
        });
        $this->cServ->on('receive', array($this, 'onReceive')) ;
        $this->cServ->on('error', array($this, 'onError')) ;
        $this->cServ->on('close', array($this, 'onClose')) ;
        $this->cServ->connect($this->serverAddr,$this->serverPort) ;
    }

    public function onConnect($server){
        echo 'connect success' ;
    }

    public function onError($server){
        echo 'ERROR!' ;
    }

    public function onReceive($server,$msg){
        echo $msg ;
    }



    public function send($data){
        //$user_id = $data['user_id'] ;
        $this->cServ->send($data) ;
        $this->close() ;
    }

    public function onClose($server){
        echo 'closed connected' ;
        //$this->cServ->close() ;
    }
}

$cserv = new ClientTest() ;


?>