<?php
namespace app\index\model;

use app\common\ToolFunction;
use app\index\model\Chargetypes;
use think\Model;

/**
 *
 */
class Parkingconfig extends Model implements BaseModelInterface
{
    public static function query($name, $pageSize)
    {
        $_Model = new self;
        if (!empty($name)) {
            $_Model->where('ParkName', 'like', '%' . $name . '%');
        }
        $_Model->order('ParkName');
        $_Models = $_Model->paginate($pageSize, false, [
            'query' => [
                'ParkName' => $name,
            ],
        ]);
        return $_Models;
    }

    public static function getOneModel($id = null)
    {
        if (is_null($id) || $id === 0) {
            //重构 使只用一个html兼容add和edit
            $_Model                       = new self;
            $_Model->TempChargeID         = 0;
            $_Model->ParkID               = 0;
            $_Model->ParkName             = "车场名称";
            $_Model->TotalParkSpaces      = 1000;
            $_Model->TotalTempParkSpaces  = 1000;
            $_Model->ScreenLight          = 16;
            $_Model->VoiceVolume          = 5;
            $_Model->ScreenFirstLine      = '车牌识别管理系统';
            $_Model->IsInsidePark         = 0;
            $_Model->MonthlyCarInAgain    = 1;
            $_Model->MonthlyCarOverTimeIn = 1;
            $_Model->IsConfirmTemporary   = 0;
        } else {
            $_Model = self::get($id);
            if (is_null($_Model)) {
                $_this = new self;
                return $_this->getError('未找到对应id的记录');
            }
        }
        return $_Model;
    }

    public function getTempCharge()
    {
        return $this->belongsTo('Chargetypes', 'ChargeID', 'TempChargeID');
    }

    public static function saveModel($_Model = null, $postData)
    {
        if (is_null($_Model)) {
            $_Model         = new self;
            $_Model->ParkID = ToolFunction::create_guid();
        }
        $_Model->TotalParkSpaces         = $postData['TotalParkSpaces'];
        $_Model->TotalTempParkSpaces     = $postData['TotalTempParkSpaces'];
        $_Model->ScreenFirstLine         = $postData['ScreenFirstLine'];
        $_Model->ScreenSecondLine        = '保持车距，依次行驶';
        $_Model->ScreenLight             = $postData['ScreenLight'];
        $_Model->VoiceVolume             = $postData['VoiceVolume'];
        $_Model->VoiceType               = '晓玲';
        $_Model->PlateConfirm            = 80;
        $_Model->IsAutoCorrecting        = 1;
        $_Model->FuzzyMatchingLength     = 2;
        $_Model->PhotoPath               = '';
        $_Model->PlateProcessingInterval = 10;
        $_Model->MonthlyCarInAgain       = array_key_exists("MonthlyCarInAgain", $postData) ? 1 : 0;
        $_Model->MonthlyCarOverTimeIn    = array_key_exists("MonthlyCarOverTimeIn", $postData) ? 1 : 0;
        $_Model->EnableTempCar           = 1;
        $_Model->NoChargeAutoOpen        = 0;
        $_Model->IsMonthlyCarOffline     = 0;
        $_Model->LocalPlate              = '鄂';
        $_Model->ParkName                = $postData['ParkName'];
        $_Model->IsConfirmTemporary      = array_key_exists("IsConfirmTemporary", $postData) ? 1 : 0;
        $_Model->TempChargeID            = $postData['TempChargeID'];
        $_Model->TempChargeName          = Chargetypes::get($postData['TempChargeID'])->ChargeName;
        $_Model->IsInsidePark            = array_key_exists("IsInsidePark", $postData) ? 1 : 0;

        if ($_Model->validate(true)->save($_Model->getData())) {
            return true;
        }
        return false;
    }

    public static function deleteModelbyId($id)
    {
        $_Model = self::get($id);
        if (!is_null($_Model)) {
            if (empty($_Model->isBind)) {
                if ($_Model->delete()) {
                    return true;
                }
            } else {return 'isbind';}
        }
        return false;
    }

    public function isBind()
    {
        return $this->hasMany('Camerainfo', 'ParkID', 'ParkID');
    }

    public function getIsInsideParkAttr($value)
    {
        $isInside = [
            0 => '否',
            1 => '是',
        ];
        if (isset($isInside[$value])) {
            return $isInside[$value];
        }

    }

}
