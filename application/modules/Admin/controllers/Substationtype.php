<?php

/*
 * 功能：后台中心－商品管理
 * Author:资料空白
 * Date:20180509
 */

class SubstationtypeController extends AdminBasicController
{
	private $m_products;
	private $m_products_type;
	private $m_products_card;

	private $m_substation_type;
	
    public function init()
    {
        parent::init();
		$this->m_products = $this->load('substation_type');
		$this->m_products_type = $this->load('products_type');
		$this->m_products_card = $this->load('products_card');
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
		
		$page = $this->get('page');
		$page = is_numeric($page) ? $page : 1;
		
		$limit = $this->get('limit');
		$limit = is_numeric($limit) ? $limit : 10;
		
		$where = array();
		
		$total=$this->m_substation_type->Where($where)->Total();
		
        if ($total > 0) {
            if ($page > 0 && $page < (ceil($total / $limit) + 1)) {
                $pagenum = ($page - 1) * $limit;
            } else {
                $pagenum = 0;
            }
			
            $limits = "{$pagenum},{$limit}";
			$sql = "SELECT * FROM `t_substation_type` Order by id desc LIMIT {$limits}";
			$items=$this->m_substation_type->Query($sql);
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
	
    public function editAction()
    {
        if ($this->AdminUser==FALSE AND empty($this->AdminUser)) {
            $this->redirect('/'.ADMIN_DIR."/login");
            return FALSE;
        }
		$id = $this->get('id');
		if($id AND $id>0){
			$data = array();
			$product=$this->m_products->SelectByID('',$id);
			$data['product'] = $product;
			
			$productstype=$this->m_products_type->Where(array('isdelete'=>0))->Order(array('sort_num'=>'DESC'))->Select();
			$data['productstype'] = $productstype;
			
			$this->getView()->assign($data);
		}else{
            $this->redirect('/'.ADMIN_DIR."/products");
            return FALSE;
		}
    }
	
    public function addAction()
    {
        if ($this->AdminUser==FALSE AND empty($this->AdminUser)) {
            $this->redirect('/'.ADMIN_DIR."/login");
            return FALSE;
        }

		$data = array();

		$this->getView()->assign($data);
    }
    public function addajaxAction()
    {
        if ($this->AdminUser==FALSE AND empty($this->AdminUser)) {
            $this->redirect('/'.ADMIN_DIR."/login");
            return FALSE;
        }
        $name = $this->getPost('name', false);
		$data = array();
		$type_list =$this->m_substation_type->Field('name')
            ->Where(array(
                'name' => $name
            ))
            ->Select();
		if(!empty($type_list[0]['name'])){
            $data = array('code' => 1003, 'msg' => '新增失败');
            Helper::response($data);
        }
        $r = $this->m_substation_type->Insert(array(
            'name' => $name,
        ));
        if($r){
            $data = array('code' => 1, 'msg' => '新增成功','data' => $type_list);
        }else{
            $data = array('code' => 1003, 'msg' => '新增失败');
        }
        Helper::response($data);
    }
	public function editajaxAction()
	{
		$method = $this->getPost('method',false);
		$id = $this->getPost('id',false);
		$typeid = $this->getPost('typeid',false);
		$name = $this->getPost('name',false);
		$password = $this->getPost('password',false);
		$description = $this->getPost('description',false);
		$stockcontrol = $this->getPost('stockcontrol',false);
		$qty = $this->getPost('qty',false);
		$price = $this->getPost('price',false);
		$auto = $this->getPost('auto',false);
		$addons = $this->getPost('addons',false);
		$active = $this->getPost('active',false);
		$sort_num = $this->getPost('sort_num',false);
		$csrf_token = $this->getPost('csrf_token', false);
        $images = $this->getPost('images', false);
        $old_price = $this->getPost('old_price', false);
		$data = array();
		
        if ($this->AdminUser==FALSE AND empty($this->AdminUser)) {
            $data = array('code' => 1000, 'msg' => '请登录');
			Helper::response($data);
        }
		
		if($method AND $typeid AND $name AND $description AND is_numeric($stockcontrol) AND is_numeric($qty) AND is_numeric($price) AND is_numeric($auto) AND is_numeric($active) AND is_numeric($sort_num) AND $csrf_token){
			if ($this->VerifyCsrfToken($csrf_token)) {
				if($price<0.01){
					$data = array('code' => 1000, 'msg' => '价格设置错误');
					Helper::response($data);
				}

                $description = str_replace(array("\r","\n","\t"), "", $description);
				$m=array(
					'typeid'=>$typeid,
					'name'=>$name,
					'password'=>$password,
					'description'=>$description,
					'stockcontrol'=>$stockcontrol,
					'qty'=>$qty,
					'price'=>$price,
					'auto'=>$auto,
					'addons'=>$addons,
					'active'=>$active,
					'sort_num'=>$sort_num,
                    'images'=>$images,
                    'old_price'=>$old_price,
				);
				if($method == 'edit' AND $id>0){
					//修正库存问题,如果不控制库存，库存默认为０
					if($stockcontrol<1){
						$m['qty'] = 0;
					}else{
						//修正库存问题,在更新商品时,如果是自动发货商品,库存不能修改
						if($auto>0){
							unset($m['qty']);
						}
					}
					$u = $this->m_products->UpdateByID($m,$id);
					if($u){
						$data = array('code' => 1, 'msg' => '更新成功');
					}else{
						$data = array('code' => 1003, 'msg' => '更新失败');
					}
				}elseif($method == 'add'){
					//修正库存问题,在添加新商品时,如果是自动发货商品,库存默认为0
					if($auto>0 OR $stockcontrol<1){
						$m['qty'] = 0;
					}
					$m['addtime'] = time();
					$u = $this->m_products->Insert($m);
					if($u){
						$data = array('code' => 1, 'msg' => '新增成功');
					}else{
						$data = array('code' => 1003, 'msg' => '新增失败');
					}
				}else{
					$data = array('code' => 1002, 'msg' => '未知方法');
				}
			} else {
                $data = array('code' => 1001, 'msg' => '页面超时，请刷新页面后重试!');
            }
		}else{
			$data = array('code' => 1000, 'msg' => '丢失参数');
		}
		Helper::response($data);
	}
	
	public function updateqtyajaxAction()
	{
		$pid = $this->getPost('pid',false);
		$csrf_token = $this->getPost('csrf_token', false);
		
		$data = array();
		
        if ($this->AdminUser==FALSE AND empty($this->AdminUser)) {
            $data = array('code' => 1000, 'msg' => '请登录');
			Helper::response($data);
        }
		
		if($pid AND $csrf_token){
			if ($this->VerifyCsrfToken($csrf_token)) {
				//修正库存问题,在添加新商品时,如果是自动发货商品,库存默认为0
				$qty = $this->m_products_card->Where(array('pid'=>$pid,'active'=>0,'isdelete'=>0))->Total();
				$qty_m = array('qty' => $qty);
				$u = $this->m_products->Where(array('id'=>$pid,'auto'=>1,'stockcontrol'=>1))->Update($qty_m);
				if($u){
					$data = array('code' => 1, 'msg' => '成功');
				}else{
					$data = array('code' => 1003, 'msg' => '失败');
				}
			} else {
                $data = array('code' => 1001, 'msg' => '页面超时，请刷新页面后重试!');
            }
		}else{
			$data = array('code' => 1000, 'msg' => '丢失参数');
		}
		Helper::response($data);
	}
	
    public function deleteAction()
    {
        if ($this->AdminUser==FALSE AND empty($this->AdminUser)) {
            $data = array('code' => 1000, 'msg' => '请登录');
			Helper::response($data);
        }
		$id = $this->get('id');
		$csrf_token = $this->getPost('csrf_token', false);
        if (FALSE != $id AND is_numeric($id) AND $id > 0) {
			if ($this->VerifyCsrfToken($csrf_token)) {
				$delete = $this->m_substation_type->Where(array('id' => $id))
                    ->Delete();
                if($delete){
                    $data = array('code' => 1, 'msg' => '删除成功', 'data' => '');
                }else{
                    $data = array('code' => 1003, 'msg' => '删除失败', 'data' => '');
                }
			} else {
                $data = array('code' => 1002, 'msg' => '页面超时，请刷新页面后重试!');
            }
        } else {
            $data = array('code' => 1001, 'msg' => '缺少字段', 'data' => '');
        }
       Helper::response($data);
    }
	
    public function getlistbytidAction()
    {
		$tid = $this->getPost('tid');
		$csrf_token = $this->getPost('csrf_token', false);
		
		if($tid AND $csrf_token){
			if ($this->VerifyCsrfToken($csrf_token)) {
				$data = array();
				$order = array('sort_num' => 'DESC');
				$field = array('id', 'name');
				$products = $this->m_products->Field($field)->Where(array('typeid'=>$tid,'active'=>1,'isdelete'=>0))->Order($order)->Select();
				$data['products'] = $products;
				$result = array('code' => 1, 'msg' => 'success','data'=>$data);
			} else {
                $result = array('code' => 1001, 'msg' => '页面超时，请刷新页面后重试!');
            }
		}else{
			$result = array('code' => 1000, 'msg' => '参数错误');
		}
        Helper::response($result);
    }
	
}