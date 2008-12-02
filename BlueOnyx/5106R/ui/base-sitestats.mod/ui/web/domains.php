<?php
/*
 * Copyright 2000, 2001 Sun Microsystems, Inc.	All rights reserved.
 * $Id: domains.php,v 1.14 2001/12/15 08:53:09 pbaltz Exp $
 *
 * Domain level reports.
 */

include_once("ServerScriptHelper.php");
include_once("base/sitestats/ReportHelper.php");
include_once('base/sitestats/selector.inc');

$helper = new ServerScriptHelper();

// Only adminUser and siteAdmin should be here
if (!$helper->getAllowed('adminUser') &&
    !($helper->getAllowed('siteAdmin') &&
      $group == $helper->loginUser['site'])) {
  header("location: /error/forbidden.html");
  return;
}

$factory = $helper->getHtmlComponentFactory('base-sitestats');

$report = new ReportHelper($type, $group, $HTTP_ACCEPT_LANGUAGE,
			   $helper->getStylePreference());

$scrollList = $factory->getScrollList("domainStats", 
				      array("domain", "requests", "bytes",
					    "pbytes"));
$i18n = $factory->getI18n();
$typestring = $i18n->interpolate("[[base-sitestats." . $type . "usage]]");
$i18nvars = array('type' => $typestring);
if (isset($group) && ($group != 'server')) {
	$cce = $helper->getCceClient();
	list($vsite) = $cce->find('Vsite', array('name' => $group));
	$vsiteObj = $cce->get($vsite);
	$i18nvars['fqdn'] = $vsiteObj['fqdn'];
} 

switch ($rpt) {
case "o":
	$myData = $report->getData("/^o\s+lRrBb\s+(\d+)\s+(\d+)\s+(\S+)\s+(\d+)\s+(\S+)\s+(.+)/");
	$scrollList = $factory->getScrollList("domainStats",
					      array("domain", "requests", 
					            "bytes", "pbytes"));
	if (isset($group) && ($group != 'server')) {
		$scrollList->setLabel($factory->getLabel("domainStatsVsite", 
							 false, 
							 $i18nvars)); 
	} else {
		$scrollList->setLabel($factory->getLabel("domainStats",
							 false, $i18nvars)); 
	}
	break;
	
case "t":
	$myData = $report->getData("/^t\s+lRrBb\s+(\d+)\s+(\d+)\s+(\S+)\s+(\d+)\s+(\S+)\s+(.+)/");
	$scrollList = $factory->getScrollList("typeStats",
					      array("filetype", "requests",
					            "bytes", "pbytes"));
	if (isset($group) && ($group != 'server')) {
		$scrollList->setLabel($factory->getLabel("typeStatsVsite",
							 false, $i18nvars)); 
	} else {
		$scrollList->setLabel($factory->getLabel("typeStats", 
							 false, $i18nvars)); 
	}
	break;

default:
	break;
}

if (!$myData) {
	$helper->destructor();
	stats_no_data($type, $group, $factory);
}

$scrollList->setEntryCountHidden(true);

for ($i = 0; $i < count($myData); $i++) {
	$myData[$i][2] = number_format($myData[$i][2]);	
	$myData[$i][3] = sprintf("%.1f", $myData[$i][3]);	
	$myData[$i][4] = $report->formatBytes($myData[$i][4]);	
	$myData[$i][5] = sprintf("%.1f", $myData[$i][5]);	
	$pBytesBar = $factory->getBar("test", $myData[$i][5]);
	$pBytesBar->setLabel("&nbsp;{$myData[$i][5]}%");
	$scrollList->addEntry(
	    array(
		$factory->getLabel(indent($myData[$i][1]) . $myData[$i][6], ""),
		$factory->getLabel("{$myData[$i][2]} ({$myData[$i][3]}%)", ""),
		$factory->getLabel($myData[$i][4], ""),
		$pBytesBar
	    ));			
}

if ($_ScrollList_pageIndex_1) {
	$scrollList->setPageIndex( $_ScrollList_pageIndex_1 );
}

$page = $factory->getPage();
print $page->toHeaderHtml();
$menu =& stats_build_menu($type, $group, $report, $factory);
print $menu->toHtml();
print "<P></P>\n";
print $scrollList->toHtml();
print $page->toFooterHtml();

$helper->destructor();

// helper function to indent things
function indent($level)
{
	$spaces = "";
	for ($i = 1; $i < $level; $i++) {
		$spaces .= "--";
	}
	return $spaces;
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
