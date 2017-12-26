<?php
namespace app\index\controller;
use app\index\model\Chargetypes;
/**
* 
*/
class ChargetypesController extends IndexController
{
	public function __construct(){
		parent::__construct();
		$this->setModelClass(new Chargetypes);
	}
}