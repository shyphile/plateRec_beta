<?php
namespace app\index\controller;
use app\index\model\Parkingconfig;

/**
* 
*/
class ParkingconfigController extends IndexController
{
	public function __construct(){
		
		parent::__construct('车场名称...');	
		$this->setModelClass(new Parkingconfig);
	}
}