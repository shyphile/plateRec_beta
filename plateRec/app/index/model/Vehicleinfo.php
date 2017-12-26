<?php
namespace app\index\model;
use think\Model;
use app\index\model\Userinfo;
use app\common\ToolFunction;
/**
* 
*/
class Vehicleinfo extends Model implements BaseModelInterface
{
	public static function query($plate,$pageSize,$userid=null){

		$_Model=new self;
		if(!empty($plate)){
			$_Model->where('Plate','like','%'.$plate.'%');
		}
		if(!is_null($userid)){
			$_Model->where('UserID',$userid);
			session('userid',$userid);
		}
		$_Model->order('Plate');
		$_Models=$_Model->paginate($pageSize,false,[
			'query'=>[
				'Plate'=>$plate,
			],
		]);
		return $_Models;
	}


	private function getUserName(){
		$userid=session('userid');
		if(!is_null($userid)){
			return Userinfo::get($userid)->Name;
		}
	}
	public static function getOneModel($id=null){
		if(is_null($id) || $id===0){
		//重构 使只用一个html兼容add和edit
			$_Model=new self;	
			$_Model->ID=0;
			$_Model->UserName=$_Model->getUserName();
			$_Model['Plate']="";			
			$_Model->VehicleType= "月租车";
			$_Model->StartTime=date('Y-m-d H:i:s', strtotime("now"));
			$_Model->EndTime=date('Y-m-d H:i:s', strtotime("+1 month"));
			$_Model->Remarks="车辆备注";
		}
		else{
			$_Model=self::get($id);
			if(is_null($_Model)){
				$_this=new self;
				return $_this->getError('未找到对应id的记录');
			}
		}
		return $_Model;
	}

	public static function saveModel($_Model=null,$postData)
	{
		if(is_null($_Model))
		{  
			$_Model=new self;
			$_Model->ID=ToolFunction::create_guid();
			$_Model->UserID= session('userid');
			$_Model->UserName=$_Model->getUserName();
		}
		$_Model->Plate=$postData['plate'];
		$_Model->VehicleType=$postData['vehicletype'];
		$_Model->StartTime=$postData['starttime'];
		$_Model->EndTime=$postData['endtime'];
		$_Model->Remarks=$postData['remarks'];
		$_Model->Color=0;
		$_Model->Status='正常';
		$_Model->IsEnable=false;
		$_Model->EnableChanelData=31;
		$temp=$_Model->validate(true)->save($_Model->getData());
		if($temp===1){
			return true;
		}
		return false;
	}

	public static function deleteModelbyId($id){
		$_Model=self::get($id);
		if(!is_null($_Model)){
			if(!($_Model->getOnparkStatus($id))){
				if($_Model->delete()){
					return true;
				}
			}
		}
		return false;
	}

	private static function getOnparkStatus($id){
		//月租车是否在场内
		return false;
	}

	
}