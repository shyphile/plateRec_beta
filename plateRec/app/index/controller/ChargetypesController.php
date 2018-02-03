<?php
namespace app\index\controller;
use app\index\model\Chargetypes;
/**
* 
*/
class ChargetypesController extends IndexController
{
	public function __construct(){
		parent::__construct('收费名称...');
		$this->setModelClass(new Chargetypes);
	}
}