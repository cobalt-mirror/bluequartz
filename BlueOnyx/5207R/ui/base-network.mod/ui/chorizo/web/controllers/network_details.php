<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Network_details extends MX_Controller {

	/**
	 * Index Page for this controller.
	 *
	 * Past the login page this loads the page for /network/network_details.
	 *
	 */

	public function index() {

		$CI =& get_instance();
		
	    // We load the BlueOnyx helper library first of all, as we heavily depend on it:
	    $this->load->helper('blueonyx');
	    init_libraries();

  		// Need to load 'BxPage' for page rendering:
  		$this->load->library('BxPage');
		$MX =& get_instance();

		// Load AM Detail Helper:
		$this->load->helper('amdetail');

	    // Get $sessionId and $loginName from Cookie (if they are set):
	    $sessionId = $CI->input->cookie('sessionId');
	    $loginName = $CI->input->cookie('loginName');
	    $locale = $CI->input->cookie('locale');

	    // Line up the ducks for CCE-Connection:
	    include_once('ServerScriptHelper.php');
		$serverScriptHelper = new ServerScriptHelper($sessionId, $loginName);
		$cceClient = $serverScriptHelper->getCceClient();
		$user = $cceClient->getObject("User", array("name" => $loginName));
		$i18n = new I18n("base-network", $user['localePreference']);
		$system = $cceClient->getObject("System");

		// Initialize Capabilities so that we can poll the access rights as well:
		$Capabilities = new Capabilities($cceClient, $loginName, $sessionId);

		// -- Actual page logic start:

		// Not 'serverNetwork'? Bye, bye!
		if (!$Capabilities->getAllowed('serverNetwork')) {
			// Nice people say goodbye, or CCEd waits forever:
			$cceClient->bye();
			$serverScriptHelper->destructor();
			Log403Error("/gui/Forbidden403");
		}

		// -- Actual page logic start:

	    // We start without any active errors:
	    $errors = array();
	    $extra_headers =array();
	    $ci_errors = array();
	    $my_errors = array();

	    // Find out if we display without menu or with menu:
		$get_form_data = $CI->input->get(NULL, TRUE);
		$fancy = FALSE;
		if ($get_form_data['short'] == "1") {
			$fancy = TRUE;
		}

		// Prepare Page:
		$factory = $serverScriptHelper->getHtmlComponentFactory("base-network");
		$BxPage = $factory->getPage();
		$i18n = $factory->getI18n();

		// Set Menu items:
		$BxPage->setVerticalMenu('base_monitor');
		$BxPage->setVerticalMenuChild('base_amStatus');
		if ($fancy == TRUE) {		
			$BxPage->setOutOfStyle(TRUE);
		}
		$page_module = 'base_sysmanage';
		$defaultPage = "basicSettingsTab";

		if ($fancy == TRUE) {
			$page_body[] = '<br><div id="main_container" class="container_16">';
		}

//---

		//
		//--- Print Detail Block:
		//

		$page_body[] = am_detail_block($factory, $cceClient, "Network", "[[base-network.amNetDetails]]");

		//
		//--- Print Sub Block:
		//

		if (file_exists( "/proc/user_beancounters" )) {
			// OpenVZ Network Interfaces:
			$list = $factory->getScrollList("amNetStats", array(' ', 'venet0', 'venet1'), array()); 
		}
		else {
			// Regular Network Interfaces:
			$list = $factory->getScrollList("amNetStats", array(' ', 'eth0', 'eth1'), array());
		}

	    $list->setAlignments(array("left", "left", "left"));
	    $list->setDefaultSortedIndex('0');
	    $list->setSortOrder('ascending');
	    $list->setSortDisabled(array('0', '1', '2'));
	    $list->setPaginateDisabled(TRUE);
	    $list->setSearchDisabled(TRUE);
	    $list->setSelectorDisabled(FALSE);
	    $list->enableAutoWidth(FALSE);
	    $list->setInfoDisabled(FALSE);
	    $list->setColumnWidths(array("246", "246", "246")); // Max: 739px

		if (file_exists( "/proc/user_beancounters" )) {
	        // OpenVZ Network Interfaces:
			$eth0_obj = $cceClient->getObject('Network', array('device' => 'venet0'));
			$eth1_obj = $cceClient->getObject('Network', array('device' => 'venet1'));
		}
		else {
			// Regular Network Interfaces:
			$eth0_obj = $cceClient->getObject('Network', array('device' => 'eth0'));
			$eth1_obj = $cceClient->getObject('Network', array('device' => 'eth1'));
		}
							   
		// Get eth0 info
		if ($eth0_obj['enabled']) {
			if (file_exists( "/proc/user_beancounters" )) {
	        	// OpenVZ Network Interfaces:
				$eth0 = `grep venet0 /proc/net/dev`;
			}
			else {
				// Regular Network Interfaces:
				$eth0 = `grep eth0 /proc/net/dev`;
			}
		  	$eth0 = chop(ltrim($eth0));
		  	$eth0 = preg_split("/[^[:alnum:]]+/", $eth0);
		  	$eth0['recv_bytes'] = simplify_number($eth0[1], "KB", "0");
		  	$eth0['recv_packets'] = simplify_number($eth0[2], "K", "0");
		  	$eth0['sent_bytes'] = simplify_number($eth0[9], "KB", "0");
		  	$eth0['sent_packets'] = simplify_number($eth0[10], "K", "0");
		  	$eth0['errors'] = $eth0[3] + $eth0[11];
		  	$eth0['collisions'] = $eth0[14];
		}
		else {
		  	$eth0['recv_bytes'] = $eth0['recv_packets'] = $eth0['sent_bytes']  =
		    $eth0['sent_packets'] = $eth0['errors'] = $eth0['collisions'] = $i18n->interpolate('stats_disabled');
		}

		// Get eth1 info
		if ($eth1_obj['enabled']) {
		  	$eth1 = `grep eth1 /proc/net/dev`;
		  	$eth1 = chop(ltrim($eth1));
		  	$eth1 = preg_split("/[^[:alnum:]]+/", $eth1);
		  	$eth1['recv_bytes'] = simplify_number($eth1[1], "KB", "0");
		  	$eth1['recv_packets'] = simplify_number($eth1[2], "KB", "0");
		  	$eth1['sent_bytes'] = simplify_number($eth1[9], "KB", "0");
		  	$eth1['sent_packets'] = simplify_number($eth1[10], "KB", "0");
		  	$eth1['errors'] = $eth1[3] + $eth1[11];
		  	$eth1['collisions'] = $eth1[14];
		}
		else {
		  	$eth1['recv_bytes'] = $eth1['recv_packets'] = $eth1['sent_bytes']  =
	    	$eth1['sent_packets'] = $eth1['errors'] = $eth1['collisions'] = $i18n->get('stats_disabled');
		}  

		$props = array('recv_bytes', 'recv_packets', 
				       'sent_bytes', 'sent_packets',
				       'errors', 'collisions');

		// add statistics to scroll list
		// need to set the style for each header so that
		// it shows up in the "Label" color
		foreach ($props as $prop) {
		  	$label = $factory->getLabel($prop);
		  	$label->setStyleTarget("labelLabel");
		  	$list->addEntry(array(
								$label,
								$eth0[$prop],
								$eth1[$prop])
					  			); 
		}

		// print it 
		$page_body[] = $list->toHtml();

		//
		//--- Print Network Utilization Block:
		//

		$gestern = date("d")-1;
		if ($gestern < "1") {
			if (is_file("/var/log/sa/sa31")) {
				$gestern = "31";
			}
			else {
				$gestern = "30";
			}
		}
		$heute = date("d");
		$gestern_cleaned = array();
		$heute_cleaned = array();
		$last24h = array();
		$saStatsGestern = "";
		$saStatsHeute = "";
		$netDevices = array();
		$seenTimes = array();
		$seenTimesSum = array();
		$gestern_cleaned_prefixed = array();
		$heute_cleaned_prefixed = array();

		if (is_file("/var/log/sa/sa$gestern")) {
			$ret = $serverScriptHelper->shell("/usr/bin/sar -n DEV -f /var/log/sa/sa$gestern", $saStatsGestern, 'root', $sessionId);

			// If locale is 'ja_JP' and AdmServ has been restarted, then we'll get the date in Japanese format.
			// We do a search and replace for these Kanji's and replace them:
			$saStatsGestern = preg_replace('/時/', ':' , $saStatsGestern);
			$saStatsGestern = preg_replace('/分/', ':' , $saStatsGestern);
			$saStatsGestern = preg_replace('/秒/', '' , $saStatsGestern);

			$gestern_cleaned = array_filter(explode("\n", $saStatsGestern));
			foreach ($gestern_cleaned as $key => $value) {
				$line = preg_split('#\s+#', $value, null, PREG_SPLIT_NO_EMPTY);
				if (!preg_match('/^([\d]{2,2}):([\d]{2,2}):([\d]{2,2})((.*){0,99})$/', $line[0])) {
					// Line doesn't start with a time stamp:
					continue;
				}
				if ((!in_array('Average:', $line)) && (!in_array('ArrayLinux', $line)) && (!in_array('IFACE', $line)) && (!in_array('Linux', $line)) && (!in_array('LINUX', $line))) {
					$hm = explode(":", $line[0]);
					if (($line[1] == "AM") || ($line[1] == "PM")) {
						// Convert to 24h format:
						if ($line[1] == "PM") {
							$hm[0] = $hm[0]+12;
						}
						$line[0] = $hm[0] . ":" . $hm[1];
						unset($line[1]);
					}
					else {
						$line[0] = $hm[0] . ":" . $hm[1];
					}

					// Add timestamp to $seenTimes:
					if (!in_array($line[0], $seenTimes)) {
						$seenTimes[] = $line[0];
					}

					$gestern_cleaned_prefixed[] = implode("\t", $line);
				}
			}
		}

		if (is_file("/var/log/sa/sa$heute")) {
			$ret = $serverScriptHelper->shell("/usr/bin/sar -n DEV  -f /var/log/sa/sa$heute", $saStatsHeute, 'root', $sessionId);

			// If locale is 'ja_JP' and AdmServ has been restarted, then we'll get the date in Japanese format.
			// We do a search and replace for these Kanji's and replace them:
			$saStatsHeute = preg_replace('/時/', ':' , $saStatsHeute);
			$saStatsHeute = preg_replace('/分/', ':' , $saStatsHeute);
			$saStatsHeute = preg_replace('/秒/', '' , $saStatsHeute);

			$heute_cleaned = array_filter(explode("\n", $saStatsHeute));
			foreach ($heute_cleaned as $key => $value) {
				$line = preg_split('#\s+#', $value, null, PREG_SPLIT_NO_EMPTY);
				if (!preg_match('/^([\d]{2,2}):([\d]{2,2}):([\d]{2,2})((.*){0,99})$/', $line[0])) {
					// Line doesn't start with a time stamp:
					continue;
				}
				if ((!in_array('Average:', $line)) && (!in_array('ArrayLinux', $line)) && (!in_array('IFACE', $line)) && (!in_array('Linux', $line)) && (!in_array('LINUX', $line))) {
					$hm = explode(":", $line[0]);
					if (($line[1] == "AM") || ($line[1] == "PM")) {
						// Convert to 24h format:
						if ($line[1] == "PM") {
							$hm[0] = $hm[0]+12;
						}
						$line[0] = $hm[0]+24 . ":" . $hm[1];
						unset($line[1]);
					}
					else {
						$line[0] = $hm[0]+24 . ":" . $hm[1];
					}

					// Add timestamp to $seenTimes:
					if (!in_array($line[0], $seenTimes)) {
						$seenTimes[] = $line[0];
					}

					$heute_cleaned_prefixed[] = implode("\t", $line);
				}
			}
		}

		$out_data_cleaned = array_merge($gestern_cleaned_prefixed, $heute_cleaned_prefixed);

		// Now we need to trim it down to 24 hours worth of data:
		$timeIhave = count($seenTimes);
		if ($timeIhave > '143') {
			$diff = $timeIhave-143;
			for ($i=0; $i < $diff; $i++) { 
				unset($seenTimes[$i]);
			}
			foreach ($seenTimes as $key => $time) {
				$time24h[] = $time;
			}
		}
		else {
			foreach ($seenTimes as $key => $time) {
				$time24h[] = $time;
			}
		}

		$num = "0";
		$labelNum = "0";
		foreach ($out_data_cleaned as $key => $value) {
			$line = preg_split('#\s+#', $value, null, PREG_SPLIT_NO_EMPTY);
			if ((!in_array('Average:', $line)) && (!in_array('ArrayLinux', $line)) && (!in_array('IFACE', $line)) && (!in_array('Linux', $line)) && (!in_array('LINUX', $line))) {

				if (in_array($line[0], $time24h)) {
					// Build Output array:
					$PostProcessing[$line[1]][$line[0]] = array(
														'rxpck/s' => str_replace(",",".",$line[2]), 
														'txpck/s' => str_replace(",",".",$line[3]), 
														'rxkB/s' => str_replace(",",".",$line[4]), 
														'txkB/s' => str_replace(",",".",$line[5]), 
														'rxcmp/s' => str_replace(",",".",$line[6]), 
														'txcmp/s' => str_replace(",",".",$line[7]), 
														'TimeStamp' => $line[0] 
														);
				}

				// Add seen netDevices:
				if (!in_array($line[1], $netDevices)) {
					$netDevices[] = $line[1];
				}
				$num++;
			}
		}

		$Listo = array();
		$listoTimes = array();
		if (isset($PostProcessing)) {
			foreach (array_keys($PostProcessing) as $key => $value) {
				$num = '0';
				foreach ($PostProcessing[$value] as $pkey => $pvalue) {
					// Fix timestamps:
					$hm = explode(":", $pvalue['TimeStamp']);
					if ($hm[0] > '23')  {
						$hm[0] = $hm[0]-24;
						if ($hm[0] == "24") {
							$hm[0] = "0";
						}
						$pvalue['TimeStamp'] = $hm[0] . ':' . $hm[1];
					}
					if (!in_array($pvalue['TimeStamp'], $listoTimes)) {
						$listoTimes[] = $pvalue['TimeStamp'];
					}
					$Listo[$value][$num] = $pvalue;
					$num++;
				}
			}

			// Space out labels:
			$num = "0";
			$labelNum = "0";
			$seenTimes = array();
			foreach ($listoTimes as $key => $value) {
				if (($num == "5") || ($labelNum > "10") || ($timeIhave < "20")) {
					$seenTimes[$key] = "'" . $value . "'";
					$labelNum = "0";
				}
				else {
					$seenTimes[$key] = "''";
					$labelNum++;
				}
				$num++;
			}


			//
			//-- Other Partitions:
			//

			$outStatsnum = "0";

			$block2 =& $factory->getPagedBlock('amNetUsage', "");
			$block2->setLabel($factory->getLabel('amNetUsage'));
			$block2->setToggle("#");
			$block2->setSideTabs(FALSE);
			$block2->setShowAllTabs("#");

			// Sort Array of netDevices:
			natcasesort($netDevices);
			foreach ($netDevices as $diskDev) {
				$diskDevLbl = " " . $diskDev . " ";
				$block2->addPage($diskDevLbl, $factory->getLabel($diskDevLbl));
				foreach ($Listo[$diskDev] as $key => $value) {
					$TPR['rxpck/s'][$key] = $value['rxpck/s'];
					$TPR['txpck/s'][$key] = $value['txpck/s'];

					$AVGWT['rxkB/s'][$key] = $value['rxkB/s'];
					$AVGWT['txkB/s'][$key] = $value['txkB/s'];

					$SXW['rxcmp/s'][$key] = $value['rxcmp/s'];
					$SXW['txcmp/s'][$key] = $value['txcmp/s'];

				}
				$block2->addFormField(
				        $factory->addBXDivider(str_replace("-","_",$diskDev), ""),
				        $factory->getLabel($diskDevLbl, false),
				        $diskDevLbl
				        );	

				$cleanDev = str_replace("-","",$diskDev);
				$varname = "myGraph" . $outStatsnum;
				$$varname = $factory->getBarGraph("A$outStatsnum$cleanDev", $TPR, $seenTimes);
				$$varname->setPoints('rxpck/s', FALSE);
				$$varname->setPoints('txpck/s', FALSE);
				$block2->addFormField(
					$$varname,
					"",
					$diskDevLbl);

				$varname = "myGraph2" . $outStatsnum;
				$$varname = $factory->getBarGraph("B$outStatsnum$cleanDev", $AVGWT, $seenTimes);
				$$varname->setPoints('rxkB/s', FALSE);
				$$varname->setPoints('txkB/s', FALSE);
				$block2->addFormField(
					$$varname,
					"",
					$diskDevLbl);

				$varname = "myGraph3" . $outStatsnum;
				$$varname = $factory->getBarGraph("C$outStatsnum$cleanDev", $SXW, $seenTimes);
				$$varname->setPoints('rxcmp/s', FALSE);
				$$varname->setPoints('txcmp/s', FALSE);
				$block2->addFormField(
					$$varname,
					"",
					$diskDevLbl);

				$outStatsnum++;
			}
			// print it 
			$page_body[] = $block2->toHtml();
		}

//---	

		if ($fancy == TRUE) {
			$page_body[] = '</div>';
		}
		else {
			// Full page display. Show "Back" Button:
			$page_body[] = am_back($factory);
		}

		// Nice people say goodbye, or CCEd waits forever:
		$cceClient->bye();
		$serverScriptHelper->destructor();

		// Out with the page:
		$BxPage->setErrors($errors);
	    $BxPage->render($page_module, $page_body);

	}
}

/*
Copyright (c) 2014 Michael Stauber, SOLARSPEED.NET
Copyright (c) 2014 Team BlueOnyx, BLUEONYX.IT
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