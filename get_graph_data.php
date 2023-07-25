<?php

    session_start();
    $select_tag=$_SESSION['transmit_tag'];

    require 'conn.php';	

    $graph_sql="SELECT a.last_time as last_time,a.exec_time as exec_time
		FROM mongo_slow_query_review a JOIN mongo_status_info b 
                ON a.ip = b.ip AND a.dbname = b.dbname AND a.port = b.port
		WHERE a.tag = '${select_tag}' AND a.querysql <> '[]' AND a.querysql <> 'null'
		AND a.last_time >= SUBDATE(NOW(),INTERVAL 14 DAY)
                ORDER BY a.last_time ASC";

  $result_echarts = mysqli_query($con,$graph_sql);

    $data="";
    $array=array();

    class User{
    	public $last_time;
    	public $exec_time;
    }

    while($row = mysqli_fetch_array($result_echarts,MYSQLI_ASSOC)){
    	$user=new User();
    	$user->last_time = $row['last_time'];
    	$user->exec_time = $row['exec_time'];
    	$array[]=$user;
    }

    $data=json_encode($array);
    echo $data;

?>

