<?php
/**
 * File: M_Products.php
 * Functionality: 产品 model
 * Author: 资料空白
 * Date: 2015-9-4
 */

class M_Products_wholesale_substation extends Model
{

	public function __construct()
	{
		$this->table = TB_PREFIX.'products_wholesale_substation';
		parent::__construct();
	}

	public function updateInfo($data, $param){
	    //删除再追加
        $delete = $this->Where(array(
            'substation_id' => $param['substation_id'],
            'product_id' => $param['product_id'],
        ))->Delete();

        $r = $this->MultiInsert($data);
	    if($r){
	        return true;
        }else{
	        return false;
        }
    }
}