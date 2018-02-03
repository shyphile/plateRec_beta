<?php
namespace app\index\controller;
use app\index\model\Camerainfo;
/**
* 
*/
class CamerainfoController extends IndexController
{
	public function __construct(){
		parent::__construct('车道名称...');
		$this->setModelClass(new Camerainfo);
	}
}