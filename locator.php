<?php

/**
* Locator test endpoint
* 
* Locator script was created as part of test interview task.
* Required PHP version: >= 5.4
* Required PHP extension enabled: cURL
* 
* @author Vitalii Bondarenko 
* @version  0.2
*/

error_reporting(E_ALL ^ E_NOTICE);
#autoloading
require_once "./bootstrap.php";
#load configguration
require_once "./spyc/spyc.php";
$config = Spyc::YAMLLoad('./config.yaml');

$result = array();
try{
    #retrieve query from incoming $_GET array
    $request = new DispatchRequest($_GET);
    #create and configure new Locator
    $locator = new Locator($config, "xml");
    #call API and obtain a response
    $response = $locator->call($request);
    #parse pure XML response and get required $result array
    $result = $locator->process($response);
}
catch(Exception $e){
    #put exception message as only $result element
    $result["Exception"] = $e->getMessage();
}
//header('Content-type: application/json; charset=utf-8'); 
header('Content-type: text/html; charset=utf-8'); 
if(!empty($result)){
    print json_encode($result, JSON_UNESCAPED_UNICODE);
    //print json_encode($result);
}