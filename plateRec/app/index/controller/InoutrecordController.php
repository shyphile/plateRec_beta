<?php
namespace app\index\controller;
use app\index\model\Inoutrecord;

/**
* 
*/
class InoutrecordController extends IndexController
{
	public function __construct(){
		parent::__construct('车牌号码...');	
		$this->setModelClass(new Inoutrecord);
	}


}