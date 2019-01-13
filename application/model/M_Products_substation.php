<?php
/**
 * File: M_Products.php
 * Functionality: 产品 model
 * Author: 资料空白
 * Date: 2015-9-4
 */

class M_Products_substation extends Model
{

	public function __construct()
	{
		$this->table = TB_PREFIX.'products_substation';
		parent::__construct();
	}

	public function updateInfo($param){
	    $exist = $this->Field('id')
            ->Where(array(
                'substation_id' => $param['substation_id'],
                'product_id' => $param['product_id'],
            ))->SelectOne();
	    if(empty($exist['id'])){
	        return $this->Insert(array(
                'substation_id' => $param['substation_id'],
                'product_id' => $param['product_id'],
                'price' => $param['price'],
            ));
        }else{
	        return $this->UpdateByID(array(
	            'price' => $param['price'],
            ), $exist['id']);
        }
    }
}