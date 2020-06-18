<?php 
    session_start();

    if($_GET['action'] == "logout"){  
        unset($_SESSION['transmit_tag']);  
	exit('<script>top.location.href="mongo_slowquery.php"</script>');
    }     
?>

<html class="x-admin-sm">
<head>
    <meta http-equiv="Content-Type"  content="text/html;  charset=UTF-8">
    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>MongoDB 慢查询日志分析平台</title>

<style type="text/css">
a:link { text-decoration: none;color: #3366FF}
a:active { text-decoration:blink;color: green}
a:hover { text-decoration:underline;color: #6600FF}
a:visited { text-decoration: none;color: green}
</style>

    <script type="text/javascript" src="xadmin/js/jquery-3.3.1.min.js"></script>
    <script src="xadmin/lib/layui/layui.js" charset="utf-8"></script>
    <script type="text/javascript" src="xadmin/js/xadmin.js"></script>
    <link rel="stylesheet" href="./css/bootstrap.min.css"> 
    <link rel="stylesheet" href="./css/simple-line-icons/css/simple-line-icons.css">
    <link rel="stylesheet" href="./css/font-awesome/css/fontawesome-all.min.css">
    <link rel="stylesheet" href="./css/styles.css">

<script language="javascript">
function TestBlack(TagName){
 var obj = document.getElementById(TagName);
 if(obj.style.display=="block"){
  obj.style.display = "none";
 }else{
  obj.style.display = "block";
 }
}
</script>


<script>
function ss(){
var slt=document.getElementById("select");
if(slt.value==""){
        alert("请选择数据库!!!");
        return false;
}
return true;
}
</script>


</head>

<body style="overflow-y:scroll">
<div class="card">
<div class="card-header bg-light">
    <h1><a href="mongo_slowquery.php?action=logout">
    <img src='./images/mongodb-logo.png'/> 慢查询日志分析平台</a></h1>
</div>

<div class="card-body">
<div class="table-responsive">                  
<form action="" method="post" name="sql_statement" id="form1" onsubmit=" return ss()">
  <div>
    <tr>
        <td><select id="select" name="tag">
	<option value="">选择数据库标签</option>
	<?php
        	require 'conn.php';
		$result = mysqli_query($con,"SELECT tag FROM mongo_status_info group by tag ASC");
		while($row = mysqli_fetch_array($result)){
		    if(isset($_POST['tag']) || isset($_GET['tag'])){
			if($_POST['tag'] == $row[0] || $_GET['tag'] == $row[0]){
			    echo "<option selected='selected' value=\"".$row[0]."\">".$row[0]."</option>"."<br>";
			} else {
			    echo "<option value=\"".$row[0]."\">".$row[0]."</option>"."<br>";
			}
		    } else{ echo "<option value=\"".$row[0]."\">".$row[0]."</option>"."<br>";}
		}
    	?>
        </select><td>
    </tr>
    <input name="submit" type="submit" class="STYLE3" value="搜索" />
    </label>
  </div>
</form>

<?php
    if(isset($_POST['submit'])){
        $tag=$_POST['tag'];
        session_start();
	$_SESSION['transmit_tag']=$tag;
        require 'show.html';
    } else {
		session_start();
	    $tag=$_SESSION['transmit_tag'];
		if(!empty($tag)){
			require 'show.html';
		} else {
			require 'top.html';
		}
    }
?>

<table style='width:100%;font-size:14px;' class='table table-hover table-condensed'>
<thead>                                   
<tr>                                    
<th>抽象语句</th>                                        
<th>主机</th>
<th>数据库标签</th>
<th>端口</th>
<th>查询集合</th>
<th>最近时间</th>
<th>执行时间</th>
<th>执行次数</th>

</tr>
</thead>
<tbody>

<?php
      session_start();
	$select_tag=$_SESSION['transmit_tag'];
    require 'conn.php';
    $perNumber=100; //每页显示的记录数  
    $page=$_GET['page']; //获得当前的页面值  
    $count=mysqli_query($con,"select count(*) from mongo_slow_query_review"); //获得记录总数
    $rs=mysqli_fetch_array($count);   
    $totalNumber=$rs[0];  
    $totalPage=ceil($totalNumber/$perNumber); //计算出总页数  

    if (empty($page)) {  
    	$page=1;  
    } //如果没有值,则赋值1

    $startCount=($page-1)*$perNumber; //分页开始,根据此方法计算出开始的记录 

    if(!empty($select_tag)){
	$sql =  "SELECT a.checksum,a.querysql,a.ip,a.tag,a.dbname,a.port,a.ns,a.origin_user,a.client_ip,a.exec_time,a.last_time,a.count 
		        FROM mongo_slow_query_review a JOIN mongo_status_info b 
                ON a.ip = b.ip AND a.dbname = b.dbname AND a.port = b.port
				WHERE a.tag = '${select_tag}' AND a.querysql <> '[]' AND a.`querysql` <> 'null'
				AND a.last_time >= SUBDATE(NOW(),INTERVAL 31 DAY)
                ORDER BY a.last_time DESC,a.count DESC
				LIMIT $startCount,$perNumber";
    } else {
        $sql = "SELECT a.checksum,a.querysql,a.ip,a.tag,a.dbname,a.port,a.ns,a.origin_user,a.client_ip,a.exec_time,a.last_time,a.count 
		FROM mongo_slow_query_review a JOIN mongo_status_info b 
                ON a.ip = b.ip AND a.dbname = b.dbname AND a.port = b.port
                WHERE a.last_time >= SUBDATE(NOW(),INTERVAL 31 DAY)
 		AND a.querysql <> '[]' AND a.`querysql` <> 'null'
                ORDER BY a.last_time DESC,a.count DESC
                LIMIT $startCount,$perNumber";
    }

    $result = mysqli_query($con,$sql);
	
    echo "<br> 慢查询日志agent采集阀值是每10分钟/次，SQL执行时间（单位：秒）</br>";

    while($row = mysqli_fetch_array($result)) 
    {
    	echo "<tr>";
        echo "<td width='100px' onclick=\"TestBlack('${row['0']}')\">✚  &nbsp;" .substr("{$row['1']}",0,50)
    ."<div id='${row['0']}' style='display:none;'><a href='slowquery_explain.php?checksum={$row['0']}'>" .$row['1'] ."</br></div></a></td>";
	echo "<td>{$row['2']}</td>";
	echo "<td>{$row['3']}</td>";
	echo "<td>{$row['5']}</td>";
	echo "<td>{$row['6']}</td>";
	echo "<td>{$row['10']}</td>";
	echo "<td>{$row['9']}</td>";
	echo "<td>{$row['11']}</td>";
	echo "</tr>";
    }
//end while

    echo "</tbody>";
    echo "</table>";
    echo "</div>";
    echo "</div>";
    echo "</div>";

    $maxPageCount=10; 
    $buffCount=2;
    $startPage=1;
 
    if ($page< $buffCount){
    	$startPage=1;
	}else if($page>=$buffCount  and $page<$totalPage-$maxPageCount){
        	$startPage=$page-$buffCount+1;
	}else{
    		$startPage=$totalPage-$maxPageCount+1;
	}
 
	$endPage=$startPage+$maxPageCount-1;
 
	$htmlstr="";
 
	$htmlstr.="<table class='bordered' border='1' align='center'><tr>";
    	if ($page > 1){
        	$htmlstr.="<td> <a href='mongo_slowquery.php?tag=$tag&page=" . "1" . "'>第一页</a></td>";
        	$htmlstr.="<td> <a href='mongo_slowquery.php?tag=$tag&page=" . ($page-1) . "'>上一页</a></td>";
    	}	

    	$htmlstr.="<td> 总共${totalPage}页</td>";

    	for ($i=$startPage;$i<=$endPage; $i++){ 
        	$htmlstr.="<td><a href='mongo_slowquery.php?tag=$tag&page=" . $i . "'>" . $i . "</a></td>";
    	}
     
    	if ($page<$totalPage){
        	$htmlstr.="<td><a href='mongo_slowquery.php?tag=$tag&page=" . ($page+1) . "'>下一页</a></td>";
        	$htmlstr.="<td><a href='mongo_slowquery.php?tag=$tag&page=" . $totalPage . "'>最后页</a></td>";
 
    	}

	$htmlstr.="</tr></table>";

	echo $htmlstr;

?>

