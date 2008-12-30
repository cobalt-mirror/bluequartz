<?php
	// Author: Brian N. Smith
	// Copyright 2006, NuOnce Networks, Inc. All rights reserved.
	// $Id: vsiteAddSub.php,v 2.0 2006/08/10 15:30:00 Exp $

	include("ServerScriptHelper.php");
	$serverScriptHelper =& new ServerScriptHelper();

	$cceClient = $serverScriptHelper->getCceClient();

	if ( $serverScriptHelper->getAllowed('adminUser') ) {
		// allowed in
	} else if ( $serverScriptHelper->getAllowed('siteAdmin') && $group == $serverScriptHelper->loginUser['site'] ) {
		// still good
	} else {
		header("location: /error/forbidden.html");
		return;
	}

	$factory = $serverScriptHelper->getHtmlComponentFactory("base-subdomains", "/base/subdomains/vsiteAddSubHandler.php?group=$group");

	$site_info = $cceClient->getObject('Vsite', array('name' => $group));
	$vsite     = $cceClient->getObject('Vsite', array('name' => $group), "subdomains");

	$hosts = $vsite["entry"];
	$a_hosts = split(",", $hosts);
	$domain = $site_info["domain"];
	$count = count($a_hosts);

	if ( $count > $vsite["max_subdomains"] ) {
		header("location: /base/subdomains/vsite.php?group=$group");
		return;
	}

	$site = $cceClient->getObject('Vsite', array('name' => $group));

	$featurePHP = $cceClient->get($site["OID"], "PHP");
	$featureCGI = $cceClient->get($site["OID"], "CGI");
	$featureSSI = $cceClient->get($site["OID"], "SSI");

	if ( $featurePHP["enabled"] ) { $php_access = "rw"; } else { $php_access = "r"; }
	if ( $featureCGI["enabled"] ) { $cgi_access = "rw"; } else { $cgi_access = "r"; }
	if ( $featureSSI["enabled"] ) { $ssi_access = "rw"; } else { $ssi_access = "r"; }

	$page = $factory->getPage();

	$block = $factory->getPagedBlock("vsite_add_header");


	$host = $factory->getVerticalCompositeFormField(array(
		$factory->getDomainName("hostname"),
		$factory->getLabel("hostname")));

	$domain = $factory->getVerticalCompositeFormField(array(
		$factory->getTextField("fqdn", $site_info["domain"], "r"),
		$factory->getLabel("domainname")));

	$hostdomain = $factory->getCompositeFormField(array($host, $domain), '&nbsp;&nbsp;');

	$webpath = $factory->getMultiChoice("rootpath");
	$webpath->addOption($factory->getOption($site_info["basedir"] . "/vhosts/"));
	$webpath->addOption($factory->getOption($site_info["basedir"] . "/web/"));
	$webpath->setSelected(0, true);

	$rootpath = $factory->getVerticalCompositeFormField(array(
		$webpath,
		$factory->getLabel("rootpath")));

	$webdir = $factory->getVerticalCompositeFormField(array(
		$factory->getTextField("webdir", ""),
		$factory->getLabel("webdir")));

	$rootweb = $factory->getCompositeFormField(array($rootpath, $webdir), '&nbsp;&nbsp;');


	$block->addFormField(
		$hostdomain,
		$factory->getLabel("enterFqdn"));

	$block->addFormField(
		$rootweb,
		$factory->getLabel("webpath")); 






/*
	$block->addFormField(
		$factory->getTextfield("groupname", $site_info["name"], ""),
		$factory->getLabel("group"));

	$block->addFormField(
		$factory->getBoolean("php", "", $php_access),
		$factory->getLabel("php"));

	$block->addFormField(
		$factory->getBoolean("cgi", "", $cgi_access),
		$factory->getLabel("cgi"));

	$block->addFormField(
		$factory->getBoolean("ssi", "", $ssi_access),
		$factory->getLabel("ssi"));
*/
	$block->addButton($factory->getSaveButton($page->getSubmitAction()));
	$block->addButton($factory->getButton("/base/subdomains/vsite.php?group=$group","button_cancel"));
	

	echo $page->toHeaderHtml();
	echo $block->toHtml();
?>
<script language="javascript">
 <!--   
   var myElement = document.form

   function AddAliases() {
      document.form.webdir.value = document.form.hostname.value;
   }
   if ( document.form.hostname.value ) { AddAliases(); }
   document.form.hostname.onchange = AddAliases;
 //-->
</script>
<?php


	echo $page->toFooterHtml();

	$serverScriptHelper->destructor();
?>
