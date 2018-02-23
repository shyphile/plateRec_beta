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
use \Workerman\common\LEDDrive;

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
    private static $terminalIP = [];
    public static function onConnect($client_id)
    {
        $ip = $_SERVER['REMOTE_ADDR'];
        // echo $ip . "\n";
        if (in_array($ip, self::$terminalIP)) {
            Gateway::sendToClient($client_id, json_encode(array(
                'type' => 'error',
            )));
            return;
        }
        self::$terminalIP[$client_id] = $ip;
        // var_dump(self::$terminalIP);
        // echo $client_id . "\n";
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
        $ip=$dataArr[0];
        if (strpos($dataArr[1], ":") !== false) {
            $code = substr($dataArr[1], 0, -1);
            array_splice($dataArr,0,2);
            $content = $dataArr;
        } else {
            $code  = $dataArr[1];
            $content = [];
        }
        self::driveCamera($ip, $code, $content);

        // 向所有人发送
        // Gateway::sendToAll("$client_id said $message\r\n");
    }

    /**
     * 当用户断开连接时触发
     * @param int $client_id 连接id
     */
    public static function onClose($client_id)
    {
        echo "$client_id leave";
        $ip               = $_SERVER['REMOTE_ADDR'];
        self::$terminalIP = self::delByKey(self::$terminalIP, $client_id);
        // 向所有人发送
        // GateWay::sendToAll("$client_id logout\r\n");

    }

    private static function delByKey($data, $key)
    {
        // if (!is_array($arr)) {
        //     return $arr;
        // }
        // foreach ($arr as $k => $v) {
        //     if ($v == $value) {
        //         unset($arr[$k]);
        //     }
        // }
        // return $arr;

        if (!array_key_exists($key, $data)) {
            return $data;
        }
        $keys  = array_keys($data);
        $index = array_search($key, $keys);
        if ($index !== false) {
            array_splice($data, $index, 1);
        }
        return $data;
    }

    public static function driveCamera($ip, $code, $content)
    {
        
        $AsyncConn            = new AsyncTcpConnection("VehicleProtocol://{$ip}:8131");
        $AsyncConn->onConnect = function ($asyncConn) use ($code, $content) {
            echo "camera connect success--->" . date('h:i:s') . "\n";
            self::$GasyncConn = $asyncConn;
            self::$code($content);
            $asyncConn->close();
            self::$GasyncConn = null;
        };
        $AsyncConn->onClose = function ($asyncConn) {
            echo "camera connection closed--->" . date('h:i:s') . "\n";
        };
        $AsyncConn->connect();
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
            self::$GasyncConn = null;
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

    private static function init($content)
    {
        $now     = date("Y-m-d H:i:s");
        $dataArr = array
        (
            'cmd'        => "set_time",
            "timestring" => $now,
        );
        self::sendVZcode($dataArr,0.5); //校正相机时间
        self::sendDataToLED('setDateTime'); //校正led时间
        self::sendDataToLED('playSound', ['初始化成功']); //校正led时间
    }

    private static function open($content)
    {
        self::delaySet(0);
    }

    private static function close($content)
    {
        self::delaySet(1);
    }

    private static function carChargeMessage($content)
    {
        self::sendDataToLED('carChargeMessage', $content); //校正led时间
    }

     private static function operationMessage($content)
    {
        self::open([]);
        self::sendDataToLED('operationMessage', $content); //校正led时间
    }


    private static function delaySet($channel)
    {
        $dataArr = array
        (
            'cmd'   => "ioctl",
            "delay" => 500,
            "io"    => $channel,
            "value" => 2,
        );
        self::sendVZcode($dataArr,0.5); 
    }

    private static function sendVZcode($arrayData,$delayTime=1)
    {
        //所有发送给相机的指令在些都要等待0.5秒再执行
        // 以免发送的数据粘包
        // 后续可以改进，通过$result结果判断队列中要发送的数据。
        // 算法可以参考前端收费窗口排队弹出的逻辑

        if (is_null(self::$GasyncConn)) {
            return 'camera no connect';
        }
        $result = self::$GasyncConn->send($arrayData);
        sleep($delayTime);
    }

    public static function sendDataToLED($func, array $data = null, $isResetLED = null, $channel = 2)
    {

        $channel = ($channel === 1 ? 'rs485-1' : 'rs485-2');
        if (is_null($data)) {
            $comData = LEDDrive::$func();
        } else {
            $comData = LEDDrive::$func(...$data);
        }
        $dataArr = array
        (
            'cmd'     => "ttransmission",
            "subcmd"  => 'send',
            "datalen" => $comData['len'],
            'data'    => $comData['code'],
            'comm'    => $channel,
        );
        self::sendVZcode($dataArr); //校正led时间
        // $result = $GLOBALS['GasyncConn']->send($dataArr);
        if (!is_null($isResetLED)) {
            $method = __FUNCTION__;
            $method('softRest');
        }
        // return $result;

    }

}
