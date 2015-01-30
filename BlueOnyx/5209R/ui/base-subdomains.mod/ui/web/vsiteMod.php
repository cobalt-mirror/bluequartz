<?php
	// Author: Brian N. Smith
	// Copyright 2006, NuOnce Networks, Inc. All rights reserved.
	// $Id: vsiteMod.php,v 2.0 2006/08/10 15:32:00 Exp $

	include_once("ServerScriptHelper.php");

	$serverScriptHelper = new ServerScriptHelper();
	$cceClient = $serverScriptHelper->getCceClient();

	if (!$serverScriptHelper->getAllowed('adminUser')) {
		header("location: /error/forbidden.html");
		return;
	}

	$factory = $serverScriptHelper->getHtmlComponentFactory("base-subdomains", "/base/subdomains/vsiteModHandler.php");
	$nuSD = $cceClient->getObject('Vsite', array('name' => $group), "subdomains");
	$vsite = $cceClient->getObject('Vsite', array('name' => $group));

	$page = $factory->getPage();
	$block = $factory->getPagedBlock("subdomain_header");

	$block->addFormField(
		$factory->getTextField("group", $vsite["name"], ""),
		$factory->getLabel("group"));

	$block->addFormField(
		$factory->getBoolean("vsite_enabled", $nuSD["vsite_enabled"]),
		$factory->getLabel("vsite_enabled"));

	$block->addFormField(
		$factory->getTextField("vsite_name", $vsite["fqdn"], "r"),
		$factory->getLabel("vsite_name"));

	$max_subdomains = $factory->getInteger("max_subdomains", $nuSD["max_subdomains"], 1, 1000);
	$max_subdomains->showBounds(1);
	$max_subdomains->setWidth(4);

	$block->addFormField(
		$max_subdomains,
		$factory->getLabel("max_subdomains"));

	$block->addButton($factory->getSaveButton($page->getSubmitAction()));
	$block->addButton($factory->getButton("/base/subdomains/system.php","button_cancel"));

	echo $page->toHeaderHtml();
	echo $block->toHtml();
	echo $page->toFooterHtml();
?>
