<?php

$method = $_SERVER['REQUEST_METHOD'];

// TODO : Must be called /index.php/API, otherwise error about PATH_INFO missing index
// TODO : /API wont work either
$request = explode('/', trim($_SERVER['PATH_INFO'],'/'));

$input = json_decode(file_get_contents('php://input'),true);

if($request[0] != 'browse' && $request[0] != 'info')
{
	http_response_code(400);
	die('Unexpected action');
}

if($request[0] == 'info' && count($request) == 0)
{
	http_response_code(400);
	die('Expected item id');
}

$objectId = '0';
if(count($request) > 1)
{
    $id = $request[1];

    if($request[0] == 'info' && strpos($id, '$') !== FALSE)
    {
        $lastPosition = strrpos($id, '$');
        $id = substr($id, 0, $lastPosition);
    }
    
	$objectId = $id;
}

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
    
    if($xml === FALSE)
    {
        http_response_code(400);
        die('Invalid response');
    }
	
	$result = $xml->xpath('//Result');
	$content = (string)$result[0][0];
	
	$clean_content = str_ireplace(['dc:', 'upnp:', 'dlna:'], '', $content);
	$content_xml = simplexml_load_string($clean_content);	
	
	$json = json_encode($content_xml);
    
    if($request[0] == 'info')
    {
        // TODO : Not very efficient!!
        $data = json_decode($json, true);
        $json = '';
        
        if(array_key_exists('item', $data))
            foreach($data['item'] as $key => $node)
            {
                if($node['@attributes']['id'] == $request[1])
                {
                    $json = json_encode($node);
                    break;
                }
            }
    }
    
	header('Content-Type: application/json');
	header('Access-Control-Allow-Origin: *');
	echo $json;
}
catch (SoapFault $exception)
{
	http_response_code(400);
	die($exception->getMessage());
}
