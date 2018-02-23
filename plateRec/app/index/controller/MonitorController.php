<?php
namespace app\index\controller;

use app\common\Gateway;
use app\common\LEDDrive;
use app\index\model\Camerainfo;
use app\index\model\Chargetypes;
use app\index\model\Inoutrecord;
use app\index\model\Onparkvehicle;
use app\index\model\Vehicleinfo;
use think\Controller;

/**
 *
 */
class MonitorController extends Controller
{
    //uid与相机的终端IP绑定了
    //实现的效果与v10车牌识别管理软件一致,只有指定的电脑才能访问指定的相机

    //跳的坑提醒:
    //index方法是相机推送数据的,而bind方法是前台用来绑定uid的.
    //web服务器会为浏览器和相机开辟两个单独进程.
    //这也就造成这两个方法不能共享这个类的成员变量和session数据.
    //介于此,就只能通过终端IP的方式来实现uid的变相共享.
    public function index()
    {
        $plateJsonData = file_get_contents("php://input");
        $info          = $this->getPlateInfo($plateJsonData);
        // $info          = ['ipAddr' => '192.168.0.21', 'plate' => '鄂A8BK78', 'base64Img' => ''];
        $cameraIP   = $info['ipAddr'];
        $plate      = $info['plate'];
        $now        = date("Y-m-d H:i:s");
        $camerainfo = Camerainfo::where('IP', $cameraIP)->field('TerminalIP,IsInCemera,EnableTempCar,IsEnable')->find();
        $carInfo    = Vehicleinfo::where('Plate', $plate)->field('UserName,VehicleType,EndTime')->find();

        if (!is_null($camerainfo)) {
            $terminalIP = $camerainfo->getData('TerminalIP');

            //判断对应电脑实时监控有没开
            if (Gateway::isUidOnline($terminalIP)) {
                if ($camerainfo->getData('IsEnable')) {

                    //车辆进场处理
                    if ($camerainfo->getData('IsInCemera')) {

                        $info     = array_merge($info, ['InTime' => $now]);
                        $jsonInfo = json_encode($info);
                        Onparkvehicle::saveModel(null, $info);
                        Inoutrecord::saveModel(null, $info);
                        Gateway::sendToUid($terminalIP, $jsonInfo);
                        $VehicleType = null;
                        if (!is_null($carInfo)) {
                            $VehicleType = $carInfo->getData('VehicleType');
                        }
                        return $this->responseToCamera($info['plate'], $VehicleType,0); //控制相机指令
                    }

                    //车辆出场处理
                    //月租车
                    if (!is_null($carInfo)) {
                         $info     = array_merge($info, ['InTime' => $now]);
                        $jsonInfo = json_encode($info);
                        Gateway::sendToUid($terminalIP, $jsonInfo);
                        return $this->responseToCamera($info['plate'], '月租车', 1); //控制相机指令
                    }
                    $outCarInfo = Onparkvehicle::where('Plate', $plate)->field('VehicleType,InTime,ChargeTypeID,ChargeTypeName')->find();

                    if (is_null($outCarInfo)) {
                        $info     = array_merge($info, ['type' => 'noRecord']);
                        $jsonInfo = json_encode($info);
                        Gateway::sendToUid($terminalIP, $jsonInfo);
                        return $this->responseToCamera($info['plate'], '无记录', 2); //控制相机指令
                    }
                    $timediff = $this->timediff($outCarInfo->getData('InTime'), $now);

                    $Chargetypes   = Chargetypes::getOneModel($outCarInfo->getData('ChargeTypeID'));
                    $chargeParmArr = $Chargetypes->ReadParmFrom24();

                    $parkingDay  = $timediff['day'] ? $timediff['day'] . '天' : '0天';
                    $parkingHour = $timediff['hour'] ? $timediff['hour'] . '时' : '0时';
                    $parkingMin  = ($timediff['min'] > 1) ? $timediff['min'] . '分' : '0分';
                    $parkingSec  = ($timediff['sec'] > 1) ? $timediff['sec'] . '秒' : '0秒';
                    $_timediff   = $parkingDay . $parkingHour . $parkingMin . $parkingSec;
                    $money       = $this->calcMoney($timediff, $chargeParmArr);
                    $tempInfo    = array('outTime' => $now, 'money' => $money, 'parkingTime' => $_timediff);
                    $info        = array_merge($info, $outCarInfo->toArray());
                    $info        = array_merge($info, $tempInfo);
                    // dump($info);
                    $jsonInfo = json_encode($info);
                    Gateway::sendToUid($terminalIP, $jsonInfo);
                }
            }

        }

    }

    private function calcMoney($timediff, $chargeParmArr)
    {
        $freeTime  = $chargeParmArr['FreeTime'] / 60;
        $chargeMax = $chargeParmArr['ChargeMaxAmount'] < $chargeParmArr['timePerMoney'][24] ? $chargeParmArr['ChargeMaxAmount'] : $chargeParmArr['timePerMoney'][24];
        if (($timediff['day'] * 24 + $timediff['hour']) > $freeTime) {
            $rangeHour = ($timediff['min'] > 1) ? ($timediff['hour'] + 1) : $timediff['hour'];
            $hourMoney = $chargeMax > $chargeParmArr['timePerMoney'][$rangeHour] ? $chargeParmArr['timePerMoney'][$rangeHour] : $chargeMax;

            $money = $timediff['day'] * $chargeMax + $hourMoney;
            return $money;
        }
        return 0;
    }

    private function timediff($begin_time, $end_time)
    {
        $begin_time = strtotime($begin_time);
        $end_time   = strtotime($end_time);
        if ($begin_time < $end_time) {
            $starttime = $begin_time;
            $endtime   = $end_time;
        } else {
            $starttime = $end_time;
            $endtime   = $begin_time;
        }
        $timediff = $endtime - $starttime;
        $days     = intval($timediff / 86400);
        $remain   = $timediff % 86400;
        $hours    = intval($remain / 3600);
        $remain   = $remain % 3600;
        $mins     = intval($remain / 60);
        $secs     = $remain % 60;
        $res      = array("day" => $days, "hour" => $hours, "min" => $mins, "sec" => $secs);
        return $res;
    }

    public function responseToCamera(...$param)
    {
        $vehicleType = isset($param[1]) ? $param[1] : '临时车';
        switch ($param[2]) {
            case 0:
                $arrValue = LEDDrive::carIOProcess(3, $param[0], $vehicleType, '欢迎光临', 10);
                break;
            case 1:
                $arrValue = LEDDrive::carIOProcess(3, $param[0], $vehicleType, '一路顺风', 10);
                break;
           case 2:
                $arrValue = LEDDrive::carIOProcess(3, $param[0], $vehicleType, '无入场记录', 10);
                break;
        }
        
        $code = $arrValue['code'];
        $len  = $arrValue['len'];
        $data = [
            "Response_AlarmInfoPlate" =>
            [
                'info'       => 'ok',
                "serialData" => [
                    [
                        "serialChannel" => 1,
                        "data"          => "$code",
                        "dataLen"       => $len,
                    ],
                ],
            ],
        ];
        $json = json_encode($data);
        return $json;
    }

    public function show()
    {
        $cameraIPs = Camerainfo::where([
            'TerminalIP' => $_SERVER['REMOTE_ADDR'],
            'IsEnable'   => 1,
        ])->field('Name,IP,ParkID')->select();
        $this->assign('cameraIPs', $cameraIPs);
        return view();
    }

    private function getPlateInfo($plateJsonData)
    {
        // $this->writeTxt($plateJsonData);
        $plateArrData = json_decode($plateJsonData, true);
        $ipAddr       = $plateArrData['AlarmInfoPlate']['ipaddr'];
        $plate        = $plateArrData['AlarmInfoPlate']['result']['PlateResult']['license'];
        $base64Img    = $plateArrData['AlarmInfoPlate']['result']['PlateResult']['imageFile'];
        $serialno     = $plateArrData['AlarmInfoPlate']['serialno'];
        return ['ipAddr' => $ipAddr, 'plate' => $plate, 'base64Img' => $base64Img];
    }

    private function writeTxt($data)
    {
        $myfile = fopen("newfile.txt", "a+") or die("Unable to open file!");
        fwrite($myfile, $data);
        fclose($myfile);
    }

    public function bind()
    {
        //$_SERVER['REMOTE_ADDR'] 不能用localhost访问 用ip地址的形式
        $client_id = input('post.client_id');
        Gateway::bindUid($client_id, $_SERVER['REMOTE_ADDR']);
    }

    public function saveOutRecord()
    {
        $postData = input('post.');
        // $postData=[
        //     'id'=>3,
        //     'plate'=>'鄂AF0236',
        //     'ipAddr'=>'192.168.0.21',
        //     'time'=>'2018-02-08 09:41:38',
        //     'base64Img'=>'',
        //     'money'=>'3',
        //     'VehicleType'=>'临时车',
        //     'ChargeTypeName'=>'低收费标准',
        //     'outTime'=>'2018-02-08 11:46:58',
        //     'parkingTime'=>'0天2时5分20秒'
        // ];
        // foreach ($postData as $key => $value) {
        //     $this->writeTxt($key.'=>'.$value);
        // }
        Inoutrecord::saveModel(null, $postData);
    }
}
