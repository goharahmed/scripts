#!/bin/php
<?php

/*
REQUIRES FOLLOWING MODULES IN OPENSIPS TO BE LAODED

loadmodule "httpd.so"
modparam("httpd", "ip", "127.0.0.1")
modparam("httpd", "port", 8887)
modparam("httpd", "post_buf_size", 4096)

loadmodule "mi_xmlrpc_ng.so"
modparam("mi_xmlrpc_ng", "http_root", "snet")

OBJECTIVE:
We may use this web-api sort of code to pull w.e info we require from any OpenSIPS BOX

USAGE:
Can be called directly from command line or from the CURL request with parameters.

For example: Pull me a List of OpenSIPS IPs in the Cluster (using Clusterer Modeul) 
Linux-CMD:# php webapi.php clusterer_list
Linux-CMD:# curl http://127.0.0.1/webapi.php?method=clusterer_list

For Example: Find if a particular User is Online on this OpenSIPS
Linux-CMD:# php webapi.php ul_show_contact location gohar@162.242.208.103
OR using CURL
Linux-CMD:# curl http://127.0.0.1/webapi.php?method=ul_show_contact&param1=location&param2=gohar@saevolgo.ca

*/

$opensip_ip = '127.0.0.1';
//Should use the same HTTPD PORT as declared in opensips.cfg
$opensip_xmlrpc_port = '8887';
//Should use the same webroot as declared in mi_xmlrpc_ng module parameters
$xmlrpc_root = 'mybox';

$count = 0;
$method;
$param;
$web_req = 0;
if(isset($_GET) && isset($_GET['method'])){
	foreach($_GET as $key => $value)
	{
		if($key == 'method') {	
			$method = $value;
		}
		if(preg_match("/^param\d$/",$key)) {
			$param[] = $value;

		}
	}
	$web_req = 1;
} else {
	foreach ($argv as $arg) {
		if($count==1)
			$method =  $arg;
		else if($count>1) {  
			$param[] = $arg;
		}
		$count++; 
	}
}
$request = xmlrpc_encode_request($method,$param);
$context = stream_context_create(array('http' => array(
				'method' => "POST",
				'header' => "Content-Type: text/xml",
				'content' => $request
				)));
				
// Dispatchs Request to Local OpenSIPS instance
$server = 'http://'.$opensip_ip.':'.$opensip_xmlrpc_port.'/'.$xmlrpc_root;

//Collect Result from opensips module
$file = file_get_contents($server, false, $context);

//Decode the XML into Array
$response = xmlrpc_decode($file);
if (is_array($response)) {
	
	/* We can filter and sort Output here to do whatever you want to do */
	/* if($method == 'ul_show_contact') {
		Then filter only the required fields to be sent back
	} else if ($method == 'ds_list') {
		Then filter the output to show only the active servers
	}
	*/
	if($web_req == 0) {
		// If it has to be printed on console then recursive Write will loop through the result to create a 1-dimensional output.	
		RecursiveWrite($response);
	}else if($web_req == 1) {
		// If to be printed on Web browser then do anything, I prefereed displaying in JSON format.
		print_r(json_encode($response));
		
		
	}
}

// OpenSIPS might send data back in cascaded arrays, so this recursively checks if element
// of returned array is array or simple variable, If a simple variable then print it else recursively call the same function.
function RecursiveWrite($array) {
    foreach ($array as $key => $vals) {
	if(is_array($vals)){
	        RecursiveWrite($vals);
	} else {
		print "$key $vals\n";
	}
    }
}

?>
