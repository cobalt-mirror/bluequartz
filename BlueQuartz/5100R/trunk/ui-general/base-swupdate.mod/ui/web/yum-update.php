<?php
// Author: Brian N. Smith, Michael Stauber
// Copyright 2006-2007, NuOnce Networks, Inc.  All rights reserved.
// Copyright 2006-2007, Stauber Multimedia Design  All rights reserved.
// $Id: yum-update.php,v 1.0 2007/12/20 9:02:00 Exp $

include("ServerScriptHelper.php");
$serverScriptHelper = new ServerScriptHelper();
$cceClient = $serverScriptHelper->getCceClient();

if (!$serverScriptHelper->getAllowed('adminUser')) {
  header("location: /error/forbidden.html");
  return;
}

$date = date("U");
$cfg = array("update" => $date);

$cceClient->setObject("System", $cfg, "yum");
$errors = $cceClient->errors();

print($serverScriptHelper->toHandlerHtml("/base/swupdate/yum.php", $errors, "base-yum"));
$serverScriptHelper->destructor();
?>
