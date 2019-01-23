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
	
	public function init()
    {
        parent::init();
		$this->m_user = $this->load('user');
		$this->m_order = $this->load('order');
        $this->m_payment = $this->load('payment');
    }

    public function indexAction()
    {
        if ($this->login==FALSE AND !$this->userid) {
            $this->redirect("/member/login");
            return FALSE;
        }
		$data = array();
		$uinfo = $this->m_user->SelectByID('nickname,email,qq,tag,createtime,avator',$this->userid);
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
            ->Where(array('userid'=>'0')) //TODO 测试
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
            $data = array('code' => 1, 'msg' => '查询成功','data'=>$list);
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

}