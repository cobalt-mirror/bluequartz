<?php
/*
 * Copyright 2000, 2001 Sun Microsystems, Inc.	All rights reserved.
 * $Id: summary.php,v 1.31 2001/12/15 08:53:09 pbaltz Exp $
 *
 * Summary information for all reports, dates, totals, etc
 */

include_once("ServerScriptHelper.php");
include_once("base/sitestats/ReportHelper.php");
include_once("base/sitestats/selector.inc");

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

$myData = $report->getData("/^x\s+(\S+)\s+(\S+)(\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+))?/");

if ($nodump && !$myData) {
	$helper->destructor();
	stats_no_data($type, $group, $factory);
} elseif (!$myData) {
	print($helper->toHandlerHtml("/base/sitestats/reportForm.php?type=$type&group=$group&noback=1"));
	$helper->destructor();
	exit;
}

// compile summary information
$label = "label";
$field = "field";

if ($type == "ftp") {	
	$totals = getInOut($report);
	$entry["NR"][$label] = $factory->getLabel("totalReceived");
	$entry["NR"][$field] = $factory->getTextField("totalReceivedVal", 
						      number_format($totals["in"]), 
						      "r");
	$entry["NS"][$label] = $factory->getLabel("totalSent");
	$entry["NS"][$field] = $factory->getTextField("totalSentVal", 
						      number_format($totals["out"]), 
						      "r");
}

for ($i = 0; $i < count($myData); $i++) {
	switch ($myData[$i][1]) {
	case "PS": 
		$entry["PS"][$label] = $factory->getLabel("reportGenerated");
		$entry["PS"][$field] = $factory->getTimeStamp("generated", mktime($myData[$i][6], $myData[$i][7], 0, $myData[$i][4], $myData[$i][5], $myData[$i][2]), "datetime", "r");			
		break;
		
	case "FR":
		$entry["FR"][$label] = $factory->getLabel("firstRequest");
		$entry["FR"][$field] = $factory->getTimeStamp("first", mktime($myData[$i][6], $myData[$i][7], 0, $myData[$i][4], $myData[$i][5], $myData[$i][2]), "datetime", "r" );  
		break;
		
	case "LR":	
		$entry["LR"][$label] = $factory->getLabel("lastRequest");
		$entry["LR"][$field] = $factory->getTimeStamp("last", mktime($myData[$i][6], $myData[$i][7], 0, $myData[$i][4], $myData[$i][5], $myData[$i][2]), "datetime", "r" );
		break;
		
	case "NH":
		$entry["NH"][$label] = $factory->getLabel("uniqueHosts");
		$entry["NH"][$field] = $factory->getTextField("hosts", 
							      number_format($myData[$i][2]), 
							      "r");
		break;
		
	case "SR":
		$entry["SR"][$label] = $factory->getLabel("successfulRequests");
		$entry["SR"][$field] = $factory->getTextField("requests", 
							      number_format($myData[$i][2]), 
							      "r");
		if (!$myData[$i][2]) {
			$noData = true;
		}
		break;
		
	case "PR":
		$entry["PR"][$label] = $factory->getLabel("pageRequests");	
		$entry["PR"][$field] = $factory->getTextField("pgrequests", 
							      number_format($myData[$i][2]),
							      "r");
		break;
		
	case "FL":
		$entry["FL"][$label] = $factory->getLabel("failedRequests");
		$entry["FL"][$field] = $factory->getTextField("failrequests", 
							      number_format($myData[$i][2]),
							      "r");
		break;
		
	case "NF":
		$entry["NF"][$label] = $factory->getLabel("distinctFiles");
		$entry["NF"][$field] = $factory->getTextField("distinctFiles", 
							      number_format($myData[$i][2]),
							      "r");
		break;
		
	case "BT":
		$entry["BT"][$label] = $factory->getLabel("bytesTransfered");
		$entry["BT"][$field] = $factory->getTextField("bytesTransfered",
							      $report->formatBytes($myData[$i][2]),
							      "r");
		break;
	}
}

if (!$entry["FL"]) {
	$entry["FL"][$label] = $factory->getLabel("failedRequests");
	$entry["FL"][$field] = $factory->getTextField("failedRequests", "0", "r" );
}

// Only report no data on generate report submission (via $nodump GET flag)
if ($nodump && ($noData || !$entry["BT"])) {
	$helper->destructor();
 	stats_no_data($type, $group, $factory);
} else if ($noData || !$entry["BT"]) {
	// If no report is found, send user to the generate report screen
	print($helper->toHandlerHtml("/base/sitestats/reportForm.php?type=$type&group=$group&noback=1"));
	$helper->destructor();
	exit;
}

$block = $factory->getPagedBlock("summaryStats");
$i18n = $factory->getI18n();
$typestring = $i18n->interpolate("[[base-sitestats." . $type . "usage]]");
$i18nvars['type'] = $typestring;

// figure out if this is the server or site stats
if (isset($group) && ($group != 'server')) {
	$cce = $helper->getCceClient();
	list($vsite) = $cce->find('Vsite', array('name' => $group));
	$vsiteObj = $cce->get($vsite);
	$i18nvars['fqdn'] = $vsiteObj['fqdn'];
	$block->setLabel($factory->getLabel('summaryStatsVsite', false, 
					    $i18nvars));
} else {
	$block->setLabel($factory->getLabel('summaryStats', false, $i18nvars));
}

$items = $report->getItems();
for ($i = 0; $i < count($items); $i++) {
	if ($entry[$items[$i]][$field] && $entry[$items[$i]][$label]) {
		$block->addFormField($entry[$items[$i]][$field], 
				     $entry[$items[$i]][$label]);
	}
}

$menu =& stats_build_menu($type, $group, $report, $factory);

// display page
$page = $factory->getPage();
print $page->toHeaderHtml();
print $menu->toHtml();
print "<P></P>\n";
print $block->toHtml();
print $page->toFooterHtml();

$helper->destructor();

// helper functions

// right now, this only works on ftp 
function getInOut($report) 
{
	$totals["in"] = 0;
	$totals["out"] = 0;		
	$myData = $report->getData("/^f\s+lRrBb\s+\d+\s+(\d+)\s+(\S+)\s+(\d+)\s+(\S+)\s+(.+)/");
	for ($i = 0; $i < count($myData); $i++) {
		switch ($myData[$i][5]) {
		case "http://i/":
			$totals["in"] = $myData[$i][1];
			break;
			
		case "http://o/":
			$totals["out"] = $myData[$i][1];
			break;
		
		default:
			break;
		}
	}
	return $totals;
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
