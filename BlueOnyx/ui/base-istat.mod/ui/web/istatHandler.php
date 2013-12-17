<?php
// Auther: Hisao SHIBUYA
// Copyright 2010, Project BlueQuartz. All rights reserved.
// $Id: $

include("ServerScriptHelper.php");

$serverScriptHelper = new ServerScriptHelper();

// Only serveriStat should be here
if (!$serverScriptHelper->getAllowed('serveriStat')) {
  header("location: /error/forbidden.html");
  return;
}

$cceClient = $serverScriptHelper->getCceClient();

$cceClient->setObject("System",
  array(
    "enabled" => $enableiStatField,
    "serverCode" => $serverCodeField,
    "networkPort" => $networkPortField),
  "iStat");

$errors = $cceClient->errors();

print($serverScriptHelper->toHandlerHtml("/base/istat/istat.php", 
        $errors, "base-istat"));

$serverScriptHelper->destructor();
?>
