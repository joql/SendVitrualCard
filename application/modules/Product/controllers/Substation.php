<?php
/*
 * 功能：会员中心－个人中心
 * Author:资料空白
 * Date:20180509
 */

class SubstationController extends PcBasicController
{
    private $m_substation;
    private $m_substation_url;
    private $m_substation_type;
    private $m_admin_user;

    public function init()
    {
        parent::init();
        $this->m_substation = $this->load('substation');
        $this->m_substation_type = $this->load('substation_type');
        $this->m_substation_url = $this->load('substation_url');
        $this->m_admin_user = $this->load('admin_user');
    }

    public function indexAction()
    {
        $data = array();
        $data['type_list'] = $this->m_substation_type->Select();
        $data['url_list'] = $this->m_substation_url->Where(array('state'=>1))->Select();
        $this->getView()->assign($data);
    }

    public function ajaxAction()
    {
        $data = array();
        $data['type_id'] = $this->getPost('type',false);
        $data['url'] = $this->getPost('url',false);
        $data['url_postfix'] = $this->getPost('url_postfix',false);
        $data['admin_name'] = $this->getPost('user',false);
        $data['admin_pwd'] = md5($this->getPost('pwd',false));
        $data['admin_qq'] = $this->getPost('qq',false);
        $data['webname'] = $this->getPost('webname',false);
        $data['remaining_sum'] = 0;
        $data['expire_time'] = time()+60*60*24*30;
        $data['state'] = 3;
        $data['create_time'] = time();
        //admin用户表信息
        $user['email'] = $data['admin_name'];
        $user['secret'] = md5(time());
        $user['password'] = password($this->getPost('pwd',false), $user['secret']);
        if(!preg_match('/^[0-9a-zA-Z]+$/',$data['url'])){
            $data = array('code' => 1001, 'msg' => '前缀仅限字母数字');
            Helper::response($data);
        }
        if(!isNumber($data['admin_qq'])){
            $data = array('code' => 1002, 'msg' => '绑定qq格式错误');
            Helper::response($data);
        }
        if(!isEmail($data['admin_name'])){
            $data = array('code' => 1003, 'msg' => '管理账号只能为邮箱');
            Helper::response($data);
        }
        $type = $this->m_substation_type->Where(array('id'=>$data['type_id']))->SelectOne();
        if(!isset($type['name'])){
            $data = array('code' => 1004, 'msg' => '版本类型不存在');
            Helper::response($data);
        }
        $url = $this->m_substation_url->Where(array('id'=>$data['url_postfix'],'state'=>1))->SelectOne();
        if(!isset($url['url'])){
            $data = array('code' => 1004, 'msg' => '域名不存在');
            Helper::response($data);
        }
        $data['bind_url'] = $data['url'].'.'.$url['url'];
        unset($data['url']);
        unset($data['url_postfix']);
        $exist_url = $this->m_substation
            ->Field('id')
            ->Where(array('bind_url'=>$data['bind_url']))
            ->SelectOne();
        if(!empty($exist_url['id'])){
            $data = array('code' => 1005, 'msg' => '域名已存在');
            Helper::response($data);
        }
        $exist_name = $this->m_substation
            ->Field('id')
            ->Where(array('admin_name'=>$data['admin_name']))
            ->SelectOne();
        if(!empty($exist_name['id'])){
            $data = array('code' => 1003, 'msg' => '管理账号已存在');
            Helper::response($data);
        }
        unset($data['webname']);
        
        $r = $this->m_substation->Insert($data);
        if($r){

            $user['substation_id']=$r;
            $this->m_admin_user->Insert($user);

            $data = array('code' => 1, 'msg' => '申请成功，等待审核');
        }else{
            $data = array('code' => 1003, 'msg' => '申请失败');
        }
        Helper::response($data);

    }

}