<?php
	// Author: Brian N. Smith
	// Copyright 2006, NuOnce Networks, Inc. All rights reserved.
	// $Id: vsiteModHandler.php,v 2.0 2006/08/10 15:32:00 Exp $

	include_once("ServerScriptHelper.php");
	$serverScriptHelper = new ServerScriptHelper();
	$cceClient = $serverScriptHelper->getCceClient();

	if (!$serverScriptHelper->getAllowed('adminUser')) {
		header("location: /error/forbidden.html");
		return;
	}

	$cfg = array(
		"vsite_enabled" => $vsite_enabled,
		"max_subdomains" => $max_subdomains
	);


	$cceClient->setObject("Vsite", $cfg, "subdomains", array('name' => $group));
	$errors = $cceClient->errors();

	print($serverScriptHelper->toHandlerHtml("/base/subdomains/system.php", $errors, "base-subdomains"));
	$serverScriptHelper->destructor();
?>
