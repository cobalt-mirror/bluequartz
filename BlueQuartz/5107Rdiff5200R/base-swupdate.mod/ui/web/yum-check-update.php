<?php
// Author: Brian N. Smith, Michael Stauber
// Copyright 2006-2007, NuOnce Networks, Inc.  All rights reserved.
// Copyright 2006-2007, Stauber Multimedia Design  All rights reserved.
// $Id: yum-check-update.php,v 1.0 2007/12/20 9:02:00 Exp $

include_once("ServerScriptHelper.php");
$serverScriptHelper = new ServerScriptHelper();
$cceClient = $serverScriptHelper->getCceClient();

if (!$serverScriptHelper->getAllowed('managePackage')) {
  header("location: /error/forbidden.html");
  return;
}

$check = date("U");
$cfg = array("check" => $check);

$cceClient->setObject("System", $cfg, "yum");
$errors = $cceClient->errors();

print($serverScriptHelper->toHandlerHtml("/base/swupdate/yum.php", $errors, "base-yum"));
$serverScriptHelper->destructor();
?>
