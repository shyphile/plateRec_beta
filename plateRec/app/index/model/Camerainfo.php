<?php
namespace app\index\model;

use app\common\ToolFunction;
use app\index\model\Parkingconfig;
use think\Model;

/**
 *
 */
class Camerainfo extends Model implements BaseModelInterface
{
    public static function query($_name, $pageSize)
    {
        $_Model = new self;
        if (!empty($_name)) {
            $_Model->where('Name', 'like', '%' . $_name . '%');
        }
        $_Model->order('ChanelIndex');
        $_Models = $_Model->paginate($pageSize, false, [
            'query' => [
                'Name' => $_name,
            ],
        ]);
        return $_Models;
    }

    public function getIsInCemeraAttr($value)
    {
        $type = array(
            "0" => "出口",
            "1" => "入口",
        );
        if (isset($type[$value])) {
            return $type[$value];
        }
        return $type[1];
    }

    public function getEnableTempCarAttr($value)
    {
        $type = array(
            "0" => "禁止",
            "1" => "允许",
        );
        if (isset($type[$value])) {
            return $type[$value];
        }
        return $type[1];
    }

    public function getIsEnableAttr($value)
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
    public function getParkingconfig()
    {
        //具体参数,看一下think/model/belongsto方法就明白了.
        //第一个参数是关联的表名,
        //第二个参数是关联表的主键名,即本表的外键.
        //第三个参数是本表的主键名
        return $this->belongsTo('Parkingconfig', 'ParkID', 'ParkID');
    }

    //获取已存在的道口编号的最大值, 以便新增时不会重复
    public static function getChanelIndex()
    {
        return self::max('ChanelIndex');
    }

    //
    public static function getOneModel($id = null)
    {
        if (is_null($id) || $id === 0) {
            //重构 使只用一个html兼容add和edit
            $_Model = new self;
            //赋0是为了让后台判断是新增操作
            $_Model->ChanelIndex   = 0;
            $_Model->Name          = '';
            $_Model->ParkID        = 0;
            $_Model->TerminalIP    = "192.168.0.18";
            $_Model->IP            = "192.168.0.21";
            $_Model->IsInCemera    = 1;
            $_Model->IsEnable      = 1;
            $_Model->EnableTempCar = 1;

        } else {
            $_Model = self::get($id);
            if (is_null($_Model)) {
                $_this = new self;
                return $_this->getError('未找到对应id的记录');
            }
        }
        return $_Model;
    }

    public static function saveModel($_Model = null, $postData)
    {
        if (is_null($_Model)) {
            $_Model              = new self;
            $_Model->ChanelIndex = self::getChanelIndex() + 1;
        }
        $_Model->Name          = $postData['Name'];
        $_Model->IP            = $postData['IP'];
        $_Model->PORT          = 80;
        $_Model->UserName      = 'admin';
        $_Model->Password      = 'admin';
        $_Model->IsInCemera    = array_key_exists("IsInCemera", $postData) ? 1 : 0;
        $_Model->InOutType     = array_key_exists("IsInCemera", $postData) ? '停车场入口' : '停车场出口';
        $_Model->IsEnable      = array_key_exists("IsEnable", $postData) ? 1 : 0;
        $_Model->EnableTempCar = array_key_exists("EnableTempCar", $postData) ? 1 : 0;
        $_Model->TerminalID    = ToolFunction::create_guid();
        $_Model->TerminalIP    = $postData['TerminalIP'];
        $_Model->ParkID        = $postData['ParkID'];

        $_Model->TerminalName       = '';
        $_Model->IsCharge           = 0;
        $_Model->WatchhouseIndex    = 1;
        $_Model->FuzzyMatch         = 0;
        $_Model->FuzzyLength        = 1;
        $_Model->MatchIgnoreChinese = 1;
        $_Model->EnableFuzzyMatch   = 0;
        $_Model->InputChannel       = $_Model->ChanelIndex;
        $_Model->ReadOfflineData    = 0;
        $_Model->ReadCard           = 0;
        $_Model->OpenGateType       = -1;
        $_Model->LEDIP              = '192.168.0.1';
        $_Model->LEDType            = 0;

        $temp = $_Model->validate(true)->save($_Model->getData());
        if ($temp === 1) {
            return true;
        }
        return false;
    }

    public static function deleteModelbyId($id)
    {
        $_Model = self::get($id);
        if (!is_null($_Model)) {
            if (!($_Model->getOnparkStatus($id))) {
                if ($_Model->delete()) {
                    return true;
                }
            }
        }
        return false;
    }

    private static function getOnparkStatus($id)
    {
        //月租车是否在场内
        return false;
    }

}
