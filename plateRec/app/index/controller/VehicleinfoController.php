<?php
namespace app\index\controller;
use app\index\model\Vehicleinfo;

/**
* 
*/
class VehicleinfoController extends IndexController
{
	public function __construct(){
		parent::__construct();
		$this->setModelClass(new Vehicleinfo);
	}

	public function showUserVehicle(){
		// 查询方法
		$userid=$this->isIdExists();

		session('userid',$userid);
		$EntityClasses=Vehicleinfo::query("",7,$userid);

		$searchedPlate=[];
		$name=request()->param('name');
		if(!empty($name)){
			foreach ($EntityClasses as $value) {
				if(strpos($value->Plate,$name)>0){
					$searchedPlate[]=$value;
				}
			}
			$this->assign('isArr',1);
			$this->assign('EntityClasses',$searchedPlate);
		}
		else{
			$this->assign('isArr',0);
			$this->assign('EntityClasses',$EntityClasses);
		}
		$this->assign('flag',1);
		return view('index');
	}
}