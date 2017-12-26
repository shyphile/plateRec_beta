<?php
namespace app\index\model;
use think\Model;
use app\common\ToolFunction;
/**
*  操作员类
*/
class Operator extends Model implements BaseModelInterface
{
	public static function login($username,$password){

		$map=array('Name'=>$username);
		$Operator=self::get($map);
		if(!is_null($Operator) && $Operator->checkPassword($password)){
			session('OperatorId',$Operator->ID);
			return true;
		}
		return false;
	}

	private function checkPassword($password){
		if($this->getData('Password')===self::encryptPassword($password)){
			return true;
		}
		return false;
	}
	private static function encryptPassword($password){
		$bytes=array();
		for ($i=0; $i < strlen($password); $i++) { 
			$tmp=substr($password, $i,1);
			$tmp=ord($tmp) ^ 87327051;
			$tmp=chr($tmp & 0xFF);
			$bytes[]=$tmp;
		}
		return implode('', $bytes);
	}

	public static function getPassword($val){
		return self::encryptPassword($val);
	}

	public static function logOut(){
		session('OperatorId',null);
		return true;
	}

	public static function isLogin(){
		$OperatorId=session('OperatorId');
		if(isset($OperatorId)){
			return true;
		}
		return false;
	}

	public static function deleteModelbyId($id){
		$_Model=self::get($id);
		if(!is_null($_Model)){
			if(!($_Model->checkLoginUser($id))){
				if($_Model->delete()){
					return true;
				}
			}
		}
		return false;
	}

	private static $role=array(
		'0dca5874-c38f-456a-b36c-b30ae13ab865' =>'收费员' , 
		'c026429f-372a-422e-928e-bd7e964b4b34'=>'管理员');

	public static function getOneModel($id=null){
		if(is_null($id) || $id===0){
		//重构 使只用一个html兼容add和edit
			$_Model=new self;
			$_Model->ID=0;
			$_Model['Name']="";
			$_Model->Password= "";
			$_Model->RoleName="收费员";
			$_Model->RoleID=array_search($_Model->RoleName, self::$role);
			$_Model->JobNumber= self::getJobNumber()+1;
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

	public static function getJobNumber(){
		return self::max('JobNumber');
	}


	public static function saveModel($_Model=null,$postData)
	{
		if(is_null($_Model))
		{
			$_Model=new self;
			$_Model->ID=ToolFunction::create_guid();
			$_Model->Name=$postData['name'];
		}
		$_Model->Password=self::encryptPassword($postData['password']);
		$_Model->RoleName=$postData['rolename'];
		$_Model->JobNumber=$postData['jobnumber'];
		$_Model->RoleID=array_search($postData['rolename'], self::$role);
		if($_Model->validate(true)->save($_Model->getData())){
			return true;
		}
		return false;
	}

	public static function query($name,$pageSize){
		$_Model=new self;
		if(!empty($name)){
			$_Model->where('Name','like','%'.$name.'%');
		}
		$_Model->order('JobNumber');
		$_Models=$_Model->paginate($pageSize,false,[
			'query'=>[
				'Name'=>$name,
			],
		]);
		return $_Models;
	}

	private function checkLoginUser($Id){
		$OperatorId=session('OperatorId');
		return $Id===$OperatorId;
	}

	public static function getLoginUser(){
		if(self::isLogin()){
			$OperatorId=session('OperatorId');
			return self::get($OperatorId)->getData('Name');
		}
	}
}


