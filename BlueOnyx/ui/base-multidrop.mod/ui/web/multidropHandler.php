<?php
 
include("ServerScriptHelper.php");
 
$serverScriptHelper = new ServerScriptHelper();
$cceClient = $serverScriptHelper->getCceClient();

$cceClient->setObject("System",
  array(
    "enable" => $enableField,
    "server" => $serverField,
    "userDomain" => $userDomainField,
    "userName" => $userNameField,
    "password" => $passwordField,
    "proto" => $protoField,
    "interval" => $intervalField),
  "Multidrop");
 
$errors = $cceClient->errors();


print($serverScriptHelper->toHandlerHtml("/base/multidrop/multidrop.php",
        $errors, "base-multidrop")); 

$serverScriptHelper->destructor();

?>

