<?php

class WebsocketTest {
    private $server;
    private static $db ;
    public function __construct() {
        $this->server = new swoole_websocket_server("0.0.0.0", 9501);
        $this->server->set(array(
//            'heartbeat_check_interval' => 5,
//            'heartbeat_idle_time' => 10,
        ));

        $this->server->on('open', function (swoole_websocket_server $server, $request) {

           self::$db = new swoole_mysql;
            $mySqlserver = array(
                'host' => '192.168.1.88',
                'user' => 'root',
                'password' => '123456',
                'database' => 'mysql',
                'chatset' => 'utf8', //指定字符集
            );

            self::$db->connect($mySqlserver, function ($db, $r) {
                if ($r === false) {
                    var_dump($db->connect_errno, $db->connect_error);
                    die;
                }
            }) ;

            $stats = $this->server->stats() ;
            echo (date("Y-m-d H:i:s", time()) .'  '.$request->fd. "当前" . $stats['connection_num'] . "人在线\n");
        });

        $this->server->on('message', function (swoole_websocket_server $server, $frame) {
            $getdata = json_decode($frame->data,true) ;
            switch ($getdata['type']){
                case 'Login' :
                    $this->handleLogin( $getdata ,$frame->fd) ;
                    break ;
                case 'ping' :
                    $this->server->push($frame->fd, $frame->data) ;
                    break ;
                case 'pong' :
                    $this->server->close($frame->fd, true) ;
                    break ;
                case 'Logout' :
                    $this->server->close($frame->fd, true) ;
                    break;
                case 'driverLocation' :
                    $this->handleDriverLocation($getdata) ;
                    break ;
                case 'driverLine' : //司机线路规划
                    $this->handleDriverLine($getdata) ;
                    break ;
                case 'driverLocationToPassengerOnTime': //司机价格计算，需要向乘客端推送价格信息
                    $this->handleDriverLocation($getdata) ;
                    break;
                case 'OrderGrab' :
                    echo print_r($getdata)."\n";
                    break ;
                default :
                    echo print_r($getdata)."\n";
                    break ;

            }
        });

        $this->server->on('close', function ($ser, $fd) {
            echo "client {$fd} closed\n";
        });

        $this->server->start();
    }

    /**
     * 登录处理方法
     *
     */
    private function handleLogin($getdata,$client_id){
        $user_id = $getdata['user_id'] ;
        echo "[$client_id] login success\n" ;
        $memcache = new \Memcache();
        $memcache->connect('127.0.0.1', 11211) or die ("Could not connect"); //连接Memcached服务器
        $clients = unserialize($memcache->get('client_login')) ;
        echo $memcache->get('client_login') ."\n";
        $isUidBind = array_search($user_id,$clients) ;
        if(!empty($isUidBind)){
            if($isUidBind != $client_id){
                echo $isUidBind . "断开1\n" ;
                $this->server->close($isUidBind) ;
                unset($clients[$isUidBind]) ;
            }
        }
        $clients[$client_id] = $user_id ;
        $memcache->set('client_login', serialize($clients));
        $memcache->close() ;
        echo 'user_id:'.$user_id." Login\n";
        //$this->server->bind($client_id, $user_id) ;
    }


    /**
     * 司机当前位置更新表
     * @param $getdata
     */
    private function handleDriverLocation($getdata){
        $user_id = $getdata['user_id'] ;
        $decodeUserID = self::decrypt($user_id) ;
        $sql = 'UPDATE ayb_driver_online SET location_latitude = '.$getdata['latitude']
            .',location_longitude = '.$getdata['longitude'].',location_time = '.time()
            .' where driver_id = '.$decodeUserID;
        self::$db->query($sql) ;
    }

    /**
     * 司机线路规划
     * @param $getdata
     */
    private function handleDriverLine($getdata){
        $memcache = new \Memcache();
        $memcache->connect('127.0.0.1', 11211) or die ("Could not connect"); //连接Memcached服务器
        $order_no = $getdata['order_no'] ;
        $line = $getdata['line'] ;
        $memcache->set('line_'.$order_no, $line);
        $memcache->close() ;
//        $sql = 'INSERT INTO ayb_order_line (order_no,order_line,add_time) VALUES (\''.$order_no.'\',\''.json_encode($line).'\','.time().')';
//        self::$db->query($sql) ;
        $putdata = [
            'type' => 'driverOnLine',
            'order_no' => $order_no,
            'line'=> $line
        ];
        $this->server->push($this->getFdByUserID($getdata['passenger_id']), json_encode($putdata));
    }

    /**
     * 司机价格计算，需要向乘客端推送价格信息
     * @param $getdata
     */
    private function handlePassengerOnTime($getdata){
        $order_no = $getdata['order_no'] ;
        $priceDetail = $getdata['priceDetail'] ;
        if(!empty($priceDetail)){
            $memcache = new \Memcache();
            $memcache->connect('127.0.0.1', 11211) or die ("Could not connect"); //连接Memcached服务器
            $orderInfo['price'] = $priceDetail['price'] ;
            $priceDetailKey = 'price_'.$order_no ;
            $memcache->delete($priceDetailKey);
            $memcache->set($priceDetailKey, serialize($priceDetail), 0, 3600);
            $memcache->close() ;
        }
        if(empty($getdata['location'])){
            $orderInfo['location'] = (object) array()  ;
        }else{
            $orderInfo['location'] = (object) $getdata['location'] ;
        }
        $orderInfo['naviInfo'] = $getdata['naviInfo'] ;
        $putdata = [
            'type' => 'driverLocationToPassengerOnTime',
            'order_no' => $getdata['order_no'],
            'driver_id'=> $getdata['user_id'],
            'data'=> $orderInfo
        ];
        $this->server->push($this->getFdByUserID($getdata['passenger_id']), json_encode($putdata));
    }

    /**
     * 根据userID 获取用户客户端ID
     * @param $user_id
     * @return false|int|string
     */
    private function getFdByUserID($user_id){
        $memcache = new \Memcache();
        $memcache->connect('127.0.0.1', 11211) or die ("Could not connect"); //连接Memcached服务器
        $clients = unserialize($memcache->get('client_login')) ;
        $fd = array_search($user_id,$clients) ;
        $memcache->close() ;
        return $fd ;
    }

    /**
     * 字符串加密
     * @param string 所需要加密的字符串
     * @return 返回所加密的字符串
     */
    private static function encrypt($string = '')
    {
        $app_key = 'JHKQi11&*HQ2skj1)!@DSaw3ucxzw'; //加密字符串所用的KEY
        $strArr = str_split(base64_encode($string));
        $strCount = count($strArr);
        foreach (str_split($app_key) as $key => $value){
            $key < $strCount && $strArr[$key] .= $value;
        }
        $enstr = str_replace(array('=', '+', '/'), array('O0O0O', 'o000o', 'oo00o'), join('', $strArr));
        return base64_encode($enstr) ;
    }

    /**
     * 字符串解密
     * @param string 所需要解密的字符串
     * @return 返回解密得到的字符
     */
    private static function decrypt($string = '')
    {
        $app_key = 'JHKQi11&*HQ2skj1)!@DSaw3ucxzw'; //加密字符串所用的KEY
        if ($string) {
            $string = base64_decode($string) ;
            $strArr = str_split(str_replace(array('O0O0O', 'o000o', 'oo00o'), array('=', '+', '/'), $string), 2);
            $strCount = count($strArr);
            foreach (str_split($app_key) as $key => $value){
                $key <= $strCount && isset($strArr[$key]) && $strArr[$key][1] === $value && $strArr[$key] = $strArr[$key][0];
            }
            return base64_decode(join('', $strArr));
        } else {
            return false;
        }
    }
}
new WebsocketTest();

?>