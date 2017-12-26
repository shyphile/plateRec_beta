<?php
namespace app\common;


class LEDCommandPackte
{
	private $_Data;
	private $_DataArry;
	private $_FC;
	private $_LEN;
	private $ResultData;

        // public LEDCommandPackte(byte FC, byte Data)
        // {
        //     this._FC = FC;
        //     this._LEN = 1;
        //     this._Data = Data;
        // }

	public function __construct($FC, $Data=[])
	{
		$this->_FC = $FC;
		$this->_LEN = count($Data);
		$this->_DataArry = $Data;
	}

	private function GetAccValue($NeedVerifyData, $AccLenght)
	{
		$num = 0;
		if ($NeedVerifyData != null)
		{
			for ($i = 0; $i < $AccLenght; $i++)
			{
				$num += $NeedVerifyData[$i];
				//$temp=(int)$num & 0xFF;  //推荐这种方法
				$temp=pack('S',$num);
				$temp=unpack('C', $temp);
				$num=$temp[1];	
			}
		}
		return $num;
	}

	// public function Parsing(byte[] Data, out byte[] Value)
	// {
	// 	bool flag = false;
	// 	Value = null;
	// 	if ((Data.Length > 4) && (this.GetAccValue(Data, Data.Length - 1) == Data[Data.Length - 1]))
	// 	{
	// 		this._FC = Data[1];
	// 		Value = new byte[Data.Length - 3];
	// 		Array.Copy(Data, 2, Value, 0, Value.Length);
	// 		flag = true;
	// 	}
	// 	return flag;
	// }

	public function ToByte()
	{
		$index = 2;
		$this->ResultData[0]=0xfd;
		$this->ResultData[1]=$this->_FC;
		if ($this->_LEN == 1)
		{
			$this->ResultData[index] = $this->_Data;
			$index++;
		}
		else
		{
			$this->ResultData=array_merge($this->ResultData,$this->_DataArry);
			$index +=$this->_LEN;
		}

		$this->ResultData[$index] = $this->GetAccValue($this->ResultData, $this->_LEN + 2);
		return $this->ResultData;
	}
}