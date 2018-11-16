<?php
// Author: Hiaso SHIBUYA
// Copyright 2009, Project BlueQuartz.  All rights reserved.
// $Id: $

include_once("ServerScriptHelper.php");

$serverScriptHelper = new ServerScriptHelper();

// Only serverVsite should be here
if (!$serverScriptHelper->getAllowed('serverVsite')) {
  header("location: /error/forbidden.html");
  return;
}

$cceClient = $serverScriptHelper->getCceClient();

list($sysoid) = $cceClient->find("System");
$vsite = $cceClient->get($sysoid, "Vsite");

if ($maxVsiteField > $vsite['maxVsiteUpper']) {
    $errors[] = new Error('[[base-vsite.VsiteMaxError]]');
} else {
    $vsite_config = array('maxVsite' => $maxVsiteField);

    $ok = $cceClient->set($sysoid, "Vsite", $vsite_config);
    $errors[] = $cceClient->errors();
}
print ($serverScriptHelper->toHandlerHtml("/base/vsite/vsite.php", $errors));

$serverScriptHelper->destructor();
?>
