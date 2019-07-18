<?php

$method = $_SERVER['REQUEST_METHOD'];

// TODO : Must be called /index.php/API, otherwise error about PATH_INFO missing index
// TODO : /API wont work either
$request = explode('/', trim($_SERVER['PATH_INFO'],'/'));

$input = json_decode(file_get_contents('php://input'),true);

if($request[0] != 'browse')
{
	http_response_code(400);
	die('Expected browse');
}

$objectId = '0';
if(count($request) > 1)
	$objectId = $request[1];

$xmlreq='<?xml version="1.0"?>
<s:Envelope xmlns:s="http://schemas.xmlsoap.org/soap/envelope/" s:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/">
  <s:Body>
    <u:Browse xmlns:u="urn:schemas-upnp-org:service:ContentDirectory:1">
      <ObjectID>' . $objectId . '</ObjectID>
      <BrowseFlag>BrowseDirectChildren</BrowseFlag>
      <Filter>*</Filter>
      <StartingIndex>0</StartingIndex>
      <RequestedCount>0</RequestedCount>
      <SortCriteria></SortCriteria>
    </u:Browse>
  </s:Body>
</s:Envelope>';


$action = "urn:schemas-upnp-org:service:ContentDirectory:1#Browse";
$location_URL="http://dnla.services.lan/ctl/ContentDir";
$client = new SoapClient(null, array(
'location' => $location_URL,
'uri'      => $location_URL,
'trace'    => 1,
));


try{
	$search_result = $client->__doRequest($xmlreq,$location_URL,$action,1);
	
	$xml = simplexml_load_string($search_result);
	
	$result = $xml->xpath('//Result');
	$content = (string)$result[0][0];
	
	$clean_content = str_ireplace(['dc:', 'upnp:', 'dlna:'], '', $content);
	$content_xml = simplexml_load_string($clean_content);	
	
	$json = json_encode($content_xml);
	header('Content-Type: application/json');
	header('Access-Control-Allow-Origin: *');
	echo $json;
}
catch (SoapFault $exception)
{
	http_response_code(400);
	die($exception->getMessage());
}
