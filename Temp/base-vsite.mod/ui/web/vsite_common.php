<?php
/*
 * Copyright 2001 Sun Microsystems, Inc.  All rights reserved.
 * $Id: vsite_common.php,v 1.11.2.2 2002/04/05 09:55:51 pbaltz Exp $
 *
 * common functions used in more than one ui page
 */

function generate_site_list(&$site_list, &$cce, &$factory, $vsite_oids, 
			    $sites_per_page = 15)
{
	// find all the sites' oids, but only actually get a certain amount per 
	// page at a time
	$maxLength = $sites_per_page;

	$site_list->setColumnWidths(array("50%", "20%", "10%", "10%"));
	$site_list->setEntryNum(count($vsite_oids));
	$site_list->setLength($maxLength);  

	$currentPage = $site_list->getPageIndex();
	$startIndex = $maxLength * $site_list->getPageIndex();

	if ($site_list->getSortOrder() == "descending") {
		$vsite_oids = array_reverse($vsite_oids);
	}

	global $loginName;

	for($i = $startIndex; 
	    ($i < ($startIndex + $maxLength)) && ($i < count($vsite_oids)); 
	    $i++) {
		$currSite = $cce->get($vsite_oids[$i]);
		$title = $factory->i18n->interpolateHtml(
				"[[base-vsite.windowTitle]]", 
				array(
					user => $loginName,
					vsite => $currSite[fqdn]
				));

		$title = urlencode($title);
		$actions = $factory->getCompositeFormField();
		$modifyButton = $factory->getModifyButton(
				"javascript: openSiteMgmt('$currSite[name]', '$currSite[fqdn]', '$title');");


		$actions->addFormField($modifyButton);
	 
		$removeButton = $factory->getRemoveButton(
				"javascript: delSite('$currSite[name]', '$currSite[fqdn]');");

		$actions->addFormField($removeButton);

		if ($currSite['suspend']) {
			$suspend_msg = $factory->i18n->getHtml("listSuspended");
		} else {
			$suspend_msg = "";
		}
		$suspend = $factory->getSimpleText($suspend_msg);
		$hostname = $factory->getSimpleText($currSite["fqdn"]);
		$ipaddr =& $factory->getTextField("fooip$i",
						  $currSite["ipaddr"],
						  $access = "r");
		$ipaddr->setPreserveData(false);

		$site_list->addEntry(array(
					$hostname,
					$ipaddr,
					$suspend,
					$actions
				     ), "", false, $i);
	}

	// all done, pass by reference so nothing to return
}

function getDelSiteScript($i18n, $return_url = "vsiteList.php")
{
	$str_deleting = $i18n->interpolateJs('[[base-vsite.deletingSite]]');
	$help_del_confirm = $i18n->interpolateJs('[[base-vsite.siteRemoveConfirm]]');
	$help_del_confirmnodisk = $i18n->interpolateJs('[[base-vsite.siteRemoveConfirmNoDisk]]');

	return "<SCRIPT LANGUAGE=\"JAVASCRIPT\">
<!--
var saving = '$str_deleting';
var del_confirm_no_disk = '$help_del_confirmnodisk';
var del_confirm = '$help_del_confirm';

function delSite(name, fqdn) 
{
	if (confirm(top.code.string_substitute(del_confirm, '[[VAR.fqdn]]',
					       fqdn))) {
		top.code.info_show( saving, 'wait' );
		location='/base/vsite/vsiteDel.php?page=$return_url&group=' + name;
	}
}

function delSiteNoDisk(oid) 
{
	if (confirm(del_confirm_no_disk)) {
		top.code.info_show( saving, 'wait' );
		location='/base/vsite/vsiteDel.php?page=$return_url&oid=' + oid;
	}
}
//-->
</SCRIPT>";
}

	// create a case insensitive regular expression from the given string
	function makeCaseInsensitive($string)
	{
		return preg_replace("/([[:alpha:]])/e",
			"'[' . strtoupper('$1') . strtolower('$1') . ']'",
			$string);
	}
/*
Copyright (c) 2003 Sun Microsystems, Inc. All  Rights Reserved.

Redistribution and use in source and binary forms, with or without modification, 
are permitted provided that the following conditions are met:

-Redistribution of source code must retain the above copyright notice, this  list of conditions and the following disclaimer.

-Redistribution in binary form must reproduce the above copyright notice, 
this list of conditions and the following disclaimer in the documentation and/or 
other materials provided with the distribution.

Neither the name of Sun Microsystems, Inc. or the names of contributors may 
be used to endorse or promote products derived from this software without 
specific prior written permission.

This software is provided "AS IS," without a warranty of any kind. ALL EXPRESS OR IMPLIED CONDITIONS, REPRESENTATIONS AND WARRANTIES, INCLUDING ANY IMPLIED WARRANTY OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE OR NON-INFRINGEMENT, ARE HEREBY EXCLUDED. SUN MICROSYSTEMS, INC. ("SUN") AND ITS LICENSORS SHALL NOT BE LIABLE FOR ANY DAMAGES SUFFERED BY LICENSEE AS A RESULT OF USING, MODIFYING OR DISTRIBUTING THIS SOFTWARE OR ITS DERIVATIVES. IN NO EVENT WILL SUN OR ITS LICENSORS BE LIABLE FOR ANY LOST REVENUE, PROFIT OR DATA, OR FOR DIRECT, INDIRECT, SPECIAL, CONSEQUENTIAL, INCIDENTAL OR PUNITIVE DAMAGES, HOWEVER CAUSED AND REGARDLESS OF THE THEORY OF LIABILITY, ARISING OUT OF THE USE OF OR INABILITY TO USE THIS SOFTWARE, EVEN IF SUN HAS BEEN ADVISED OF THE POSSIBILITY OF SUCH DAMAGES.

You acknowledge that  this software is not designed or intended for use in the design, construction, operation or maintenance of any nuclear facility.
*/
?>
