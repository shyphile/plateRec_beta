<?php
namespace app\index\model;

use app\common\ToolFunction;
use think\Model;

/**
 *
 */
class Userinfo extends Model implements BaseModelInterface
{
    public static function query($name, $pageSize)
    {
        $_Model = new self;
        if (!empty($name)) {
            $_Model->where('Name', 'like', '%' . $name . '%');
        }
        $_Model->order('Name');
        $_Models = $_Model->paginate($pageSize, false, [
            'query' => [
                'Name' => $name,
            ],
        ]);
        return $_Models;
    }

    public static function getOneModel($id = null)
    {
        if (is_null($id) || $id === 0) {
            //重构 使只用一个html兼容add和edit
            $_Model                   = new self;
            $_Model->ID               = 0;
            $_Model['Name']           = "";
            $_Model->MaxCarNum        = "1";
            $_Model->IsEnableOverflow = 1;
            $_Model->Phone            = "";
            $_Model->Remarks          = "用户备注";
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
            $_Model     = new self;
            $_Model->ID = ToolFunction::create_guid();
        }
        $_Model->Name             = $postData['name'];
        $_Model->MaxCarNum        = $postData['maxcarnum'];
        $_Model->IsEnableOverflow = 0;
        $_Model->Phone            = $postData['phone'];
        $_Model->IDNumber         = '';
        $_Model->Remarks          = $postData['remarks'];
        $_Model->StoredValue      = 0.0;
        $_Model->IsEnableOverflow = array_key_exists("IsEnableOverflow", $postData) ? 1 : 0;
        $_Model->DeptID           = "26893ef6-1de3-4077-a3d4-3b25d1d0389d";
        $_Model->DeptName         = "部门";
        if ($_Model->validate(true)->save($_Model->getData())) {
            return true;
        }
        return false;
    }

    public static function deleteModelbyId($id)
    {
        $_Model = self::get($id);
        if (!is_null($_Model)) {
            if ($_Model->delete()) {
                if ($_Model->deleteVehicleinfoByUserID()) {
                    return true;
                }
            }

        }
        return false;
    }

    public function Vehicleinfoes()
    {
    	//参数1:需关联的表
    	//参数2:本表的主键
    	//参数3:关联表的外键
        return $this->hasMany('Vehicleinfo', 'ID', 'UserID');
    }

    private function deleteVehicleinfoByUserID()
    {
        if (false === $this->Vehicleinfoes()->where('UserID',$this->ID)->delete()) {
            return false;
        }
        return true;
    }

}
