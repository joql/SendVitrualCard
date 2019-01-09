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
    public function init()
    {
        parent::init();
		$this->m_order = $this->load('order');
		$this->m_products = $this->load('products');
		$this->m_email_queue = $this->load('email_queue');

        $this->m_substation = $this->load('substation');
        $this->m_substation_type = $this->load('substation_type');
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

            $sql = "SELECT s1.id,sp1.name as type_name,s1.admin_name,s1.admin_qq,s1.bind_url,
                        s1.remaining_sum,s1.create_time,s1.expire_time,s1.state FROM 
                        `t_substation` as s1 
                        left join 
                        `t_substation_type` as 
                        sp1 on sp1.id = s1.type_id  Order by s1.id desc 
                        LIMIT {$limits}";
            $items=$this->m_products->Query($sql);
			foreach ((array)$items as &$v){
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
        $this->getView()->assign($data);
    }
    public function addajaxAction()
    {
        if ($this->AdminUser==FALSE AND empty($this->AdminUser)) {
            $this->redirect('/'.ADMIN_DIR."/login");
            return FALSE;
        }

        $data['type_id'] = $this->getPost('type_id', false);
        $data['admin_name'] = $this->getPost('admin_name', false);
        $data['admin_pwd'] = md5($this->getPost('admin_pwd', false));
        $data['bind_url'] = $this->getPost('bind_url', false);
        $data['remaining_sum'] = $this->getPost('remaining_sum',
                false)*100;
        $data['admin_qq'] = $this->getPost('admin_qq', false);
        $data['expire_time'] = strtotime($this->getPost('expire_time', false));
        $data['state'] = $this->getPost('state', false);
        $data['create_time'] = time();

        $exist = $this->m_substation->Field('id')->Where(array('bind_url'=>$data['bind_url']))->SelectOne();
        if(!empty($exist[0]['id'])){
            $data = array('code' => 1003, 'msg' => '新增失败');
            Helper::response($data);
        }
        $r = $this->m_substation->Insert($data);
        if($r){
            $data = array('code' => 1, 'msg' => '新增成功');
        }else{
            $data = array('code' => 1003, 'msg' => '新增失败');
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
					$where1 = array('id'=>$id);
					$where = '(status=0 or status=2)';//已完成和未支付的才可以删
					$delete = $this->m_order->Where($where1)->Where($where)->Update(array('isdelete'=>1));
					if($delete){
						$data = array('code' => 1, 'msg' => '删除成功', 'data' => '');
					}else{
						$data = array('code' => 1003, 'msg' => '删除失败', 'data' => '');
					}
				}else{
					$ids = json_decode($id,true);
					if(isset($ids['ids']) AND !empty($ids['ids'])){
						$idss = implode(",",$ids['ids']);
						$where = "(status=0 or status=2) and id in ({$idss})";
						$delete = $this->m_order->Where($where)->Update(array('isdelete'=>1));
						if($delete){
							$data = array('code' => 1, 'msg' => '成功');
						}else{
							$data = array('code' => 1003, 'msg' => '删除失败');
						}
					}else{
						$data = array('code' => 1000, 'msg' => '请选中需要删除的订单');
					}
				}	
			} else {
                $data = array('code' => 1002, 'msg' => '页面超时，请刷新页面后重试!');
            }
        } else {
            $data = array('code' => 1001, 'msg' => '缺少字段', 'data' => '');
        }
       Helper::response($data);
    }
	
	public function payAction()
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
				if($order['status']>0){
					$this->redirect('/'.ADMIN_DIR."/order/view/?id=".$order['id']);
					return FALSE;
				}else{
					$data['order'] = $order;
					$this->getView()->assign($data);
				}
			}else{
				$this->redirect('/'.ADMIN_DIR."/order");
				return FALSE;
			}
		}else{
            $this->redirect('/'.ADMIN_DIR."/order");
            return FALSE;
		}
    }
	
    public function payajaxAction()
    {
        if ($this->AdminUser==FALSE AND empty($this->AdminUser)) {
            $data = array('code' => 1000, 'msg' => '请登录');
			Helper::response($data);
        }
		$id = $this->get('id');
		$csrf_token = $this->getPost('csrf_token', false);
		
        if (FALSE != $id AND is_numeric($id) AND $id > 0) {
			if ($this->VerifyCsrfToken($csrf_token)) {
				$order = $this->m_order->SelectByID('',$id);
				if(is_array($order) AND !empty($order)){
					if($order['status']>0){
						$data = array('code' => 1, 'msg' => '订单已支付', 'data' => '');
					}else{
						//业务处理
						$config = array('paymethod'=>'admin','tradeid'=>0,'paymoney'=>$order['money'],'orderid'=>$order['orderid'] );
						$notify = new \Pay\notify();
						$data = $notify->run($config);
					}
				}else{
					$data = array('code' => 1002, 'msg' => '订单不存在', 'data' => '');
				}
			} else {
                $data = array('code' => 1001, 'msg' => '页面超时，请刷新页面后重试!');
            }
        } else {
            $data = array('code' => 1000, 'msg' => '缺少字段', 'data' => '');
        }
       Helper::response($data);
    }
	
	public function sendAction()
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
				if($order['status']=='1' OR $order['status']=='3'){
					$data['order'] = $order;
					$this->getView()->assign($data);
				}else{
					$this->redirect('/'.ADMIN_DIR."/order/view/?id=".$order['id']);
					return FALSE;
				}
			}else{
				$this->redirect('/'.ADMIN_DIR."/order");
				return FALSE;
			}
		}else{
            $this->redirect('/'.ADMIN_DIR."/order");
            return FALSE;
		}
    }
	
    public function sendajaxAction()
    {
        if ($this->AdminUser==FALSE AND empty($this->AdminUser)) {
            $data = array('code' => 1000, 'msg' => '请登录');
			Helper::response($data);
        }
		$id = $this->getPost('id');
		$kami = $this->getPost('kami');
		$csrf_token = $this->getPost('csrf_token', false);
		
        if (FALSE != $id AND is_numeric($id) AND $id > 0) {
			if ($this->VerifyCsrfToken($csrf_token)) {
				$order = $this->m_order->SelectByID('',$id);
				if(is_array($order) AND !empty($order)){
					if($order['status']=='1' OR $order['status']=='3'){
						//业务处理
						$kami = str_replace(array("\r","\n","\t"), "", $kami);
						$update = $this->m_order->Where(array('id'=>$id))->Where('status=1 or status=3')->Update(array('status'=>2,'kami'=>$kami));
						if($update){
							$m = array();
							//3.1.4.1通知用户,定时任务去执行
							if(isEmail($order['email'])){
								$content = '用户:' . $order['email'] . ',购买的商品['.$order['productname'].'],卡密是:'.$kami;
								$m[]=array('email'=>$order['email'],'subject'=>'商品购买成功','content'=>$content,'addtime'=>time(),'status'=>0);
							}
							//3.1.4.2通知管理员,定时任务去执行
							if(isEmail($this->config['admin_email'])){
								$content = '用户:' . $order['email'] . ',购买的商品['.$order['productname'].'],卡密发送成功';
								$m[]=array('email'=>$this->config['admin_email'],'subject'=>'用户购买商品','content'=>$content,'addtime'=>time(),'status'=>0);
							}
							if(!empty($m)){
								$this->m_email_queue->MultiInsert($m);
							}
							$data = array('code' => 1, 'msg' => '订单已处理', 'data' => '');
						}else{
							$data = array('code' => 1004, 'msg' => '处理失败', 'data' => '');
						}
					}else{
						$data = array('code' => 1, 'msg' => '订单状态不需要处理', 'data' => '');
					}
				}else{
					$data = array('code' => 1002, 'msg' => '订单不存在', 'data' => '');
				}
			} else {
                $data = array('code' => 1001, 'msg' => '页面超时，请刷新页面后重试!');
            }
        } else {
            $data = array('code' => 1000, 'msg' => '缺少字段', 'data' => '');
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
                return '开启';
            case '2' :
                return '关闭';
        }
        return '关闭';
    }
}