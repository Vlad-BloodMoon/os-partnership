<?php
function sendMessage( $url, $data = "" ){
	$curl_handle=curl_init();
	curl_setopt($curl_handle, CURLOPT_CAINFO, "cacert.pem");
	curl_setopt($curl_handle, CURLOPT_URL, $url);
	curl_setopt($curl_handle, CURLOPT_TIMEOUT, 120);
	curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, TRUE);
	if( $data != "" ){
		curl_setopt($curl_handle, CURLOPT_POSTFIELDS, "$data");
	}
	$reply = curl_exec($curl_handle);
	if (!$reply) {
    	//die('Error: "' . curl_error($curl_handle) . '" - Code: ' . curl_errno($curl_handle));
		$reply = "$url: ".'Error: "' . curl_error($curl_handle) . '" - Code: ' . curl_errno($curl_handle);
	}
	curl_close($curl_handle);
	return $reply;
}

?>
