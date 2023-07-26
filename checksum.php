<?php

// 将JSON值替换为问号?
function checksum($jsonData){
//$jsonData ='{"find":"TaobaoDetailTransBasicInfoWrapper","filter":{"userId":"949836","channelCode":"qianzhan"},"projection":{"$sortKey":{"$meta":"sortKey"}},"sort":{"ct":-1},"limit":30,"shardVersion":[{"sec":1447,"inc":0},{"$id":"58a6c473b6e31bac07661a9f"}]}';  
	$jsonArray = json_decode($jsonData,true); 	

	foreach ($jsonArray as $key=>$value){  
    
		if($key!='find' && $key!='filter'){
			$jsonArray[$key]='?';
		}
	
		if (is_array($value)) {
			foreach ($value as $k => $v) {
				$jsonArray[$key][$k] = '?'; // 将嵌套数组中的值设置为 '?'
			}
		} else {
			$jsonArray[$key] = '?'; // 将非数组的值设置为 '?'
		}

	}

       // 输出修改后的数据
	return json_encode($jsonArray);	

}

?>

