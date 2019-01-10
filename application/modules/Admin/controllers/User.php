<?php

/*
 * 功能：后台中心－用户
 * Author:资料空白
 * Date:20180509
 */

class UserController extends AdminBasicController
{
	private $m_user;
	private $m_substation;
    public function init()
    {
        parent::init();
		$this->m_user = $this->load('user');
		$this->m_substation = $this->load('substation');
    }

    public function indexAction()
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

	//ajax
	public function ajaxAction()
	{
        if ($this->AdminUser==FALSE AND empty($this->AdminUser)) {
            $data = array('code' => 1000, 'msg' => '请登录');
			Helper::response($data);
        }
		

        $substation = $this->get('substation');
        $name = $this->get('name');

        $where1 = array();
        //查询条件
        $get_params = [
            'substation_id' => $substation,
            'nickname' => $name,
        ];
        $where = $this->conditionSQL($get_params);

		$page = $this->get('page');
		$page = is_numeric($page) ? $page : 1;
		
		$limit = $this->get('limit');
		$limit = is_numeric($limit) ? $limit : 10;
		
		$total=$this->m_user->Where($where1)->Where($where)->Total();
		
        if ($total > 0) {
            if ($page > 0 && $page < (ceil($total / $limit) + 1)) {
                $pagenum = ($page - 1) * $limit;
            } else {
                $pagenum = 0;
            }


            $limits = "{$pagenum},{$limit}";
			$items=$this->m_user->Where($where1)->Where($where)->Limit($limits)->Order(array('id'=>'DESC'))->Select();
			
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
				$delete = $this->m_user->DeleteByID($id);
				$data = array('code' => 1, 'msg' => '删除成功', 'data' => '');
			} else {
                $data = array('code' => 1002, 'msg' => '页面超时，请刷新页面后重试!');
            }
        } else {
            $data = array('code' => 1001, 'msg' => '缺少字段', 'data' => '');
        }
       Helper::response($data);
    }

    private function conditionSQL($param)
    {
        $condition = "1";
        if (isset($param['nickname']) AND empty($param['nickname']) === FALSE) {
            $condition .= " AND `nickname` LIKE '%{$param['nickname']}%'";
        }
        if (isset($param['substation_id']) AND empty($param['substation_id']) === FALSE ) {
            $condition .= " AND `substation_id` = '{$param['substation_id']}'";
        }
        return ltrim($condition, " AND ");
    }
}