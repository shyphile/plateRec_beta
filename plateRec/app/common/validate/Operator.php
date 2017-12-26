<?php
namespace app\common\validate;
use think\Validate;
/**
* 
*/
class Operator extends Validate
{
	protected $rule=[
	"Name"=>"require|unique:Operator|length:1,40",
	"Password"=>"require",
	"JobNumber"=>"require",
	];
}