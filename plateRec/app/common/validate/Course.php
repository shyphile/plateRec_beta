<?php
namespace app\common\validate;
use think\Validate;
/**
* 
*/
class Course extends Validate
{
	protected $rule=[
	"name"=>"require|unique:course",
	];
}