<?php
namespace app\index\model;
use think\Model;
use app\index\model\Userinfo;
use app\index\model\Camerainfo;
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
			$_Model['Plate']="鄂";			
			$_Model->VehicleType= "月租车";
			$_Model->StartTime=date('Y-m-d H:i:s', strtotime("now"));
			$_Model->EndTime=date('Y-m-d h:i:s', strtotime("+1 month"));
			$_Model->EnableChanelData=0xFF;
			$_Model->Remarks="车辆备注";
			$_Model->ChargeTypeID="00000000-0000-0000-0000-000000000000";
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

	public function getChargeTypes(){

		return $this->belongsTo('Chargetypes', 'ChargeTypeID', 'ChargeID');
	}

	public function getCamerainfoes(){
		return Camerainfo::all();
	}

	public static function saveModel($_Model=null,$postData)
	{
		//dump($postData);
		if(is_null($_Model))
		{  
			$_Model=new self;
			$_Model->ID=ToolFunction::create_guid();
			$_Model->UserID= session('userid');
			$_Model->UserName=$_Model->getUserName();
		}
		$_Model->Plate=$postData['plate'];
		$_Model->VehicleType=$postData['vehicletype'];
		$_Model->StartTime=$postData['starttime'].' 00:00:00';
		$_Model->EndTime=$postData['endtime'].' 23:59:59';
		$_Model->Remarks=$postData['remarks'];
		$_Model->ChargeTypeID=($postData['vehicletype']==='月租车')?'00000000-0000-0000-0000-000000000000':$postData['ChargeType'];
		//$_Model->ChargeTypeID=$postData['ChargeType'];
		$_Model->ChargeTypeName=($postData['vehicletype']==='月租车')?'':
		$_Model->getChargeTypes->ChargeName;
		$_Model->Color=0;
		$_Model->Status='正常';
		$_Model->IsEnable=false;
		$_Model->EnableChanelData=$_Model->calcChanelData($postData['EnableChanelData']);
		$_Model->Nickname='';
		$_Model->Parking='00000000-0000-0000-0000-000000000000';
		$_Model->BankUser=0;
		$_Model->ParkingName='';
		$_Model->ChargePackageID='00000000-0000-0000-0000-000000000000';
		$_Model->CardNumber='';
		// $_Model->SpaceNumber='';
		// $_Model->RoomNumber='';
		// $_Model->Remark='';
		$temp=$_Model->validate(true)->save($_Model->getData());
		if($temp===1){
			return true;
		}
		return false;
	}

	private function calcChanelData($sourceData){
		$temp;
		for($i=0;$i<255;$i++){
			$temp[]=0;
		}
		foreach ($sourceData as $value) {
			array_splice($temp,$value*(-1),1,'1');
		}
		return bindec(implode('',$temp));
	}

	public static function deleteModelbyId($id){
		$_Model=self::get($id);
		if(!is_null($_Model)){
			if(!($_Model->getOnparkStatus)){
				if($_Model->delete()){
					return true;
				}
			}
		}
		return 'onparking';
	}

	public function getOnparkStatus(){
		//月租车是否在场内
		return $this->belongsTo('Onparkvehicle','Plate','Plate');
		//return false;
	}

	
}