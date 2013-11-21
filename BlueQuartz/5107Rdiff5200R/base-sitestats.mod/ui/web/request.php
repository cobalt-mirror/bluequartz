<?php
/*
 * Copyright 2000, 2001 Sun Microsystems, Inc.  All rights reserved.
 * $Id: request.php,v 1.18 2001/12/15 08:53:09 pbaltz Exp $
 *
 * Files requested report
 */

include_once("ServerScriptHelper.php");
include_once("base/sitestats/ReportHelper.php");
include_once("base/sitestats/selector.inc");

$helper = new ServerScriptHelper();

// Only menuServerServerStats and siteAdmin should be here
if (!$helper->getAllowed('menuServerServerStats') &&
    !$helper->getAllowed('manageSite') &&
    !($helper->getAllowed('siteAdmin') &&
      $group == $helper->loginUser['site'])) {
  header("location: /error/forbidden.html");
  return;
}

$factory = $helper->getHtmlComponentFactory('base-sitestats');

$report = new ReportHelper($type, $group, $HTTP_ACCEPT_LANGUAGE, 
			   $helper->getStylePreference());

$title = "requestStats";
$fileCol = "requestFile";

switch ($type) {
case "mail": 
	/*
	 * In and Out are reversed here for $fileCol, but I don't want to 
	 * change it because of translations.  I would probably forget to 
	 * reverse it in the other locale files.
	 */
	if ($io == "o") {
		// outgoing
		$regex = "/^S\s+RrBb\s+(\d+)\s+(\S+)\s+(\d+)\s+(\S+)\s+(.+)/";
		$fileCol .= "In";
		$title .= "Out";
	} else {
		$regex = "/^r\s+lRrBb\s+\d+\s+(\d+)\s+(\S+)\s+(\d+)\s+(\S+)\s+(.+)/";
		$fileCol .= "Out";
		$title .= "In";
	}
	break;
	
default:
	// regular vsite
	$regex = "/^r\s+lRrBb\s+\d+\s+(\d+)\s+(\S+)\s+(\d+)\s+(\S+)\s+(.+)/";
	break;
}

$myData = $report->getData($regex);
if (!$myData) {
	$helper->destructor();
	stats_no_data($type, $group, $factory);
}

$scrollList = $factory->getScrollList("$title", 
				      array("$fileCol", "requestNumber",
					    "bytes", "pbytes"));

$i18n = $factory->getI18n();
$typestring = $i18n->interpolate("[[base-sitestats." . $type . "usage]]");
$i18nvars['type'] = $typestring;
if (isset($group) && $group != 'server') {
	$cce = $helper->getCceClient();
	list($vsite) = $cce->find('Vsite', array('name' => $group));
	$vsiteObj = $cce->get($vsite);
	$i18nvars['fqdn'] = $vsiteObj['fqdn'];
	$scrollList->setLabel($factory->getLabel($title . "Vsite", false, 
						 $i18nvars)); 
} else {
	$scrollList->setLabel($factory->getLabel($title . "Vsite", false,
						 $i18nvars)); 
}
$scrollList->setEntryCountHidden(true);
for ($i = 0; $i < count($myData); $i++) {
	switch ($type) {
	case "web":
		$site = "http:/" . $myData[$i][5]; 
		$site_label = $site;
		break;
		
	case "mail":
		$site = preg_replace("#/(.*)/#", "\\1", $myData[$i][5]);
		$site_label = $site;
		$site = "mailto:" . $site;
		$host = preg_replace("#.*@(.*)#", "\\1", $site);
		break;
		
	case "ftp": 
		$site = preg_replace("#/home/.sites/\d+/([^/])+/(.*)#", "./\\2",
				     $myData[$i][5]);
		$site_label = $site;
		break;
	}

	$myData[$i][1] = number_format($myData[$i][1]);	
	$myData[$i][2] = sprintf("%.1f", $myData[$i][2]);	
	$myData[$i][3] = $report->formatBytes($myData[$i][3]);	
	$myData[$i][4] = sprintf("%.1f", $myData[$i][4]);	
	$pBytesBar = $factory->getBar("test", $myData[$i][4]);
	$pBytesBar->setLabel("&nbsp;{$myData[$i][4]}%");
	if ("ftp" != $type) {
		$scrollList->addEntry(
		    array(
			$factory->getUrl("requestUrl", $site, $site_label,
					 "NEW", "r"),
			$factory->getLabel("{$myData[$i][1]} ({$myData[$i][2]}%)", ""),
			$factory->getLabel($myData[$i][3], ""),
			$pBytesBar
		    ));			
	} else {
		$scrollList->addEntry(
		    array(
			$factory->getLabel("$site", ""),
			$factory->getLabel("{$myData[$i][1]} ({$myData[$i][2]}%)", ""),
			$factory->getLabel($myData[$i][3], ""),
			$pBytesBar
		    ));	
	}
}

$page = $factory->getPage();
print $page->toHeaderHtml();
$menu =& stats_build_menu($type, $group, $report, $factory);
print $menu->toHtml();
print "<P></P>\n";
print $scrollList->toHtml();

if ($type == "mail") {
	if ($io == "o") {
		$request = $factory->getButton("request.php?type=$type&group=$group&io=i",
					       "requestInBut");
	} else {
		$request = $factory->getButton("request.php?type=$type&group=$group&io=o",
					       "requestOutBut");
	}
	print "<BR>" . $request->toHtml();
} 

print $page->toFooterHtml();

$helper->destructor();
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
