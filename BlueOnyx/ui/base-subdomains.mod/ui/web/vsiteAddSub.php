<?php
	// Author: Brian N. Smith
	// Copyright 2006, NuOnce Networks, Inc. All rights reserved.
	// $Id: vsiteAddSub.php,v 2.0 2006/08/10 15:30:00 Exp $

	include("ServerScriptHelper.php");
	$serverScriptHelper = new ServerScriptHelper();

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
	$a_hosts = preg_split("/,/", $hosts);
	$domain = $site_info["domain"];
	$count = count($a_hosts);

	if ( $count > $vsite["max_subdomains"] ) {
		header("location: /base/subdomains/vsite.php?group=$group");
		return;
	}

	$site = $cceClient->getObject('Vsite', array('name' => $group));

	$page = $factory->getPage();

	$block = $factory->getPagedBlock("vsite_add_header");


	$block->addFormField(
		$factory->getDomainName("hostname"),
		$factory->getLabel("hostname"));

	$block->addFormField(
		$factory->getTextField("fqdn", $site_info["domain"], "r"),
		$factory->getLabel("domainname"));

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

	$block->addFormField(
		$factory->getCompositeFormField(array($rootpath, $webdir), '&nbsp;&nbsp;'),
		$factory->getLabel("webpath")); 

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
