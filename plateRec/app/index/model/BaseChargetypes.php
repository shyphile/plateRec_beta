<?php
namespace app\index\model;
/**
 * 收费类型的通用接口
 */
interface BaseChargetypes{
	public static function query($name,$pageSize);
	public static function saveModel($_Model=null,$postData);
	public static function getOneModel($id=null);
	public static function deleteModelbyId($id);
	
}