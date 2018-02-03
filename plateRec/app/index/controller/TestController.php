<?php
namespace app\index\controller;
use app\common\Gateway;
use think\Controller;
// use app\index\model\Operator;
/**
* 
*/
class TestController extends IndexController
{
	public function updateA($uid,$jsonInfo){
		// dump('a');
		Gateway::sendToUid($uid,$jsonInfo);
	}

	private static function create_guid() 
	{
		$charid = strtoupper(md5(uniqid(mt_rand(), true)));
		$hyphen = chr(45);
		$uuid =substr($charid, 0, 8).$hyphen
		.substr($charid, 8, 4).$hyphen
		.substr($charid,12, 4).$hyphen
		.substr($charid,16, 4).$hyphen
		.substr($charid,20,12);
		return $uuid;
	}


}