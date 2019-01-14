<?php

/*
 * 功能：会员中心－个人中心
 * Author:资料空白
 * Date:20180509
 */

class IndexController extends PcBasicController
{
	private $m_products_type;
    private $m_products;
    public function init()
    {
        parent::init();
		$this->m_products_type = $this->load('products_type');
        $this->m_products = $this->load('products');
    }

    public function indexAction()
    {
		if(file_exists(INSTALL_LOCK)){
			$data = array();
			if(isset($this->config['tplindex']) AND file_exists(APP_PATH.'/application/modules/Product/views/index/tpl/'.$this->config['tplindex'].'.html')){
				$data['title'] = "购买商品";
				$tpl = 'tpl_'.$this->config['tplindex'];
				//var_dump($tpl);die();
				$this->display($tpl, $data);
				return FALSE;

			}else{
				$order = array('sort_num' => 'DESC');
				$products_type = $this->m_products_type->Where(array('active'=>1,'isdelete'=>0))->Order($order)->Select();
				//var_dump($products_type);die();
                if(isset($products_type)){
                    foreach ($products_type as $id => $value){

                        if($this->substation_id != 'master'){
                            $sql = "select 
                                      p.*,ps.price as price_new,(p.sale_base+ifnull(o.sale_num,0)) as sale_num  from `t_products` as p 
                                      left join (SELECT * FROM `t_products_substation` WHERE substation_id={$this->substation_id}) as ps on ps.product_id=p.id 
                                      left join (SELECT pid,sum(number) as sale_num FROM `t_order` WHERE isdelete=0 AND status>0 GROUP BY pid) as o on o.pid=p.id
                                      WHERE ".convertSQL(array(
                                    'p.typeid'=>$value['id'],
                                    'p.active'=>1,
                                    'p.isdelete'=>0
                                ), true)." order by p.sort_num desc";
                            $products = (array)$this->m_products->Query($sql);
                            foreach ($products as &$v){
                                if(isset($v['price_new']) and $v['price_new'] != ''){
                                    $v['price'] = $v['price_new'];
                                }
                            }
                        }else{
                            $sql = "select 
                                      p.*,(p.sale_base+ifnull(o.sale_num,0)) as sale_num  from `t_products` as p 
                                      left join (SELECT pid,sum(number) as sale_num FROM `t_order` WHERE isdelete=0 AND status>0 GROUP BY pid) as o on o.pid=p.id
                                      WHERE ".convertSQL(array(
                                    'p.typeid'=>$value['id'],
                                    'p.active'=>1,
                                    'p.isdelete'=>0
                                ), true)." order by p.sort_num desc";
                            $products = $this->m_products
                                ->Query($sql);
                        }
                        $products_type[$id]['products'] = $products;

                    }
                }
                //var_dump($products_type);die();
				$data['products_type'] = $products_type;
				$data['title'] = "首页";
				$this->getView()->assign($data);
			}
		}else{
			$this->redirect("/install/");
			return FALSE;
		}
    }
}