<?php
namespace app\index\model;

interface BaseModelInterface{
	public static function query($name,$pageSize);
	public static function saveModel($_Model=null,$postData);
	public static function getOneModel($id=null);
	public static function deleteModelbyId($id);
	
}