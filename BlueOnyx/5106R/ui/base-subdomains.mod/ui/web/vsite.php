<?php
	// Author: Brian N. Smith
	// Copyright 2006, NuOnce Networks, Inc. All rights reserved.
	// $Id: vsite.php,v 2.0 2006/08/10 15:33:00 Exp $

	include("ServerScriptHelper.php");

	$serverScriptHelper =& new ServerScriptHelper();
	$cceClient = $serverScriptHelper->getCceClient();

	if ( $serverScriptHelper->getAllowed('adminUser') ) {
		$access = "rw";
	} else if ( $serverScriptHelper->getAllowed('siteAdmin') && $group == $serverScriptHelper->loginUser['site'] ) {
		$access  = "r";
	} else {
		header("location: /error/forbidden.html");
		return;
	}

	if ( $_save == "1" ) {
		$cfg = array(
			"vsite_enabled" => $vsite_enabled,
			"max_subdomains" => $max_subdomains
		);
		$cceClient->setObject("Vsite", $cfg, "subdomains", array('name' => $group));
		$errors = $cceClient->errors();
	}

	$factory = $serverScriptHelper->getHtmlComponentFactory("base-subdomains", "/base/subdomains/vsite.php");
	
	$site_info = $cceClient->getObject('Vsite', array('name' => $group));
	$vsite     = $cceClient->getObject('Vsite', array('name' => $group), "subdomains");

	if ( $vsite["max_subdomains"] == 0 ) {
		$sys = $cceClient->getObject("System", array(), "subdomains");
		$cfg["max_subdomains"] = $vsite["max_subdomains"] = $sys["default_max_subdomains"];
		$cceClient->setObject("Vsite", $cfg, "subdomains", array('name' => $group));
	}

	$page = $factory->getPage();

	$block = $factory->getPagedBlock("vsite_header");
	$block->processErrors($serverScriptHelper->getErrors());

	$block->addFormField(
		$factory->getTextField("group", $site_info["name"], ""),
		$factory->getLabel("group"));

	$block->addFormField(
		$factory->getBoolean("vsite_enabled", $vsite["vsite_enabled"], $access),
		$factory->getLabel("vsite_enabled"));

	$block->addFormField(
		$factory->getTextField("vsite_name", $site_info["fqdn"], "r"),
		$factory->getLabel("vsite_name"));

	$max_subdomains = $factory->getInteger("max_subdomains", $vsite["max_subdomains"], 1, 1000, $access);
	$max_subdomains->showBounds(1);
	$max_subdomains->setWidth(4);

	$block->addFormField(
		$max_subdomains,
		$factory->getLabel("max_subdomains"));

	if ( $access == "rw" ) {
		$block->addButton($factory->getSaveButton($page->getSubmitAction()));
	}


	$subdomainOIDs = $cceClient->find("Subdomains", array("group" => $group));

	$subs = $factory->getScrollList("sub_title",
		array("sub_domain", "sub_path", ""));
	$subs->setDefaultSortedIndex(0);
	$subs->setAlignments(array("left", "left", "center"));
	$subs->setLength(1000);

	$domain = $site_info["domain"];

	$count = count($subdomainOIDs);

	if ( $vsite["vsite_enabled"] ) {
		foreach ( $subdomainOIDs as $OID ) {
			$subdomain = $cceClient->get($OID);
			$actions = $factory->getCompositeFormField();
			$fqdn = $subdomain["hostname"] . "." . $domain;
			$delButton = $factory->getRemoveButton("javascript:confirmVsiteDel($OID, '$fqdn')");
			if ( ! $subdomain["isUser"] ) {
				$actions->addFormField($delButton);
			}

			$subs->addEntry(array(
				$factory->getTextField("", $subdomain["hostname"], "r"),
				$factory->getTextField("", $subdomain["webpath"] , "r"),
				$actions
			));
		}
	}

	if ( $vsite["vsite_enabled"] ) {
		if ( $count < $vsite["max_subdomains"] ) {
			$subs->addButton($factory->getAddButton("/base/subdomains/vsiteAddSub.php?group=$group", "new_sub"));
		}
	}

	echo $page->toHeaderHtml();
?>
<script language="javascript">
  <!--
    function confirmVsiteDel ( oid, vhost ) {
      if ( confirm("Do you want to remove " + vhost + "?") ) {
        document.location.href = "/base/subdomains/vsiteDelSub.php?oid=" + oid + "&group=<?=$group?>"; 
      }
    }
  //-->
</script>
<?php
	echo $block->toHtml();
	echo "<br>";
	echo $subs->toHtml();
	echo $page->toFooterHtml();

	$serverScriptHelper->destructor();
?>

	
