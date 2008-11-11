<?php
// Author: Brian N. Smith
// Copyright 2007, NuOnce Networks, Inc.  All rights reserved.
// $Id: convert2passwdHandler.php, v1.0 2007/12/14 09:48:00 Exp $bsmith

include_once("ServerScriptHelper.php");
$serverScriptHelper = new ServerScriptHelper();
$cceClient = $serverScriptHelper->getCceClient();

//Only users with adminUser capability should be here
if (!$serverScriptHelper->getAllowed('adminUser')) {
  header("location: /error/forbidden.html");
  return;
}

$cfg = array("convert" => "1", "runconvert" => "1");

$cceClient->setObject("System", $cfg, "convert2passwd");
$errors = $cceClient->errors();

print($serverScriptHelper->toHandlerHtml("/base/user/convert2passwd.php", $errors, "base-convert2passwd"));
$serverScriptHelper->destructor();

?>
