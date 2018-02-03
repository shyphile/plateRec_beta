<?php
namespace app\index\model;

use app\common\ToolFunction;
use think\Model;

/**
 *
 */
class Chargetypes extends Model implements BaseModelInterface
{
    public static function query($name, $pageSize)
    {
        $_Model = new self;
        if (!empty($name)) {
            $_Model->where('ChargeName', 'like', '%' . $name . '%');
        }
        $_Model->order('ChargeName');
        $_Models = $_Model->paginate($pageSize, false, [
            'query' => [
                'ChargeName' => $name,
            ],
        ]);
        return $_Models;
    }

    public static function getOneModel($id = null)
    {
        if (is_null($id) || $id === 0) {
            //重构 使只用一个html兼容add和edit
            $_Model               = new self;
            $_Model->ChargeID     = 0;
            $_Model['ChargeName'] = "收费标准名称";
            $_Model->ChargeType   = "二十四小时式收费";
            $_Model->Parm   = "HgAByAAAAAEKABQAHgAoADIAPABGAFAAWgBkAG4AeACCAIwAlgCgAKoAtAC+AMgA0gDcAOYA8AAAAAAAAAAAAA==";
        } else {
            $_Model = self::get($id);
            if (is_null($_Model)) {
                $_this = new self;
                return $_this->getError('未找到对应id的记录');
            }
        }
        return $_Model;
    }

    public function ReadParmFrom24()
    {
        $parmByteArry = self::toBytes(base64_decode($this->Parm));
        $resultArr    = array();
        if (count($parmByteArry) === 64) {
            $resultArr['FreeTime']        = $parmByteArry[0] + ($parmByteArry[1] << 8);
            $resultArr['IsFreeTimeCount'] = $parmByteArry[2];
            $resultArr['ChargeMaxAmount'] = ($parmByteArry[3] + ($parmByteArry[4] << 8)) / 10.0;

            $resultArr['OverNightCharges']      = ($parmByteArry[5] + ($parmByteArry[6] << 8)) / 10.0;
            $resultArr['IsTopContainOverNight'] = $parmByteArry[7];
            for ($i = 0; $i < 24; $i++) {
                $resultArr['timePerMoney'][$i + 1] = ($parmByteArry[$i * 2 + 8] + ($parmByteArry[$i * 2 + 9] << 8)) / 10.0;
            }
            $resultArr['FirstUnit']   = $parmByteArry[56] + ($parmByteArry[57] << 8);
            $resultArr['FirstCharge'] = ($parmByteArry[58] + ($parmByteArry[59] << 8)) / 10.0;
        }
        // dump($resultArr);
        return $resultArr;
    }

    private static function toBytes($data)
    {
        $byte = [];
        for ($i = 0; $i < strlen($data); $i++) {
            $tmp    = substr($data, $i, 1);
            $byte[] = hexdec(bin2hex($tmp));
        }
        return $byte;
    }

    public static function saveModel($_Model = null, $postData)
    {
        if (is_null($_Model)) {
            $_Model             = new self;
            $_Model->ChargeID   = ToolFunction::create_guid();
            $_Model->ChargeType = $postData['ChargeType'];
        }
        $_Model->ChargeName = $postData['ChargeName'];
        $_Model->Parm       = base64_encode(self::writeParmFrom24($postData['Parm']));
        if ($_Model->validate(true)->save($_Model->getData())) {
            return true;
        }
        return false;
    }

    private static function writeParmFrom24($data)
    {
        array_walk($data, function (&$value, $key) {
            if (!is_array($value)) {
                if (!in_array($key, ["IsFreeTimeCount", "IsTopContainOverNight", "FreeTime", "FirstUnit"])) {
                    $value = (float) $value * 10.0;
                }
            } else {
                array_walk($value, function (&$value1, $key) {
                    $value1 = (float) $value1 * 10.0;
                });
            }
        });
        $tempArr = [];
        foreach ($data as $key => $value) {
            if (in_array($key, ["IsFreeTimeCount", "IsTopContainOverNight", "FirstUnit", "FirstCharge"])) {
                $tempArr[$key] = $data[$key];
                unset($data[$key]);
            }
        }
        array_splice($data, 1, 0, $tempArr["IsFreeTimeCount"]);
        array_splice($data, 4, 0, $tempArr["IsTopContainOverNight"]);
        array_splice($data, 6, 0, $tempArr["FirstUnit"]);
        array_splice($data, 7, 0, $tempArr["FirstCharge"]);
        array_splice($data, 8, 0, 0);
        array_splice($data, 9, 0, 0);
        array_splice($data, 10, 0, 0);
        $bytes;
        array_walk($data, function ($value, $key) use (&$bytes) {
            if (!is_array($value)) {
                if (!in_array($key, ['0', '1', '4', '5'])) {
                    $bytes .= pack('C', $value);
                    $bytes .= pack('C', $value >> 8);
                } else {
                    $bytes .= pack('C', $value);
                }
            } else {
                array_walk($value, function ($value1, $key) use (&$bytes) {
                    $bytes .= pack('C', $value1);
                    $bytes .= pack('C', $value1 >> 8);
                });
            }
        });
        return $bytes;
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
        return $this->hasMany('Parkingconfig', 'TempChargeID', 'ChargeID');
    }

}
