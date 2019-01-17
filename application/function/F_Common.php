<?php
/**
 * Created by PhpStorm.
 * User: Joql
 * Date: 2019/1/11
 * Time: 16:58
 */

function convertSQL($param, $alias=false)
{
    $condition = "1";

    foreach ($param as $k=>$v){
        if(!isset($v) or empty($v) === true){
            continue;
        }
        $alias === false && $keys = explode('.',$k);
        if($alias === false && count($keys)>1){
            $k = $keys[1];
            unset($keys);
        }
        if(is_array($v)){
            switch (array_keys($v)[0]){
                case 'like':
                    $condition .= " AND {$k} LIKE '%".current($v)."%'";
                    break;
            }
        }else{
            $condition .= " AND {$k} = '{$v}'";
        }
    }
    return ltrim($condition, " AND ");
}

function getTopDomainhuo(){
    $host=$_SERVER['HTTP_HOST'];

    $matchstr="[^\.]+\.(?:(".$str.")|\w{2}|((".$str.")\.\w{2}))$";
    if(preg_match("/".$matchstr."/ies",$host,$matchs)){
        $domain=$matchs['0'];
    }else{
        $domain=$host;
    }
    return $domain;

}