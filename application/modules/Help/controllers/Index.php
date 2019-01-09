<?php

/*
 * 功能：帮助文档
 * Author:资料空白
 * Date:20180508
 */

class IndexController extends PcBasicController
{

    private $article;
	public function init()
    {
        parent::init();
		$this->article = $this->load('article');
    }

    public function indexAction()
    {
        $data = array();
        $where = array('isdelete'=>0,'status'=>1);

        $page = $this->get('page');
        $page = is_numeric($page) ? $page : 1;

        $limit = $this->get('limit');
        $limit = is_numeric($limit) ? $limit : 10;

        $total=$this->article->Where($where)->Total();
        if ($total > 0) {
            if ($page > 0 && $page < (ceil($total / $limit) + 1)) {
                $pagenum = ($page - 1) * $limit;
            } else {
                $pagenum = 0;
            }

            $limits = "{$pagenum},{$limit}";
            $items=$this->article->Where($where)->Limit($limits)->Order(array('id'=>'DESC'))->Select();
            if (empty($items)) {
                $arr = [];
            } else {
                $arr = $items;
            }
        } else {
            $arr = [];
        }
        //var_dump($arr);
        $data['data'] = $arr;
		$data['title'] = "帮助中心";
        $this->getView()->assign($data);
    }
    public function idAction()
    {
        $pid = $this->get('pid');
        if($pid AND is_numeric($pid) AND $pid>0){
            $data = array();
            $where = array('isdelete'=>0,'status'=>1,'id'=>$pid);

            $items=$this->article->Where($where)->Order(array('id'=>'DESC'))->SelectOne();
            if (empty($items)) {
                $this->redirect("/product/?error=没有该文章1");
                return FALSE;
            } else {
                $arr = $items;
            }
            //var_dump($arr);
            $data['data'] = $arr;
            $data['title'] = $arr['title']."_文章详情";
            $this->getView()->assign($data);
        }else{
            $this->redirect("/product/?error=没有该文章");
            return FALSE;
        }
    }
}