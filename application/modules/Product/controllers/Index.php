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
                        $order = array('sort_num' => 'DESC');
                        $products = $this->m_products->Where(array('typeid'=>$value['id'],'active'=>1,'isdelete'=>0))->Order($order)->Select();
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