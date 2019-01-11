<?php
//QQ156154611

class ArticleController extends AdminBasicController
{
	private $m_order;
	private $m_products;
	private $m_email_queue;

    private $m_article;
    private $m_substation;
    public function init()
    {
        parent::init();
		$this->m_order = $this->load('order');
		$this->m_products = $this->load('products');
		$this->m_email_queue = $this->load('email_queue');

        $this->m_article = $this->load('article');
        $this->m_substation = $this->load('substation');
    }

    public function indexAction()
    {
        if ($this->AdminUser==FALSE AND empty($this->AdminUser)) {
            $this->redirect('/'.ADMIN_DIR."/login");
            return FALSE;
        }
		$data = array();
		$m_article=$this->m_article->Where(array('isdelete'=>0))->Order(array('sort_num'=>'DESC'))->Select();
		$data['products'] = $m_article;
        $substation_list = $this->m_substation->Select();
        $data['substation_list'] = $substation_list;
		$this->getView()->assign($data);
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
            $product=$this->m_article->SelectByID('',$id);
            $data['m_article'] = $product;
            $substation_list = $this->m_substation->Select();
            $data['substation_list'] = $substation_list;
            $this->getView()->assign($data);
        }else{
            $this->redirect('/'.ADMIN_DIR."/article");
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
        $substation_list = $this->m_substation->Select();
        $data['substation_list'] = $substation_list;
        $this->getView()->assign($data);
    }
    public function editajaxAction()
    {
        $method = $this->getPost('method',false);
        $id = $this->getPost('id',false);
        $title = $this->getPost('title',false);
        $content = $this->getPost('description',false);
        $status = $this->getPost('status',false);
        $substation_id = $this->getPost('substation',false);
        $csrf_token = $this->getPost('csrf_token', false);
        $data = array();

        if ($this->AdminUser==FALSE AND empty($this->AdminUser)) {
            $data = array('code' => 1000, 'msg' => '请登录');
            Helper::response($data);
        }

        if($method AND $title AND $content AND  is_numeric($status) AND $csrf_token){
            if ($this->VerifyCsrfToken($csrf_token)) {
                $m=array(
                    'title'=>$title,
                    'content'=>$content,
                    'status'=>$status,
                    'substation_id'=>$substation_id,
                );
                if($method == 'edit' AND $id>0){
                    $u = $this->m_article->UpdateByID($m,$id);
                    if($u){
                        $data = array('code' => 1, 'msg' => '更新成功');
                    }else{
                        $data = array('code' => 1003, 'msg' => '更新失败');
                    }
                }elseif($method == 'add'){
                    $m['addtime'] = time();
                    $m['isdelete'] = 0;
                    $u = $this->m_article->Insert($m);
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
                $where = 'status=0';//只有未激活的才可以删除
                $delete = $this->m_article->Where($where)->UpdateByID(array('isdelete'=>1),$id);
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
    //ajax
	public function ajaxAction()
	{
        if ($this->AdminUser==FALSE AND empty($this->AdminUser)) {
            $data = array('code' => 1000, 'msg' => '请登录');
			Helper::response($data);
        }

        $title = $this->get('title');
        $substation_id = $this->get('substation');
        $get_param = array(
            'a.title' => [
                'like' => $title
            ],
            'a.substation_id' => $substation_id,
        );

		$page = $this->get('page');
		$page = is_numeric($page) ? $page : 1;

		$limit = $this->get('limit');
		$limit = is_numeric($limit) ? $limit : 10;

		$total=$this->m_article->Where(convertSQL($get_param))->Where('isdelete=0')->Total();

        if ($total > 0) {
            if ($page > 0 && $page < (ceil($total / $limit) + 1)) {
                $pagenum = ($page - 1) * $limit;
            } else {
                $pagenum = 0;
            }
            $limits = "{$pagenum},{$limit}";
			$field = "a.id,a.title,a.substation_id,s.bind_url,a.status,a.isdelete,a.addtime";

			$sql = "select {$field} from t_article a 
                      left join t_substation s on s.id=a.substation_id 
                      where isdelete=0 and ".convertSQL($get_param, true)." 
                      order by a.id desc
                      limit {$limit}";
			//die($sql);
			$items=$this->m_article->Query($sql);
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

	public function viewAction()
    {
        if ($this->AdminUser==FALSE AND empty($this->AdminUser)) {
            $this->redirect('/'.ADMIN_DIR."/login");
            return FALSE;
        }
		$id = $this->get('id');
		if($id AND $id>0){
			$data = array();
			$order = $this->m_order->SelectByID('',$id);
			if(is_array($order) AND !empty($order)){
				$data['order'] = $order;
				$this->getView()->assign($data);
			}else{
				$this->redirect('/'.ADMIN_DIR."/article");
				return FALSE;
			}
		}else{
            $this->redirect('/'.ADMIN_DIR."/article");
            return FALSE;
		}
    }

    private function conditionSQL($param)
    {
        $condition = "1";
        if (isset($param['title']) AND empty($param['title']) ===
            FALSE) {
            $condition .= " AND `title` LIKE '%{$param['title']}%'";
        }
        if (isset($param['substation_id']) AND empty($param['substation_id']) === FALSE) {
            $condition .= " AND `substation_id` = '{$param['substation_id']}'";
        }
        return ltrim($condition, " AND ");
    }
}