<?php
namespace app\common\validate;
use think\Validate;
/**
* 
*/
class Chargetypes extends Validate
{
	protected $rule=[
		"ChargeName"=>"require|unique:chargetypes",
	];
}