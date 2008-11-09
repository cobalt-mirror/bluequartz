<?php
/*
 * $Id: selector.inc,v 1.8.2.1 2001/12/22 01:00:07 pbaltz Exp $
 * Copyright 2001 Sun Microsystems, Inc.  All rights reserved.
 */

function &stats_build_menu($type, $group, &$report, &$factory)
{
	$links = $report->getLinks();
	$link_action = array();
	$link_text = array();
	$i18n = $factory->getI18n();
	$index = 0;
	
	// add summary link
	$link_action[$index] = "/base/sitestats/summary.php?type=$type&group=$group";
	$link_text[$index] = '[[base-sitestats.summary]]';
	$index++;
	
	// VirtualHost
	if ($report->isApplicable("v", $links)) {
		if ($type == 'mail') {
			$extra = '&io=i';
		}
	
		$link_action[$index] = "/base/sitestats/vsites.php?type=$type&group=$group$extra";
		if ($type == "net") {
			$link_text[$index] = '[[base-sitestats.byIP]]';
		} else if ($type == 'mail') {
			$link_text[$index] = '[[base-sitestats.sendReceiveByDomain]]';
		} else {
			$link_text[$index] = '[[base-sitestats.vsitesReportBut]]';
		}
		$index++;
	}
	
	// Hourly Summary
	if ($report->isApplicable("h", $links)) {
		$link_action[$index] = "/base/sitestats/periodic.php?type=$type&group=$group&period=h";
		$link_text[$index] = '[[base-sitestats.hourly]]';
		$index++;
	}
	
	// by day of the week
	if ($report->isApplicable('d', $links)) {
		$link_action[$index] = "/base/sitestats/periodic.php?type=$type&group=$group&period=d";
		$link_text[$index] = '[[base-sitestats.useByDayOfWeek]]';
		$index++;
	}

	// Daily Report
	if ($report->isApplicable("D", $links)) {
		$link_action[$index] = "/base/sitestats/periodic.php?type=$type&group=$group&period=D";
		$link_text[$index] = '[[base-sitestats.daily]]';
		$index++;
	}
	
	// Weekly Report
	if ($report->isApplicable("W", $links)) {
		$link_action[$index] = "/base/sitestats/periodic.php?type=$type&group=$group&period=W";
		$link_text[$index] = '[[base-sitestats.weekly]]';
		$index++;
	}
	
	// Monthly Report
	if ($report->isApplicable("m", $links)) {
		$link_action[$index] = "/base/sitestats/periodic.php?type=$type&group=$group&period=m";
		$link_text[$index] = '[[base-sitestats.monthly]]';
		$index++;
	}
	
	// Domain Report
	if ($report->isApplicable("o", $links)) {
		$link_action[$index] = "/base/sitestats/domains.php?type=$type&group=$group&rpt=o";
		$link_text[$index] = '[[base-sitestats.requestorDomain]]';
		$index++;
	}
	
	// Request Report
	if ($report->isApplicable("r", $links)) {
		$link_action[$index] = "/base/sitestats/request.php?type=$type&group=$group";
		if ($type == 'mail') {
			$link_text[$index] = 
			    '[[base-sitestats.requestReportButmail]]';
		} else {
			$link_text[$index] = 
			    '[[base-sitestats.requestReportBut]]';
		}
		$index++;
	}
	
	// Filetype Report
	if ($report->isApplicable("t", $links)) {
		$link_action[$index] = "/base/sitestats/domains.php?type=$type&group=$group&rpt=t";
		$link_text[$index] = '[[base-sitestats.typesReportBut]]';
		$index++;
	}
	
	$linkButton = $factory->getMultiButton('selectStat', $link_action, 
					       $link_text);
	
	$customize = $factory->getButton("reportForm.php?type=$type&group=$group&noback=0", 
					 "customizeBut");
	
	$download = $factory->getButton("/base/sitestats/downloadlogs.php?type=$type&group=$group",
					"downloadBut");

	 return($factory->getCompositeFormField(array($linkButton, $customize,
						      $download), "&nbsp;"));
}

// standard exit function when there is no data
function stats_no_data($type, $group, &$factory)
{
	$page = $factory->getPage();
	$i18n = $factory->getI18n();
	$errorMsg = $i18n->get("[[base-sitestats.noDataError]]");		
	print $page->toHeaderHtml();
	$customize = $factory->getButton("/base/sitestats/reportForm.php?type=$type&group=$group", 
					 "customizeBut");
	print $customize->toHtml();
	print "<BR>$errorMsg<BR><BR>";
	print $page->toFooterHtml();
	exit;
}
?>
