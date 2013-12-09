<?php
	// Author: Brian N. Smith
	// Copyright 2006, NuOnce Networks, Inc. All rights reserved.
	// $Id: system.php

	include_once("ServerScriptHelper.php");

	$serverScriptHelper = new ServerScriptHelper();
	$cceClient = $serverScriptHelper->getCceClient();

	if (!$serverScriptHelper->getAllowed('serverServerDesktop')) {
		header("location: /error/forbidden.html");
		return;
	}

	if ( $_save == "1" ) {
		$cfg = array(
			"vsite_enabled" => $vsite_enabled,
			"default_max_subdomains" => $default_max_subdomains
		);
		$cceClient->setObject("System", $cfg, "subdomains");
		$errors = $cceClient->errors();
	}

	$factory = $serverScriptHelper->getHtmlComponentFactory("base-subdomains");
	$nuSD = $cceClient->getObject("System", array(), "subdomains");

	$page = $factory->getPage();
	$block = $factory->getPagedBlock("subdomain_header");

	$block->addDivider(
		$factory->getLabel("default_settings"));

	$block->addFormField(
		$factory->getBoolean("vsite_enabled", $nuSD["vsite_enabled"]),
		$factory->getLabel("vsite_enabled"));

	$default_max_subdomains = $factory->getInteger("default_max_subdomains", $nuSD["default_max_subdomains"], 1, 1000);
	$default_max_subdomains->showBounds(1); 
	$default_max_subdomains->setWidth(4);

	$block->addFormField(
		$default_max_subdomains,
		$factory->getLabel("default_max_subdomains"));

	$block->addButton($factory->getSaveButton($page->getSubmitAction()));


	$serverScriptHelper->destructor();

	echo $page->toHeaderHtml();
	echo $block->toHtml();
	echo $page->toFooterHtml();
?>
