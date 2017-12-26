<?php
namespace app\index\controller;
use think\Controller;
use think\Request;
use app\common\LEDDrive;
use app\index\Model\Operator;
use app\index\Model\BaseModelInterface ;
use think\Config;
class IndexController extends Controller
{
	public function __construct(){
		parent::__construct();

		$temp=func_get_arg(0);
		if($temp==='a'){
			return;
		}
		if(!Operator::isLogin()){
			return $this->error('请先登入',url('Login/index'));
		}
		
	}
	private $ModelClass;

	public function setModelClass(BaseModelInterface $ModelClass){
		$this->ModelClass=$ModelClass;
	}

	public function index($userid=null){
		//查询方法
		session('userid',null);
		Config::set("paginate.list_rows",10);
		$pageSize=Config::get('paginate.list_rows');
		$name=Request::instance()->get('name');
		$EntityClasses=$this->ModelClass->query($name,$pageSize,$userid);

		//展示辅导员信息
		$this->assign('isArr',0);
		$this->assign('EntityClasses',$EntityClasses);
		return view();
	}

	public function add(){
		$_Model=$this->ModelClass->getOneModel();
		$this->assign('_Model',$_Model);	
		return $this->fetch('addORedit');
	}

	public function save(){
		$result=$this->saveModel();
		if($result){
			if(empty(session('userid'))){
				return $this->success('添加成功',url('index'));
			}
			return $this->success('添加成功',url('showUserVehicle?id='.session('userid')));
		}
		return $this->error('添加失败');
	}

	public function edit(){
		$id=$this->isIdExists();
		$_Model=$this->ModelClass->getOneModel($id);
		if(is_null($_Model)){
			return $this->error('不存在的id');
		}
		if($_Model instanceOf Operator){
			$this->assign('password',$_Model::getPassword($_Model['Password']));
		}
		$this->assign('_Model',$_Model);
		return $this->fetch('addORedit');
	}

	public function update(){
		$id=$this->isIdExists();
		$_Model=$this->ModelClass->getOneModel($id);
		$result=$this->saveModel($_Model);
		if($result){
			if(empty(session('userid'))){
				return $this->success('修改成功',url('index'));
			}
			return $this->success('修改成功',url('showUserVehicle?id='.session('userid')));
		}
		return $this->error('修改失败'.$_Model->getError());
	}

	private function saveModel($_Model=null){
		$postData=Request::instance()->post();
		return $this->ModelClass->saveModel($_Model,$postData);
	}


	public function delete(){
		$id=$this->isIdExists();
		$result=$this->ModelClass->deleteModelbyId($id);
		if($result==='hasmore'){
			return $this->error('禁止删除当前登入用户');
		}
		if(!$result){
			return $this->error('删除失败');
		}

		if(empty(session('userid'))){
			return $this->success('删除成功',url('index'));
		}
		return $this->success('删除成功',url('showUserVehicle?id='.session('userid')));

	}

	protected function isIdExists(){
		$id=Request::instance()->param('id');
		if(is_null($id) || $id===0){
			return $this->error('id号为'.$id.'的编号不存在');
		}
		return $id;
	}

	private function openDoor(){
		$arrValue=LEDDrive::PlaySound("一路顺风");
		$strValue='';
		array_walk($arrValue,function($value)use(&$strValue){
			$strValue.=pack('C',$value);
		});
		$code=base64_encode($strValue);
		$len=count($arrValue);
		$data=[
			"Response_AlarmInfoPlate"=>
			[
				"serialData"=>[
					[
						"serialChannel"=>1,
						"data"=>"$code",
						"dataLen"=>$len,
					]
				]
			]
		];
		$json=json_encode($data);
		return $json;
	}
}
