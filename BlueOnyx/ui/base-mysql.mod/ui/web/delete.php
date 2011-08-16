<?php
	// Author: Brian N. Smith
	// Copyright 2006, NuOnce Networks, Inc. All rights reserved.
	// $Id: delete.php,v 1.1 2006/08/09 10:18:00 Exp $

	include_once("ServerScriptHelper.php");
	$serverScriptHelper = new ServerScriptHelper();
	$cceClient = $serverScriptHelper->getCceClient();

	if (!$serverScriptHelper->getAllowed('adminUser')) {
		header("location: /error/forbidden.html");
		return;
	}

	$delete = date("U");
	$cfg = array("delete" => $delete);

	$cceClient->setObject("System", $cfg, "mysql");
	$errors = $cceClient->errors();

	print($serverScriptHelper->toHandlerHtml("/base/mysql/mysql.php", $errors, "base-mysql"));
	$serverScriptHelper->destructor();
?>
