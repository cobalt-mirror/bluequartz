<?php
	// Author: Brian N. Smith
	// Copyright 2006, NuOnce Networks, Inc. All rights reserved.
	// $Id: vsiteModSubHandler.php,v 2.0 2006/08/10 15:32:00 Exp $

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

	if ( ! $php ) { $php = "0"; }
	if ( ! $cgi ) { $cgi = "0"; }
	if ( ! $ssi ) { $ssi = "0"; }

	$update = date("U");

	$mod = date("U");

	$cfg = array(
		"mod" => $mod,
		"hostname" => $hostname,
		"update" => $update,
		"props" => $php . $cgi . $ssi
	);

	$cceClient->setObject("Vsite", $cfg, "subdomains", array('name' => $group));

	print($serverScriptHelper->toHandlerHtml("/base/subdomains/vsite.php?group=$group", $errors, "base-subdomains"));
	$serverScriptHelper->destructor();
?>
