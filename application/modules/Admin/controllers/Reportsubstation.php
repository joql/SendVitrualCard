<?php

/*
 * 功能：后台中心－统计报表
 * Author:资料空白
 * Date:20180509
 */

class ReportsubstationController extends AdminBasicController
{
    private $m_order;
    private $m_substation;

    public function init()
    {
        parent::init();
        $this->m_order = $this->load('order');
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

    public function ajaxAction(){
        $list = array();
        $substation_id = $this->CommonAdmin ?: $this->get('substation');

        $get_params = [
            'substation_id' => $substation_id,
        ];
        $where = $this->conditionSQL($get_params);

        $substation_list = $this->m_substation
            ->Field(array('id','bind_url'))
            ->Where($where)
            ->Select();
        foreach ((array)$substation_list as $v){
            //当日统计
            $today_report = array();
            $starttime = strtotime(date("Y-m-d"));
            $endtime = strtotime(date("Y-m-d 23:59:59"));
            $sql ="SELECT count(*) AS total,sum(money) AS shouru FROM `t_order` Where 
isdelete=0 AND status>0 AND addtime>={$starttime} AND 
addtime<={$endtime} AND substation_id={$v['id']}";
            $total_result = $this->m_order->Query($sql);
            if(is_array($total_result) AND !empty($total_result)){
                $today_report['total'] = $total_result[0]['total'];
                $today_report['money'] = number_format($total_result[0]['shouru'],2,".",".");
            }else{
                $today_report['total'] = 0;
                $today_report['money'] = 0.00;
            }
            $data['today_report'] = $today_report;
            //当月统计
            $month_report = array();
            $firstday = date('Y-m-01', strtotime(date("Y-m-d")));
            $lastday = date('Y-m-d', strtotime("{$firstday} +1 month -1 day"));
            $firstday = strtotime($firstday);
            $lastday = strtotime($lastday);

            $sql ="SELECT count(*) AS total,sum(money) AS shouru FROM `t_order` Where isdelete=0 AND status>0 AND addtime>={$firstday} AND addtime<={$lastday} AND substation_id={$v['id']}";
            $total_result = $this->m_order->Query($sql);
            if(is_array($total_result) AND !empty($total_result)){
                $month_report['total'] = $total_result[0]['total'];
                $month_report['money'] = number_format($total_result[0]['shouru'],2,".",".");
            }else{
                $month_report['total'] = 0;
                $month_report['money'] = 0.00;
            }
            $data['month_report'] = $month_report;
            //总计
            $total_report = array();
            $sql ="SELECT count(*) AS total,sum(money) AS shouru FROM `t_order` Where isdelete=0 AND status>0 AND substation_id={$v['id']}";
            $total_result = $this->m_order->Query($sql);
            if(is_array($total_result) AND !empty($total_result)){
                $total_report['total'] = $total_result[0]['total'];
                $total_report['money'] = number_format($total_result[0]['shouru'],2,".",".");
            }else{
                $total_report['total'] = 0;
                $total_report['money'] = 0.00;
            }
            $data['total_report'] = $total_report;
            $list[] = array(
                'url' => $v['bind_url'],
                'order_today' => $today_report['total'],
                'income_today' => $today_report['money'],
                'order_month' => $month_report['total'],
                'income_month' => $month_report['money'],
                'order_all' => $month_report['total'],
                'income_all' => $month_report['money'],
            );
        }
        if(count($substation_list)>0){
            $data = array('code'=>0,'count'=>count($substation_list),'data'=>$list,'msg'=>'有数据');
        }else{
            $data = array('code'=>1001,'count'=>0,'data'=>array(),'msg'=>'无数据');
        }

        Helper::response($data);

    }

    private function conditionSQL($param)
    {
        $condition = "1";
        if (isset($param['substation_id']) AND empty($param['substation_id']) === FALSE) {
            $condition .= " AND `id` = '{$param['substation_id']}'";
        }
        return ltrim($condition, " AND ");
    }
}