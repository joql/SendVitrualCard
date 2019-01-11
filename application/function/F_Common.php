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