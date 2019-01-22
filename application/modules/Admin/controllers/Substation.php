<?php

/*
 * 功能：后台中心－订单
 * Author:资料空白
 * Date:20180509
 */

class SubstationController extends AdminBasicController
{
	private $m_order;
	private $m_products;
	private $m_email_queue;

    private $m_substation;
    private $m_substation_type;
    private $m_substation_url;
    private $m_config;
    private $m_admin_user;
    public function init()
    {
        parent::init();
		$this->m_order = $this->load('order');
		$this->m_products = $this->load('products');
		$this->m_email_queue = $this->load('email_queue');

        $this->m_substation = $this->load('substation');
        $this->m_substation_type = $this->load('substation_type');
        $this->m_substation_url = $this->load('substation_url');
        $this->m_config = $this->load('config');
        $this->m_admin_user = $this->load('admin_user');
    }

    public function indexAction()
    {
        if ($this->AdminUser==FALSE AND empty($this->AdminUser)) {
            $this->redirect('/'.ADMIN_DIR."/login");
            return FALSE;
        }
		$data = array();
		$this->getView()->assign($data);
    }

	//ajax
	public function ajaxAction()
	{
        if ($this->AdminUser==FALSE AND empty($this->AdminUser)) {
            $data = array('code' => 1000, 'msg' => '请登录');
			Helper::response($data);
        }
		
		$where1 = array();
		
//		$orderid = $this->get('orderid');
//		$email = $this->get('email',false);
//		$status = $this->get('status');
//		$pid = $this->get('pid');
//
//        //查询条件
//        $get_params = [
//            'orderid' => $orderid,
//            'email' => $email,
//			'status' => $status,
//			'pid' => $pid,
//        ];
//        $where = $this->conditionSQL($get_params);
		
		$page = $this->get('page');
		$page = is_numeric($page) ? $page : 1;
		
		$limit = $this->get('limit');
		$limit = is_numeric($limit) ? $limit : 10;
		
		$total=$this->m_substation->Total();
		
        if ($total > 0) {
            if ($page > 0 && $page < (ceil($total / $limit) + 1)) {
                $pagenum = ($page - 1) * $limit;
            } else {
                $pagenum = 0;
            }
			
            $limits = "{$pagenum},{$limit}";
			$field = array('id','type_id','admin_name','admin_qq',
                'bind_url','remaining_sum','create_time','expire_time','state');
			$items=$this->m_order->Field($field)->Limit($limits)->Order(array('id'=>'DESC'))->Select();

            $sql = "SELECT s1.id,sp1.name as type_name,s1.admin_name,s1.admin_qq,s1.payment_account,s1.bind_url,
                        s1.remaining_sum,s1.create_time,s1.expire_time,s1.state FROM 
                        `t_substation` as s1 
                        left join 
                        `t_substation_type` as 
                        sp1 on sp1.id = s1.type_id  Order by s1.id desc 
                        LIMIT {$limits}";
            $items=(array)$this->m_products->Query($sql);
			foreach ($items as $k=>&$v){
                $v['remaining_sum'] = $v['remaining_sum']/100;
                $v['create_time'] = date('Y-m-d', $v['create_time']);
                $v['expire_time'] = date('Y-m-d', $v['expire_time']);
                $v['state'] = $this->getState($v['state']);
            }
            if (empty($items)) {
                $data = array('code'=>1002,'count'=>0,'data'=>array(),'msg'=>'无数据');
            } else {
                $data = array('code'=>0,'count'=>$total,'data'=>$items,'msg'=>'有数据');
            }
        } else {
            $data = array('code'=>1001,'count'=>0,'data'=>array(),'msg'=>'无数据');
        }
		Helper::response($data);
	}

    public function addAction()
    {
        if ($this->AdminUser==FALSE AND empty($this->AdminUser)) {
            $this->redirect('/'.ADMIN_DIR."/login");
            return FALSE;
        }
        $data = array();
        $type_list = $this->m_substation_type->Select();
        $data['type_list'] = $type_list;
        $url_list = $this->m_substation_url->Where(array('state'=>1))
            ->Select();
        $data['url_list'] = $url_list;
        $this->getView()->assign($data);
    }
    public function addajaxAction()
    {
        if ($this->AdminUser==FALSE AND empty($this->AdminUser)) {
            $this->redirect('/'.ADMIN_DIR."/login");
            return FALSE;
        }

        $data['type_id'] = $this->getPost('type_id', false);
        $data['payment_account'] = $this->getPost('payment_account', false);
        $data['admin_name'] = $this->getPost('admin_name', false);
        $data['admin_pwd'] = md5($this->getPost('admin_pwd', false));
        $postfix =$this->getPost('url_postfix', false);
        if($postfix !== 'top'){
            $data['bind_url'] = $this->getPost('bind_url', false)
                .'.'.$postfix;
        }else{
            $data['bind_url'] = $this->getPost('bind_url', false);
        }
        $data['remaining_sum'] = $this->getPost('remaining_sum',
                false)*100;
        $data['admin_qq'] = $this->getPost('admin_qq', false);
        $data['expire_time'] = strtotime($this->getPost('expire_time', false));
        $data['state'] = 3;
        $data['create_time'] = time();

        //admin用户表信息
        $user['email'] = $data['admin_name'];
        $user['secret'] = md5(time());
        $user['password'] = password($this->getPost('admin_pwd',
            false), $user['secret']);


        $exist_url = $this->m_substation->Field('id')->Where(array('bind_url'=>$data['bind_url']))->SelectOne();
        if(!empty($exist_url['id'])){
            $data = array('code' => 1003, 'msg' => '域名已存在');
            Helper::response($data);
        }
        $exist_name = $this->m_admin_user->Field('id')->Where(array
        ('email'=>$data['admin_name']))->SelectOne();
        if(!empty($exist_name['id'])){
            $data = array('code' => 1003, 'msg' => '邮箱已存在');
            Helper::response($data);
        }
        $r = $this->m_substation->Insert($data);
        if($r){

            $user['substation_id']=$r;
            $this->m_admin_user->Insert($user);

            $data = array('code' => 1, 'msg' => '新增成功');
        }else{
            $data = array('code' => 1003, 'msg' => '新增失败');
        }
        Helper::response($data);
    }

    public function editAction()
    {
        if ($this->AdminUser==FALSE AND empty($this->AdminUser)) {
            $this->redirect('/'.ADMIN_DIR."/login");
            return FALSE;
        }
        $id = $this->get('id');
        if($id AND $id>0){
            $data = array();
            $substation=$this->m_substation->SelectByID('',$id);
            $substation['expire_time'] = date('Y-m-d', $substation['expire_time']);
            $data['substation'] = $substation;
            $type_list = $this->m_substation_type->Select();
            $data['type_list'] = $type_list;
            $url_list = $this->m_substation_url->Where(array('state'=>1))
                ->Select();
            $data['url_list'] = $url_list;
            $this->getView()->assign($data);
        }else{
            $this->redirect('/'.ADMIN_DIR."/article");
            return FALSE;
        }
    }
    public function editajaxAction()
    {
        if ($this->AdminUser==FALSE AND empty($this->AdminUser)) {
            $this->redirect('/'.ADMIN_DIR."/login");
            return FALSE;
        }
        $id = $this->get('id');
        $data['type_id'] = $this->getPost('type_id', false);
        $data['admin_qq'] = $this->getPost('admin_qq', false);
        $data['expire_time'] = strtotime($this->getPost('expire_time', false));

        $r = $this->m_substation->UpdateById($data,$id);
        if($r){
            $data = array('code' => 1, 'msg' => '修改成功');
        }else{
            $data = array('code' => 1003, 'msg' => '修改失败');
        }
        Helper::response($data);
    }
	public function viewAction()
    {
        if ($this->AdminUser==FALSE AND empty($this->AdminUser)) {
            $this->redirect('/'.ADMIN_DIR."/login");
            return FALSE;
        }
		$id = $this->get('id');
		if($id AND $id>0){
			$data = array();
			$order = $this->m_order->SelectByID('',$id);
			if(is_array($order) AND !empty($order)){
				$data['order'] = $order;
				$this->getView()->assign($data);
			}else{
				$this->redirect('/'.ADMIN_DIR."/order");
				return FALSE;
			}
		}else{
            $this->redirect('/'.ADMIN_DIR."/order");
            return FALSE;
		}
    }
	
    public function deleteAction()
    {
        if ($this->AdminUser==FALSE AND empty($this->AdminUser)) {
            $data = array('code' => 1000, 'msg' => '请登录');
			Helper::response($data);
        }
		$id = $this->get('id',false);
		$csrf_token = $this->getPost('csrf_token', false);
        if ($csrf_token) {
			if ($this->VerifyCsrfToken($csrf_token)) {
				if($id AND is_numeric($id) AND $id>0){
					$delete = $this->m_substation->Where(array('id'=>$id))->Delete();
					if($delete){
                        $this->m_admin_user
                            ->Where("substation_id !='master'")
                            ->Where(array('substation_id'=>$id))
                            ->Delete();
						$data = array('code' => 1, 'msg' => '删除成功', 'data' => '');
					}else{
						$data = array('code' => 1003, 'msg' => '删除失败', 'data' => '');
					}
				}else{
                    $data = array('code' => 1001, 'msg' => '缺少字段', 'data' => '');
                }
			} else {
                $data = array('code' => 1002, 'msg' => '页面超时，请刷新页面后重试!');
            }
        } else {
            $data = array('code' => 1001, 'msg' => '缺少字段', 'data' => '');
        }
       Helper::response($data);
    }
    public function pwdInitAction()
    {
        if ($this->AdminUser==FALSE AND empty($this->AdminUser)) {
            $data = array('code' => 1000, 'msg' => '请登录');
			Helper::response($data);
        }
		$id = $this->get('id',false);
		$csrf_token = $this->getPost('csrf_token', false);
        if ($csrf_token) {
			if ($this->VerifyCsrfToken($csrf_token)) {
				if($id AND is_numeric($id) AND $id>0){
				    $u = (array)$this->m_substation->Field('admin_name')->Where(array('id'=>$id))->SelectOne();
					$secret = md5(time());
					$password = password('123456', $secret);
					$r = $this->m_admin_user->Where('email=\''.$u['admin_name'].'\'')->UpdateOne(array(
					    'secret' => $secret,
					    'password' => $password,

                    ));
					if($r){
						$data = array('code' => 1, 'msg' => '重置成功', 'data' => '');
					}else{
						$data = array('code' => 1003, 'msg' => '重置失败', 'data' => '');
					}
				}else{
                    $data = array('code' => 1001, 'msg' => '缺少字段', 'data' => '');
                }
			} else {
                $data = array('code' => 1002, 'msg' => '页面超时，请刷新页面后重试!');
            }
        } else {
            $data = array('code' => 1001, 'msg' => '缺少字段', 'data' => '');
        }
       Helper::response($data);
    }

    public function agreeAction()
    {
        if ($this->AdminUser==FALSE AND empty($this->AdminUser)) {
            $data = array('code' => 1000, 'msg' => '请登录');
            Helper::response($data);
        }
        $id = $this->get('id');
        $csrf_token = $this->getPost('csrf_token', false);
        if (!$csrf_token || !$this->VerifyCsrfToken($csrf_token)) {
            $data = array('code' => 1002, 'msg' => '页面超时，请刷新页面后重试!');
            Helper::response($data);
        }
        $r = $this->m_substation->UpdateByID(array('state'=>1), $id);
        if ($r) {
            $confs = $this->m_config->Field(array('catid','name','value','tag','lock','updatetime'))
                ->Where
            ("substation_id='master'")->Select();
            foreach ((array)$confs as $k=>$v){
                $confs[$k]['substation_id'] = $id;
            }
            $this->m_config->MultiInsert($confs);
            $data = array('code'=>1,'msg'=>'更新成功');
        } else {
            $data = array('code'=>1002,'msg'=>'更新失败');
        }
        Helper::response($data);
    }

    public function blockAction()
    {
        if ($this->AdminUser==FALSE AND empty($this->AdminUser)) {
            $data = array('code' => 1000, 'msg' => '请登录');
            Helper::response($data);
        }
        $id = $this->get('id');

        $r = $this->m_substation->UpdateByID(array('state'=>2), $id);
        if ($r) {
            $data = array('code'=>1,'msg'=>'更新成功');
        } else {
            $data = array('code'=>1002,'msg'=>'更新失败');
        }
        Helper::response($data);
    }
	
    private function conditionSQL($param)
    {
        $condition = "1";
        if (isset($param['orderid']) AND empty($param['orderid']) === FALSE) {
            $condition .= " AND `orderid` LIKE '%{$param['orderid']}%'";
        }
        if (isset($param['email']) AND empty($param['email']) === FALSE) {
            $condition .= " AND `email` LIKE '%{$param['email']}%'";
        }
        if (isset($param['status']) AND $param['status']>-1 ) {
            $condition .= " AND `status` = {$param['status']}";
        }
        if (isset($param['pid']) AND empty($param['pid']) === FALSE AND $param['pid']>0 ) {
            $condition .= " AND `pid` = {$param['pid']}";
        }		
        return ltrim($condition, " AND ");
    }

    /**
     * use for:获取分站状态
     * auth: Joql
     * @param $state
     * @return string
     * date:2019-01-09 22:25
     */
    private  function getState($state){
        switch ($state){
            case '1' :
                return '已审核';
            case '2' :
                return '停用';
            case '3' :
                return '未审核';
        }
        return '停用';
    }
}