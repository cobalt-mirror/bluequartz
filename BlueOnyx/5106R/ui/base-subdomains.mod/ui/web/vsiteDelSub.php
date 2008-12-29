<?php
	// Author: Brian N. Smith
	// Copyright 2006, NuOnce Networks, Inc. All rights reserved.
	// $Id: vsiteDelSub.php,v 2.0 2006/08/10 15:31:00 Exp $

	include("ServerScriptHelper.php");
	$serverScriptHelper = new ServerScriptHelper();
	$cceClient = $serverScriptHelper->getCceClient();

	if ( $serverScriptHelper->getAllowed('adminUser') ) {
		// good
	} else if ( $serverScriptHelper->getAllowed('siteAdmin') && $group == $serverScriptHelper->loginUser['site'] ) {
		// good
	} else {
		header("location: /error/forbidden.html");
		return;
	}

	$cceClient->destroy($oid);
	$errors = $cceClient->errors();

	print($serverScriptHelper->toHandlerHtml("/base/subdomains/vsite.php?group=$group", $errors, "base-subdomains"));
	$serverScriptHelper->destructor();
?>
