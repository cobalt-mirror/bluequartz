<?php
	// Author: Brian N. Smith
	// Copyright 2006, NuOnce Networks, Inc. All rights reserved.
	// $Id: vsiteModSub.php,v 2.0 2006/08/10 15:33:00 Exp $

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

	$site_info = $cceClient->getObject('Vsite', array('name' => $group));

	$featurePHP = $cceClient->get($site_info["OID"], "PHP");
	$featureCGI = $cceClient->get($site_info["OID"], "CGI");
	$featureSSI = $cceClient->get($site_info["OID"], "SSI");

	if ( $featurePHP["enabled"] ) { $php_access = "rw"; } else { $php_access = "r"; $php = "0"; }
	if ( $featureCGI["enabled"] ) { $cgi_access = "rw"; } else { $cgi_access = "r"; $cgi = "0"; }
	if ( $featureSSI["enabled"] ) { $ssi_access = "rw"; } else { $ssi_access = "r"; $ssi = "0"; }

	$base_dir = $site_info["basedir"] . "/vsites/";
	$site_config_file = $base_dir . $site . "/.site.config";
	$fi = fopen($site_config_file, "r");
	$site_config = fgets($fi, 4096);
	fclose($fi);

	list($php, $cgi, $ssi) = preg_split("//", $site_config, 3, PREG_SPLIT_NO_EMPTY);

	$fqdn = $site . "." . $site_info["domain"];


	$factory = $serverScriptHelper->getHtmlComponentFactory("base-subdomains", "/base/subdomains/vsiteModSubHandler.php");
	$page = $factory->getPage();

	$block = $factory->getPagedBlock("vsite_mod_header");

	$block->addFormField(
		$factory->getBoolean("group", $site_info["name"], ""),
		$factory->getLabel("group"));

	$block->addFormField(
		$factory->getTextField("hostname", $site, ""),
		$factory->getLabel("hostname"));

	$block->addFormField(
		$factory->getTextField("vsite_name", $fqdn, "r"),
		$factory->getLabel("vsite_name"));

	$block->addFormField(
		$factory->getBoolean("php", $php, $php_access),
		$factory->getLabel("php"));

	$block->addFormField(
		$factory->getBoolean("cgi", $cgi, $cgi_access),
		$factory->getLabel("cgi"));

	$block->addFormField(
		$factory->getBoolean("ssi", $ssi, $ssi_access),
		$factory->getLabel("ssi"));

	$block->addButton($factory->getSaveButton($page->getSubmitAction()));
	$block->addButton($factory->getButton("/base/subdomains/vsite.php?group=$group","button_cancel"));

	echo $page->toHeaderHtml();
	echo $block->toHtml();
	echo $page->toFooterHtml();

	$serverScriptHelper->destructor();
?>
