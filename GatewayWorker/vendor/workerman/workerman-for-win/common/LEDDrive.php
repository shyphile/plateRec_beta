<?php
namespace Workerman\common;

/**
 * led驱动板的控制类
 * 因为workerman异步tcp不能获取相机串口返回的数据,
 * 所以本类只能单向发数据给相机,
 * 然后由相机透明传输给led控制板.
 */
class LEDDrive
{
    private static $_commcode;
    private static $ResultValue = 0;

    //此方法没用 因为workerman的异步tcp获取不到相机的串口返回数据.
    public static function readVersion()
    {
        $data = new LEDCommandPackte(50, 0);
        $data = $data->ToByte();
        $data = self::getSendData($data);
        return $data;
    }

    //校正时间
    public static function setDateTime()
    {
        $year    = (date("Y") - 2000) & 0xff;
        $month   = date("m") & 0xff;
        $day     = date("d") & 0xff;
        $hour    = date("G") & 0xff;
        $minute  = date("i") & 0xff;
        $second  = date("s") & 0xff;
        $dateArr = array($year, $month, $day, $hour, $minute, $second);
        $buffer  = new LEDCommandPackte(4, $dateArr);
        return self::getSendData($buffer->ToByte());
    }

    //设置屏幕类型
    public static function setLEDType($LEDType, $LEDPolar, $IsDoubleColor)
    {
        $data = $LEDType & 3;
        $num  = ($LEDPolar << 6) & 0x40;
        $data = $data + $num;
        if ($IsDoubleColor) {
            $data = $data | 0x80;
        }
        $buffer = new LEDCommandPackte(1, $data);
        return self::getSendData($buffer->ToByte());
    }

    //重启控制板
    public static function softRest()
    {
        $data = new LEDCommandPackte(0x2a, 1);
        $data = $data->ToByte();
        $data = self::getSendData($data);
        return $data;
    }

    //播放声音
    public static function playSound($operationMessage)
    {
        $byte = static::encodeGBK2Bytes($operationMessage);
        array_unshift($byte, count($byte));
        array_push($byte, 0);

        $buffer = new LEDCommandPackte(9, $byte);
        return self::getSendData($buffer->ToByte());
    }

    //设置屏幕内容
    public static function setContent($ProgramIndex, $ProgramStr)
    {
        $byte = static::encodeGBK2Bytes($ProgramStr);
        array_unshift($byte, count($byte));
        array_unshift($byte, $ProgramIndex);
        $buffer = new LEDCommandPackte(11, $byte);
        $buffer = $buffer->ToByte();
        return self::getSendData($buffer);
    }

    //出场收费信息
    public static function carChargeMessage($Color, $Plate, $UseDays, $UseHours, $UseMinutes, $ChargeMessage, $DispTime)
    {
        $ChargeMessage=$ChargeMessage.'元';
        $DataArry    = array();
        $PlateArray  = static::encodeGBK2Bytes($Plate);
        $ChargeArray = static::encodeGBK2Bytes($ChargeMessage);
        $DataArry[]  = $Color;
        $DataArry[]  = count($PlateArray);
        $DataArry    = array_merge($DataArry, $PlateArray);
        $DataArry[]  = $UseDays;
        $DataArry[]  = $UseHours;
        $DataArry[]  = $UseMinutes;
        $DataArry[]  = count($ChargeArray);
        $DataArry    = array_merge($DataArry, $ChargeArray);
        $DataArry[]  = $DispTime;
        $buffer      = new LEDCommandPackte(6, $DataArry);
        $buffer      = $buffer->ToByte();
        return self::getSendData($buffer);
    }

    //进场提示信息
    public static function carIOProcess($Color, $Plate, $VehicleType, $Message, $DispTime)
    {
        $DataArry         = array();
        $PlateArray       = static::encodeGBK2Bytes($Plate);
        $VehicleTypeArray = static::encodeGBK2Bytes($VehicleType);
        $MessageArray     = static::encodeGBK2Bytes($Message);
        $DataArry[]       = $Color;
        $DataArry[]       = count($PlateArray);
        $DataArry         = array_merge($DataArry, $PlateArray);
        $DataArry[]       = count($VehicleTypeArray);
        $DataArry         = array_merge($DataArry, $VehicleTypeArray);
        $DataArry[]       = count($MessageArray);
        $DataArry         = array_merge($DataArry, $MessageArray);
        $DataArry[]       = $DispTime;
        // var_dump($DataArry);
        $buffer = new LEDCommandPackte(5, $DataArry);
        $buffer = $buffer->ToByte();
        return self::getSendData($buffer);
    }

    //剩余车位显示
    public static function dispParkSpace($Color, $SpaceStr, $DispTime, $IsSound)
    {
        $DataArry   = array();
        $MsgArray   = static::encodeGBK2Bytes($SpaceStr);
        $DataArry[] = $Color;
        $DataArry[] = count($MsgArray);
        $DataArry   = array_merge($DataArry, $MsgArray);
        $DataArry[] = $DispTime;
        $DataArry[] = $IsSound ? 1 : 0;
        $buffer     = new LEDCommandPackte(13, $DataArry);
        $buffer     = $buffer->ToByte();
        return self::getSendData($buffer);
    }

    //到期天数播报
    public static function expireCarMessage($Color, $Plate, $VehicleType, $AvailableDays, $DispTime)
    {
        $DataArry         = array();
        $PlateArray         = static::encodeGBK2Bytes($Plate);
        $VehicleTypeArray = static::encodeGBK2Bytes($VehicleType);
        $DataArry[]       = $Color;
        $DataArry[]       = count($PlateArray);
        $DataArry         = array_merge($DataArry, $PlateArray);
        $DataArry[]       = count($VehicleTypeArray);
        $DataArry         = array_merge($DataArry, $VehicleTypeArray);
        $DataArry[]       = $AvailableDays;
        $DataArry[]       = $DispTime;
        $buffer           = new LEDCommandPackte(7, $DataArry);
        $buffer           = $buffer->ToByte();
        return self::getSendData($buffer);
    }

    //确认放行后的提示音
    public static function operationMessage($Color, $OperationMessage, $DispTime, $IsSound)
    {
        $DataArry   = array();
        $MsgArray   = static::encodeGBK2Bytes($OperationMessage);
        $DataArry[] = $Color;
        $DataArry[] = count($MsgArray);
        $DataArry   = array_merge($DataArry, $MsgArray);
        $DataArry[] = $DispTime;
        $DataArry[] = $IsSound ? 1 : 0;
        $buffer     = new LEDCommandPackte(8, $DataArry);
        $buffer     = $buffer->ToByte();
        return self::getSendData($buffer);
    }

    //设置亮度
    public static function setBrightnes($Brightnes)
    {
        if (($Brightnes > 0) && ($Brightnes < 0x11)) {
            $buffer = new LEDCommandPackte(3, $Brightnes);
            $buffer = $buffer->ToByte();
            return self::getSendData($buffer);
        }
    }
    //设置速度
    public static function setMoveSpeed($UPSpeed, $DownSpeed)
    {
        $data   = array($UPSpeed, $DownSpeed);
        $buffer = new LEDCommandPackte(2, $data);
        $buffer = $buffer->ToByte();
        return self::getSendData($buffer);
    }

    //设置音量
    public static function setVolume($Volume)
    {
        if (($Volume >= 0) && ($Volume < 11)) {
            $buffer = new LEDCommandPackte(15, $Volume);
            $buffer = $buffer->ToByte();
            return self::getSendData($buffer);
        }
    }

    //获取发送给相机的二进制数据
    private static function getSendData(array $arrValue)
    {
        $strValue = '';
        array_walk($arrValue, function ($value) use (&$strValue) {
            $strValue .= pack('C', $value);
        });
        $code = base64_encode($strValue);
        $len  = count($arrValue);
        return array(
            'len'  => $len,
            'code' => $code,
        );
    }

    //字符编码,GBK
    private static function encodeGBK2Bytes($data)
    {
        $byte = array();
        if (!empty($data)) {
            $fileType = mb_detect_encoding($data, array('UTF-8', 'GBK', 'LATIN1', 'BIG5'));
            if ($fileType != 'GBK') {
                $data = mb_convert_encoding($data, 'GBK', $fileType);
            }
        }

        for ($i = 0; $i < strlen($data); $i++) {
            $tmp    = substr($data, $i, 1);
            $byte[] = hexdec(bin2hex($tmp));

        }
        return $byte;
    }
}
