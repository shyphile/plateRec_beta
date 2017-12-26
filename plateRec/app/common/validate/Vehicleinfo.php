<?php
namespace app\common\validate;
use think\Validate;
/**
* 
*/
class Vehicleinfo extends Validate
{
	protected $rule=[
		"Plate"=>"require|unique:Vehicleinfo|length:5,9",
	];
}