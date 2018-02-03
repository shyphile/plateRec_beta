<?php
namespace app\index\controller;

use think\Controller;

// use app\index\model\Operator;
/**
 *
 */
class ToolController extends Controller
{
    public function getImage()
    {
        $imgPath = input('get.imgPath');
        $imgPath=str_replace('\\','/',$imgPath);
        return $this->imgToBase64($imgPath);
    }

    /**
     * 获取图片的Base64编码(不支持url)
     * @param $img_file 传入本地图片地址
     * @return string
     */
    private function imgToBase64($img_file)
    {
        $img_file   = iconv('UTF-8', 'GB2312', $img_file);
        if(!file_exists($img_file)){
            $img_file="nopic.jpg";
        }

        $img_base64 = '';
      //  if (file_exists($img_file)) {
            $app_img_file = $img_file; // 图片路径
            $img_info     = getimagesize($app_img_file); // 取得图片的大小，类型等
            $fp           = fopen($app_img_file, "r"); // 图片是否可读权限

            if ($fp) {
                $filesize     = filesize($app_img_file);
                $content      = fread($fp, $filesize);
                $file_content = chunk_split(base64_encode($content)); // base64编码
                switch ($img_info[2]) {
                    //判读图片类型
                    case 1:$img_type = "gif";
                    break;
                    case 2:$img_type = "jpg";
                    break;
                    case 3:$img_type = "png";
                    break;
                }
                $img_base64 = 'data:image/' . $img_type . ';base64,' . $file_content; //合成图片的base64编码
            }
            fclose($fp);
     //   }
        return $img_base64; //返回图片的base64
    }
}
