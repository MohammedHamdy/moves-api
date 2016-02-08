<?php
header("Content-Type:application/json");
include("function.php");
	//request (URL)
	if(!empty ($_GET['name'])){
	$_GET['name'] = str_replace('+', '', $_GET['name']);

		$name = $_GET['name'];
		
		$activity = get_activity($name);
		if(empty($activity))
			// no data
		response(200,"No data",NULL);
		else
			response(200,"data founded",$activity);
	}
	else{
		// invalid
		response(400,"Invalid request",NULL);
	}
	function response($status,$status_message,$data){
		header("HTTP/1.1 $status $status_message");
		$response['status'] = $status;
		$response['status_message'] = $status_message;
		$response['data'] = $data;
		
		$json_response = json_encode($response);
		echo $json_response;
		
	}
?>