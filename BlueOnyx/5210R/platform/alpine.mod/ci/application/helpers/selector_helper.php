<?php

/*
 * $Id: selector_helper.php
 *
 * Utility functions for generating reports
 */

function &stats_build_menu_tabs($type, $group, &$report, &$factory) {
	$links = $report->getLinks();
	$link_action = array();
	$link_text = array();
	$i18n = $factory->getI18n();
	
	// add summary link
	$link_text["summary"] = '[[base-sitestats.summary]]';
	
	// VirtualHost
	$extra = '';
	if ($report->isApplicable("v", $links)) {
		if ($type == 'mail') {
			$extra = '&io=i';
		}
	
		
		if ($type == "net") {
			$link_text["byIP"] = '[[base-sitestats.byIP]]';
		} else if ($type == 'mail') {
			$link_text["sendReceiveByDomain"] = '[[base-sitestats.sendReceiveByDomain]]';
		} else {
			$link_text["vsitesReportBut"] = '[[base-sitestats.vsitesReportBut]]';
		}
	}
	
	// Hourly Summary
	if ($report->isApplicable("h", $links)) {
		$link_text["hourly"] = '[[base-sitestats.hourly]]';
	}
	
	// by day of the week
	if ($report->isApplicable('d', $links)) {
		$link_text["useByDayOfWeek"] = '[[base-sitestats.useByDayOfWeek]]';
	}

	// Daily Report
	if ($report->isApplicable("D", $links)) {
		$link_text["daily"] = '[[base-sitestats.daily]]';
	}
	
	// Weekly Report
	if ($report->isApplicable("W", $links)) {
		$link_text["weekly"] = '[[base-sitestats.weekly]]';
	}
	
	// Monthly Report
	if ($report->isApplicable("m", $links)) {
		$link_text["monthly"] = '[[base-sitestats.monthly]]';
	}
	
	// Domain Report
	if ($report->isApplicable("o", $links)) {
		$link_text["requestorDomain"] = '[[base-sitestats.requestorDomain]]';
	}
	
	// Request Report
	if ($report->isApplicable("r", $links)) {
		if ($type == 'mail') {
			$link_text["requestReportButmail"] = '[[base-sitestats.requestReportButmail]]';
		} else {
			$link_text["requestReportBut"] = '[[base-sitestats.requestReportBut]]';
		}
	}
	
	// Filetype Report
	if ($report->isApplicable("t", $links)) {
		$link_text["typesReportBut"] = '[[base-sitestats.typesReportBut]]';
	}
	
	return $link_text;
}

function &stats_build_menu($type, $group, &$report, &$factory) {
	$links = $report->getLinks();
	$link_action = array();
	$link_text = array();
	$i18n = $factory->getI18n();
	$index = 0;
	
	// add summary link
	$link_action[$index] = "/sitestats/summary?type=$type&group=$group";
	$link_text[$index] = '[[base-sitestats.summary]]';
	$index++;
	
	// VirtualHost
	$extra = '';
	if ($report->isApplicable("v", $links)) {
		if ($type == 'mail') {
			$extra = '&io=i';
		}
	
		$link_action[$index] = "/sitestats/vsites?type=$type&group=$group$extra";
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
		$link_action[$index] = "/sitestats/summary?type=$type&group=$group&period=h";
		$link_text[$index] = '[[base-sitestats.hourly]]';
		$index++;
	}
	
	// by day of the week
	if ($report->isApplicable('d', $links)) {
		$link_action[$index] = "/sitestats/summary?type=$type&group=$group&period=d";
		$link_text[$index] = '[[base-sitestats.useByDayOfWeek]]';
		$index++;
	}

	// Daily Report
	if ($report->isApplicable("D", $links)) {
		$link_action[$index] = "/sitestats/summary?type=$type&group=$group&period=D";
		$link_text[$index] = '[[base-sitestats.daily]]';
		$index++;
	}
	
	// Weekly Report
	if ($report->isApplicable("W", $links)) {
		$link_action[$index] = "/sitestats/summary?type=$type&group=$group&period=W";
		$link_text[$index] = '[[base-sitestats.weekly]]';
		$index++;
	}
	
	// Monthly Report
	if ($report->isApplicable("m", $links)) {
		$link_action[$index] = "/sitestats/summary?type=$type&group=$group&period=m";
		$link_text[$index] = '[[base-sitestats.monthly]]';
		$index++;
	}
	
	// Domain Report
	if ($report->isApplicable("o", $links)) {
		$link_action[$index] = "/sitestats/domains?type=$type&group=$group&rpt=o";
		$link_text[$index] = '[[base-sitestats.requestorDomain]]';
		$index++;
	}
	
	// Request Report
	if ($report->isApplicable("r", $links)) {
		$link_action[$index] = "/sitestats/request?type=$type&group=$group";
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
		$link_action[$index] = "/sitestats/domains?type=$type&group=$group&rpt=t";
		$link_text[$index] = '[[base-sitestats.typesReportBut]]';
		$index++;
	}
	
	$linkButton = $factory->getMultiButton('selectStat', $link_action, $link_text);
	
	$download = $factory->getButton("/sitestats/downloadlogs?type=$type&group=$group", "downloadBut");

	$out = $factory->getCompositeFormField(array($linkButton, $download), "&nbsp;");

	return $out;
}

// standard exit function when there is no data
function stats_no_data($type, $group, &$factory) {
	$page = $factory->getPage();
	$i18n = $factory->getI18n();
	$errorMsg = $i18n->get("[[base-sitestats.noDataError]]");		
	$out =  "<BR>&nbsp;&nbsp;$errorMsg<BR><BR>";
	return $out;
}

// right now, this only works on ftp 
function getInOut($report) {
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

function getTrafficStats ($period, $type, &$report, &$factory) {

		$page = $factory->getPage();
		$i18n = $factory->getI18n();

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

		$description = $i18n->get("[[base-{$type}sitestats.{$descriptionId}]]");

		$myData = $report->getData($regex);

		$total = 0;
		$ylabel = "";
		$divisor = 1;
		for ($i = 0; $i < count($myData); $i++) {
			$total += $myData[$i][3];
		}
		if (count($myData) == "0") {
			$avg = "0";
		}
		else {
			$avg = $total / count($myData);
		}

		if ($avg <= 1000) {
			$divisor = 1;
			$ylabel = $i18n->get("[[base-sitestats.bytes]]");
		} else if (($avg > 1000) && ($avg <= 1000000)) {
			$divisor = 1024;
			$ylabel = $i18n->get("[[base-sitestats.kilobyte_abbr]]");
		} else if (($avg > 1000000) && ($avg <= 1000000000)) {
			$divisor = 1024*1024;
			$ylabel = $i18n->get("[[base-sitestats.megabyte_abbr]]");
		} else {
			$divisor = 1024*1024*1024;
			$ylabel = $i18n->get("[[base-sitestats.gigabyte_abbr]]");
		}

		$xlabels = array();
		$subcount = "0";

		for ($i = 0; $i < count($myData); $i++) {
			$dateString = preg_replace("/\s+/", "/", $myData[$i][5]);
			$dateStringArray = explode('/', $dateString);
			if (count($dateStringArray) > 2) {
				if (count($myData > 30)) {
					if ($subcount == 0) {
						array_push($xlabels, "'" . $dateStringArray[0] . "/" . $dateStringArray[1] . "/" . $dateStringArray[2] . "'");
					}
					else {
						array_push($xlabels, "'&nbsp;'");
					}
					$subcount++;
					if ($subcount > 4) {
						$subcount = 0;
					}
				}
				else {
					array_push($xlabels, "'" . $dateStringArray[0] . "/" . $dateStringArray[1] . "/" . $dateStringArray[2] . "'");
				}
			}
			else {
				array_push($xlabels, "'" . $dateString . "'");
			}
		}

		$data = array($ylabel => array());
		for ($i = 0; $i < count($myData); $i++) {
			// We need to format the numbers correctly. We divide by our divisor and the result
			// must have a dot between the integer and the fractals, not a comma. To provide
			// more accurate after comma data we push out up to 10 after-comma-digits:
			array_push($data[$ylabel], number_format($myData[$i][3]/$divisor, 10, '.', ''));
		}

//		$graphStats = $factory->getPagedBlock($i18n->interpolate($description), array($defaultPage));

		$myGraph = $factory->getBarGraph($descriptionId, $data, $xlabels);
		if ($descriptionId == "dailySummaryDesc") {
			$myGraph->setBars($ylabel, TRUE);
			$myGraph->setLines($ylabel, FALSE);
			$myGraph->setPoints($ylabel, FALSE);
		}
		else {
			$myGraph->setBars($ylabel, FALSE);
			$myGraph->setLines($ylabel, TRUE);
			$myGraph->setPoints($ylabel, TRUE);
		}

		return $myGraph;
}

function genScrollListStats ($statsType, $SSshorts, $SSvals, $type, &$report, &$factory) {

//print_rp("----------------------");
//print_rp($statsType);
//print_rp($SSshorts);
//print_rp($SSvals);
//print_rp($type);
////print_rp($report);
//print_rp("----------------------");

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

		$VsiteScrollListBlock = $factory->getPagedBlock($statsType, array("basicSettingsTab"));
		$VsiteScrollListBlock->setToggle("#");
		$VsiteScrollListBlock->setSideTabs(FALSE);

		if ($type == 'net') {
			$VsiteScrollList = $factory->getScrollList($statsType, 
							       array(
						       		"ipAddr", "bytes", 
									"pbytes"
							       ), array());
		    $VsiteScrollList->setAlignments(array("left", "left", "left"));
		    $VsiteScrollList->setColumnWidths(array("246", "246", "246")); // Max: 739px
		}
		elseif ($type == 'mail') {
			$VsiteScrollList = $factory->getScrollList($statsType, 
							       array( 
									"{$io}Domain",
									"requests",
									"requests",
									"bytes", 
									"pbytes",
									"pbytes"
							       ), array());
			$VsiteScrollList->setAlignments(array("left", "left", "left", "left", "left", "left"));	
		    $VsiteScrollList->setColumnWidths(array("184", "184", "30", "124", "184", "30")); // Max: 739px
		}
		else {
			$VsiteScrollList = $factory->getScrollList($statsType, 
							       array( 
									"site",
									"requests",
									"requests", 
									"bytes",
									"pbytes",
									"pbytes"
							       ), array());
			$VsiteScrollList->setAlignments(array("left", "left", "left", "left", "left", "left"));	
		    $VsiteScrollList->setColumnWidths(array("184", "184", "30", "124", "184", "30")); // Max: 739px
		}

		$i18n = $factory->getI18n();
		$typestring = $i18n->interpolate("[[base-sitestats." . $type . "usage]]");

	    $VsiteScrollList->setDefaultSortedIndex('0');
	    $VsiteScrollList->setSortOrder('ascending');
	    $VsiteScrollList->setSortDisabled(array('3'));
	    $VsiteScrollList->setPaginateDisabled(FALSE);
	    $VsiteScrollList->setSearchDisabled(FALSE);
	    $VsiteScrollList->setSelectorDisabled(FALSE);
	    $VsiteScrollList->enableAutoWidth(FALSE);
	    $VsiteScrollList->setInfoDisabled(FALSE);


		$noDescription = "";
		for ($i = 0; $i < count($myData); $i++) {
			$site = preg_replace("#http://(\S+)/#", "\\1", $myData[$i][5]); 
			if ($site == "localhost") { 
				$site = $i18n->getHtml("[[base-sitestats.Home]]"); 
			}
			if ($site == "other") { 
				$site = $i18n->getHtml("[[base-sitestats.other]]"); 
			}
			$myData[$i][1] = number_format($myData[$i][1]);	
			$myData[$i][2] = sprintf("%.1f", $myData[$i][2]);	
			$myData[$i][3] = simplify_number($myData[$i][3], "KB", "2");	
			$myData[$i][4] = sprintf("%.1f", $myData[$i][4]);	
			$pBytesBar = $factory->getBar("vsite$i", floor($myData[$i][4]));
			$pBytesBar->setLabel("&nbsp;{$myData[$i][4]}%");
			$pBytesBar->setLabelType("nolabel");
			$pBytesBar->setHelpTextPosition("right");

			if ($type != "net") {
				$VsiteScrollList->addEntry(
				    array(
					$site,
					"{$myData[$i][1]}",
					"({$myData[$i][2]}%)",
					$myData[$i][3],
					$pBytesBar,
					floor($myData[$i][4])
				    ));
			} else {
				$VsiteScrollList->addEntry(
				    array(
					$site,
					$myData[$i][3],
					$pBytesBar
				    ));
			}			
		}

		// Show the ScrollList out:
		return $VsiteScrollList;
}

/*
Copyright (c) 2014 Michael Stauber, SOLARSPEED.NET
Copyright (c) 2014 Team BlueOnyx, BLUEONYX.IT
Copyright (c) 2003 Sun Microsystems, Inc. 
All Rights Reserved.

1. Redistributions of source code must retain the above copyright 
   notice, this list of conditions and the following disclaimer.

2. Redistributions in binary form must reproduce the above copyright 
   notice, this list of conditions and the following disclaimer in 
   the documentation and/or other materials provided with the 
   distribution.

3. Neither the name of the copyright holder nor the names of its 
   contributors may be used to endorse or promote products derived 
   from this software without specific prior written permission.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS 
"AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT 
LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS 
FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE 
COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, 
INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, 
BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; 
LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER 
CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT 
LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN 
ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE 
POSSIBILITY OF SUCH DAMAGE.

You acknowledge that this software is not designed or intended for 
use in the design, construction, operation or maintenance of any 
nuclear facility.

*/
?>