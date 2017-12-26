<?php
namespace app\index\controller;
use app\index\model\Operator;
/**
* 
*/
class OperatorController extends IndexController
{
	public function __construct(){
		parent::__construct();
		$this->setModelClass(new Operator);
	}
}