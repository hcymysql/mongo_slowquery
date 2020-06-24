<?php

// 将JSON值替换为问号?
function checksum($jsonData){

	$jsonArray = json_decode($jsonData,true); 	

	foreach ($jsonArray as $key=>$value){  
    
		if($key!='find' && $key!='filter'){
			$jsonArray[$key]='?';
		}
	
		foreach($value as $k=>$v){
			$jsonArray[$key][$k]='?';
		}

	}

       // 输出修改后的数据
	return json_encode($jsonArray);	

}

?>
