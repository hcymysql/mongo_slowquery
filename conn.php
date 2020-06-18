<?php 
//https://github.com/hcymysql/mongo_slowquery
        
     $con = mysqli_connect("127.0.0.1","admin","123456","mongo_slowsql","3306") or die("数据库链接错误" . PHP_EOL .mysqli_connect_error());
     mysqli_query($con,"set names utf8"); 
?>  
