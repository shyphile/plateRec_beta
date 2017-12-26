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

}