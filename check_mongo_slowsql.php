<?php

error_reporting(E_USER_WARNING | E_USER_NOTICE);
ini_set('date.timezone','Asia/Shanghai');
require 'conn.php';
require 'checksum.php';

$list = mysqli_query($con,"select ip,tag,user,pwd,port,dbname,threshold_slow_ms from mongo_status_info");

while( list($ip,$tag,$user,$pwd,$port,$dbname,$threshold_slow_ms) = mysqli_fetch_array($list))
{
	
	try{
		$mongo_conn = new MongoClient("mongodb://$user:$pwd@$ip:$port/$dbname" , array("connectTimeoutMS" => "3000"));
	}
          
	catch(Exception $e) {
		echo '连接报错，错误信息是： ' .$e->getMessage()."\n";
	}

	$collection = $mongo_conn->selectCollection("$dbname","system.profile");

/*
查询慢日志
db.getSiblingDB("samples").system.profile.find({millis:{$gte:1000}},{millis:1,ns:1,query:1,ts:1,client:1,user:1}).sort({ts:-1}).limit(1000)
*/
	$where=array("millis" => array('$gte' => (int)$threshold_slow_ms ));
	$fields=array("millis" => 1,"ns" => 1,"query" => 1,"ts" => 1, "client" => 1, "user" => 1);
	$cursor = $collection->find($where,$fields)->limit(1000);
	$cursor = $cursor->sort(array("ts" => -1));

	$is_data = "SELECT a.id AS checksum FROM mongo_slow_query_review a JOIN mongo_status_info b 
					ON a.ip = b.ip AND a.dbname = b.dbname AND a.port = b.port
					WHERE a.ip = '$ip' and a.tag='$tag' and a.dbname='$dbname' and a.port='$port' order by a.id DESC LIMIT 1";

	$result = mysqli_query($con, $is_data);			
	
	if(mysqli_num_rows($result) == 0){ 
		echo 'mongo_slow_query_review表里找不到慢日志数据，开始初始化抓取慢SQL'."\n";
		init();
		
	} else {
		echo  '开始捕获增量慢SQL日志......'."\n";
		incr();
	}	
	echo '----------------------------------------------------------'."\n";
	echo '- END -'."\n";
} //end while


function init(){
	global  $cursor,$con;
	global $ip,$tag,$user,$pwd,$port,$dbname;
	
	foreach ($cursor as $doc) {
	$querysql = json_encode($doc['query']);
	$exec_time = round($doc['millis']/1000,2); //单位转换为秒
	$lt = get_object_vars($doc['ts']);
	$last_time = $lt['sec'];
	$ns = $doc['ns'];
	$origin_user = $doc['user'];
	$client_ip = $doc['client'];
	
	$ltd = new MongoDate($last_time);
	$last_time_cst = $ltd->toDateTime()->format('Y-m-d H:i:s');

	//print_r($doc); //打开调试
	
	$sql = "SELECT a.last_time AS last_time, a.checksum AS checksum FROM mongo_slow_query_review a JOIN mongo_status_info b 
    ON a.ip = b.ip AND a.dbname = b.dbname AND a.port = b.port
    WHERE a.dbname='$dbname' order by a.id DESC LIMIT 1";
    
	$result = mysqli_query($con,$sql);
	$row = mysqli_fetch_assoc($result);
		
	//入库
	      $fingerprint = checksum($querysql);
		$checksum = md5($fingerprint.$ns);
		//echo '$row[\'checksum\']: '. $row['checksum'] . "\n";
		//echo '$checksum: ' .$checksum  . "\n"; 
		if ($row['checksum'] == $checksum){
		    $insert_slowsql ="REPLACE INTO 	mongo_slow_query_review
								  (checksum,fingerprint,querysql,ip,tag,dbname,port,ns,origin_user,client_ip,exec_time,last_time)
								  VALUES('$checksum','$fingerprint','$querysql','$ip','$tag','$dbname','$port','$ns','$origin_user','$client_ip','$exec_time','$last_time_cst')";	    
		} else {
		    $insert_slowsql ="INSERT INTO  mongo_slow_query_review
								  (checksum,fingerprint,querysql,ip,tag,dbname,port,ns,origin_user,client_ip,exec_time,last_time)
								  VALUES('$checksum','$fingerprint','$querysql','$ip','$tag','$dbname','$port','$ns','$origin_user','$client_ip','$exec_time','$last_time_cst') ON DUPLICATE KEY UPDATE querysql='$querysql',exec_time=$exec_time,last_time='$last_time_cst',count=count+1";			
		}
	
		//echo '$insert_slowsql: '. $insert_slowsql . "\n";
								
		if (mysqli_query($con, $insert_slowsql)) {
				echo "{$ip}:{$tag} 监控数据采集入库成功\n";
				if ($row['checksum'] == $checksum){
					$count = "UPDATE mongo_slow_query_review SET count=count+1 order by id desc limit 1";
					mysqli_query($con,$count);
				}
				echo "---------------------------\n\n";
		} else {
				echo "{$ip}:{$tag}	监控数据采集入库失败\n";
				echo "Error: " . $insert_slowsql . "\n" . mysqli_error($con);
            }
	}   // end foreach
}

############################################################
// 增量
function incr(){
	global  $cursor,$con;
	global $ip,$tag,$user,$pwd,$port,$dbname;
	
	foreach ($cursor as $doc) {
	$querysql = json_encode($doc['query']);
	$exec_time = round($doc['millis']/1000,2); //单位转换为秒
	$lt = get_object_vars($doc['ts']);
	$last_time = $lt['sec'];
	$ns = $doc['ns'];
	$origin_user = $doc['user'];
	$client_ip = $doc['client'];
	
	$ltd = new MongoDate($last_time);
	$last_time_cst = $ltd->toDateTime()->format('Y-m-d H:i:s');

	//print_r($doc); //打开调试
	
	$sql = "SELECT a.last_time AS last_time, a.checksum AS checksum FROM mongo_slow_query_review a JOIN mongo_status_info b 
    ON a.ip = b.ip AND a.dbname = b.dbname AND a.port = b.port
    WHERE a.dbname='$dbname' order by a.last_time DESC LIMIT 1";
	
	$result = mysqli_query($con,$sql);
	$row = mysqli_fetch_assoc($result);
	
	$slt=isset($row['last_time']) ? $row['last_time']: '1997-01-01 00:00:00';
	$d = new MongoDate(strtotime($slt."+8 hour"));

/*	
	echo '$last_time :'.$last_time."\n";
	echo '$d->sec :'.$d->sec."\n";
*/
	
	if ($last_time > $d->sec) { //有新的慢SQL日志
	//入库
	      $fingerprint = checksum($querysql);
		$checksum = md5($fingerprint.$ns);
		//echo '$row[\'checksum\']: '. $row['checksum'] . "\n";
		//echo '$checksum: ' .$checksum  . "\n"; 
		if ($row['checksum'] == $checksum){
		    $insert_slowsql ="REPLACE INTO 	mongo_slow_query_review
								  (checksum,fingerprint,querysql,ip,tag,dbname,port,ns,origin_user,client_ip,exec_time,last_time)
								  VALUES('$checksum','$fingerprint','$querysql','$ip','$tag','$dbname','$port','$ns','$origin_user','$client_ip','$exec_time','$last_time_cst')";	    
		} else {
		    $insert_slowsql ="INSERT INTO  mongo_slow_query_review
								  (checksum,fingerprint,querysql,ip,tag,dbname,port,ns,origin_user,client_ip,exec_time,last_time)
								  VALUES('$checksum','$fingerprint','$querysql','$ip','$tag','$dbname','$port','$ns','$origin_user','$client_ip','$exec_time','$last_time_cst') ON DUPLICATE KEY UPDATE querysql='$querysql',exec_time=$exec_time,last_time='$last_time_cst',count=count+1";			
		}
	
		//echo '$insert_slowsql: '. $insert_slowsql . "\n";
								
		if (mysqli_query($con, $insert_slowsql)) {
				echo "{$ip}:{$tag}	监控数据采集入库成功\n";
				if ($row['checksum'] == $checksum){
					$count = "UPDATE mongo_slow_query_review SET count=count+1 order by id desc limit 1";
					mysqli_query($con,$count);
				}
				echo "---------------------------\n\n";
		} else {
				echo "{$ip}:{$tag}	监控数据采集入库失败\n";
				echo "Error: " . $insert_slowsql . "\n" . mysqli_error($con);
            }
	}   else { echo "{$ip}:{$tag}	没有检测到有增量慢SQL"."\n"; }

    }	// end foreach
}

?>
