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

function get_file($url,$name,$folder = './')
{
    set_time_limit((24 * 60) * 60);
    // 设置超时时间
    $destination_folder = $folder . '/';
    // 文件下载保存目录，默认为当前文件目录
    if (!is_dir($destination_folder)) {
        // 判断目录是否存在
        mkdirs($destination_folder);
    }
    $newfname = $destination_folder.$name;
    // 取得文件的名称
    $file = fopen($url, 'rb');
    // 远程下载文件，二进制模式
    if ($file) {
        // 如果下载成功
        $newf = fopen($newfname, 'wb');
        // 远在文件文件
        if ($newf) {
            // 如果文件保存成功
            while (!feof($file)) {
                // 判断附件写入是否完整
                fwrite($newf, fread($file, 1024 * 8), 1024 * 8);
            }
        }
    }
    if ($file) {
        fclose($file);
    }
    if ($newf) {
        fclose($newf);
    }
    return true;
}

function mkdirs($path, $mode = '0777')
{
    if (!is_dir($path)) {
        // 判断目录是否存在
        mkdirs(dirname($path), $mode);
        // 循环建立目录
        mkdir($path, $mode);
    }
    return true;
}

//循环删除目录和文件函数
function delDirAndFile($dirName)
{
    if ($handle = opendir("$dirName")) {
        while (false !== ($item = readdir($handle))) {
            if ($item != "." && $item != "..") {
                if (is_dir("$dirName/$item")) {
                    delDirAndFile("$dirName/$item");
                } else {
                    unlink("$dirName/$item");
                }
            }
        }
        closedir($handle);
        //rmdir($dirName);
    }
}

//循环删除目录和文件函数
function delsql($dirName)
{
    if ($handle = opendir("$dirName")) {
        while (false !== ($item = readdir($handle))) {
            if ($item != "." && $item != "..") {
                if (is_dir("$dirName/$item")) {
                    delDirAndFile("$dirName/$item");
                } else {
                    unlink("$dirName/$item");
                }
            }
        }
        closedir($handle);
        rmdir($dirName);
    }
}