<?php
namespace app\common\validate;
use think\Validate;
/**
* 
*/
class Camerainfo extends Validate
{
	protected $rule=[
	"Name"=>"require|unique:camerainfo",
	];
}