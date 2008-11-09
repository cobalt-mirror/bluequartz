<?php
/*
 * Copyright 2000, 2001 Sun Microsystems, Inc.  All rights reserved.
 * $Id: periodic.php,v 1.16 2001/12/17 20:20:56 pbaltz Exp $
 *
 * Creates reports for hourly, daily activity
 */

include_once("ServerScriptHelper.php");
include_once("base/sitestats/ReportHelper.php");
include_once("uifc/BarGraph.php");
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

switch ($period) {
case "D":
	$regex = "/^D\s+RrBb\s+(\d+)\s+(\S+)\s+(\d+)\s+(\S+)\s+(.+)/";
	$descriptionId = "fullDailyDesc";
	break;
	
case "W":
	$regex = "/^W\s+RrBb\s+(\d+)\s+(\S+)\s+(\d+)\s+(\S+)\s+(.+)/";
	$descriptionId = "fullWeeklyDesc";
	break;
	
case "m":
	$regex = "/^m\s+RrBb\s+(\d+)\s+(\S+)\s+(\d+)\s+(\S+)\s+(.+)/";
	$descriptionId = "fullMonthlyDesc";
	break;
	
case "h":
	$regex = "/^h\s+RrBb\s+(\d+)\s+(\S+)\s+(\d+)\s+(\S+)\s+(\d+)/";
	$descriptionId = "hourlySummaryDesc";
	break;
	
case "d":
default:
	$period = "d";
	$regex = "/^d\s+RrBb\s+(\d+)\s+(\S+)\s+(\d+)\s+(\S+)\s+(.+)/";
	$descriptionId = "dailySummaryDesc";
	break;
}

$i18n = $report->getI18n();
$description = $i18n->get("[[base-{$type}sitestats.{$descriptionId}]]");

$myData = $report->getData($regex);

$total = 0;
$ylabel = "";
$divisor = 1;
for ($i = 0; $i < count($myData); $i++) {
	$total += $myData[$i][3];
}
$avg = $total / count($myData);

if ($avg <= 1000) {
	$divisor = 1;
	$ylabel = $i18n->get("[[base-sitestats.bytes]]");
} else if (($avg > 1000) && ($avg <= 1000000)) {
	$divisor = 1024;
	$ylabel = $i18n->get("[[base-sitestats.kilobyte_abbr]]");
} else if (($avg > 1000000) && ($avg <= 1000000000)) {
	$divisor = 1048576;
	$ylabel = $i18n->get("[[base-sitestats.megabyte_abbr]]");
} else {
	$divisor = 1073741824;
	$ylabel = $i18n->get("[[base-sitestats.gigabyte_abbr]]");
}

$options = array("y_label" => $ylabel,
		 "y_label_skip" => 2,
		 "bar_spacing" => 5,
		 "shadow_depth" => 2,
		 "shadow_color" => 'red' );

$xlabels = array();
for ($i = 0; $i < count($myData); $i++) {
	array_push($xlabels, preg_replace("/\s+/", "/", $myData[$i][5]));
}

$data = array(array());

for ($i = 0; $i < count($myData); $i++) {
	array_push($data[0], $myData[$i][3] / $divisor);
}

$page = $factory->getPage();
$bar = new BarGraph($page, $data);
$bar->setXLabels($xlabels);
$bar->setYLabel($ylabel);
$imagename = time();
chdir("/usr/sausalito/ui/web/base/sitestats/");
$filename = "./img/" . $imagename . ".png"; 
$bar->setFilename($filename);

print $page->toHeaderHtml();
$menu =& stats_build_menu($type, $group, $report, $factory);
print $menu->toHtml();
print "<P></P>\n";
print $bar->toHtml();
print "$description <BR><BR>";
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
