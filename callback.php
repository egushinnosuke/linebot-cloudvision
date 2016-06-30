<?php
$resMessage="はげ";
$channel="0000000000";
$secret="f0000000000000000000000000000000";
$mid="u90377c66be898f239f5ae3aadac9240f";
$api_key = "AIzaSyDHXq-9WgT9wUHdwoFAf69D9xxaFhgOrxE";
$r = recev_line();
$checked_content = check_content($r["type"] , $r["id"] , $secret , $channel ,$mid);

post_line( $r["from"] , $checked_content , $secret , $channel ,$mid );




function recev_line(){
	$r=json_decode(file_get_contents('php://input') , true);
	try{
		return array( "id"=>$r["result"][0]["content"]["id"] ,  "from"=>$r["result"][0]["content"]["from"] , "type"=>$r["result"][0]["content"]["contentType"] , "body"=>$r["result"][0]["content"]["text"]);
	}catch (Exception $e) {
		return false;
	}
}

function check_content($type, $id, $secret, $channel ,$mid){
	if ($type == "2"){ // 画像だったときの処理
		$gazou = get_image($id, $secret, $channel ,$mid);
		return $gazou;
	}else{ // 画像以外
		return "写真を送ってちょ。\r\n送ってもらった写真を識別するよ";
	}
}

function get_image($id, $secret, $channel ,$mid){

	$url = 'https://trialbot-api.line.me/v1/bot/message/'.$id.'/content';
	$options = array(
		'http' => array(
			'method' => 'GET',
			'ignore_errors' => true,
			'header'=>
			"Content-type: application/json; charset=UTF-8\r\n"
			."X-Line-ChannelID: ".$channel."\r\n"
			."X-Line-ChannelSecret: ".$secret."\r\n"
			."X-Line-Trusted-User-With-ACL: ".$mid
			)
		);

	$context = stream_context_create( $options );
	$result = file_get_contents( $url, false, $context );
	$img = base64_encode($result);
	//$result = json_decode(file_get_contents('php://input') , true);
	//$result_base64 = imagecreatefromstring($result);
	//return $result;
	$scheme = 'data:application/octet-stream;base64,';  
	$size = getimagesize($scheme . $img); 
	$img_label = imgrecgnize($result);
	return $img_label;
}

function imgrecgnize($img){
	// リファラー (許可するリファラーを設定した場合)
	$referer = "https://office-eguchi.co.jp" ;
	//$api_key = "AIzaSyDHXq-9WgT9wUHdwoFAf69D9xxaFhgOrxE";
	// 画像へのパス
	//$image_path = "./image.jpg" ;

	// リクエスト用のJSONを作成
	$json = json_encode( array(
		"requests" => array(
			array(
				"image" => array(
					"content" => base64_encode($img) ,
				) ,
				"features" => array(
					array(
						"type" => "LABEL_DETECTION" ,
						"maxResults" => 5 ,
					) ,
				) ,
			) ,
		) ,
	) ) ;

	// リクエストを実行
	$curl = curl_init() ;
	curl_setopt( $curl, CURLOPT_URL, "https://vision.googleapis.com/v1/images:annotate?key=AIzaSyDHXq-9WgT9wUHdwoFAf69D9xxaFhgOrxE" );//. $api_key ) ;
	curl_setopt( $curl, CURLOPT_HEADER, true ) ; 
	curl_setopt( $curl, CURLOPT_CUSTOMREQUEST, "POST" ) ;
	curl_setopt( $curl, CURLOPT_HTTPHEADER, array( "Content-Type: application/json" ) ) ;
	curl_setopt( $curl, CURLOPT_SSL_VERIFYPEER, false ) ;
	curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true ) ;
	if( isset($referer) && !empty($referer) ) curl_setopt( $curl, CURLOPT_REFERER, $referer ) ;
	curl_setopt( $curl, CURLOPT_TIMEOUT, 15 ) ;
	curl_setopt( $curl, CURLOPT_POSTFIELDS, $json ) ;
	$res1 = curl_exec( $curl ) ;
	$res2 = curl_getinfo( $curl ) ;
	curl_close( $curl ) ;

	// 取得したデータ
	$json = substr( $res1, $res2["header_size"] ) ;	
	$array = json_decode($json, true);

	//$r["result"][0]["content"]["from"]
	$labelAnnotations = $array["responses"][0]["labelAnnotations"];
	$answer = "おそらく・・・\r\n";
	for ($i = 0 ; $i < count($labelAnnotations); $i++ ){
		$answer .= (string)$array["responses"][0]["labelAnnotations"][$i]["description"];
		$answer .= "とか\r\n";
	}
	$answer .= "じゃないかな";
	//return $answer;
	return $answer;
	//return $labelAnnotations[2]["description"];
}

function post_line( $to , $body , $secret , $channel ,$mid ){
	$url = 'https://trialbot-api.line.me/v1/events/';
	$data = array(
		"to"=>array( $to ), 
		"toChannel"=>"1383378250",
		"eventType"=>"138311608800106203",
		"content"=>array(
			"to"=>$to,
			"toType"=>1,
			"contentType"=>1,
			"text"=>$body
			)
		);

	$options = array(
		'http' => array(
			'method' => 'POST',
			'content' => json_encode( $data ),
			'ignore_errors' => true,
			'header'=> 
			"Content-type: application/json; charset=UTF-8\r\n"
			."X-Line-ChannelID: ".$channel."\r\n"
			."X-Line-ChannelSecret: ".$secret."\r\n"
			."X-Line-Trusted-User-With-ACL: ".$mid
			)
		);

	$context = stream_context_create( $options );
	$result = file_get_contents( $url, false, $context );
	$result_json = json_decode($result,true);
	return $result_json;
}