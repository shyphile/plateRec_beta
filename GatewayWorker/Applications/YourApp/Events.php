<?php
/**
 * This file is part of workerman.
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the MIT-LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @author walkor<walkor@workerman.net>
 * @copyright walkor<walkor@workerman.net>
 * @link http://www.workerman.net/
 * @license http://www.opensource.org/licenses/mit-license.php MIT License
 */

/**
 * 用于检测业务代码死循环或者长时间阻塞等问题
 * 如果发现业务卡死，可以将下面declare打开（去掉//注释），并执行php start.php reload
 * 然后观察一段时间workerman.log看是否有process_timeout异常
 */
//declare(ticks=1);

use Workerman\Connection\AsyncTcpConnection;
use \GatewayWorker\Lib\Gateway;
date_default_timezone_set('PRC'); //设置中国时区
/**
 * 主逻辑
 * 主要是处理 onConnect onMessage onClose 三个方法
 * onConnect 和 onClose 如果不需要可以不用实现并删除
 */
class Events
{
    /**
     * 当客户端连接时触发
     * 如果业务不需此回调可以删除onConnect
     *
     * @param int $client_id 连接id
     */
    private static $GasyncConn = null;
    public static function onConnect($client_id)
    {
        echo $client_id . "\n";
        Gateway::sendToClient($client_id, json_encode(array(
            'type'      => 'init',
            'client_id' => $client_id,
        )));
    }

    /**
     * 当客户端发来消息时触发
     * @param int $client_id 连接id
     * @param mixed $message 具体消息
     */
    public static function onMessage($client_id, $message)
    {
        $dataArr = json_decode($message, true);
        var_dump($dataArr);
        if (strpos($dataArr[0], ":") !== false) {
            $code = strstr($dataArr[0], ":", true);
            array_shift($dataArr);
            $content = $dataArr;
        } else {
            $code    = $dataArr[0];
            $content = [];
        }

        if ($code !== 'connect') {
            if (end($content) === 'resetled') {
                self::sendDataToLED($code, $content, true);
            } else {
                var_dump($code, $content);
                self::createAsyncTcpConnection(...$content);
               // self::sendDataToLED($code, $content);
            }

        } else {
            //self::createAsyncTcpConnection(...$content);
        }

        // 向所有人发送
        // Gateway::sendToAll("$client_id said $message\r\n");
    }

    /**
     * 当用户断开连接时触发
     * @param int $client_id 连接id
     */
    public static function onClose($client_id)
    {
        // 向所有人发送
        GateWay::sendToAll("$client_id logout\r\n");
    }

    public static function createAsyncTcpConnection($ipAddr)
    {
        
        $time_id;
        if (!is_null(self::$GasyncConn)) {
            return 'camera connected';
        }
        $AsyncConn            = new AsyncTcpConnection("VehicleProtocol://{$ipAddr}:8131");
        $AsyncConn->onConnect = function ($asyncConn) {
           echo "camera connect success--->" . date('h:i:s') . "\n";
            self::$GasyncConn = $asyncConn;
            self::sendDataToLED('open', []);
            $asyncConn->close();
            self::$GasyncConn=null;
            // $GLOBALS['time_id']    = Timer::add(3, function () use ($asyncConn) {
            //     $asyncConn->send('heartBeatPacket');
            // });
            // $GLOBALS['Gconnection']->send('camera connect success');
           
        };
        $AsyncConn->onMessage = function ($asyncConn, $returnval) {
            // var_dump($returnval);
        };

        $AsyncConn->onClose = function ($asyncConn) {
            // $GLOBALS['Gconnection']->send('camera connection closed');
            //Timer::del($GLOBALS['time_id']);
            echo "camera connection closed--->" . date('h:i:s') . "\n";
        };
        $AsyncConn->onError = function ($asyncConn, $code, $msg) {
            echo "Error code:$code msg:$msg\n";
        };
        $AsyncConn->connect();

    }

    public static function sendDataToLED($func, array $data = null, $isResetLED = null, $channel = 2)
    {
        if (is_null(self::$GasyncConn)) {
            return 'camera no connect';
        }

        // $channel = ($channel === 1 ? 'rs485-1' : 'rs485-2');
        // if (is_null($data)) {
        //     $comData = LEDDrive::$func();
        // } else {
        //     $comData = LEDDrive::$func(...$data);
        // }
        $dataArr = array
            (
            'cmd'   => "ioctl",
            "delay" => 1000,
            "io"    => 0,
            "value" => 2,

        );
        $result = self::$GasyncConn->send($dataArr);
        // if (!is_null($isResetLED)) {
        //     sleep(0.5); //延时0.5s重启控制器
        //     $method = __FUNCTION__;
        //     $method('softRest');
        // }
        return $result;

    }

}
