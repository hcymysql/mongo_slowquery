<?php

    require 'conn.php';

    $top_sql="SELECT a.dbname AS dbname ,SUM(a.count) AS top_count 
	      FROM mongo_slow_query_review a JOIN mongo_status_info b 
              ON a.ip = b.ip AND a.dbname = b.dbname AND a.port = b.port
	      WHERE a.querysql <> '[]' AND a.querysql <> 'null'
	      AND a.last_time >= SUBDATE(NOW(),INTERVAL 14 DAY)
              GROUP BY a.dbname DESC";

    $result_echarts = mysqli_query($con,"$top_sql");

    $top_data="";
    $array= array();

    class User{
    	public $dbname;
    	public $top_count;
    }

    while($row = mysqli_fetch_array($result_echarts,MYSQL_ASSOC)){
    	$user=new User();
    	$user->dbname = $row['dbname'];
    	$user->top_count = $row['top_count'];
    	$array[]=$user;
    }

    $top_data=json_encode($array);
    echo $top_data;

?>

