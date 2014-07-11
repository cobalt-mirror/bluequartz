<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Cpu_details extends MX_Controller {

	/**
	 * Index Page for this controller.
	 *
	 * Past the login page this loads the page for /am/cpu_details.
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
		$i18n = new I18n("base-am", $user['localePreference']);
		$system = $cceClient->getObject("System");

		// Initialize Capabilities so that we can poll the access rights as well:
		$Capabilities = new Capabilities($cceClient, $loginName, $sessionId);

		// -- Actual page logic start:

		// Not 'serverShowActiveMonitor'? Bye, bye!
		if (!$Capabilities->getAllowed('serverShowActiveMonitor')) {
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
		$factory = $serverScriptHelper->getHtmlComponentFactory("base-am");
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

		$page_body[] = am_detail_block($factory, $cceClient, "CPU", "[[base-am.amCPUDetails]]");

		//
		//--- Print Sub Block:
		//

		$subblock = $factory->getPagedBlock($i18n->interpolate("[[base-am.amCPUStats]]"), array($defaultPage));

		// load average
		$loadavg1 = `cat /proc/loadavg  | cut -d' ' -f1`;
		$loadavg1 = chop($loadavg1);
		$subblock->addFormField(
			$factory->getNumber("loadavg1Field", $loadavg1, 0, 0, "r"),
			$factory->getLabel("amCPULoadAvg", true, 
				array("mins" => "1"), 
				array("mins" => "1")), $defaultPage
		);

		$loadavg15 = `cat /proc/loadavg  | cut -d' ' -f3`;
		$loadavg15 = chop($loadavg15);
		$subblock->addFormField(
			$factory->getNumber("loadavg15Field", $loadavg15, 0, 0, "r"),
			$factory->getLabel("amCPULoadAvgFifteen", true, 
				array("mins" => "15"), 
				array("mins" => "15")), $defaultPage
		);

		// uptime
		$upsecs = floor(`cat /proc/uptime | cut -d' ' -f1`);
		$upmins = floor($upsecs/60);
		$uphrs = floor($upmins/60);
		$upmins %= 60;
		$updays = floor($uphrs/24);
		$uphrs %= 24;

		$up = "";
		if ($updays == 1) {
			$up .= "[[base-am.day,val=$updays]] ";
		} else if ($updays > 1) {
			$up .= "[[base-am.days,val=$updays]] ";
		}

		if ($uphrs == 1) {
			$up .= "[[base-am.hour,val=$uphrs]] ";
		} else if ($uphrs > 1) {
			$up .= "[[base-am.hours,val=$uphrs]] ";
		}

		if ($upmins == 1) {
			$up .= "[[base-am.minute,val=$upmins]]";
		} else {
			$up .= "[[base-am.minutes,val=$upmins]]";
		}
		$subblock->addFormField(
			$factory->getTextField("upField", $i18n->interpolate($up), "r"),
			$factory->getLabel("amCPUUptime", true), $defaultPage
		);

		// cpu temperature
		$temp = `cat /proc/cpuinfo | grep temperature`;
		if ($temp = preg_replace("/[^0-9\.]*/i", "", $temp)) {
			$itemp = $i18n->interpolate("[[base-am.degrees,val=\"$temp\"]]");
			$subblock->addFormField(
				$factory->getTextField("tempField", $itemp, "r"),
				$factory->getLabel("amCPUTemp", true), $defaultPage
			);
		}

		// print it 
		$page_body[] = $subblock->toHtml();

		//
		//--- Prepare CPU LoadAverage Block:
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
			$ret = $serverScriptHelper->shell("/usr/bin/sar -q -f /var/log/sa/sa$gestern", $saStatsGestern, 'root', $sessionId);

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
				if ((!in_array('Average:', $line)) && (!in_array('ArrayLinux', $line)) && (!in_array('runq-sz', $line)) && (!in_array('Linux', $line)) && (!in_array('LINUX', $line))) {
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
			$ret = $serverScriptHelper->shell("/usr/bin/sar -q -f /var/log/sa/sa$heute", $saStatsHeute, 'root', $sessionId);

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
				if ((!in_array('Average:', $line)) && (!in_array('ArrayLinux', $line)) && (!in_array('runq-sz', $line)) && (!in_array('Linux', $line)) && (!in_array('LINUX', $line))) {
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
					$PostProcessing['CPU'][$line[0]] = array(
														'runq-sz' => str_replace(",",".",$line[1]), 
														'plist-sz' => str_replace(",",".",$line[2]), 
														'ldavg-1' => str_replace(",",".",$line[3]), 
														'ldavg-5' => str_replace(",",".",$line[4]), 
														'ldavg-15' => str_replace(",",".",$line[5]), 
														'TimeStamp' => $line[0] 
														);
				}
				// Add seen netDevices:
				if (!in_array('CPU', $netDevices)) {
					$netDevices[] = 'CPU';
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
			//-- Print Stats:
			//

			$outStatsnum = "0";

			$block2 =& $factory->getPagedBlock('amCPUStats', "");
			$block2->setLabel($factory->getLabel('amCPUStats'));
			$block2->setToggle("#");
			$block2->setSideTabs(FALSE);
			$block2->setShowAllTabs("#");

			// Sort Array of netDevices:
			natcasesort($netDevices);
			foreach ($netDevices as $diskDev) {
				$diskDevLbl = " " . $diskDev . " ";
				$block2->addPage($diskDevLbl, $factory->getLabel($diskDevLbl));
				foreach ($Listo[$diskDev] as $key => $value) {
					$TPR['ldavg-1'][$key] = $value['ldavg-1'];
					$TPR['ldavg-5'][$key] = $value['ldavg-5'];
					$TPR['ldavg-15'][$key] = $value['ldavg-15'];

					$AVGWT['runq-sz'][$key] = $value['runq-sz'];
					$AVGWT['plist-sz'][$key] = $value['plist-sz'];

				}
				$block2->addFormField(
				        $factory->addBXDivider(str_replace("-","_",$diskDev), ""),
				        $factory->getLabel($diskDevLbl, false),
				        $diskDevLbl
				        );	

				$cleanDev = str_replace("-","",$diskDev);
				$varname = "myGraph" . $outStatsnum;
				$$varname = $factory->getBarGraph("A$outStatsnum$cleanDev", $TPR, $seenTimes);
				$$varname->setPoints('ldavg-1', FALSE);
				$$varname->setPoints('ldavg-5', FALSE);
				$$varname->setPoints('ldavg-15', FALSE);
				$block2->addFormField(
					$$varname,
					"",
					$diskDevLbl);

				$varname = "myGraph2" . $outStatsnum;
				$$varname = $factory->getBarGraph("B$outStatsnum$cleanDev", $AVGWT, $seenTimes);
				$$varname->setPoints('runq-sz', FALSE);
				$$varname->setPoints('plist-sz', FALSE);
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