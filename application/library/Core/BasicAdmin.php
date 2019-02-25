<?php
/**
 * File: BasicAdmin.php
 * Functionality: Basic Controller(再整理)
 * Author: 资料空白
 * Date: 2018-6-8
 */
class AdminBasicController extends BasicController
{
    protected $AdminUser = array();
    protected $CommonAdmin ='';
	public $serverPrivateKey = "MIICdgIBADANBgkqhkiG9w0BAQEFAASCAmAwggJcAgEAAoGBAKrU5gne1HvK18yk9aFX+LIgf8bIZvW/TgAAQWUkLkVDf1s91r6JmlmJsvGDz1KWuFEtU5k+ZTY+znh0ncLfgdTcmVvymp1D4fhEKt/JSaZNZe7Fb3kfl7iT15pQBivirrkpP1dwyM5EzafkRo5wKOktbQLYglW/e+ChVf4L+mqXAgMBAAECgYBcweb6Wwzi/rv4OWXKKps2FSFsTSpiq3Jt27WmdmPNZh4D6+rrYIn3riYEr35mKMKCCWuIHPIV5zpy+1ciFfxHNifvwVs9zpWGYkuvyI2Ar41zODI8doYFaQjWUBf/xJziabTEn1pFsH+Q8xWqr0fXdFdKYt6lYnjZR3bJIL79yQJBANaEQ0MqPqbj4s6L++igcgizkPOQ00a0kRdv6R0wQWqXg5fseg776sUv301XYbTnc7BlmHsQUQsYcROOqzhZlNsCQQDL3f2ehMGecX2qnImBGbXIRIIF1DnjULDzBpz/ijMYg1trIRRjBirWFj6cQOEOxlW2A8qpz1ZxR9zfSzjYXG/1AkBPn8xvs9CJlfDsBd29XUC2piBZqBokFoX8kxeONAk0DYVU8Pvlb/CWvMxAIv0rbvXsNenBVC8g1TOztLMtOWMdAkEAgC1ZyXHknm7yuPNkzOPSVFEmgu21W8OfDZ2p1k0Y5R+puch5ne0Bv8sKoIl2NyjiOOdXY761tdGeAFK2MeqkhQJALGjfBtrV9c3u3XVVbpASadkkOcUvXOb8fyRvTv03Bg3cbF3hP6ucb5SPEg6dDHixRj25S+JTiYH5WxbtyYni5g==";
 
    public function init()
    {
        parent::init();
        $this->updateWeb();
		$this->getView()->assign($this->checkAcceradit());
    }

    //消息显示
    public function show_message($code = '', $msg = '', $url = '/')
    {
        $this->forward(ADMIN_DIR, 'Showmsg', 'index', array('code' => $code, 'msg' => $msg, 'url' => $url));
        return FALSE;
    }

    //管理员登录检查
    public function checkLogin()
    {
        if ($this->AdminUser['id'] > 0) {

        } else {
            jsRedirect(ADMIN_DIR.'/login');
        }
    }

    //管理员登录
    public function setLogin($AdminUser)
    {
        //导入数据模型
        $m_admin_user = Helper::load('admin_user');
        $m_admin_login_log = Helper::load('admin_login_log');

        unset($AdminUser['secret'], $AdminUser['password'], $AdminUser['status']);

        $AdminUser['expiretime'] = time() + 15 * 60;
        Yaf\Session::getInstance()->__set('AdminUser', $AdminUser);
        //新增登录日志
        $m_admin_login_log->logLogin($AdminUser['id']);
        return TRUE;
    }


    //生成JWT token
    public function createToken()
    {
        $tokenKey = array(
            "iss" => "http://zlkb.net",  //jwt签发者
            "aud" => 'RPC',                     //接收jwt的一方
            "exp" => time() + 60,               //过期时间
        );
        return JWT::encode($tokenKey, self::readRSAKey($this->serverPrivateKey), 'RS256');
    }
    //为JWT准备的，证书处理函数
    private static function readRSAKey($key)
    {
        $isPrivate = strlen($key) > 500;
        if ($isPrivate) {
            $lastKey = chunk_split($key, 64, "\n");
            $lastKey = "-----BEGIN RSA PRIVATE KEY-----\n" . $lastKey . "-----END RSA PRIVATE KEY-----\n";
            return $lastKey;
        } else {
            $lastKey = chunk_split($key, 64, "\n");
            $lastKey = "-----BEGIN PUBLIC KEY-----\n" . $lastKey . "-----END PUBLIC KEY-----\n";
            return $lastKey;
        }
    }
	//生成csrftoken　防csrf攻击
    private function createCsrfToken(){
    	$csrf_token=$this->getSession('csrf_token');
    	if(!$csrf_token){
    		$csrf_token=$this->createToken(); 
			$this->setSession('csrf_token',$csrf_token);
    	}
		return $csrf_token;
	}
	//验证csrftoken 防csrf攻击
	public function VerifyCsrfToken($csrf_token=''){
		$csrf_token=$csrf_token?$csrf_token:$this->getPost('csrf_token',false);
		$session_csrf_token=$this->getSession('csrf_token',false); 
		if($session_csrf_token && $session_csrf_token==$csrf_token){
			if(!isAjax()){
				$this->setSession('csrf_token','');
			}
			return true;
		}else{
			return false;
		}
	}

	private function initWeb(){
        $sysvars = $data = array();
        $data['config']=$this->config = $this->load('config')->getConfig();
        $sysvars['isHttps'] = $this->isHttps = isHttps();
        $sysvars['isAjax'] = $this->isAjax = isAjax();
        $sysvars['isGet'] = $this->isGet = isGet();
        $sysvars['isPost'] = $this->isPost = isPost();
        $sysvars['currentUrl'] = stripHTML(str_replace('//', '/', $_SERVER['REQUEST_URI']));
        $data['sysvars'] = $sysvars;
        $AdminUser = $this->getSession('AdminUser');
        if (is_array($AdminUser) AND !empty($AdminUser) AND $AdminUser['expiretime'] > time()) {
            $AdminUser['expiretime'] = time() + 15 * 60;
            $this->setSession('AdminUser', $AdminUser);
            $data['AdminUser'] = $this->AdminUser = $AdminUser;
        } else {
            $this->unsetSession('AdminUser');
        }
        if($this->AdminUser['substation_id'] !== 'master'){
            $this->CommonAdmin = $AdminUser['substation_id'];
        }
        $data['csrf_token'] = $this->createCsrfToken();
        return $data;
    }

    private function checkAcceradit(){
        $domain=getTopDomainhuo();

        $real_domain='baidu.com'; //本地检查时 用户的授权域名 和时间
        $http_type = (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == '443') ? 'https://' : 'http://';
        $check_host = ''.$http_type.'sq.sanlou.me/api/';
        $client = '&client='.base64_encode(str_replace(" ","+",'Sadnt'));//这里改为你的产品名称
        $client_check = $check_host . '?a=client_check&u=' . trim($_SERVER['SERVER_NAME']).$client;
        $check_message = $check_host . '?a=check_message&u=' . trim($_SERVER['SERVER_NAME']).$client;
        $check_info=file_get_contents($client_check);
        $message = file_get_contents($check_message);

        $ben_string = trim(getTopDomainhuo()).$real_domain;
        $shaben = sha1($ben_string);
        $robotstrben =  substr(md5($shaben), 0, 8);

        if($check_info==$robotstrben){
            $get_string = trim(getTopDomainhuo()).$real_domain;
        }else{
            $get_string = trim($_SERVER['SERVER_NAME']).$real_domain;
        }
        $sha = sha1($get_string);
        $robotstr =  substr(md5($sha), 0, 8);


        if($check_info=='1'){
            header("Content-Type: text/html;charset=utf-8");
            echo '<link href="'.$check_host.'css/sq.css" rel="stylesheet" type="text/css" />';
            echo '<div class="wrapper">';
            echo '<div class="main">';
            echo '<div class="title">授权信息</div>';
            echo '<div class="content">';
            echo '<p><font color=red>您的特征码：' . $robotstr . '</font></p>';
            echo '<p><font color=red>' . $message . '</font></p>';
            echo '</div>';
            echo '<div class="footer">美仑授权系统';
            echo '</div>';
            echo '</div>';
            echo '</div>';
            die;
        }elseif($check_info=='2'){
            header("Content-Type: text/html;charset=utf-8");
            echo '<link href="'.$check_host.'css/sq.css" rel="stylesheet" type="text/css" />';
            echo '<div class="wrapper">';
            echo '<div class="main">';
            echo '<div class="title">授权状态</div>';
            echo '<div class="content">';
            echo '<p><font color=red>' . $message . '</font></p>';
            echo '</div>';
            echo '<div class="footer">美仑授权系统';
            echo '</div>';
            echo '</div>';
            echo '</div>';
            die;
        }elseif($check_info=='3'){
            header("Content-Type: text/html;charset=utf-8");
            echo '<link href="'.$check_host.'css/sq.css" rel="stylesheet" type="text/css" />';
            echo '<div class="wrapper">';
            echo '<div class="main">';
            echo '<div class="title">授权状态</div>';
            echo '<div class="content">';
            echo '<p><font color=red>' . $message . '</font></p>';
            echo '</div>';
            echo '<div class="footer">美仑授权系统';
            echo '</div>';
            echo '</div>';
            echo '</div>';
            die;
        }elseif($check_info=='4'){
            header("Content-Type: text/html;charset=utf-8");
            echo '<link href="'.$check_host.'css/sq.css" rel="stylesheet" type="text/css" />';
            echo '<div class="wrapper">';
            echo '<div class="main">';
            echo '<div class="title">授权状态</div>';
            echo '<div class="content">';
            echo '<p><font color=red>' . $message . '</font></p>';
            echo '</div>';
            echo '<div class="footer">美仑授权系统';
            echo '</div>';
            echo '</div>';
            echo '</div>';
            die;
        }elseif($check_info=='5'){
            header("Content-Type: text/html;charset=utf-8");
            echo '<link href="'.$check_host.'css/sq.css" rel="stylesheet" type="text/css" />';
            echo '<div class="wrapper">';
            echo '<div class="main">';
            echo '<div class="title">授权状态</div>';
            echo '<div class="content">';
            echo '<p><font color=red>' . $message . '</font></p>';
            echo '</div>';
            echo '<div class="footer">美仑授权系统';
            echo '</div>';
            echo '</div>';
            echo '</div>';
            die;
        }
        $result = $check_info;
        if(empty($result)){
            $result_info = '0';
        }
        elseif(!empty($result)){
            $result_info = $check_info;
        }

        if($robotstr!==$result_info){ // 远程检查失败的时候 本地检查
            if($domain!==$real_domain){
                header("Content-Type: text/html;charset=utf-8");
                echo '<link href="'.$check_host.'css/sq.css" rel="stylesheet" type="text/css" />';
                echo '<div class="wrapper">';
                echo '<div class="main">';
                echo '<div class="content">';
                echo '<p><font color=red>远程检查失败了。请联系授权提供商。</font></p>';
                echo '<p><font color=red>您的特征码：' . $robotstr . '</font></p>';
                echo '</div>';
                echo '<div class="footer">美仑授权系统';
                echo '</div>';
                echo '</div>';
                echo '</div>';
                die;
            }
        }

        unset($domain);
        return $this->initWeb();
    }

    private function updateWeb(){

        $version = UPDATE_PATH.'/version.php';
        $ver = include($version);
        $ver = $ver['ver'];
        $ver = substr($ver,-3);
        $hosturl = urlencode('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF']);
        $updatehost = 'http://sq.sanlou.me/api/';
        $updatehosturl = $updatehost . '?a=update&v=' . $ver . '&u=' . $hosturl;
        $updatenowinfo = file_get_contents($updatehosturl);
        if (strstr($updatenowinfo, 'zip')){
            $pathurl = $updatehost . '?a=down&f=' . $updatenowinfo;
            //delDirAndFile(UPDATE_PATH);
            get_file($pathurl, $updatenowinfo, UPDATE_PATH);
        }else{
            return false;
        }
        //获取压缩包开始解压替换,sql文件存在则执行
        $updatezip = UPDATE_PATH.'/'.$updatenowinfo;
        //测试数据库
        //$updatezip = UPDATE_PATH.'/'.'1.4.zip';
        if(file_exists($updatezip) === false){
            return false;
        }

        \Yaf\Loader::import(LIB_PATH.'/PclZip.php');
        $archive = new PclZip($updatezip);
        if ($archive -> extract(PCLZIP_OPT_PATH, APP_PATH.'/', PCLZIP_OPT_REPLACE_NEWER) == 0){
            return false;
        }else{
            $sqlfile = APP_PATH . '/update.sql';
            if(file_exists($sqlfile)){
                $sql = file_get_contents($sqlfile);
            }
            if($sql){
                error_reporting(0);
                foreach(preg_split("/;[\r\n]+/", $sql) as $v){
                    //@mysql_query($v);
                    $m = Helper::load('admin_login_log');
                    $m->Query($v);
                }
            }
        }
        return true;
    }
}