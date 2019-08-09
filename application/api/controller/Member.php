<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2019/6/18
 * Time: 9:39
 */

namespace app\api\controller;

use app\common\controller\Api;
use app\common\model\Setting;
use app\common\model\Sms;
use app\common\model\SpingGiftLog;
use app\common\model\SpingMember;
use app\common\model\UserToken;

class Member extends Api
{
    public $clientSecret = 'zhuyeqings313eddsadsazh';
    public $clientId = 'chunfen';

    public function userinfo(){
        $result = ['status'=>1,'data'=>[],'msg'=>'成功'];
        $uid = input('uid',0);
        if (empty($uid)){
            $uid = $this->userId;
        }
        $member = new SpingMember();
        $memberinfo = $member->getUserInfo($uid);
        $memberinfo['c_avatar'] = get_domain().str_replace('./','/',$memberinfo['c_avatar']);
        $memberinfo['city'] = empty($memberinfo['city'])?"未知":$memberinfo['city'];
        $result['data'] = $memberinfo;
        return $result;
    }

    public function Wxlogin(){
        $setting = new Setting();

        $APPID = $setting->getValue('wx_appid');
        $AppSecret = $setting->getValue('wx_app_secret');
        $code = input('code');
        $url="https://api.weixin.qq.com/sns/jscode2session?appid=".$APPID."&secret=".$AppSecret."&js_code=".$code."&grant_type=authorization_code";
        $arr = $this->vget($url);  // 一个使用curl实现的get方法请求
        $arr = json_decode($arr,true);
        return json($arr);
//        $openid = $arr['openid'];
//        $session_key = $arr['session_key'];
//
//        // 数据签名校验
//        $signature = input('signature');
//        $signature2 = sha1(input('rawData').$session_key);
//        if ($signature != $signature2) {
//            echo '数据签名验证失败！';die;
//        }
//
//        //开发者如需要获取敏感数据，需要对接口返回的加密数据( encryptedData )进行对称解密
//        //加载解密文件，在官方有下载
//        include_once './../vendor/wxBizDataCrypt.php';
//        $encryptedData = input('encryptedData');
//        $iv = input('iv');
//        $pc = new \WXBizDataCrypt($APPID, $session_key);
//        $errCode = $pc->decryptData($encryptedData, $iv, $data=[]);  //其中$data包含用户的所有数据
//        if ($errCode != 0) {
//            echo '解密数据失败！';die;
//        }
//
//        //生成第三方3rd_session
//        $session3rd  = null;
//        $strPol = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz";
//        $max = strlen($strPol)-1;
//        for($i=0;$i<16;$i++){
//            $session3rd .=$strPol[rand(0,$max)];
//        }
////        echo $session3rd;
//        $json['3rd_session'] = $session3rd;
//        $json['data'] = $data;
////        $this->response->addHeader('Content-Type: application/json');
////        $this->response->setOutput(json_encode($json));
//        return $json;
    }

    public function vget($url){
        $info=curl_init();
        curl_setopt($info,CURLOPT_RETURNTRANSFER,true);
        curl_setopt($info,CURLOPT_HEADER,0);
        curl_setopt($info,CURLOPT_NOBODY,0);
        curl_setopt($info,CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($info,CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($info,CURLOPT_URL,$url);
        $output= curl_exec($info);
        curl_close($info);
        return $output;
    }

    public function go($url,$method='POST',$data=''){
        if(!$url){
            return ;
        }
        if(!$method){
            return ;
        }
        $method=strtoupper($method);
        $header = array("Accept-Charset: utf-8");
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)');
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
        //if($method=='POST'){
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        //}
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            return curl_error($ch);
        }
        curl_close($ch);
        return $result;
    }

    public function gotow($url,$post_data){
        //初始化
            $curl = curl_init();
      //设置抓取的url
      curl_setopt($curl, CURLOPT_URL, $url);
      //设置头文件的信息作为数据流输出
     curl_setopt($curl, CURLOPT_HEADER, 1);
    //设置获取的信息以文件流的形式返回，而不是直接输出。
     curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
      //设置post方式提交
     curl_setopt($curl, CURLOPT_POST, 1);
     //设置post数据

     curl_setopt($curl, CURLOPT_POSTFIELDS, $post_data);
    //执行命令
     $data = curl_exec($curl);
     //关闭URL请求
     curl_close($curl);
    //显示获得的数据
     return $data;
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
//        strpos($imgpath,'uploads/')+1
        $picn = substr($imgpath,9);
        $fileurl="./upload/postersavator/".$picn;
        imagepng($img,$fileurl);
        imagedestroy($img);
        return $fileurl;
    }


    /**
     * 保存竹叶青用户信息到spingmember
     */

    public function saveMemberzyq($data=[]){
        if (!empty($data)){
            $memberModel = new SpingMember();
            return $memberModel->saveData($data);
        }

    }
    public function SaveMember(){
        $memberModel = new SpingMember();
        $data = input('param.info');
        $val = $memberModel->where('openid',$data['openid'])->find();
        if ($val){
            $result = ['status'=>1,'data'=>[],'msg'=>'成功'];
            $usertoken = new UserToken();
            $val2 = $usertoken->setToken($val['id']);
            $result['data']['uid'] = $val['id'];
            $result['data']['token'] = $val2['data'];
            if (!$val2['status']){
                $result['status'] = 0;
                $result['msg'] = $val2['msg'];
            }
            return $result;
        }
        $userdata = [];
        $userdata['openid'] = $data['openid'];
        $userdata['nickname'] = $data['nickName'];
        $userdata['sex'] = $data['gender'] > 0?$data['gender'] : 3;
        $userdata['avatar'] = $data['avatarUrl'];
        $userdata['bd_avatar'] = $memberModel->getavatar($data['avatarUrl']);
        $userdata['c_avatar'] = $this->yuan_img($userdata['bd_avatar']);
        $userdata['city'] = $data['city'];
        $userdata['create_time'] = time();
        return $memberModel->saveData($userdata);

    }
    /**
     * 获取短信验证码
     */
    public function getverity(){
        $url = 'http://zysc1.fullnode.com:11805/CfApi/sendYzm';
        $mobile = input('mobile','');
        $jmarray = ['mobile'=>$mobile,'clientId'=>$this->clientId,'timestamp'=>time()];
        $curl_data = ['clientId'=>$this->clientId,'mobile'=>$mobile,'sign'=>$this->getSign($jmarray,$this->clientSecret),'timestamp'=>time()];
//        print_r(json_encode($curl_data));
        $val = $this->go($url,'post',json_encode($curl_data));
        $val = json_decode($val,true);
        if ($val['code'] != 'SUC'){
            return json($val);
        }else{
            $smsmodel = new Sms();
            $jg = $smsmodel->send($mobile,'receive',['mobile'=>$mobile,'timestamp'=>time(),'code'=>$val['data']],$val['data']);
            if ($jg){
                return ['code'=> 'SUC','msg'=>'发送成功'];
            }
        }

    }
    /**
     * 验证验证码
     */
    public function chechcode(){
        $result = ['status'=>1,'data'=>[],'msg'=>'验证成功'];
        $mobile = input('param.mobile/s','');
        $verily_code = input('param.verily_code/s','');
        if (!isset($mobile) || !isset($verily_code)){
            $result['status'] = 0;
            $result['msg'] = '参数错误';
            return $result;
        }
        $smsmodel = new Sms();
        $val = $smsmodel->check($mobile,$verily_code,'receive');
        if (!$val){
            $result['status'] = 0;
            $result['msg'] = '验证码错误';
        }
        return $result;
    }

    public function  postgift(){
        $url = "http://zysc1.fullnode.com:11805/CfApi/getCfGift";
        $mobile = input('mobile','');
        $sex = input('sex','');
        $city = input('city','');
        $cert_code = input('cert_code','');
        if (!isset($mobile) || !isset($sex) || !isset($city) || !isset($cert_code)){
            return ['code'=> 'E1','msg'=>'发送成功'];
        }

        $jmarray = ['mobile'=>$mobile,'clientId'=>$this->clientId,'timestamp'=>time(),'city'=>$city,'cert_code'=>$cert_code,'sex'=>$sex];
        $curl_data = [
            'clientId'=>$this->clientId,
            'mobile'=>$mobile,
            'sex'=>$sex,
            'city'=>$city,
            'cert_code'=>$cert_code,
            'sign'=>$this->getSign($jmarray,$this->clientSecret),
            'timestamp'=>time()
        ];
//        print_r($curl_data);
        $val = $this->go($url,'post',json_encode($curl_data));
        $val = json_decode($val,true);

        if ($val['code'] != 'SUC'){
            return json($val);
        }else{
                return ['code'=> 'SUC','msg'=>'礼卷发送注册成功'];
        }
    }

    /**
     * 请求加密算法
     * @param $array
     * @param string $clientSecret
     * @return string
     */
    private function getSign($array,$clientSecret=''){
        ksort($array);
        $str = "";
        foreach ($array as $k => $v) {
            $str.= $k.$v;
        }
        $restr=$str.md5($clientSecret);
        $sign = strtoupper(sha1($restr));
        return $sign;
    }


}