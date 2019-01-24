<?php

/*
 * 功能：后台中心－商品管理
 * Author:资料空白
 * Date:20180509
 */

class ProductsController extends AdminBasicController
{
	private $m_products;
	private $m_products_type;
	private $m_products_card;
	private $m_products_substation;
	private $m_products_wholesale_substation;

    public function init()
    {
        parent::init();
		$this->m_products = $this->load('products');
		$this->m_products_type = $this->load('products_type');
		$this->m_products_card = $this->load('products_card');
		$this->m_products_substation = $this->load('products_substation');
		$this->m_products_wholesale_substation = $this->load('products_wholesale_substation');
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
		
		$where = array('isdelete'=>0);
		
		$total=$this->m_products->Where($where)->Total();
		
        if ($total > 0) {
            if ($page > 0 && $page < (ceil($total / $limit) + 1)) {
                $pagenum = ($page - 1) * $limit;
            } else {
                $pagenum = 0;
            }
			
            $limits = "{$pagenum},{$limit}";
            if($this->CommonAdmin === ''){//主站
                $sql = "SELECT 
                          p1.id,p1.name,p1.price,p1.qty,p1.auto,p1.active,p1.stockcontrol,p1.sort_num,p1.sale_base,p2.name as typename,(p1.sale_base+ifnull(o.sale_num,0)) as sale_num 
                          FROM `t_products` as p1 
                          left join `t_products_type` as p2 on p1.typeid = p2.id
                          left join (SELECT pid,sum(number) as sale_num FROM `t_order` WHERE isdelete=0 AND status>0 GROUP BY pid) as o on o.pid=p1.id
                          WHERE p1.isdelete=0 
                          Order by p1.id desc 
                          LIMIT {$limits}";
            }else{
                //分站
                $sql = "SELECT 
                          p1.id,p1.name,p1.price,p1.qty,p1.auto,p1.active,p1.stockcontrol,p1.sort_num,p1.sale_base,p2.name as typename,ps.price as price_new,(p1.sale_base+ifnull(o.sale_num,0)) as sale_num  
                          FROM `t_products` as p1 
                          left join `t_products_type` as p2 on p1.typeid = p2.id 
                          left join 
                            ( SELECT * FROM `t_products_substation` WHERE substation_id={$this->CommonAdmin}) as ps on ps.product_id=p1.id
                          left join (SELECT pid,sum(number) as sale_num FROM `t_order` WHERE isdelete=0 AND status>0 GROUP BY pid) as o on o.pid=p1.id
                          WHERE p1.isdelete=0 
                          Order by p1.id desc 
                          LIMIT {$limits}";
            }
			$items=(array)$this->m_products->Query($sql);
            foreach ($items as &$v){
                if(isset($v['price_new']) and $v['price_new'] !== ''){
                    $v['price'] = $v['price_new'];
                }
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
            if(!empty($this->CommonAdmin)){
                //分站售价
                $s = $this->m_products_substation
                    ->Field('price')
                    ->Where(array(
                    'product_id' => $product['id'],
                    'substation_id' => $this->CommonAdmin,
                ))->SelectOne();
                !empty($s['price']) && $product['price'] = $s['price'];
            }
            //批发价
            $wholesale = (array)$this->m_products_wholesale_substation
                ->Field('num,price')
                ->Where(array(
                    'product_id' => $product['id'],
                    'substation_id' => $this->CommonAdmin === '' ? 'master' : $this->CommonAdmin,
                ))->Select();

            $data['wholesale'] = $wholesale;
			$data['product'] = $product;

			
			$productstype=$this->m_products_type
                ->Where(array('isdelete'=>0))
                ->Order(array('sort_num'=>'DESC'))
                ->Select();

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
		$productstype=$this->m_products_type->Where(array('isdelete'=>0))->Order(array('sort_num'=>'DESC'))->Select();
		$data['productstype'] = $productstype;
		$this->getView()->assign($data);
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
        $price_official = $this->getPost('price_official', false);
        $price_agent = $this->getPost('price_agent', false);
        $wholesale_price = $this->getPost('wholesale_price', false);
        $wholesale_num = (array)$this->getPost('wholesale_num', false);
        $sale_base = $this->getPost('sale_base', false);
		$data = array();
		
        if ($this->AdminUser==FALSE AND empty($this->AdminUser)) {
            $data = array('code' => 1000, 'msg' => '请登录');
			Helper::response($data);
        }
		
		if($this->CommonAdmin != ''
            || ($method AND $typeid AND $name AND $description AND is_numeric($stockcontrol) AND is_numeric($qty) AND is_numeric($price) AND is_numeric($auto) AND is_numeric($active) AND is_numeric($sort_num) AND $csrf_token)){
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
                    'price_official'=>$price_official,
                    'price_agent'=>$price_agent,
                    'sale_base'=>$sale_base,
				);
				if($this->CommonAdmin !=''){
				    if($price < $old_price){
                        $data = array('code' => 1013, 'msg' => '售价必须高于成本价');
                        Helper::response($data);
                    }
				    $s = array(
				        'product_id' => $id,
                        'price' => $price,
                        'substation_id' => $this->CommonAdmin,
                    );
                    $u=$this->m_products_substation->updateInfo($s);
                    if($u){
                        if($this->CommonAdmin != ''){
                            //分站情况
                            foreach ($wholesale_num as $k=>$v){
                                $data_wholesale[] = array(
                                    'substation_id' => $this->CommonAdmin,
                                    'product_id' => $id,
                                    'num' => $wholesale_num[$k],
                                    'price' => $wholesale_price[$k],
                                );
                            }
                            $this->m_products_wholesale_substation->updateInfo((array)$data_wholesale,array(
                                'substation_id' => $this->CommonAdmin,
                                'product_id' => $id,
                            ));
                        }else{
                            //主站
                            foreach ($wholesale_num as $k=>$v){
                                $data_wholesale[] = array(
                                    'substation_id' => 'master',
                                    'product_id' => $id,
                                    'num' => $wholesale_num[$k],
                                    'price' => $wholesale_price[$k],
                                );
                            }
                            $this->m_products_wholesale_substation->updateInfo((array)$data_wholesale,array(
                                'substation_id' => 'master',
                                'product_id' => $id,
                            ));
                        }
                        $data = array('code' => 1, 'msg' => '更新成功');
                    }else{
                        $data = array('code' => 1003, 'msg' => '更新失败');
                    }
                }
				elseif($method == 'edit' AND $id>0){
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
					    //商品编辑成功，开始更新关联数据，含批发价信息表

                        if($this->CommonAdmin != ''){
                            //分站情况
                            foreach ($wholesale_num as $k=>$v){
                                $data_wholesale[] = array(
                                    'substation_id' => $this->CommonAdmin,
                                    'product_id' => $id,
                                    'num' => $wholesale_num[$k],
                                    'price' => $wholesale_price[$k],
                                );
                            }
                            $this->m_products_wholesale_substation->updateInfo((array)$data_wholesale,array(
                                'substation_id' => $this->CommonAdmin,
                                'product_id' => $id,
                            ));
                        }else{
                            //主站
                            foreach ($wholesale_num as $k=>$v){
                                $data_wholesale[] = array(
                                    'substation_id' => 'master',
                                    'product_id' => $id,
                                    'num' => $wholesale_num[$k],
                                    'price' => $wholesale_price[$k],
                                );
                            }
                            $this->m_products_wholesale_substation->updateInfo((array)$data_wholesale,array(
                                'substation_id' => 'master',
                                'product_id' => $id,
                            ));
                        }
						$data = array('code' => 1, 'msg' => '更新成功');
					}else{
						$data = array('code' => 1003, 'msg' => '更新失败');
					}
				}
				elseif($method == 'add'){
					//修正库存问题,在添加新商品时,如果是自动发货商品,库存默认为0
					if($auto >0 or $stockcontrol<1){
						$m['qty'] = 0;
					}
					$m['addtime'] = time();
					$u = $this->m_products->Insert($m);
					if($u){
                        //商品编辑成功，开始更新关联数据，含批发价信息表
                        if($this->CommonAdmin != ''){
                            //分站情况
                            foreach ($wholesale_num as $k=>$v){
                                $data_wholesale[] = array(
                                    'substation_id' => $this->CommonAdmin,
                                    'product_id' => $u,
                                    'num' => $wholesale_num[$k],
                                    'price' => $wholesale_price[$k],
                                );
                            }
                            $this->m_products_wholesale_substation->updateInfo((array)$data_wholesale,array(
                                'substation_id' => $this->CommonAdmin,
                                'product_id' => $u,
                            ));
                        }else{
                            //主站
                            foreach ($wholesale_num as $k=>$v){
                                $data_wholesale[] = array(
                                    'substation_id' => 'master',
                                    'product_id' => $u,
                                    'num' => $wholesale_num[$k],
                                    'price' => $wholesale_price[$k],
                                );
                            }
                            $this->m_products_wholesale_substation->updateInfo((array)$data_wholesale,array(
                                'substation_id' => 'master',
                                'product_id' => $u,
                            ));
                        }
						$data = array('code' => 1, 'msg' => '新增成功');
					}else{
						$data = array('code' => 1003, 'msg' => '新增失败');
					}
				}
				else{
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
				//检查是否存在可用的卡密
				$qty = $this->m_products_card->Where(array('pid'=>$id,'active'=>0,'isdelete'=>0))->Total();
				if($qty>0){
					$data = array('code' => 1004, 'msg' => '存在可用卡密，请导出', 'data' => '');
				}else{
					$where = 'active=0';//只有未激活的才可以删除
					$delete = $this->m_products->Where($where)->UpdateByID(array('isdelete'=>1),$id);
					if($delete){
						$data = array('code' => 1, 'msg' => '删除成功', 'data' => '');
					}else{
						$data = array('code' => 1003, 'msg' => '删除失败', 'data' => '');
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
	
    public function getlistbytidAction()
    {
        if ($this->AdminUser==FALSE AND empty($this->AdminUser)) {
            $data = array('code' => 1000, 'msg' => '请登录');
            Helper::response($data);
        }
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

    /**
     * use for:商品图片上传
     * auth: Joql
     * date:2019-01-22 22:17
     */
    public function photoUploadAction()
    {
        if ($this->AdminUser==FALSE AND empty($this->AdminUser)) {
            $data = array('code' => 1000, 'msg' => '请登录');
            Helper::response($data);
        }
        $data = array();
        \Yaf\Loader::import(LIB_PATH.'/Upload.php');
        $upload = new \Dj\Upload('file',[
            'ext'=>'jpg,jpeg,png,gif',
            'size'=>5242880
        ]);
        $filelist = $upload->save(UPLOAD_PATH.'/img');
        if(is_array($filelist)){
            # 返回数组，文件就上传成功了
            //print_r($filelist);

            $data = array('code' => 1, 'msg' => '上传成功','data'=>$filelist['uri']);
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