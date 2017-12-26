<?php
namespace app\common;
/**
* led驱动板的控制类
*/
class LEDDrive 
{
	private static $_commcode;
	private static $ResultValue = 0;

	// public static CheckResultData($ResultData=[])
	// {
	// 	$num = -1;
	// 	if (ResultData.Length < 5)
	// 	{
	// 		return num;
	// 	}
	// 	if (ResultData[ResultData.Length - 1] == GetAccValue(ResultData, ResultData.Length - 1))
	// 	{
	// 		_commcode = ResultData[1];
	// 		ResultValue = ResultData[3];
	// 		return 1;
	// 	}
	// 	return -2;
	// }

	// public static bool CheckResultData(byte[] ResultData, out byte[] Result)
	// {
	// 	bool flag = false;
	// 	Result = null;
	// 	if ((ResultData.Length >= 5) && (ResultData[ResultData.Length - 1] == GetAccValue(ResultData, ResultData.Length - 1)))
	// 	{
	// 		_commcode = ResultData[1];
	// 		ResultValue = ResultData[3];
	// 		flag = true;
	// 		Result = new byte[ResultData[3]];
	// 		Array.Copy(ResultData, 4, Result, 0, Result.Length);
	// 	}
	// 	return flag;
	// }

	// private static byte GetAccValue(byte[] NeedVerifyData, int AccLenght)
	// {
	// 	byte num = 0;
	// 	if (NeedVerifyData != null)
	// 	{
	// 		for (int i = 0; i < AccLenght; i++)
	// 		{
	// 			num = (byte)(num + NeedVerifyData[i]);
	// 		}
	// 	}
	// 	return num;
	// }

 //        public static bool ReadVersion(out string VerStr) //读版本号
 //        {
 //        	byte[] buffer2;
 //        	VerStr = "";
 //        	bool flag = false;
 //        	byte[] data = new LEDCommandPackte(50, 0).ToByte();
 //        	link.Send(data, data.Length);
 //        	byte[] result = null;
 //        	if (link.Receive(out buffer2) && CheckResultData(buffer2, out result))
 //        	{
 //        		string[] textArray1 = new string[] { "Ver:", result[0].ToString(), ".", result[1].ToString(), ".", result[2].ToString() };
 //        		VerStr = string.Concat(textArray1);
 //        		flag = true;
 //        	}
 //        	return flag;
 //        }


 //        public static void SetDateTime() //校正时间
 //        {
 //        	byte[] data = new byte[] { (byte)(DateTime.Now.Year - 0x7d0), (byte)DateTime.Now.Month, (byte)DateTime.Now.Day, (byte)DateTime.Now.Hour, (byte)DateTime.Now.Minute, (byte)DateTime.Now.Second };
 //        	byte[] buffer = new LEDCommandPackte(4, data).ToByte();
 //        	link.Send(buffer, buffer.Length);
 //        }


 //        public static bool SetParm(byte LEDType, byte LEDPolar, bool IsDoubleColor) //设置屏幕类型
 //        {
 //        	byte[] buffer2;
 //        	bool flag = false;
 //        	byte data = (byte)(LEDType & 3);
 //        	int num2 = (LEDPolar << 6) & 0x40;
 //        	data = (byte)(data + ((byte)num2));
 //        	if (IsDoubleColor)
 //        	{
 //        		data = (byte)(data | 0x80);
 //        	}
 //        	byte[] buffer = new LEDCommandPackte(1, data).ToByte();
 //        	string temp = "";
 //        	foreach (byte b in buffer)
 //        	{
 //        		temp += b.ToString("X2");
 //        	}
 //        	link.Send(buffer, buffer.Length);
 //        	if (link.Receive(out buffer2))
 //        	{
 //        		if (CheckResultData(buffer2) != 1)
 //        		{
 //        			return flag;
 //        		}
 //        		if (ResultValue == 1)
 //        		{
 //        			flag = true;
 //        		}
 //        	}
 //        	return flag;
 //        }

 //        public static bool SoftRest() //重启控制板
 //        {
 //        	byte[] buffer2;
 //        	bool flag = false;
 //        	byte[] data = new LEDCommandPackte(0x2a, 1).ToByte();
 //        	string temp = "";
 //        	foreach (byte b in data)
 //        	{
 //        		temp += b.ToString("X2");
 //        	}
 //        	link.Send(data, data.Length);
 //        	if (link.Receive(out buffer2))
 //        	{
 //        		if (CheckResultData(buffer2) != 1)
 //        		{
 //        			return flag;
 //        		}
 //        		if (ResultValue == 1)
 //        		{
 //        			flag = true;
 //        		}
 //        	}
 //        	return flag;
 //        }

        public static function PlaySound($operationMessage)  //播放声音
        {
        	$index = 0;
        	$operationMessage =static::characet($operationMessage);
        	$bytes=self::string2bytes($operationMessage);
        	array_unshift($bytes,count($bytes));
        	array_push($bytes, 0);

        	$buffer = new LEDCommandPackte(9, $bytes);
        	return $buffer->ToByte();
        }

        private static function characet($data){
        	if( !empty($data) ){
        		$fileType = mb_detect_encoding($data , array('UTF-8','GBK','LATIN1','BIG5'));
        		if( $fileType != 'GBK'){
        			$data = mb_convert_encoding($data ,'GBK' , $fileType);
        		}
        	}
        	return $data;
        }

        private static function string2bytes($str){
        	$bytes=array();
        	for ($i=0; $i < strlen($str); $i++) { 
        		$tmp=substr($str, $i,1);
        		$bytes[]=hexdec(bin2hex($tmp));
        	}
        	return $bytes;
        }

        // public static bool SetProgram(byte ProgramIndex, string ProgramStr)  //设置屏幕内容
        // {
        // 	byte[] buffer4;
        // 	bool flag = false;
        // 	byte[] bytes = Encoding.GetEncoding("GBK").GetBytes(ProgramStr);
        // 	byte[] destinationArray = new byte[bytes.Length + 2];
        // 	destinationArray[0] = ProgramIndex;
        // 	destinationArray[1] = (byte)bytes.Length;
        // 	Array.Copy(bytes, 0, destinationArray, 2, bytes.Length);
        // 	byte[] buffer = new LEDCommandPackte(11, destinationArray).ToByte();
        // 	link.Send(buffer, buffer.Length);
        // 	if (link.Receive(out buffer4))
        // 	{
        // 		if (CheckResultData(buffer4) != 1)
        // 		{
        // 			return flag;
        // 		}
        // 		if (ResultValue == 1)
        // 		{
        // 			flag = true;
        // 		}
        // 	}
        // 	return flag;
        // }

        // public static bool SetBrightnes(byte Brightnes) //设置亮度
        // { 
        // 	bool flag = false;
        // 	if ((Brightnes > 0) && (Brightnes < 0x11))
        // 	{
        // 		byte[] buffer2;
        // 		byte[] buffer = new LEDCommandPackte(3, Brightnes).ToByte();
        // 		link.Send(buffer, buffer.Length);
        // 		if (!link.Receive(out buffer2))
        // 		{
        // 			return flag;
        // 		}
        // 		if (CheckResultData(buffer2) != 1)
        // 		{
        // 			return flag;
        // 		}
        // 		if (ResultValue == 1)
        // 		{
        // 			flag = true;
        // 		}
        // 	}
        // 	return flag;
        // }

        // public static bool SetMoveSpeed(byte UPSpeed, byte DownSpeed) //设置速度
        // {
        // 	byte[] buffer3;
        // 	bool flag = false;
        // 	byte[] data = new byte[] { UPSpeed, DownSpeed };
        // 	byte[] buffer = new LEDCommandPackte(2, data).ToByte();
        // 	link.Send(buffer, buffer.Length);
        // 	if (link.Receive(out buffer3))
        // 	{
        // 		if (CheckResultData(buffer3) != 1)
        // 		{
        // 			return flag;
        // 		}
        // 		if (ResultValue == 1)
        // 		{
        // 			flag = true;
        // 		}
        // 	}
        // 	return flag;
        // }

        // public static bool SetVolume(byte Volume) //设置音量
        // {
        // 	if ((Volume >= 0) && (Volume < 11))
        // 	{
        // 		byte[] buffer = new LEDCommandPackte(15, Volume).ToByte();
        // 		link.Send(buffer, buffer.Length);
        // 	}
        // 	return false;
        // }

        //  public byte[] ToByte()
        // {
        //     int index = 2;
        //     this.ResultData = new byte[this._LEN + 3];
        //     this.ResultData[0] = 0xfd;
        //     this.ResultData[1] = this._FC;
        //     if (this._LEN == 1)
        //     {
        //         this.ResultData[index] = this._Data;
        //         index++;
        //     }
        //     else
        //     {
        //         Array.Copy(this._DataArry, 0, this.ResultData, index, this._LEN);
        //         index += this._LEN;
        //     }
        //     this.ResultData[index] = this.GetAccValue(this.ResultData, this._LEN + 2);
        //     return this.ResultData;
        // }



    }
