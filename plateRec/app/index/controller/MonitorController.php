<?php
namespace app\index\controller;
use think\Controller;
use app\common\Gateway;
use think\Request;
/**
* 
*/
class MonitorController extends Controller
{
	private $clientID;
	private $uid="xh_test";
	public function index(){
		$plateJsonData=file_get_contents("php://input");	
		$info=$this->getPlateInfo($plateJsonData);
		$jsonInfo=json_encode($info);
		if(!isset($this->clientID)){
			Gateway::sendToUid($this->uid,$jsonInfo);
		}
	}

	public function show(){
		return view();
	}

	private function getPlateInfo($plateJsonData){
		$this->writeTxt($plateJsonData);
		$plateArrData=json_decode($plateJsonData,true);
		$ipAddr=$plateArrData['AlarmInfoPlate']['ipaddr'];
		$plate=$plateArrData['AlarmInfoPlate']['result']['PlateResult']['license'];
		$base64Img=$plateArrData['AlarmInfoPlate']['result']['PlateResult']['imageFile'];
		$serialno=$plateArrData['AlarmInfoPlate']['serialno'];
		return ['ipAddr'=>$ipAddr,'plate'=>$plate,'serialno'=>$serialno,'base64Img'=>$base64Img];
	}

	private function writeTxt($data){
		$myfile = fopen("newfile.txt", "w") or die("Unable to open file!");
		fwrite($myfile, $data);
		fclose($myfile);
	}


	public function bind(){
		$val=input('post.client_id');
		$this->clientID=$val;
		Gateway::bindUid($this->clientID, $this->uid);
	}

}