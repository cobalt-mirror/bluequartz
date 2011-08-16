<?php
// Author: Rickard Osser
// Copyright 2011, BluApp AB. All rights reserved.
// $Id: yum-removeRepo.php,v 1.0 


include("ServerScriptHelper.php");

$serverScriptHelper = new ServerScriptHelper();
$cceClient = $serverScriptHelper->getCceClient();

if (!$serverScriptHelper->getAllowed('adminUser')) {
  header("location: /error/forbidden.html");
  return;
}

$cceClient->destroy($_REMOVE);

$errors = $cceClient->errors();

print($serverScriptHelper->toHandlerHtml("/base/swupdate/yum.php?_PagedBlock_selectedId_yumgui_head=repos", $errors, false));
$serverScriptHelper->destructor();

?>
