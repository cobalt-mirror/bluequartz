<?php

// Author: Michael Stauber <mstauber@blueonyx.it>
// Copyright 2006-2010, Team BlueOnyx. All rights reserved.

include_once('ServerScriptHelper.php');
include_once('AutoFeatures.php');
include_once('Capabilities.php');

$serverScriptHelper = new ServerScriptHelper();
$i18n = $serverScriptHelper->getI18n("base-vsite");
$helper = new ServerScriptHelper();

// Only adminUser and siteAdmin should be here
if (!$helper->getAllowed('adminUser') &&
    !($helper->getAllowed('siteAdmin') &&
      $group == $helper->loginUser['site'])) {
  header("location: /error/forbidden.html");
  return;
}

if (!$group) {
  header("location: /error/forbidden.html");
  return;
}

$cceClient = $serverScriptHelper->getCceClient();

// Which site are we editing?
$oids = $cceClient->find('Vsite', array('name' => $group));
if ($oids[0] == '') {
    exit();
}
else {
    $cceClient->set($oids[0], 'PHP', array("prefered_siteAdmin" => $prefered_siteAdmin));
}

$errors = $cceClient->errors();

print($serverScriptHelper->toHandlerHtml("/base/vsite/fileOwner.php?group=$group", $errors));

$serverScriptHelper->destructor();

?>
