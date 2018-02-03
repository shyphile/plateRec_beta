<?php
namespace app\index\model;

use app\common\ToolFunction;
use think\Model;

/**
 *
 */
class Onparkvehicle extends Model implements BaseModelInterface
{
    public static function query($_name, $pageSize)
    {
        $_Model = new self;
        if (!empty($_name)) {
            $_Model->where('Plate', 'like', '%' . $_name . '%');
        }
        $_Model->order('InTime desc');
        $_Models = $_Model->paginate($pageSize, false, [
            'query' => [
                'Plate' => $_name,
            ],
        ]);
        return $_Models;
    }

    public function getIsHandInputAttr($value)
    {
        $type = array(
            "0" => "否",
            "1" => "是",
        );
        if (isset($type[$value])) {
            return $type[$value];
        }
        return $type[1];
    }

    public function getCamerainfoes()
    {
        return $this->belongsTo('camerainfo', 'InChannelIndex', 'ChanelIndex');
    }

    /**
     * 本方法调用方式的不同,实现的效果会有不同.
     * $this->getgetParkingconfig,这样调是返回Parkingconfig模型对象;
     * $this->getgetParkingconfig(),这样调返回的是与Parkingconfig模型
     * 相关联对象;
     *原理跟踪代码实现过程可以了解:
     *$this->getgetParkingconfig:这样调用时,会触发魔术方法__get(),
     *因为不存在getgetParkingconfig这个属性.
     *此魔术方法调用getAttr()方法,注意该方法这行语句:
     *$value = $this->getRelationData($modelRelation)
     *它执行的是关联类中的getRelationData方法,这个方法再调用本方法,
     *执行本方法的代码后,返回与Parkingconfig关联的对象
     *此对象再参与getRelationData后面逻辑的运算,运算结果就是
     *返回Parkingconfig模型对象
     *
     * $this->getgetParkingconfig():这样调用,是直接调用本方法,
     * 它不会执行getRelationData()这个方法,
     * 导致的结果就是直接一个与Parkingconfig模型相关联的对象
     *
     * Parkingconfig模型对象和与Parkingconfig模型相关联的对象
     * 是两个不同的类,不能等同对待.
     *
     * @return 关联对象,实际上就是车场表的模型对象
     */
    // public function getParkingconfig()
    // {
    //     //具体参数,看一下think/model/belongsto方法就明白了.
    //     //第一个参数是关联的表名,
    //     //第二个参数是本表的外键.
    //     //第三个参数是关联表的主键名
    //     return $this->belongsTo('Parkingconfig', 'ParkID', 'ParkID');
    // }

    //
    public static function getOneModel($id = null)
    {

    }

    private function getUserVehicleinfo($plate)
    {
        return Vehicleinfo::where('Plate', $plate)->field('ID,UserID,UserName,VehicleType')->find();
    }

    private function getParkingconfig($cameraIP)
    {
        return Camerainfo::where('IP', $cameraIP)->field('ParkID')->find()->getParkingconfig;
    }

    //查询车牌是否已入场
    private static function isOnParking($plate)
    {
        if (!is_null(self::get($plate))) {
            return true;
        }
        return false;
    }

    public static function saveModel($_Model = null, $postData)
    {
        $plate = $postData['plate'];
        if (self::isOnParking($plate)) {
            self::destroy($plate);
        }

        if (is_null($_Model)) {
            $_Model = new self;
        }
        $_Model->Plate = $plate;

        $_this             = new self;
        $info              = $_this->getUserVehicleinfo($plate);
        $_Model->VehicleID = (!is_null($info)) ? $info->getData('ID') : (ToolFunction::create_guid());
        $_Model->UserID    = !is_null($info) ? $info->getData('UserID') : '00000000-0000-0000-0000-000000000000';
        $_Model->UserName  = !is_null($info) ? $info->getData('UserName') : '临时用户';

        $parkinfo = $_this->getParkingconfig($postData['ipAddr']);

        $_Model->VehicleType = !is_null($info) ? $info->getData('VehicleType') : '临时车';

        $_Model->ChargeTypeID   = is_null($info) ? $parkinfo->getData('TempChargeID') : '00000000-0000-0000-0000-000000000000';
        $_Model->ChargeTypeName = is_null($info) ? $parkinfo->TempChargeName : '';
        $_Model->InTime         = date('Y-m-d H:i:s');

        $_Model->ParkID       = $parkinfo->ParkID;
        $_Model->ParkName     = $parkinfo->ParkName;
        $_Model->TempFlag     = 0;
        $_Model->PlateConfirm = 95;
        $_Model->IsHandInput  = 0;
        $_Model->IsAlarm      = 1;
        $_Model->ImagePath    = $_this->setPic($postData['plate'], $postData['base64Img']);

        $_Model->InChannelIndex = Camerainfo::where('IP', $postData['ipAddr'])->field('ChanelIndex')->find()->ChanelIndex;

        $_Model->save();
        // $temp                   = $_Model->validate(true)->save($_Model->getData());
        // if ($temp === 1) {
        //     return true;
        // }
        // return false;
    }

    private function setPic($plate, $base64_image_content)
    {
        $base64_image_content = "data:image/jpg;base64," . $base64_image_content;
        if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $base64_image_content, $result)) {
            $type     = $result[2];
            $_plate   = iconv("utf-8", "gb2312//IGNORE", $plate);
            $imgPath  = "E:/pic/" . 'plate' . date('YmdHis') . ".{$type}";
            $new_file = str_replace('plate', $_plate, $imgPath);
            if (file_put_contents($new_file, base64_decode(str_replace($result[1], '', $base64_image_content)))) {
                return str_replace('plate', $plate, $imgPath);
            }
            return null;
        }
    }

    public static function deleteModelbyId($id)
    {
        $_Model = self::get($id);
        if (!is_null($_Model)) {
            if ($_Model->delete()) {
                return true;
            }
        }
        return false;
    }

}
