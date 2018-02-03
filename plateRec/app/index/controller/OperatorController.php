<?php
namespace app\index\controller;
use app\index\model\Operator;
/**
* 
*/
class OperatorController extends IndexController
{
	public function __construct(){
		$placeholder='操作者名称...'; //查询条件提示信息
		parent::__construct($placeholder);
		$this->setModelClass(new Operator);
	}
}