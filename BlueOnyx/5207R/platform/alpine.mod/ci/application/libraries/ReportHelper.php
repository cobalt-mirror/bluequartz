<?php
/*
 * $Id: ReportHelper.php
 *
 * Utility functions for generating reports
 */

global $isReportHelperDefined;
if ( $isReportHelperDefined )
	return;
$isReportHelperDefined = true;

include_once("I18n.php");
include_once("uifc/HtmlComponentFactory.php");
include_once("uifc/Stylist.php");
include_once("uifc/Button.php");

class ReportHelper {
	// private variables
	var $type;
	var $group;
	var $i18n;
	var $stylist;
	var $locale;
	var $style;

	var $links;
	var $items;

	// public functions

	/*
	 * description: constructor
	 * param: type = (web|ftp|mail|net), group = (server|siteN)
	 * defaults: type = web, group = server
	 */
	function ReportHelper($argType = "web", $argGroup = "server", $argLocale = "en", $argStyle = "BlueOnyx") {
		if ($this->isValidType($argType)) { 
			$this->type = $argType;
		} else {
			$this->type = "web";
		}

		$this->group = $argGroup;	
		$this->style = $argStyle;
		$this->locale = $argLocale;

		$this->i18n = new I18n("base-{$argType}sitestats", 
							 $this->locale);

		$this->stylist = new Stylist();
		$this->stylist->setResource($argStyle, $this->locale);

		/* 
		 * Analog output label decoder ring
		 * x	GENERAL	 	General Summary
		 * 1	YEARLY		Yearly Report
		 * Q	QUARTERLY	Quarterly Report
		 * m	MONTHLY		Monthly Report
		 * W	WEEKLY		Weekly Report
		 * D	DAILYREP	Daily Report
		 * d	DAILYSUM	Daily Summary
		 * H	HOURLYREP	Hourly Report
		 * h	HOURLYSUM	Hourly Summary
		 * w	WEEKHOUR	Hour of the Week Summary
		 * 4	QUARTERREP	Quarter-Hour Report
		 * 6	QUARTERSUM	Quarter-Hour Summary
		 * 5	FIVEREP		Five-Minute Report
		 * 7	FIVESUM		Five-Minute Summary
		 * S	HOST		Host Report
		 * l	REDIRHOST	Host Redirection Report
		 * L	FAILHOST	Host Failure Report
		 * Z	ORGANISATION	Organisation Report
		 * o	DOMAIN		Domain Report
		 * r	REQUEST		Request Report
		 * i	DIRECTORY	Directory Report
		 * t	FILETYPE	File Type Report
		 * z	SIZE		File Size Report
		 * P	PROCTIME	Processing Time Report
		 * E	REDIR		Redirection Report
		 * I	FAILURE		Failure Report
		 * f	REFERRER	Referrer Report
		 * s	REFSITE		Referring Site Report
		 * N	SEARCHQUERY	Search Query Report
		 * n	SEARCHWORD	Search Word Report
		 * Y	INTSEARCHQUERY	Internal Search Query Report
		 * y	INTSEARCHWORD	Internal Search Word Report
		 * k	REDIRREF	Redirected Referrer Report
		 * K	FAILREF		Failed Referrer Report
		 * B	BROWSERREP	Browser Report
		 * b	BROWSERSUM	Browser Summary
		 * p	OSREP		Operating System Report
		 * v	VHOST		Virtual Host Report
		 * R	REDIRVHOST	Virtual Host Redirection Report
		 * M	FAILVHOST	Virtual Host Failure Report
		 * u	USER		User Report
		 * j	REDIRUSER	User Redirection Report
		 * J	FAILUSER	User Failure Report
		 * c	STATUS		Status Code Report
		 */
		$this->links = 
		    array( 
			"web" => 
			    array( 
				"server" => array("o", "v", "m", "W", "D", "h",
						  "d", "t", "s"), 
				"site" => array("m", "W", "D", "h", "d", "r",
						"o", "t", "s")
			    ),
			"ftp" =>
			    array(
				"server" => array("o", "v", "D", "m", "W", "h",
						  "d", "t", "s"),
				"site" => array("o", "m", "W", "D", "h", "d",
						"r", "t", "s")
			    ),
			"mail" => 
			    array(
				"server" => array("v", "D", "m", "W", "h", "d",
						  "s"),
				"site" => array("D", "m", "W", "h", "d", "r",
						"s")
			    ),
			"net" => 
			    array(
				"server" => array("v", "m", "W", "D", "h", "d",
						  "s")
			    )
		    );

		$this->items = 
				array(
			"web" => array("PS", "FR", "LR", "NH", "SR", "PR",
							 "FL", "NF", "BT"),
			"ftp" => array("PS", "FR", "LR", "NH", "NS", "NR",
							 "SR", "NF", "BT"),
			"mail" => array("PS", "FR", "LR", "SR", "BT"),
			"net" => array("PS", "FR", "LR", "BT")
				); 
	}

	function getI18n() {
		return $this->i18n;
	}

	function getStylist() {
		return $this->stylist;
	}

	function getItems() {
		return $this->items[$this->type];
	}

	function getLinks() {
		if ($this->group == "server") {
			return $this->links[$this->type]["server"];
		} else {
			return $this->links[$this->type]["site"];
		}
	}

	function isApplicable($item, $applicableSet) {
		return in_array($item, $applicableSet);
	}

	function isValidType($type) {
		return preg_match("/(^web$)|(^mail$)|(^ftp$)|(^net$)/", $type);
	}
	
	function getStatsFilename($type, $group = "server") {

		// Get CI instance:
		$CI =& get_instance();

	    // Get $sessionId and $loginName from Cookie (if they are set):
	    $sessionId = $CI->input->cookie('sessionId');
	    $loginName = $CI->input->cookie('loginName');

		if (!$type) {
			return false;
		}
	
		if($group == 'server') {
			$basedir = '/home/.sites/server';
		} else {
			include_once("ServerScriptHelper.php");
			$ssh = new ServerScriptHelper($sessionId, $loginName) or die ("no SSH");
			$cce = $ssh->getCceClient() or die ("no CCE");

			$vsite = $cce->getObject('Vsite', array('name' => $group));
			$basedir = $vsite['basedir'];
		}
		return "{$basedir}/logs/{$type}.stats";
	}

	
	function exitNoData($factory) {
		$page = $factory->getPage();
		$i18n = $this->getI18n();
		$errorMsg = $i18n->get("[[base-sitestats.noDataError]]");		
		print($page->toHeaderHtml());
		$customize = $factory->getButton("reportForm.php?type=$this->type&group=$this->group", 
						 "customizeBut");
		print($customize->toHtml());
		print("<BR>$errorMsg<BR><BR>");
		print($page->toFooterHtml());
		exit;
	}

	/*
	 * params: 
	 *	val - numeric value
	 * returns: string value for val as x.xx kb|Mb|Gb
	 */
	function formatBytes($val) {
		$lang = $this->getI18n(); 
		if ($val > 10737418240) {
			$val = number_format($val / 1073741824, 2);
			return $val . " " .
					$lang->get("[[base-sitestats.gigabyte_abbr]]");
		} else if ($val > 10485760) {
			$val = number_format($val / 1048576, 2);
			return $val . " " . 
					$lang->get("[[base-sitestats.megabyte_abbr]]");
		} else if ($val > 10240) {
			$val = number_format($val / 1024, 2);
			return $val . " " .
					$lang->get("[[base-sitestats.kilobyte_abbr]]");
		} else {
			return number_format($val);
		}
	}

	function getData($regex) {
		$statsfile = $this->getStatsFilename($this->type, $this->group);

		$handle = @fopen($statsfile, "r");
		if (!$handle) {
			return false;
		} 

		$myData = $this->readData($handle, $regex);
		fclose ($handle);

		return $myData;
	}

	/*
	 * pass handle to input file and regex to return data for
	 * returns array of data
	 * add support for maxlines?
	 */
	function readData($argFileHandle, $argRegEx) {
		$data = array(array());
		array_pop($data);
		while (!feof($argFileHandle)) {
			$line = fgets($argFileHandle, 4096);
			$line = preg_replace("/\[([a-zA-Z\s]+)\]/",
					     "[[base-sitestats.\\1]]", $line);
			if (preg_match($argRegEx, $line, $matches)) {
				array_push($data, $matches);
			}
		}
		return $data;
	} 

	// for debugging
	function displayRawData($raw) {
		for ($i = 0; $i < count($raw); $i++) {
			for ($j = 0; $j < count($raw[$i]); $j++) {
				print "-{$raw[$i][$j]}-";
			}
			print "<BR>";
		}
	}
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