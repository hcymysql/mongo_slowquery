<?php

require 'conn.php';

$checksum=$_GET['checksum'];

$get_info= "SELECT a.querysql as querysql,a.dbname as dbname,b.ip as ip,b.user as user,b.pwd as pwd,b.port as port,
				  a.ns as ns,a.origin_user as origin_user,a.client_ip as client_ip
                  FROM mongo_slow_query_review a JOIN mongo_status_info b 
                  ON a.ip = b.ip AND a.dbname = b.dbname AND a.port = b.port
                  WHERE a.checksum  = '$checksum'";

$result = mysqli_query($con,$get_info);


list($querysql,$dbname,$ip,$user,$pwd,$port,$ns,$origin_user,$client_ip) = mysqli_fetch_array($result);

$ns_collection = explode(".",$ns);
$collection = $ns_collection[1];

?>

<html>
<head>
    <meta http-equiv="Content-Type"  content="text/html;  charset=UTF-8">
    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>慢查询日志</title>
    <link rel="stylesheet" href="./css/simple-line-icons/css/simple-line-icons.css">
    <link rel="stylesheet" href="./css/font-awesome/css/fontawesome-all.min.css">
    <link rel="stylesheet" href="./css/styles.css">
</head>

<body style="overflow-y:scroll">

<div class="card">

    <div class="card-header bg-light">
        <font size="5" face="arial" color="blue">来源用户：</font><b><?php echo $origin_user."<br>"; ?></b>
    </div>

    <div class="card-header bg-light">
        <font size="5" face="arial" color="blue">应用端IP：</font><b><?php echo $client_ip."<br>"; ?></b>
    </div>

    <div class="card-header bg-light">
        <font size="5" face="arial" color="blue">执行的SQL：</font><b><?php echo $querysql."<br>"; ?></b>
    </div>

<?php

	try{
		$mongo_conn = new MongoClient("mongodb://$user:$pwd@$ip:$port/$dbname" , array("connectTimeoutMS" => "3000"));
		$db = $mongo_conn->$dbname;
	}
          
	catch(Exception $e) {
		echo '连接报错，错误信息是： ' .$e->getMessage()."\n";
	}

?>	
	
        <div class="card-header bg-light">
        <font size="5" face="arial" color="blue">	
<?php

	$coll_info= $db->command(array('collStats' => $collection) );
	echo '集合'.$collection.' 行数count统计是：'.$coll_info['count']."<br>";
	echo '集合'.$collection.' 表大小Size统计是：'.round($coll_info['size']/(1024*1024*1024),2)."GB"."<br>";
	echo '集合'.$collection.' 索引大小Index Size统计是：'.round($coll_info['totalIndexSize']/(1024*1024*1024),2)."GB"."<br>";

	?>
        </font>
        </div>

	<div class="card-header bg-light">
	<font size="5" face="arial" color="blue">
        <?php  echo '集合'.$collection.'索引信息：'."<br>";?>
	</font>
	</div>	
	
		<div class="card-body">
		<div class="table-responsive">
		<table class="table table-hover">
		<?php
			$getindex=$db->command(array('listIndexes' => $collection ) );
			echo "<pre>";
			print_r($getindex);
			echo "</pre>";
		?>	
		</table>
		</div>
		</div>

	
    <div class="card-header bg-light">
        <font size="5" face="arial" color="blue">Explain执行计划：</font>
    </div>

<div class="card-body">
<div class="table-responsive">
<table class="table table-hover">

<?php

$json=json_decode($querysql, true);
$explain = $db->command(array('explain' => $json ,'verbosity'=>'queryPlanner') );
echo "<pre>";
print_r($explain);
echo "</pre>";

//echo '<br><h3><a href="javascript:history.back(-1);">点击此处返回</a></h3></br>';

?>

<h3>- END-</h3>

</table>
</div>
</div>
</div>
</body>
</html>


