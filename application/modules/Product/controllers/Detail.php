<?php

/*
 * 功能：商品模块－商品独立页
 * Author:资料空白
 * Date:20180707
 */

class DetailController extends PcBasicController
{
	private $m_products;
	private $m_products_pifa;
    private $m_payment;
    private $m_products_substation;
    private $m_products_wholesale_substation;

    public function init()
    {
        parent::init();
		$this->m_products = $this->load('products');
		$this->m_products_pifa = $this->load('products_pifa');
		$this->m_products_substation = $this->load('products_substation');
		$this->m_products_wholesale_substation = $this->load('products_wholesale_substation');
        $this->m_payment = $this->load('payment');
    }

    public function indexAction()
    {
		$pid = $this->get('pid');
		if($pid AND is_numeric($pid) AND $pid>0){
			$product = $this->m_products->Where(array('id'=>$pid,'active'=>1,'isdelete'=>0))->SelectOne();
            if(!empty($product) && $this->substation_id != 'master'){
                $exist = $this->m_products_substation
                    ->Where(array(
                        'substation_id' => $this->substation_id,
                        'product_id' => $product['id'],
                    ))->SelectOne();
                !empty($exist['price']) && $product['price'] = $exist['price'];
            }
			if(!empty($product)){
				$data = array();
				//先拿折扣
				$data['pifa'] = "";
				if($this->config['discountswitch']){
					$pifa = $this->m_products_pifa->getPifa($pid);
					if(!empty($pifa)){
						$data['pifa'] = json_encode($pifa);
					}
				}
                //获取支付方式
                $payments = $this->m_payment->getConfig();
                $data['payments']=$payments;
                //否则
                $data['product'] = $product;
                if($product['addons']){
                    $addons = explode(',',$product['addons']);
                    $data['addons'] = $addons;
                }else{
                    $data['addons'] = array();
                }
                $data['qcodeurl'] = $product['name']."_购买商品";
                $data['title'] = $product['name']."_购买商品";
                $this->getView()->assign($data);
			}else{
				$this->redirect("/product/");
				return FALSE;	
			}
		}else{
			$this->redirect("/product/?error=没有该商品");
			return FALSE;
		}
    }

    public function wholeSaleajaxAction(){
        $id = $this->getPost('id',false);

        $data = array();
        if(empty($id)){
            $data = array('code' => 1003, 'msg' => '当前商品没有设置批发价');
            Helper::response($data);
        }
        $list = (array)$this->m_products_wholesale_substation
            ->Field('num,price')
            ->Where(array(
            'substation_id' => $this->substation_id,
            'product_id'=>$id,
        ))->Select();
        $data = array('code' => 1, 'msg' => '', 'data' => $list);
        Helper::response($data);
    }
}