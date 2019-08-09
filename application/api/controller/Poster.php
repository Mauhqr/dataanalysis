<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2019/6/11
 * Time: 15:19
 */

namespace app\api\controller;


use app\common\controller\Api;
use app\common\model\SpingMember;
use app\common\model\SpingPoster;
use think\Db;

class Poster extends Api
{

    public function saveposter(){
        $data = input('param.');
        $posterModel = new SpingPoster();
        return $posterModel->saveData($data);
    }

    /**
     * 获取海报api
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function index(){
        $zyq_id = input('zyq_id',0);
        $posterModel = new SpingPoster();
        $result = ['status'=>1,'data'=>[],'msg'=>'成功'];
        $info = $posterModel->index($zyq_id);
        if ($info){
            $result['data'] = $info;
        }else{
            $result['status'] = 0;
            $result['msg'] = '失败';
        }
        return $result;
    }

    /**
     * 获取二维码api
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function geterweima(){
        $uid = $this->userId;
        $posterModel = new SpingPoster();
        $info = $posterModel->index($uid,1);
        $member = new SpingMember();
        $memberinfo = $member->getUserInfo($uid);
        $result = ['status'=>1,'data'=>[],'msg'=>'成功'];
        $result['data']['avatar'] = $memberinfo['c_avatar'];
        $result['data']['nickname'] = $memberinfo['nickname'];
        if ($info['poster_pic'] && file_exists('./'.$info['poster_pic'])){
            $result['data']['QrCode'] = get_domain().'/'.$info['poster_pic'];
        }else{
            $url = 'https://www.xoolv.cn/wap?id='.$uid;
            $avatarurl = str_replace('./','/',$memberinfo['c_avatar']);
            /**
             * 生成二维码
             */
            $erweimaurl = $this->scerweima($url,$avatarurl);
            $result['data']['QrCode'] = get_domain().'/'.$erweimaurl;
            $posterModel->saveData(['uid'=>$uid,'poster_name'=>$memberinfo['nickname'],'poster_pic'=>$erweimaurl,'type'=>1,'create_time'=>time()]);
        }
        return $result;
    }

        /**
         * 获取随机海报
         * Author: yanjie   <823986855@qq.com>
         * Date: 2018/9/12 0012
         */
        public function getPoster2()
        {
            $id=input('ids')? input('ids') : '';
            $nickname = session('user.names');//微信昵称
            if(session('user_dossier.cur_job')==''){
                $curjob ='--';
            }else{
                $curjob = msubstr(session('user_dossier.cur_job')."·".session('user_dossier.cur_corp'),0,22);//职位
            }
            $invurl = cmf_get_domain() . cmf_url('wx/register/index') . "?invcode=".cmf_get_current_user_id(); //邀请链接
            $erweimaurl=PLUGINS_PATH.'..'.$this->QRcode($invurl); //生成二维码
            $url=session('user.avatar');
            if(preg_match('/^http(s)?:\\/\\/.+/',$url))
            {
                $logo = session('user.avatar');//微信头像
            }else
            {
                $logo = PLUGINS_PATH.'../upload/'.session('user.avatar');//微信头像
                if(!is_file($logo)){
                    $logo = '/public/assets/images/people.png';//微信头像
                }
            }
            $logo=$this->resize_img($logo);
            $logo=$this->yuan_img($logo);
            if($id==''){
                $choose=rand(1,3);
            }elseif($id==1){
                $choose=2;
            }elseif($id==2){
                $choose=3;
            }elseif($id==3){
                $choose=1;
            }
            if($choose==1){
                $name=[
                    'name'=>$nickname,     //名字
                    'size'=>'14',     //大小磅
                    'namey'=>'139'    //y轴坐标
                ];
                $job=[
                    'name'=>$curjob,     //名字
                    'size'=>'10',     //大小磅
                    'joby'=>'166'    //y轴坐标
                ];
                $logourl=[
                    'url'=>$logo,     //名字
                    'logox'=>'161',     //x轴坐标
                    'logoy'=>'34',    //y轴坐标
                    'width'=>'76',    //宽
                    'height'=>'76',    //高
                ];
                $qrcodeurl=[
                    'url'=>$erweimaurl,     //名字
                    'qrx'=>'150',     //x轴坐标
                    'qry'=>'406',    //y轴坐标
                    'width'=>'99',    //宽
                    'height'=>'99',    //高
                ];
                $beijing = '/public/assets/images/poster1.png';//海报最底层得背景
            }elseif($choose==2){
                $name=[
                    'name'=>$nickname,     //名字
                    'size'=>'14',     //大小磅
                    'namey'=>'335'    //y轴坐标
                ];
                $job=[
                    'name'=>$curjob,     //名字
                    'size'=>'10',     //大小磅
                    'joby'=>'362'    //y轴坐标
                ];
                $logourl=[
                    'url'=>$logo,     //名字
                    'logox'=>'161',     //x轴坐标
                    'logoy'=>'230',    //y轴坐标
                    'width'=>'76',    //宽
                    'height'=>'76',    //高
                ];
                $qrcodeurl=[
                    'url'=>$erweimaurl,     //名字
                    'qrx'=>'150',     //x轴坐标
                    'qry'=>'434',    //y轴坐标
                    'width'=>'99',    //宽
                    'height'=>'99',    //高
                ];
                $beijing = '/public/assets/images/poster2.png';//海报最底层得背景
            }else{
                $name=[
                    'name'=>$nickname,     //名字
                    'size'=>'14',     //大小磅
                    'namey'=>'391'    //y轴坐标
                ];
                $job=[
                    'name'=>$curjob,     //名字
                    'size'=>'10',     //大小磅
                    'joby'=>'418'    //y轴坐标
                ];
                $logourl=[
                    'url'=>$logo,     //名字
                    'logox'=>'161',     //x轴坐标
                    'logoy'=>'285',    //y轴坐标
                    'width'=>'76',    //宽
                    'height'=>'76',    //高
                ];
                $qrcodeurl=[
                    'url'=>$erweimaurl,     //名字
                    'qrx'=>'290',     //x轴坐标
                    'qry'=>'459',    //y轴坐标
                    'width'=>'99',    //宽
                    'height'=>'99',    //高
                ];
                $beijing = '/public/assets/images/poster3.png';//海报最底层得背景
            }
            $url=$this->poster($name,$job,$logourl,$qrcodeurl,$beijing);
            return ['code'=>1,'ids'=>$choose,'url'=>$url];
        }

        /**
         * 合成海报
         * Author: yanjie   <823986855@qq.com>
         * Date: 2018/9/12 0012
         */
        public function poster($name,$job,$logourl,$qrcodeurl,$beijing)
        {
//        header("content-type: image/png");//如果要看报什么错，可以先注释调这个header
            $beijing = imagecreatefrompng($beijing);
            $avator = imagecreatefrompng($logourl['url']);
            $erweimaurl = imagecreatefrompng($qrcodeurl['url']);
            $image_3 = imageCreatetruecolor(imagesx($beijing),imagesy($beijing));
            $color = imagecolorallocate($image_3, 255, 255, 255);
            imagefill($image_3, 0, 0, $color);
//        imageColorTransparent($image_3, $color);  //透明
            imagecopyresampled($image_3,$beijing,0,0,0,0,imagesx($beijing),imagesy($beijing),imagesx($beijing),          imagesy($beijing));
            //字体颜色
            $white = imagecolorallocate($image_3, 111, 255, 255);
            $rqys = imagecolorallocate($image_3, 51, 51, 51); //#333333
            $black = imagecolorallocate($image_3,0,0,0);
            $font = PLUGINS_PATH."../public/assets/font/msyh.ttf";  //写的文字用到的字体。字体最好用系统有得
//      imagettftext设置生成图片的文本
            //名称显示
            $nameBox=imagettfbbox(14, 0, $font, $name['name']); //磅
            $namewith=abs($nameBox[2]-$nameBox[0]);
            $namex=abs((imagesx($beijing)-$namewith)/2);
            //职位居中显示
            $fontBox=imagettfbbox(10, 0, $font, $job['name']); //磅
            $fontwith=abs($fontBox[2]-$fontBox[0]);
            $jobx=abs((imagesx($beijing)-$fontwith)/2);
            imagettftext($image_3,$name['size'],0,$namex,$name['namey'],$rqys,$font,$name['name']); //磅
            imagettftext($image_3,$job['size'],0,$jobx,$job['joby'],$rqys,$font,$job['name']);
            imagecopymerge($image_3,$avator, $logourl['logox'],$logourl['logoy'],0,0,$logourl['width'],$logourl['height'],100);//左，上，右，下，宽度，高度，透明度
            imagecopymerge($image_3,$erweimaurl, $qrcodeurl['qrx'],$qrcodeurl['qry'],0,0,$qrcodeurl['width'],$qrcodeurl['height'], 100);
            //生成图片
            //imagepng($image_3);//在浏览器上显示
            clearstatcache(); //清除缓存is_file
            $fileurl="./upload/posters/100000".session('user.id').".png";
            $showurl="/upload/posters/100000".session('user.id').".png";
            imagepng($image_3,$fileurl);//保存到本地
            imagedestroy($image_3);
            return $showurl;
        }
        //二维码生成
        public function QRcode($data) {
            $url = urldecode($data);
            $fileurl="./upload/posters/100000".session('user.id')."qr.png";
            $showurl="/upload/posters/100000".session('user.id')."qr.png";
            QRcode::png($url, $fileurl, 3, 2.2);
            return $showurl;
        }

        /**
         * 缩放图片
         * Author: yanjie   <823986855@qq.com>
         * Date: 2018/9/12 0012
         */
        public function resize_img($url){
            $file = $url;
            $ext  = pathinfo($url);
            $src_im = null;
            list($width, $height) = getimagesize($file); //获取原图尺寸
            $percent = (76/$width); //缩放尺寸  76px
            $newwidth = $width * $percent;
            $newheight = $height * $percent;
            switch ($ext['extension']) {
                case 'jpg':
                    $src_im = imagecreatefromjpeg($file);
                    break;
                case 'png':
                    $src_im = imagecreatefrompng($file);
                    break;
            }
            $dst_im = imagecreatetruecolor($newwidth, $newheight);
            imagecopyresized($dst_im, $src_im, 0, 0, 0, 0, $newwidth, $newheight, $width, $height);
            $fileurl="./upload/posters/100000".session('user.id')."avator.png";
            imagepng($dst_im, $fileurl); //输出压缩后的图片
            imagedestroy($dst_im);
            imagedestroy($src_im);
            return $fileurl;
        }

        /**
         * 图片裁剪为圆形图片
         * Author: yanjie   <823986855@qq.com>
         * Date: 2018/9/12 0012
         */
        public function yuan_img($imgpath){
            $ext     = pathinfo($imgpath);
            $src_img = null;
            switch ($ext['extension']) {
                case 'jpg':
                    $src_img = imagecreatefromjpeg($imgpath);
                    break;
                case 'png':
                    $src_img = imagecreatefrompng($imgpath);
                    break;
            }
            $wh  = getimagesize($imgpath);
            $w   = $wh[0];
            $h   = $wh[1];
            $w   = min($w, $h);
            $h   = $w;
            $img = imagecreatetruecolor($w, $h);
            //这一句一定要有
            imagesavealpha($img, true);
            //拾取一个完全透明的颜色,最后一个参数127为全透明
            $bg = imagecolorallocatealpha($img, 255, 255, 255, 127);
            imagefill($img, 0, 0, $bg);
            $r   = $w / 2; //圆半径
            $y_x = $r; //圆心X坐标
            $y_y = $r; //圆心Y坐标
            for ($x = 0; $x < $w; $x++) {
                for ($y = 0; $y < $h; $y++) {
                    $rgbColor = imagecolorat($src_img, $x, $y);
                    if (((($x - $r) * ($x - $r) + ($y - $r) * ($y - $r)) < ($r * $r))) {
                        imagesetpixel($img, $x, $y, $rgbColor);
                    }
                }
            }
            $fileurl="./upload/posters/100000".session('user.id')."avator.png";
            imagepng($img,$fileurl);
            imagedestroy($img);
            return $fileurl;
        }

        public function getposter3(){
            $id = input('id',0);
            $user_id = input('uid',0);
            if(empty($id) || empty($user_id)){
                return ['status' => false,'msg' => '参数错误','data' => ''];
            }
            if(file_exists('./poster/poster_'.$id.'_'.$user_id.'.png')){
                return ['status' => true,'msg' => '获取成功','data' => get_domain().'/poster/poster_'.$id.'_'.$user_id.'.png'];
            }
            $info = Db::name('poster')->where('id',$id)->find();
            if(empty($info)){
                return ['status' => false,'msg' => '海报不存在','data' => ''];
            }

            if(!file_exists('./qrcode/erweima_'.$user_id.'.png')){
                $url = get_domain()."/wap/?url=registergroup&invitecode=".$user_id;
                $url = "WWW.baidu.com";
                $spingmember = new SpingMember();
                $user_info = $spingmember->getUserInfo($user_id);
                $logo =  '.'.$user_info['c_avatar'];
                $ewm = $this->scerweima($url,$logo);
            }else{
                $ewm = 'qrcode/erweima_'.$user_id.'.png';
            }

            $posterpath = str_replace(get_domain(), '', _sImage($info['imgpath']));
            $this->get_warrant($posterpath, $ewm, $id);
            return ['status' => true,'msg' => '获取成功','data' => get_domain().'/poster/poster_'.$id.'_'.$user_id.'.png'];
        }

    public function scerweima($url='',$logo_url=''){
        require_once './../vendor/phpqrcode/phpqrcode.php';
        $value = $url;         //二维码内容
        $errorCorrectionLevel = 'H';  //容错级别
        $matrixPointSize = 6;      //生成图片大小
        //生成二维码图片
        $filename = 'qrcode/erweima_'.$this->userId.'.png';
        \QRcode::png($value,$filename , $errorCorrectionLevel, $matrixPointSize, 2);
        $QR = $filename;        //已经生成的原始二维码图片文件
        $QR = imagecreatefromstring(file_get_contents($QR));

        if($logo_url){
            $logo_url = get_domain().$logo_url;
            $logo = imagecreatefrompng($logo_url);

            $QR_width = imagesx($QR);
            $QR_height = imagesy($QR);
            $logo_width = imagesx($logo);
            $logo_height = imagesy($logo);
            // Scale logo to fit in the QR Code
            $logo_qr_width = $QR_width/4;
            $scale = $logo_width/$logo_qr_width;
            $logo_qr_height = $logo_height/$scale;
            $from_width = ($QR_width-$logo_qr_width)/2;
            //echo $from_width;exit;

            imagecopyresampled($QR, $logo, $from_width, $from_width, 0, 0, $logo_qr_width, $logo_qr_height, $logo_width, $logo_height);
        }
        //输出图片
        imagepng($QR, $filename);
        imagedestroy($QR);
        return $filename;
    }

    public function get_warrant($posterpath,$ewm,$id){
        header("Content-type: image/jpeg");
        if(!is_dir('./poster')){
            mkdir('./poster',0777);
        }
        //获取海报信息
        $info = getimagesize($posterpath);
        //获取海报扩展名
        $type = image_type_to_extension($info[2],false);

        //获取水印图片的宽高
        list($src_w, $src_h) = getimagesize($ewm);

        //创建图片的实例
        $posterpath = imagecreatefromstring(file_get_contents($posterpath));
        $ewm = imagecreatefrompng($ewm);
        //将水印图片复制到目标图片上
        imagecopy($posterpath, $ewm, 300, 740, 0, 0, $src_w, $src_h);

        //新图片地址
        $image = 'poster/poster_'.$id.'_'.$this->userId.'.png';

        $func = "image{$type}";
        $func($posterpath,$image);

        imagedestroy($posterpath);
        imagedestroy($ewm);
    }
}