<?php
/**
 * File: M_Products.php
 * Functionality: 产品 model
 * Author: 资料空白
 * Date: 2015-9-4
 */

class M_Substation extends Model
{

	public function __construct()
	{
		$this->table = TB_PREFIX.'substation';
		parent::__construct();
	}

}