<?php
namespace Workerman\common;

/**
 * led命令组包类
 */
class LEDCommandPackte
{
    private $_Data;
    private $_FC;
    private $_LEN;
    private $ResultData;

    public function __construct($FC, $Data)
    {
        $this->_FC   = $FC;
        $this->_LEN  = is_array($Data) ? count($Data) : 1;
        $this->_Data = $Data;
    }
/**
 * 和校验
 * @param [type] $NeedVerifyData [description]
 * @param [type] $AccLenght      [description]
 */
    private function GetAccValue($NeedVerifyData, $AccLenght)
    {
        $num = 0;
        if (!is_null($NeedVerifyData)) {
            for ($i = 0; $i < $AccLenght; $i++) {
                $num += $NeedVerifyData[$i];
                $num = (int) $num & 0xFF; //推荐这种方法
                // $temp=pack('S',$num);
                // $temp=unpack('C', $temp);
                // $num=$temp[1];
            }
        }
        return $num;
    }
/**
 * 工具方法 将命令转成字节数组
 */
    public function ToByte()
    {
        $this->ResultData[] = 0xfd;
        $this->ResultData[] = $this->_FC;
        if ($this->_LEN == 1) {
            $this->ResultData[] = $this->_Data;
        } else {
            $this->ResultData = array_merge($this->ResultData, $this->_Data);
        }

        $this->ResultData[] = $this->GetAccValue($this->ResultData, $this->_LEN + 2);
        return $this->ResultData;
    }
}
