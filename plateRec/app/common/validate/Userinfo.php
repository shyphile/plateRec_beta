<?php
namespace app\common\validate;
use think\Validate;
/**
* 
*/
class Userinfo extends Validate
{
	protected $rule=[
		"Name"=>'require',
		"MaxCarNum"=>'require',
	];
	
}