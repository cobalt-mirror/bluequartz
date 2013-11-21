<?php
	// Author: Brian N. Smith
	// Copyright 2006, NuOnce Networks, Inc. All rights reserved.
	// $Id: mysqlHandler.php,v 1.1 2006/08/09 10:19:00 Exp $

	include_once("ServerScriptHelper.php");
	$serverScriptHelper = new ServerScriptHelper();
	$cceClient = $serverScriptHelper->getCceClient();

	if (!$serverScriptHelper->getAllowed('adminUser')) {
		header("location: /error/forbidden.html");
		return;
	}
	else {
	    print($serverScriptHelper->toHandlerHtml("/base/mysql/mysql.php?redirected=1", $errors, "base-mysql"));
	}
	$serverScriptHelper->destructor();
?>


