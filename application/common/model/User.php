<?php
namespace app\common\model;

use think\model\concern\SoftDelete;
use think\Validate;
use think\Db;

class User extends Common
{
    use SoftDelete;
    protected $deleteTime = 'isdel';

    protected $autoWriteTimestamp = true;
    protected $updateTime = 'utime';


    const STATUS_NORMAL = 1;        //用户状态 正常
    const STATUS_DISABLE = 2;       //用户状态 停用

    const SEX_BOY = 1;
    const SEX_GIRL = 2;
    const SEX_OTHER = 3;

    //protected $resultSetType = 'collection';

    protected $rule = [
        //'username' => 'length:6,20|alphaDash',
        'mobile' => ['regex' => '^1[3|4|5|7|8][0-9]\d{4,8}$'],
        'sex' => 'in:1,2,3',
        'nickname' => 'length:2,50',
        'password' => 'length:6,20',
        'p_mobile' => ['regex' => '^1[3|4|5|7|8][0-9]\d{4,8}$'],
    ];
    protected $msg = [
        //'username.length' => '用户名长度6~20位',
        //'username.alphaDash' => '用户名只能是字母、数字或下划线组成',
        'mobile' => '请输入一个合法的手机号码',
        'sex' => '请选择合法的性别',
        'nickname' => '昵称长度为2-50个字符',
        'password' => '密码长度6-20位',
        'p_mobile' => '邀请人栏请输入一个合法的手机号码',
    ];

    /**
     * (废弃)注册添加用户,此接口废弃掉了，建议使用smsLogin方法
     * @param array $data 新建用户的数据数组
     * @param int $loginType 登陆类型，1网页登陆，存session，2接口登陆，返回token
     *
     */
//    public function toAdd($data, $loginType=1)
//    {
//        $result = array(
//            'status' => false,
//            'data' => '',
//            'msg' => ''
//        );
//
//        //校验数据
//        $validate = new Validate($this->rule, $this->msg);
//        if(!$validate->check($data)){
//            $result['msg'] = $validate->getError();
//            return $result;
//        }
//
//        //校验短信验证码
//        $smsModel = new Sms();
//        if(!$smsModel->check($data['mobile'], $data['code'], 'reg')){
//            $result['msg'] = '短信验证码错误';
//            return $result;
//        }
//        $data['ctime'] = time();
//        $data['password'] = $this->enPassword($data['password'], $data['ctime']);
//
//        if(!isset($data['avatar'])){
//
//            $http_type = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';
//            $data['avatar'] =$http_type . $_SERVER['HTTP_HOST'].config('jshop.default_image');
//        }
//        if(!isset($data['nickname'])){
//            $data['nickname'] = format_mobile($data['mobile']);
//        }
//
//        //保存推荐人
//        if(isset($data['pid'])){
//            $pinfo = $this->where(['id'=>$data['pid']])->find();
//            if(!$pinfo){
//                error_code(10014);
//            }
//        }
//        Db::startTrans();//增加事物
//        try {
//            //插入数据库
//            $this->data($data)->allowField(true)->save();
//
//            if ($data['authorId']) {//有授权过来，说明是第三方登录过来，需要更新user_wx表，此处直接更新老用户手机号，存在风险 TODO
//                $userWxModel = new UserWx();
//                $userWxModel->update(['user_id' => $this->id, 'mobile' => $data['mobile']], ['id' => $data['authorId']]);
//            }
//            Db::commit();
//        }catch (\Exception $e) {
//                Db::rollback();
//                $result['msg'] = $e->getMessage();
//                return $result;
//        }
//        return $this->setSession($this ,$loginType);
//    }

    /**
     * 用户账户密码登陆
     * @param array $data 用户登陆信息
     * @param int   $loginType 1就是默认的，存session，2就是返回user_token
     * @param int   $platform 平台id，主要和session有关系 1就是默认的平台，，2就是微信小程序平台，当需要放回user_token的时候，会用到此字段
     *
     */
    public function toLogin($data, $loginType=1,$platform=1)
    {
        $result = array(
            'status' => false,
            'data' => '',
            'msg' => ''
        );

        if(!isset($data['mobile']) || !isset($data['password'])) {
            $result['msg'] = '请输入手机号码或者密码';
            return $result;
        }
        //校验验证码
        if(session('?login_fail_num')){
            if(session('login_fail_num') >= config('jshop.login_fail_num')){
                if(!isset($data['captcha']) || $data['captcha'] == ''){
                    return error_code(10013);
                }
                if(!captcha_check($data['captcha'])){
                    return error_code(10012);
                };
            }
        }

        $userInfo = $this->where(array('username'=>$data['mobile']))->whereOr(array('mobile'=>$data['mobile']))->find();
        if(!$userInfo){
            $result['msg'] = '没有找到此账号';
            return $result;
        }


        //判断是否是用户名登陆
        $userInfo = $this->where(array('username|mobile'=>$data['mobile'],'password'=>$this->enPassword($data['password'], $userInfo->ctime)))->find();
        if($userInfo){
            $result = $this->setSession($userInfo,$loginType,$platform);            //根据登陆类型，去存session，或者是返回user_token
        }else{
            //写失败次数到session里
            if(session('?login_fail_num')){
               session('login_fail_num',session('login_fail_num')+1);
            }else{
                session('login_fail_num',1);
            }
            $result['msg'] = '密码错误，请重试';
        }

        return $result;


    }

    /**
     * 手机短信验证码登陆，同时兼有手机短信注册的功能，还有第三方账户绑定的功能
     * @param $data
     * @param int $loginType 登陆类型，1网页登陆，存session，2接口登陆，返回token
     * @param int $platform
     * @return array
     */
    public function smsLogin($data, $loginType=1,$platform=1)
    {
        $result = array(
            'status' => false,
            'data' => '',
            'msg' => ''
        );
        if(!isset($data['mobile'])) {
            $result['msg'] = '请输入手机号码';
            return $result;
        }
        if(!isset($data['code'])) {
            $result['msg'] = '请输入验证码';
            return $result;
        }

        //判断是否是用户名登陆
        $smsModel = new Sms();
        $userWxModel = new UserWx();
        if(!$smsModel->check($data['mobile'], $data['code'], 'login')){
            $result['msg'] = '短信验证码错误';
            return $result;
        }

        $userInfo = $this->where(array('mobile'=>$data['mobile']))->find();
        if(!$userInfo){
            //没有此用户，创建此用户
            $userData['mobile'] = $data['mobile'];

            //判断是否是小程序里的微信登陆，如果是，就查出来记录，取他的头像和昵称
            if(isset($data['user_wx_id'])){
                $user_wx_info = $userWxModel->where(['id'=>$data['user_wx_id']])->find();
                if($user_wx_info){
                    if(!isset($data['avatar'])){
                        $data['avatar'] = $user_wx_info['avatar'];
                    }
                    if(!isset($data['nickname'])){
                        $data['nickname'] = $user_wx_info['nickname'];
                    }
                }
            }
            //如果没有头像和昵称，那么就取系统头像和昵称吧
            if(isset($data['avatar'])){
                $userData['avatar'] = $data['avatar'];
            }else{
                $http_type = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';
//                $userData['avatar'] = config('jshop.default_image');
                $userData['avatar'] = _sImage('');
            }
            if(isset($data['nickname'])){
               $userData['nickname']  = $data['nickname'];
            }else{
                $userData['nickname'] = format_mobile($data['mobile']);
            }
            if (isset($data['invitecode']) && $data['invitecode']) {
                $pid   = $this->getUserIdByShareCode($data['invitecode']);
                $pinfo = model('common/User')->where(['id' => $pid])->find();
                if ($pinfo) {
                    $userData['pid'] = $pid;
                } else {
                    error_code(10014);
                }
            }

            $userData['ctime'] = time();
            if(isset($data['password'])){
                $userData['password'] = $this->enPassword($data['password'], $userData['ctime']);
            }

            //取默认的用户等级
            $userGradeModel = new UserGrade();
            $userGradeInfo = $userGradeModel->where('is_def',$userGradeModel::IS_DEF_YES)->find();
            if($userGradeInfo){
                $userData['grade'] = $userGradeInfo['id'];
            }


            $user_id = $this->insertGetId($userData);
            if(!$user_id){
                return error_code(10000);
            }
            $userInfo = $this->where(array('id'=>$user_id))->find();
        }else{
            //如果有这个账号的话，判断一下是不是传密码了，如果传密码了，就是注册，这里就有问题，因为已经注册过
            if(isset($data['password'])){
                return error_code(11019);
            }
        }
        //判断是否是小程序里的微信登陆，如果是，就给他绑定微信账号
        if(isset($data['user_wx_id'])){
            $userWxModel->save(['user_id'=>$userInfo['id']],['id'=>$data['user_wx_id']]);
        }

        $result = $this->setSession($userInfo ,$loginType,$platform);            //根据登陆类型，去存session，或者是返回user_token


        return $result;


    }

    /**
     * 登陆注册的时候，发送短信验证码
     */
    public function sms($mobile, $code)
    {
        $result = [
            'status' => false,
            'data' => '',
            'msg' => '成功'
        ];

        $userInfo = $this->where(array('mobile'=>$mobile))->find();
        if($code == 'reg') {
            //注册
            if ($userInfo) {
                $result['msg'] = '此账号已经注册过，请直接登陆';
                return $result;
            }
            $code = 'login';        //手机短信注册和手机短信登陆是一个接口，所以，在这要换算成login，详见smsLogin方法

            //判断账号状态
//            if($userInfo->status != self::STATUS_NORMAL) {
//                $result['msg'] = '此账号已停用';
//                return $result;
//            }
        }elseif($code == 'login'){
            //登陆
        } elseif ($code === 'veri') {
            // 找回密码
        }else{
            //其他业务逻辑
            $result['msg'] = '无此业务类型';
            return $result;
        }

        //没问题了，就去发送短信验证码
        $smsModel = new Sms();
        return $smsModel->send($mobile, $code,[]);
    }

    /**
     *设置用户登录信息或者更新用户登录信息
     * User:tianyu
     * @param $userInfo
     * @param $data
     * @param $loginType            登陆类型1是存session，主要是商户端的登陆和网页版本的登陆,2就是token
     * @param int $platform         1就是普通的登陆，主要是vue登陆，2就是小程序登陆，写这个是为了保证h5端和小程序端可以同时保持登陆状态
     * @param int $type         1的话就是登录,2的话就是更新
     * @return array
     */
    public function setSession($userInfo ,$loginType,$platform=1,$type=1)
    {
        $result = [
            'status' => false,
            'data' => '',
            'msg' => ''
        ];
        //判断账号状态
        if($userInfo->status != self::STATUS_NORMAL) {
            $result['msg'] = '此账号已停用';
            return $result;
        }


        switch ($loginType)
        {
            case 1:
                session('user',$userInfo->toArray());
                $result['status'] = true;
                break;
            case 2:
                $userTokenModel = new UserToken();
                $result = $userTokenModel->setToken($userInfo['id'],$platform);
                break;
        }

        if ($type == 1)
        {
            //$userLogModel = new UserLog();        //添加登录日志
            //$userLogModel->setLog($userInfo['id'],$userLogModel::USER_LOGIN);
        }
        return $result;

    }

    public function editInfo($id,$sex='',$birthday='',$nickname='',$avatar='')
    {
        $result = [
            'status' => false,
            'data' => '',
            'msg' => ''
        ];
        $data = [];

        if($sex != ''){
            $data['sex'] = $sex;
        }
        if($birthday != ''){
            $data['birthday'] = $birthday;
        }
        if($nickname != ''){
            $data['nickname'] = htmlentities($nickname);
        }
        if ($avatar != '' ){
            $data['avatar'] = $avatar;
        }
        $re = $this->save($data,['id'=>$id]);
        if($re !== false)
        {
            //$userLogModel = new UserLog();
            //$userLogModel->setLog($id,$userLogModel::USER_EDIT);
            $result['status'] = true;
            $result['msg'] = '保存成功';
            return $result;
        }
        else
        {
            return error_code(10005);
        }
    }



    /**
     * 密码加密方法
     * @param string $pw 要加密的字符串
     * @return string
     */
    private function enPassword($password,$ctime){

        return md5(md5($password).$ctime);
    }

    protected function tableWhere($post)
    {
        $where = [];
        if(isset($post['sex']) && $post['sex'] != ""){
            $where[] = ['sex', 'eq', $post['sex']];
        }
        if(isset($post['id']) && $post['id'] != ""){
            $where[] = ['id', 'eq', $post['id']];
        }
        if(isset($post['username']) && $post['username'] != ""){
            $where[] = ['username', 'like', '%'.$post['username'].'%'];
        }
        if(isset($post['mobile']) && $post['mobile'] != ""){
            $where[] = ['mobile', 'eq', $post['mobile']];
        }
        if(isset($post['birthday']) && $post['birthday'] != ""){
            $where[] = ['birthday', 'eq', $post['birthday']];
        }
        if(isset($post['nickname']) && $post['nickname'] != ""){
            $where[] = ['nickname', 'like', '%'.$post['nickname'].'%'];
        }
        if(isset($post['status']) && $post['status'] != ""){
            $where[] = ['status', 'eq', $post['status']];
        }
        if(isset($post['pmobile']) && $post['pmobile'] != ""){
            if($puser_id = get_user_id($post['pmobile'])){
                $where[] = ['pid', 'eq', $puser_id];
            }else{
                $where[] = ['pid', 'eq', '99999999'];       //如果没有此用户，那么就赋值个数值，让他查不出数据
            }
        }

        $result['where'] = $where;
        $result['field'] = "*";
        $result['order'] = "id desc";
        return $result;
    }

    /**
     * 根据查询结果，格式化数据
     * @param $list
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    protected function tableFormat($list)
    {
        foreach ($list as $k => $v) {
            if ($v['sex']) {
                $list[$k]['sex'] = config('params.user')['sex'][$v['sex']];
            }
            if ($v['status']) {
                $list[$k]['status'] = config('params.user')['status'][$v['status']];
            }
            if ($v['pid']) {
                $list[$k]['pid_name'] = get_user_info($v['pid']);
            }
            if ($v['ctime']) {
                $list[$k]['ctime'] = getTime($v['ctime']);
            }
            if (isset($v['avatar']) && $v['avatar']) {
                $list[$k]['avatar'] = _sImage($v['avatar']);
            }
        }
        return $list;
    }


    public function changeAvatar($id,$image_url)
    {
        $data['avatar'] = $image_url;
        $where['id'] = $id;
        if($this->save($data,$where)){
            return true;
        }else{
            return false;
        }
    }


    /**
     * 获取用户的信息
     * @return array|null|\PDOStatement|string|\think\Model
     */
    public function getUserInfo($user_id)
    {
        $data = $this->where('id',$user_id)->find();
        if($data){
            $data['status'] = config('params.user')['status'][$data['status']];
            $data['p_mobile'] = $this->getUserMobile($data['pid']);
            return $data;
        }else{
            return "";
        }

    }

    /**
     * 更新密码验证/找回密码验证
     * @param $data
     * @return array
     */
    public function checkCode($data)
    {
        $result = ['status'=>false,'msg'=>'','data'=>''];
        //修改密码验证原密码
        if ( isset($data['password']) && !empty($data['password']))
        {
            $user = $this->getUserInfo($data['user_id']);
            if ($user['password'] !== $this->enPassword($data['password'],$user['ctime']) )
            {
                $result['status'] = false;
                $result['msg'] = '原密码不正确!';
                return $result;
            }
        }

        if ( strval($data['newPwd']) !== strval($data['rePwd']) )
        {
            $result['msg'] = '两次密码不一致,请重新输入';
            return $result;
        }

        if ( strlen($data['newPwd']) < 6 )
        {
            $result['msg'] = '密码不能小于6位数';
            return $result;
        }

        //找回密码验证手机验证码
        if ( isset($data['code']) && !empty($data['code']))
        {
            $smsModel = new Sms();
            if ( !$smsModel->check($data['mobile'],$data['code'],'veri') )
            {
                $result['msg'] = '手机验证码错误!';
                return $result;
            }
        }

        return $this->editPwd($data['user_id'],$data['newPwd']);

    }


    /**
     *  修改密码
     * @param $user_id
     * @param $pwd
     * @return array
     */
    private function editPwd($user_id,$newPwd)
    {
        $result = [
            'status' => true,
            'msg' => '',
            'data' => ''
        ];

        $res_pwd = $this->save([
            'password'=>$this->enPassword($newPwd,$this->where('id',$user_id)->value('ctime'))
        ],['id'=>$user_id]);

        if ( !$res_pwd )
        {
            $result['status'] = false;
            $result['msg'] = '修改失败请重试!';
            return $result;
        }

        $result['msg'] = '密码修改成功!';
        return $result;
    }


    /**
     *
     *  获取用户的推荐列表
     * @param $user_id
     * @param $page
     * @param $limit
     *
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function recommendList($user_id, $page=1, $limit=10)
    {
        $data = $this
            ->field('nickname, avatar, mobile, ctime')
            ->where('pid', $user_id)
            ->page($page, $limit)
            ->select();
        $count = $this
            ->field('nickname, avatar, mobile, ctime')
            ->where('pid', $user_id)
            ->count();
        if (!$data->isEmpty())
        {
            foreach ($data as $v) {
                $v['ctime'] = getTime($v['ctime']);
            }
            $result['data'] = $data;
        }
        return $result = [
            'status' => true,
            'msg' => '获取成功',
            'data' => $data,
            'total' => ceil($count/$limit)
        ];
    }


    /**
     * 获取用户的积分
     * @param $user_id
     * @param int $order_money
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getUserPoint($user_id, $order_money = 0)
    {
        $return = [
            'status' => false,
            'msg' => '获取失败',
            'data' => 0,
            'available_point' => 0,
            'point_rmb' => 0,
            'switch' => 1
        ];

        $settingModel = new Setting();
        $switch = $settingModel->getValue('point_switch');
        if($switch == 2)
        {
            $return['status'] = true;
            $return['switch'] = 2;
            return $return;
        }

        $where[] = ['id', 'eq', $user_id];
        $data = $this->field('point')->where($where)->find();
        if($data !== false)
        {
            if($order_money != 0)
            {
                //计算可用积分
                $settingModel = new Setting();
                $orders_point_proportion = $settingModel->getValue('orders_point_proportion'); //订单积分使用比例
                $max_point_deducted_money = $order_money*($orders_point_proportion/100); //最大积分抵扣的钱
                $point_discounted_proportion = $settingModel->getValue('point_discounted_proportion'); //积分兑换比例
                $needs_point = $max_point_deducted_money*$point_discounted_proportion;
                $return['available_point'] = floor($needs_point>$data['point']?$data['point']:$needs_point);
                $return['point_rmb'] = $return['available_point']/$point_discounted_proportion;
            }

            $return['msg'] = '获取成功';
            $return['data'] = $data['point'];
            $return['status'] = true;
        }

        return $return;
    }


    /**
     * 获取用户昵称 （废弃方法，不建议使用，建议使用get_user_info()函数）
     * @param $user_id
     * @return mixed|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getUserNickname($user_id)
    {
        $where[] = ['id', 'eq', $user_id];
        $result = $this->field('nickname, mobile')
            ->where($where)
            ->find();
        if($result)
        {
            $nickname = $result['nickname']?$result['nickname']:format_mobile($result['mobile']);
        }
        else
        {
            $nickname = '';
        }

        return $nickname;
    }


    /**
     * 获取用户手机号
     * @param $user_id
     * @return mixed|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getUserMobile($user_id)
    {
        $where[] = ['id', 'eq', $user_id];
        $result = $this->field('mobile')->where($where)->find();
        return $result['mobile']?$result['mobile']:'';
    }


    /**
     * 通过手机号获取用户ID
     * @param $mobile
     * @return bool|mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getUserIdByMobile($mobile)
    {
        $where[] = ['mobile', 'eq', $mobile];
        $where[] = ['status', 'eq', self::STATUS_NORMAL];
        $result = $this->field('id')->where($where)->find();
        return $result['id']?$result['id']:false;
    }


    /**
     * 绑定上级
     * @param $user_id
     * @param $superior_id
     * @return array
     * @throws \think\exception\DbException
     */
    public function setMyInvite($user_id, $superior_id)
    {
        $return = [
            'status' => false,
            'msg' => '填写邀请码失败',
            'data' => ''
        ];
        if($user_id == $superior_id)
        {
            $return['msg'] = '自己不能邀请自己';
            return $return;
        }

        $userInfo = $this->get($user_id);
        if($userInfo['pid'] && $userInfo['pid'] != 0)
        {
            $return['msg'] = '已有上级邀请，不能绑定其他的邀请';
            return $return;
        }

        $superior = $this->get($superior_id);
        if(!$superior)
        {
            $return['msg'] = '不存在这个邀请码';
            return $return;
        }

        $flag = $this->isInvited($user_id, $superior_id);
        if(!$flag)
        {
            $return['msg'] = '不允许填写下级的邀请码';
            return $return;
        }

        $data['pid'] = $superior_id;
        $where[] = ['id', 'eq', $user_id];
        $return['data'] = $this->save($data, $where);
        if($return['data'] !== false)
        {
            $return['status'] = true;
            $return['msg'] = '填写邀请码成功';
        }

        return $return;
    }


    /**
     * 是否是可以关联的
     * @param $user_id
     * @param $superior_id
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function isInvited($user_id, $superior_id)
    {
        $result = true;
        $return = true;
        while ($result) {
            unset($where);
            $where[] = ['id', 'eq', $superior_id];
            $superior = $this->field('pid')->where($where)->find();
            if($superior['pid'] == $user_id)
            {
                //不能
                $result = false;
                $return = false;
            }else if($superior['pid'] == 0){
                //能
                $result = false;
                $return = true;
            }
            $superior_id = $superior['pid'];
        }
        return $return;
    }


    /**
     * 获取用户分享码
     * @param $user_id
     * @return float|int|string
     */
    public function getShareCodeByUserId($user_id)
    {
        $code = ($user_id+1234)*3;
        return $code;
    }


    /**
     * 获取用户ID
     * @param $code
     * @return float|int
     */
    public function getUserIdByShareCode($code)
    {
        $user_id = ($code/3)-1234;
        return $user_id;
    }


    /**
     * （废弃，请用smsLogin方法）绑定用户
     * @param array $data 新建用户的数据数组
     * @param int $loginType 登陆类型，1网页登陆，存session，2接口登陆，返回token
     * @return array|null|\PDOStatement|string|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
//    public function toBindAdd($data, $loginType=1)
//    {
//        //判断手机号是否存在
//        $user_id = $this->getUserIdByMobile($data['mobile']);
//        if(!$user_id){
//           return $this->toAdd($data, $loginType);
//        }
//        $result = array(
//            'status' => false,
//            'data' => '',
//            'msg' => ''
//        );
//
//        //校验数据
//        $validate = new Validate($this->rule, $this->msg);
//        if(!$validate->check($data)){
//            $result['msg'] = $validate->getError();
//            return $result;
//        }
//
//        //校验短信验证码
//        $smsModel = new Sms();
//        if(!$smsModel->check($data['mobile'], $data['code'], 'reg')){
//            $result['msg'] = '短信验证码错误';
//            return $result;
//        }
//
//        Db::startTrans();//增加事物
//        try {
//            //更新数据
//            $userWxModel = new UserWx();
//            $userWxModel->update(['user_id' =>$user_id, 'mobile' => $data['mobile']], ['id' => $data['authorId']]);
//            Db::commit();
//        }catch (\Exception $e) {
//            Db::rollback();
//            $result['msg'] = $e->getMessage();
//            return $result;
//        }
//        $where[] = ['id', 'eq', $user_id];
//        $result = $this->where($where)->find();
//        return $this->setSession($result ,$loginType);
//    }


    /**
     * 修改邀请人
     * @param $id
     * @param $mobile
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function editInvite($id, $mobile)
    {
        $return = [
            'status' => false,
            'msg' => '操作失败',
            'data' => ''
        ];

        $where[] = ['mobile', 'eq', $mobile];
        $inviteInfo = $this->where($where)->find();
        if(!$inviteInfo)
        {
            $return['msg'] = '没有这个手机号注册的用户';
            return $return;
        }
        if($id == $inviteInfo['id'])
        {
            $return['msg'] = '自己不能邀请自己';
            return $return;
        }

        $isInvited = $this->isInvited($id, $inviteInfo['id']);
        if(!$isInvited)
        {
            $return['msg'] = '不能关联这个邀请人，因为他是你的下级或者下下级';
            return $return;
        }
        $return['status'] = true;
        $return['msg'] = '操作成功';
        $return['data'] = $inviteInfo['id'];
        return $return;
    }


    /**
     * 后台添加用户
     * @param $data
     * @return array
     */
    public function manageAdd($data)
    {
        $return = [
            'status' => false,
            'msg'    => '添加失败',
            'data'   => ''
        ];

        if (!isset($data['mobile']) || $data['mobile'] == '') {
            $return['msg'] = '手机号必填';
            return $return;
        }
        if (!isMobile($data['mobile'])) {
            $return['msg'] = '请输入正确的手机号';
            return $return;
        }
        $flag = $this->checkUserByMobile($data['mobile']);
        if ($flag) {
            $return['msg'] = '手机号已经存在，请更换手机号重新添加';
            return $return;
        }
        if ($data['password'] == '' || strlen($data['password']) < 6 || strlen($data['password']) > 20) {
            $return['msg'] = '密码必填，6-20位';
            return $return;
        }
        //密码效验
        if ($data['password'] !== $data['repassword']) {
            $return['msg'] = '两次输入的密码不一致，请重新输入。';
            return $return;
        }


        $time                = time();
        $newData['username'] = null;
        $newData['mobile']   = $data['mobile'];
        $newData['password'] = $this->enPassword($data['password'], $time);
        $newData['sex']      = isset($data['sex']) ? $data['sex'] : 3;
        $newData['birthday'] = $data['birthday'] ? $data['birthday'] : null;
        $newData['avatar']   = isset($data['avatar']) ? $data['avatar'] : '';
        $newData['nickname'] = $data['nickname'];
        $newData['balance']  = 0;
        $newData['point']    = 0;
        $newData['ctime']    = $time;
        $newData['utime']    = $time;
        $newData['status']   = isset($data['status']) ? $data['status'] : self::STATUS_NORMAL;
        $newData['pid']      = 0;
        $newData['grade']      = $data['grade'];

        $result         = $this->save($newData);
        $return['data'] = $this->id;

        if ($result) {
            if (session('manage.id')) {
                $userLogModel = new UserLog();
                $userLogModel->setLog(session('manage.id'), $userLogModel::USER_REG);
            }
            $return['status'] = true;
            $return['msg']    = '添加成功';

        }

        return $return;
    }


    /**
     * 后台修改用户
     * @param $data
     * @return array
     */
    public function manageEdit($data)
    {
        $return = [
            'status' => false,
            'msg'    => '修改失败',
            'data'   => ''
        ];

        //校验数据
        $validate = new Validate($this->rule, $this->msg);
        if (!$validate->check($data)) {
            $return['msg'] = $validate->getError();
            return $return;
        }
        if(isset($data['p_mobile'])&& $data['p_mobile']!=''){
            $p = $this->editInvite($data['id'],$data['p_mobile']);
            if($p['status'] === false){
                $return['msg'] = $p['msg'];
                return $return;
            }else{
                $data['pid'] = $p['data'];
            }
        }
        if($data['p_mobile']==''){
            $data['pid'] = '';
        }
        //输入密码时修改密码
        if(isset($data['password'])&&$data['password']!=''){
            if (strlen($data['password']) < 6 || strlen($data['password']) > 20) {
                $return['msg'] = '密码长度为6-20位';
                return $return;
            }
            //密码效验
            if ($data['password'] !== $data['repassword']) {
                $return['msg'] = '两次输入的密码不一致，请重新输入。';
                return $return;
            }
            $userInfo = $this->get($data['id']);
            $newData['password'] = $this->enPassword($data['password'], $userInfo['ctime']);
        }

        $where[]             = ['id', 'eq', $data['id']];
        $newData['nickname'] = $data['nickname'];
        $newData['sex']      = $data['sex'] ? $data['sex'] : 3;
        $newData['birthday'] = $data['birthday'] ? $data['birthday'] : null;
        $newData['avatar']   = $data['avatar'];
        $newData['status']   = $data['status'];
        $newData['pid']   = $data['pid'];
        $newData['grade']      = $data['grade'];
        $result         = $this->save($newData, $where);
        $return['data'] = $result;

        if ($result) {
            $return['status'] = true;
            $return['msg']    = '修改成功';
        }

        return $return;
    }

    /**
     * 根据用户手机号获取用户id
     */
    public function checkUserByMobile($mobile)
    {
        $where[] = ['mobile', 'eq', $mobile];
        $where[] = ['status', 'eq', self::STATUS_NORMAL];
        $res     = $this->field('id')->where($where)->find();
        return $res;
    }


    /**
     * 设置csv header
     * @return array
     */
    public function csvHeader()
    {
        return [
            [
                'id' => 'mobile',
                'desc' => '手机号',
            ],
            [
                'id' => 'sex',
                'desc' => '性别',
            ],
            [
                'id' => 'birthday',
                'desc' => '生日',
            ],
            [
                'id' => 'avatar',
                'desc' => '头像',
            ],
            [
                'id' => 'nickname',
                'desc' => '昵称',
            ],
            [
                'id' => 'balance',
                'desc' => '余额',
            ],
            [
                'id' => 'point',
                'desc' => '积分',
                // 'modify'=>'getBool'
            ],
            [
                'id' => 'status',
                'desc' => '状态',
                //'modify'=>'getMarketable',
            ],
//            [
//                'id' => 'pid_name',
//                'desc' => '邀请人',
//            ],
//            [
//                'id' => 'ctime',
//                'desc' => '创建时间',
//            ],
            [
                'id' => 'username',
                'desc' => '用户名',
            ],
//
        ];
    }


    /**
     * 返回layui的table所需要的格式
     * @author sin
     * @param $post
     * @return mixed
     */
    public function tableData($post,$isPage=true)
    {
        if(isset($post['limit'])){
            $limit = $post['limit'];
        }else{
            $limit = config('paginate.list_rows');
        }
        $tableWhere = $this->tableWhere($post);
        $list = [];
        if($isPage){
            $list = $this->with('grade')->field($tableWhere['field'])->where($tableWhere['where'])->order($tableWhere['order'])->paginate($limit);
            $data = $this->tableFormat($list->getCollection());         //返回的数据格式化，并渲染成table所需要的最终的显示数据类型
            $re['count'] = $list->total();
        }else{
            $list = $this->field($tableWhere['field'])->where($tableWhere['where'])->order($tableWhere['order'])->select();
            if(!$list->isEmpty()){
                $data = $this->tableFormat($list->toArray());
            }
            $re['count'] = count($list);
        }
        $re['code'] = 0;
        $re['msg'] = '';

        $re['data'] = $data;

        return $re;
    }


    /**
     * 获取csv数据
     * @param $post
     * @return array
     */
    public function getCsvData($post)
    {
        $result = [
            'status' => false,
            'data' => [],
            'msg' => '无可导出商品'
        ];
        $header = $this->csvHeader();
        $userData = $this->tableData($post, false);


        if ($userData['count'] > 0) {
            $tempBody = $userData['data'];
            $body = [];
            $i = 0;

            foreach ($tempBody as $key => $val) {
                $i++;
                foreach ($header as $hk => $hv) {
                    if (isset($val[$hv['id']]) && $val[$hv['id']] && isset($hv['modify'])) {
                        if (function_exists($hv['modify'])) {
                            $body[$i][$hk] = $hv['modify']($val[$hv['id']]);
                        }
                    } elseif (isset($val[$hv['id']]) &&!empty($val[$hv['id']])) {
                        $body[$i][$hk] = $val[$hv['id']];
                    } else {
                        $body[$i][$hk] = '';
                    }
                }
            }
            $result['status'] = true;
            $result['msg'] = '导出成功';
            $result['data'] = $body;
            return $result;
        } else {
            //失败，导出失败
            return $result;
        }
    }

    public function doAdd($data = [])
    {
        $result=$this->insert($data);
        if($result)
        {
            return $this->getLastInsID();
        }
        return $result;
    }
    public function grade(){
        return $this->hasOne("UserGrade",'id','grade')->bind(['grade_name'	=> 'name']);
    }

}
