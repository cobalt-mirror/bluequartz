<?php
/*
 * Copyright 2000, 2001 Sun Microsystems, Inc.  All rights reserved.
 * $Id: vsites.php,v 1.22.2.1 2001/12/22 01:00:07 pbaltz Exp $
 *
 * Activity broken down by virtual site or ip address
 */

include_once("ServerScriptHelper.php");
include_once("base/sitestats/ReportHelper.php");
include_once("base/sitestats/selector.inc");

$helper = new ServerScriptHelper();
$factory = $helper->getHtmlComponentFactory('base-sitestats');

$report = new ReportHelper($type, $group, $HTTP_ACCEPT_LANGUAGE, 
			   $helper->getStylePreference());

$title = "vsiteStats";

switch ($io) {
case "i": 
	// incoming
	$regex = "/^B\s+RrBb\s+(\d+)\s+(\S+)\s+(\d+)\s+(\S+)\s+(.+)/";
	$title .= "In";
	break;
	
case "o": 
	// outgoing
	$title .= "Out";
	$regex = "/^f\s+lRrBb\s+\d+\s+(\d+)\s+(\S+)\s+(\d+)\s+(\S+)\s+(.+)/";
    	break;
	
case "io": 
	// both, totaled
    	$regex = "/^B\s+RrBb\s+(\d+)\s+(\S+)\s+(\d+)\s+(\S+)\s+(.+)|^f\s+lRrBb\s+\d+\s+(\d+)\s+(\S+)\s+(\d+)\s+(\S+)\s+(.+)/";
    	break;
	
default: 
	// regular vsite
	if ($type == "ftp") {
		$regex = "/^B\s+RrBb\s+(\d+)\s+(\S+)\s+(\d+)\s+(\S+)\s+(.+)/";
	} else if ($type == "net") {
		$regex = "/^S\s+RrBb\s+(\d+)\s+(\S+)\s+(\d+)\s+(\S+)\s+(.+)/";
		$title = '[[base-netsitestats.vsiteStats]]';
	} else {
		$regex = "/^v\s+RrBb\s+(\d+)\s+(\S+)\s+(\d+)\s+(\S+)\s+(.+)/";
	}
	break;
}

$myData = $report->getData($regex);
if ($nodump && !$myData) {
	$helper->destructor();
	stats_no_data($type, $group, $factory);
} elseif (!$myData) {
	print($helper->toHandlerHtml("/base/sitestats/reportForm.php?type=$type&group=$group&noback=1"));
	$helper->destructor();
	exit;
}

if ($type == 'net') {
	$scrollList = $factory->getScrollList($title, 
					       array(
					       		"ipAddr", "bytes", 
							"pbytes"
					       ));
} else if ($type == 'mail') {
	$scrollList = $factory->getScrollList($title, 
					       array( 
							"{$io}Domain",
							"requests",
							"bytes", "pbytes"
					       ));
} else {
	$scrollList = $factory->getScrollList($title, 
					       array( 
							"site", "requests", 
							"bytes", "pbytes"
					       ));
}

$i18n = $factory->getI18n();
$typestring = $i18n->interpolate("[[base-sitestats." . $type . "usage]]");
$scrollList->setLabel($factory->getLabel($title, false, 
					 array('type' => $typestring)));

$scrollList->setEntryCountHidden(true);
$noDescription = "";
for ($i = 0; $i < count($myData); $i++) {
	$site = preg_replace("#http://(\S+)/#", "\\1", $myData[$i][5]); 
	if ($site == "localhost") { 
		$site = "[[base-sitestats.Home]]"; 
	}
	if ($site == "other") { 
		$site = "[[base-sitestats.other]]"; 
	}
	$myData[$i][1] = number_format($myData[$i][1]);	
	$myData[$i][2] = sprintf("%.1f", $myData[$i][2]);	
	$myData[$i][3] = $report->formatBytes($myData[$i][3]);	
	$myData[$i][4] = sprintf("%.1f", $myData[$i][4]);	
	$pBytesBar = $factory->getBar("test", $myData[$i][4]);
	$pBytesBar->setLabel("&nbsp;{$myData[$i][4]}%");
	if ($type != "net") {
		$scrollList->addEntry(
		    array(
			$factory->getLabel($site, false),
			$factory->getLabel("{$myData[$i][1]} ({$myData[$i][2]}%)", false),
			$factory->getLabel($myData[$i][3], false),
			$pBytesBar
		    ));
	} else {
		$scrollList->addEntry(
		    array(
			$factory->getLabel($site, false),
			$factory->getLabel($myData[$i][3], false),
			$pBytesBar
		    ));
	}			
}

// display page
$page = $factory->getPage();
print $page->toHeaderHtml();
$menu =& stats_build_menu($type, $group, $report, $factory);
print $menu->toHtml();
print "<P></P>\n";
print $scrollList->toHtml();

switch ($io) {
case "o":
	$vsitesi = $factory->getButton("vsites.php?type=$type&group=$group&io=i", "vsites_in");
	print("<BR>" . $vsitesi->toHtml());
	break;
	
case "i":
	$vsiteso = $factory->getButton("vsites.php?type=$type&group=$group&io=o", "vsites_out");
	print("<BR>" . $vsiteso->toHtml());
	break;
	
default:
	break;
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
