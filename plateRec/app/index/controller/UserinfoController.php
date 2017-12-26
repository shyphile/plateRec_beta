<?php
namespace app\index\controller;
use app\index\model\Userinfo;

/**
* 
*/
class UserinfoController extends IndexController
{
	public function __construct(){
		parent::__construct();	
		$this->setModelClass(new Userinfo);
	}
}