<?php
namespace app\index\controller;

use app\common\LEDDrive;
use app\index\Model\BaseModelInterface;
use app\index\Model\Operator;
use app\index\Model\Chargetypes;
use app\index\Model\Camerainfo;
use think\Config;
use think\Controller;
use think\Request;

class IndexController extends Controller
{
	private $placeholder;  //查询条件的提示信息
    public function __construct($placeInfo=null)
    {
        parent::__construct();
        $this->placeInfo=$placeInfo;

        if (!Operator::isLogin()) {
            return $this->error('请先登入', url('Login/index'));
        }

    }
    private $ModelClass;

    public function setModelClass(BaseModelInterface $ModelClass)
    {
        $this->ModelClass = $ModelClass;
    }

    /**
     * 信息展示和查询
     * @param   $id        专用于关联车辆所属的用户.
     * @param   $placeInfo 查询文本框的提示信息
     */
    public function index($id = null,$placeInfo=null)
    {     
        session('userid', null);  
        if(session('OperatorId') && is_null($this->ModelClass))  {
            $this->ModelClass=new Operator;
            $this->placeInfo="操作者名称...";
        }

        Config::set("paginate.list_rows", 10);
        if(is_null($this->ModelClass) && !is_null(session('userid')) ){
            return view('Operator/index');
        }
        $pageSize      = Config::get('paginate.list_rows');
        $name          = Request::instance()->get('name');
        $EntityClasses = $this->ModelClass->query($name, $pageSize, $id);
        //展示辅导员信息
        $this->assign('isArr', 0);
        $this->assign('placeInfo',$this->placeInfo);
        $this->assign('EntityClasses', $EntityClasses);
        if(session('OperatorId') && $this->ModelClass instanceof Operator)  {
            return view('Operator/index');
        }
        return view();
    }
    /**
     * 新增信息
     */
    public function add()
    {
        $_Model = $this->ModelClass->getOneModel(); //新增时默认展示的数据生成

        if ($_Model instanceof Chargetypes) {
            $Parm=$_Model->ReadParmFrom24();
            $this->assign('Parm', $Parm);
        }
        $this->assign('_Model', $_Model);   //默认数据供V层展示

        return $this->fetch('addORedit'); //调取V层指定页面展示
    }

    public function save()
    {
        $result = $this->saveModel();
        if ($result) {
            if (empty(session('userid'))) {
                return $this->success('添加成功', url('index'));
            }
            return $this->success('添加成功', url('showUserVehicle?id=' . session('userid')));
        }
        return $this->error('添加失败');
    }

    public function edit()
    {
        $id     = $this->isIdExists();
        $_Model = $this->ModelClass->getOneModel($id);
        if (is_null($_Model)) {
            return $this->error('不存在的id');
        }
        if ($_Model instanceof Operator) {
            $this->assign('password', $_Model::getPassword($_Model['Password']));
        }
        elseif ($_Model instanceof Chargetypes) {

            $Parm=$_Model->ReadParmFrom24();
            $this->assign('Parm', $Parm);
        }
        $this->assign('_Model', $_Model);
        return $this->fetch('addORedit');
    }

    public function update()
    {
        $id     = $this->isIdExists();
        $_Model = $this->ModelClass->getOneModel($id);
        $result = $this->saveModel($_Model);
        if ($result) {
            if (empty(session('userid'))) {
                return $this->success('修改成功', url('index'));
            }
            return $this->success('修改成功', url('showUserVehicle?id=' . session('userid')));
        }
        return $this->error('修改失败' . $_Model->getError());
    }

    private function saveModel($_Model = null)
    {
        $postData = Request::instance()->post();
        return $this->ModelClass->saveModel($_Model, $postData);
    }

    public function delete()
    {
        $id     = $this->isIdExists();
        $result = $this->ModelClass->deleteModelbyId($id);
        if ($result === 'hasmore') {
            return $this->error('禁止删除当前登入用户');
        }
        elseif ($result==='isbind') {
            return $this->error("删除失败,当前删除对象已被其它对象绑定.");
        }
        elseif ($result==='onparking') {
            return $this->error("该车辆已进场,请确认出场后再删");
        }
        
        if (!$result) {
            return $this->error('删除失败');
        }

        if (empty(session('userid'))) {
            return $this->success('删除成功', url('index'));
        }
        return $this->success('删除成功', url('showUserVehicle?id=' . session('userid')));

    }

    protected function isIdExists()
    {
        $id = Request::instance()->param('id');
        if (is_null($id) || $id === 0) {
            return $this->error('id号为' . $id . '的编号不存在');
        }
        return $id;
    }

    private function openDoor()
    {
        $arrValue = LEDDrive::PlaySound("一路顺风");
        $strValue = '';
        array_walk($arrValue, function ($value) use (&$strValue) {
            $strValue .= pack('C', $value);
        });
        $code = base64_encode($strValue);
        $len  = count($arrValue);
        $data = [
            "Response_AlarmInfoPlate" =>
            [
                "serialData" => [
                    [
                        "serialChannel" => 1,
                        "data"          => "$code",
                        "dataLen"       => $len,
                    ],
                ],
            ],
        ];
        $json = json_encode($data);
        return $json;
    }
}
