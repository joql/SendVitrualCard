<?php

/*
 * 功能：会员中心－个人中心
 * Author:资料空白
 * Date:20180509
 */

class CenterController extends PcBasicController
{
    private $m_user;
    private $m_order;
    private $m_payment;
    private $m_recharge;

	public function init()
    {
        parent::init();
		$this->m_user = $this->load('user');
		$this->m_order = $this->load('order');
        $this->m_payment = $this->load('payment');
        $this->m_recharge = $this->load('recharge');
    }

    public function indexAction()
    {
        if ($this->login==FALSE AND !$this->userid) {
            $this->redirect("/member/login");
            return FALSE;
        }
		$data = array();
		$uinfo = $this->m_user->SelectByID('nickname,email,qq,tag,createtime,avator,money',$this->userid);
		$data['uinfo'] = $this->uinfo = array_merge($this->uinfo, $uinfo);
		$data['title'] = "我的资料";

        //获取支付方式
        $payments = $this->m_payment->getConfig();
        $data['payments']=$payments;
        $this->getView()->assign($data);
    }

	public function profilesajaxAction()
	{
		$nickname = $this->getPost('nickname',false);
		$qq = $this->getPost('qq',false);
		$tag = $this->getPost('tag',false);
		$csrf_token = $this->getPost('csrf_token', false);
		
		$data = array();
		
        if ($this->login==FALSE AND !$this->userid) {
            $data = array('code' => 1000, 'msg' => '请登录');
			Helper::response($data);
        }
		
		if($nickname AND $csrf_token){
			if ($this->VerifyCsrfToken($csrf_token)) {
				$nickname_string = new \Safe\MyString($nickname);
				$nickname = $nickname_string->trimall()->getValue();
				
				$qq_string = new \Safe\MyString($qq);
				$qq = $qq_string->trimall()->getValue();
				
				$this->m_user->UpdateByID(array('nickname'=>$nickname,'qq'=>$qq,'tag'=>$tag),$this->userid);
				$data = array('code' => 1, 'msg' => '更新成功');
			} else {
                $data = array('code' => 1001, 'msg' => '页面超时，请刷新页面后重试!');
            }
		}else{
			$data = array('code' => 1000, 'msg' => '丢失参数');
		}
		Helper::response($data);
	}


	public function getRechargeListAction(){
        $data = array();
        $csrf_token = $this->getPost('csrf_token', false);
        if (!$this->VerifyCsrfToken($csrf_token)) {
            $data = array('code' => 1002, 'msg' => '页面超时，请刷新页面后重试!');
            Helper::response($data);
        }
        if ($this->login==FALSE AND !$this->userid) {
            $data = array('code' => 1000, 'msg' => '请登录');
            Helper::response($data);
        }
        $page = $this->getPost('page',false);
        $start_time = strtotime($this->getPost('starttime',false));
        $end_time = strtotime($this->getPost('endtime',false));

        $list = (array)$this->m_recharge
            ->Field('id,money as total,orderid,status,addtime')
            ->Where(array('userid'=> $this->userid))
            ->Where(empty($start_time) ? '' : 'addtime > '.$start_time)
            ->Where(empty($end_time) ? '' : 'addtime < '.$end_time)
            ->Order('id desc')
            ->Limit(($page-1)*15,15)
            ->Select();
        if(empty($list)){
            $data = array('code' => 1001, 'msg' => '没有查到记录');
        }else{
            foreach ($list as &$v){
                $v['addtime'] = date('Y-m-d H:i:s',$v['addtime']);
                $v['status'] = $this->getOrderStatus($v['status']);
            }
            $data = array('code' => 1, 'msg' => '查询成功','data'=>$list,'page_num'=>15);
        }
        Helper::response($data);
    }

    /**
     * use for:获取消费记录列表
     * auth: Joql
     * date:2019-01-24 10:24
     */
    public function getOutListAction(){
        $data = array();
        $csrf_token = $this->getPost('csrf_token', false);
        if (!$this->VerifyCsrfToken($csrf_token)) {
            $data = array('code' => 1002, 'msg' => '页面超时，请刷新页面后重试!');
            Helper::response($data);
        }
        if ($this->login==FALSE AND !$this->userid) {
            $data = array('code' => 1000, 'msg' => '请登录');
            Helper::response($data);
        }
        $page = $this->getPost('page',false);
        $start_time = strtotime($this->getPost('starttime',false));
        $end_time = strtotime($this->getPost('endtime',false));

        $list = (array)$this->m_order
            ->Field('id,money as total,number as num,productname as name,orderid as oid,addtime as time')
            //->Where(array('userid'=>$this->userid))
            ->Where(array('userid'=>$this->userid))
            ->Where(empty($start_time) ? '' : 'addtime > '.$start_time)
            ->Where(empty($end_time) ? '' : 'addtime < '.$end_time)
            ->Order('id desc')
            ->Limit(($page-1)*15,15)
            ->Select();
        if(empty($list)){
            $data = array('code' => 1001, 'msg' => '没有查到记录');
        }else{
            foreach ($list as &$v){
                $v['time'] = date('Y-m-d H:i:s',$v['time']);
            }
            $data = array('code' => 1, 'msg' => '查询成功','data'=>$list,'page_num'=>15);
        }
        Helper::response($data);
    }

    /**
     * use for:更新头像
     * auth: Joql
     * @return bool
     * date:2019-01-22 15:00
     */
	public function uploadAvatorAction(){

        if ($this->login==FALSE AND !$this->userid) {
            $this->redirect("/member/login");
            return FALSE;
        }
        $data = array();
        \Yaf\Loader::import(LIB_PATH.'/Upload.php');
        $upload = new \Dj\Upload('avatar_img',[
            'ext'=>'jpg,jpeg,png,gif',
            'size'=>5242880
        ]);
        $filelist = $upload->save(UPLOAD_PATH.'/img');
        if(is_array($filelist)){
            # 返回数组，文件就上传成功了
            //print_r($filelist);
            $r = $this->m_user->UpdateByID(array('avator'=>$filelist['uri']),$this->userid);
            if(empty($r)){
                $data = array('code' => 1000, 'msg' => '保存失败');
            }else{
                $data = array('code' => 1, 'msg' => '保存成功','data'=>$filelist['uri']);
            }
            Helper::response($data);
        }else{
            # 如果返回负整数(int)就是发生错误了
            $error_msg = [
                -1=>'上传失败',
                -2=>'文件存储路径不合法',
                -3=>'上传非法格式文件,请上传以下格式jpg,jpeg,png,gif',
                -4=>'文件大小不合符规定',
                -5=>'token验证错误'
            ];
            $data = array('code' => 1000, 'msg' => $error_msg[$filelist]);
            Helper::response($data);
        }
    }

    /**
     * use for:保存充值订单
     * auth: Joql
     * date:2019-01-23 21:44
     */
    public function saveRechargeOrderAction(){
        //下订单
        if ($this->login==FALSE AND !$this->userid) {
            $data = array('code' => 1002, 'msg' => '请登录');
            Helper::response($data);
        }
        $money = $this->getPost('money');
        $csrf_token = $this->getPost('csrf_token', false);
        $paymethod = $this->getPost('paymethod');


        if(is_numeric($money) AND $money>0 AND $csrf_token AND $paymethod){
            if ($this->VerifyCsrfToken($csrf_token)) {
                $payments = $this->m_payment->getConfig();
                if(isset($payments[$paymethod]) AND !empty($payments[$paymethod])){
                    $payconfig = $payments[$paymethod];
                    if($payconfig['active']=0){
                        $data = array('code' => 1002, 'msg' => '支付渠道已关闭');
                        Helper::response($data);
                    }
                }else{
                    $data = array('code' => 1001, 'msg' => '支付渠道异常');
                    Helper::response($data);
                }

                $myip = getClientIP();

                //生成orderid
                mt_srand();
                $postfix = mt_rand(1000, 9999).substr(md5(mt_rand(1000, 9999)),3,6);
                $prefix = isset($this->config['orderprefix'])?$this->config['orderprefix']:'zlkb';
                $orderid = $prefix. date('Y') . date('m') . date('d') . date('H') . date('i') . date('s') .$postfix ;

                //开始下单，入库
                $m=array(
                    'orderid'=>$orderid,
                    'userid'=>$this->userid,
                    'money'=>$money,
                    'ip'=>$myip,
                    'status'=>0,
                    'paymethod'=>$paymethod,
                    'addtime'=>time(),
                );
                $id=$this->m_recharge->Insert($m);
                if($id>0){
                    $r = $this->payRechargeOrder($id);
                    if($r['code'] === 1){
                        $oid = base64_encode($id);
                        $data = array('code' => 1, 'msg' => '订单创建成功','data'=>array(
                            'type'=>$paymethod,'oid'=>$oid,
                            'uid'=>$this->uinfo['id'],
                            'pay' => $r['pay'],
                        )
                        );
                    }else{
                        $data = array('code' => 1004, 'msg' => '订单创建异常');
                    }

                }else{
                    $data = array('code' => 1003, 'msg' => '订单异常');
                }
            } else {
                $data = array('code' => 1001, 'msg' => '页面超时，请刷新页面后重试!');
            }
        }else{
            $data = array('code' => 1000, 'msg' => '有内容没有输入完整，在确认一遍？');
        }
        Helper::response($data);
    }

    /**
     * use for:获取支付二维码
     * auth: Joql
     * @param $oid
     * @return array|bool
     * date:2019-01-23 22:46
     */
    private function payRechargeOrder($oid){
        if($oid){
            if(is_numeric($oid) AND $oid>0 ){
                $recharge = $this->m_recharge->Where(array('id'=>$oid,'isdelete'=>0))->SelectOne();
                if(!empty($recharge)){
                    //获取支付方式
                    $payments = $this->m_payment->getConfig();
                    $data['order']=$recharge;
                    $data['payments']=$payments;
                    $data['code']=1;
                    try{
                        $orderid = $recharge['orderid'];

                        $payclass = "\\Pay\\".$recharge['paymethod']."\\".$recharge['paymethod'];
                        $PAY = new $payclass();
                        $params =array('pid'=>'999999','orderid'=>$orderid,'money'=>$recharge['money'],'productname'=>'余额充值','weburl'=>$this->config['weburl'],'qrserver'=>$this->config['qrserver']);
                        $pay_url = $PAY->pay($payments[$recharge['paymethod']],$params);
                        if($pay_url=='' || !isset($pay_url['code']) || $pay_url['code']!=1){
                            return FALSE;
                        }
                        //var_dump($pay_url); die;
                        $data['pay']=$pay_url['data'];
                    } catch (\Exception $e) {
                        $data = array('code' => 1005, 'msg' => $e->getMessage());
                    }
                }else{
                    return FALSE;
                }
            }else{
                return FALSE;
            }
        }else{
            return false;
        }
        return $data;
    }

    public function verifyajaxAction(){
        $rid    = (int)base64_decode($this->getPost('rid'));
        $csrf_token = $this->getPost('csrf_token', false);
        if($rid AND is_numeric($rid) AND $rid>0 AND $csrf_token){
            if ($this->VerifyCsrfToken($csrf_token)) {
                $recharge = $this->m_recharge->Where(array('id'=>$rid,'isdelete'=>0))->SelectOne();
                if(empty($recharge)){
                    $data=array('code'=>1002,'msg'=>'没有订单');
                }else{
                    if($recharge['status']<1){
                        $data = array('code' => 1003, 'msg' => '未支付,请稍等片刻');
                    }else{
                        $data = array('code' => 1, 'msg' => '支付成功','data'=>$recharge);
                    }
                }
            } else {
                $data = array('code' => 1001, 'msg' => '页面超时，请刷新页面后重试!');
            }
        }else{
            $data = array('code' => 1000, 'msg' => '丢失参数');
        }
        Helper::response($data);
    }

    private function getOrderStatus($status){
        switch ($status){
            case '0':
                return '待支付';
            case '2':
                return '支付成功';
            default:
                return '支付异常';
        }
    }

}