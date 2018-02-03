<?php
namespace Workerman\Protocols;

use Workerman\Connection\TcpConnection;

/**
 * 本类三个作用:
 * 1.接收相机发送来的应答数据;
 * 2.将应答数据解包;
 * 3.将发送给相机的命令数组打包成二进制数据.
 */
class VehicleProtocol
{
    //发送给相机的命令序号.
    private static $identifier = 0; 

    /**
     * 接收相机的应答数据
     * @param  $buffer     相机应答的数据流
     * @param  $connection 异步连接相机的 连接对象
     * @return $buffer的数据长度
     */
    public static function input($buffer, TcpConnection $connection)
    {
        // Judge whether the package length exceeds the limit.
        if (strlen($buffer) >= TcpConnection::$maxPackageSize) {
            $connection->close();
            return 0;
        }
        //vz相机tcp连接协议头有8个字节,返回0表示继续接收应答数据.
        if (strlen($buffer) < 8) {
            return 0;  
        }
        $len  = substr($buffer, 4);
        $len  = unpack('NdataLen', $len);
        $temp = $len['dataLen'] + 8; //坑，这里必须加上包头的长度：8个字节。
        return $temp;
    }


    /**
     * 上位机发送指令给相机时,自动调用此方法组包,生成组包数据后再发送给相机
     * @param  any $buffer 指令数据 
     * @return 相机能识别的协议头+json的数据包
     */
    public static function encode($buffer)
    {
        //程序给相机的心跳包
        if ($buffer === 'heartBeatPacket') {
           return $heartBeatPacket = pack("c4N", 86, 90, 1, 0, 0);
        }

        //一般的控制指令
        $jsonCMD = json_encode($buffer);
        $len     = strlen($jsonCMD); //这里就不需要加上包头的长度。
        $exeCode = pack("c4N", 86, 90, 0, self::$identifier++, $len) . $jsonCMD;
        if (self::$identifier > 0xff) {
            self::$identifier = 0;
        }
        return $exeCode;
    }

    /**
     * 上位机收到相机应答时,自动调用此方法解包,生成解包数据后再返回给处理程序
     * @param  $buffer 应答数据,一般为json.
     * @return array   去掉协议头后的数据,数组类型
     */
    public static function decode($buffer)
    {
        $abc     = self::characet(substr($buffer, 8, -1)); //不含协议头的数据,用utf8编码
        $recData = json_decode($abc, true);
        return $recData;
    }

    /**
     * utf8编码二进制数据.
     * @param  [type] $data [description]
     * @return [type]       [description]
     */
    private static function characet($data)
    {
        if (!empty($data)) {
            $fileType = mb_detect_encoding($data, array('UTF-8', 'GBK', 'LATIN1', 'BIG5'));
            if ($fileType != 'UTF-8') {
                $data = mb_convert_encoding($data, 'utf-8', $fileType);
            }
        }
        return $data;
    }
}
