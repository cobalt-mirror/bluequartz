<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Memory_details extends MX_Controller {

	/**
	 * Index Page for this controller.
	 *
	 * Past the login page this loads the page for /am/memory_details.
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

		$page_body[] = am_detail_block($factory, $cceClient, "Memory", "[[base-am.amMemDetails]]");

		//
		//--- Print Sub Block:
		//

		$kernelver = `uname -r`;
		if (preg_match('/^2.4/', $kernelver)) {
		    $kernel = 2.4;
		} elseif (preg_match('/^2.6/', $kernelver)) {
		    $kernel = 2.6;
		}
		$mttl = `cat /proc/meminfo | grep MemTotal`;
		$mfree = `cat /proc/meminfo | grep MemFree`;
		$sttl = `cat /proc/meminfo | grep SwapTotal`;
		$sfree = `cat /proc/meminfo | grep SwapFree`;
		$uptime = `cat /proc/uptime`;

		switch($kernel) {
		case 2.4:
		    $mempages = `cat /proc/stat | grep page`;
		    $memswaps = `cat /proc/stat | grep swap`;
		    break;
		case 2.6:
		    $mempages = `cat /proc/vmstat | grep pgpgin` . `cat /proc/vmstat | grep pgpgout`;
		    $memswaps = `cat /proc/swaps | grep partition`;
		    break;
		}

		if (!strlen($mttl) || !strlen($mfree) || !strlen($sttl) || 
		    !strlen($sfree) || !strlen($mempages) || 
		    !strlen($memswaps) || !strlen($uptime)) {

			// get the sub block 
			$subblock = $factory->getPagedBlock(
				$i18n->interpolate("[[base-am.amMemStats]]"), array($defaultPage));

			$noinfo = $factory->getTextField("noinfo", $i18n->interpolate("[[base-am.no_mem_stats]]"), "r");
			$noinfo->setLabelType("nolabel");

			$subblock->addFormField(
				$noinfo,
				$factory->getLabel("noinfo"),
				$defaultPage
			);
		}
		else {

			// get the sub block 
			$subblock = $factory->getPagedBlock(
				$i18n->interpolate("[[base-am.amMemStats]]"), array($defaultPage));

			// total mem
			$mttl = preg_replace("/[^0-9]*/i", "", $mttl);
			$subblock->addFormField(
				$factory->getTextField("mttlField", 
				$i18n->interpolate("[[base-am.MemKB,val=\"$mttl\"]]"), "r"),
				$factory->getLabel("amMemTotal"),
				$defaultPage
			);

			// free mem
			$mfree = preg_replace("/[^0-9]*/i", "", $mfree);
			$subblock->addFormField(
				$factory->getTextField("mfreeField",
				$i18n->interpolate("[[base-am.MemKB,val=\"$mfree\"]]"), "r"),
				$factory->getLabel("amMemFree"),
				$defaultPage
			);

			// used mem
			$mused = $mttl - $mfree;
			$mpcnt = round(($mused*100)/$mttl);
			$subblock->addFormField(
				$factory->getTextField("musedField", 
				$i18n->interpolate("[[base-am.amMemPcnt,used=\"$mused\",pcnt=\"$mpcnt\"]]"),"r"),
				$factory->getLabel("amMemUsed"),
				$defaultPage
			);
				
			$sttl = preg_replace("/[^0-9]*/i", "", $sttl);
			$subblock->addFormField(
				$factory->getTextField("sttlField", 
				$i18n->interpolate("[[base-am.MemKB,val=\"$sttl\"]]"), "r"),
				$factory->getLabel("amSwapTotal"),
				$defaultPage
			);

			$sfree = preg_replace("/[^0-9]*/i", "", $sfree);
			$subblock->addFormField(
				$factory->getTextField("sfreeField",
				$i18n->interpolate("[[base-am.MemKB,val=\"$sfree\"]]"), "r"),
				$factory->getLabel("amSwapFree"),
				$defaultPage
			);

			switch($kernel){
			case 2.4:
			    list($tmp, $pagesin, $pagesout) = preg_split("/[[:space:]]+/", $mempages);
			    list($tmp, $swapin, $swapout) = preg_split("/[[:space:]]+/", $memswaps);
			    break;
			case 2.6:
			    list($tmp, $pagesin, $tmp, $pagesout) = preg_split("/[[:space:]]+/", $mempages);
			    list($tmp, $tmp, $size, $swaps) = preg_split("/[[:space:]]+/", $memswaps);
			    break;
			}

			list($uptime, $tmp) = preg_split("/[[:space:]]+/", $uptime);

			$pages = round(($pagesin + $pagesout) / $uptime);
			$string = $i18n->interpolate("[[base-am.amPagesSec,pages=\"$pages\"]]");
			$subblock->addFormField($factory->getTextField("pagessec", $string, "r"),
				$factory->getLabel("pagesPerSec"),
				$defaultPage);

			switch($kernel){
			case 2.4:
			    $swaps  = round(($swapin + $swapout) / $uptime);
			    break;
			case 2.6:
			    $swaps  = round($swaps / $uptime);
			    break;
			}

			$string = $i18n->interpolate("[[base-am.amSwapsSec,swaps=\"$swaps\"]]");
			$subblock->addFormField($factory->getTextField("swapssec", $string, "r"),
				$factory->getLabel("swapsPerSec"),
				$defaultPage);

			$sused = $sttl - $sfree;
			if ($sttl > 0) {
			    $spcnt = round(($sused*100)/$sttl);
			} else {
			    $spcnt = 0;
			}
			$subblock->addFormField(
				$factory->getTextField("susedField", $i18n->interpolate("[[base-am.amMemPcnt,used=\"$sused\",pcnt=\"$spcnt\"]]"), "r"),
				$factory->getLabel("amSwapUsed"),
				$defaultPage
			);

		}

		// print it 
		$page_body[] = $subblock->toHtml();

		//
		//--- Prepare Memory Utilization Block:
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
			$ret = $serverScriptHelper->shell("/usr/bin/sar -r -f /var/log/sa/sa$gestern", $saStatsGestern, 'root', $sessionId);

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
			$ret = $serverScriptHelper->shell("/usr/bin/sar -r -f /var/log/sa/sa$heute", $saStatsHeute, 'root', $sessionId);

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
				if ((!in_array('Average:', $line)) && (!in_array('ArrayLinux', $line)) && (!in_array('kbmemfree', $line)) && (!in_array('Linux', $line)) && (!in_array('LINUX', $line))) {
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
					$PostProcessing['amMemStats'][$line[0]] = array(
														'kbmemfree' => str_replace(",",".",$line[1]), 
														'kbmemused' => str_replace(",",".",$line[2]), 
														'%memused' => str_replace(",",".",$line[3]), 
														'kbbuffers' => str_replace(",",".",$line[4]), 
														'kbcached' => str_replace(",",".",$line[5]), 
														'kbcommit' => str_replace(",",".",$line[6]), 
														'%commit' => str_replace(",",".",$line[7]), 
														'TimeStamp' => $line[0] 
														);
				}
				// Add seen netDevices:
				if (!in_array('amMemStats', $netDevices)) {
					$netDevices[] = 'amMemStats';
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
						$pvalue['TimeStamp'] = $hm[0] . ':' . $hm[1];
						if ($hm[0] == "24") {
							$hm[0] = "0";
						}
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

			$block2 =& $factory->getPagedBlock('amMemStats', "");
			$block2->setLabel($factory->getLabel("[[base-am.amMemStats]]"));
			$block2->setToggle("#");
			$block2->setSideTabs(FALSE);
			$block2->setShowAllTabs("#");

			// Sort Array of netDevices:
			natcasesort($netDevices);
			foreach ($netDevices as $diskDev) {
				$diskDevLbl = 'amMemStats';
				$block2->addPage('amMemStats', $factory->getLabel("[[base-am.amMemStats]]"));
				foreach ($Listo[$diskDev] as $key => $value) {
					$TPR['kbmemfree'][$key] = "'" . $value['kbmemfree'] . "'";
					$TPR['kbmemused'][$key] = "'" . $value['kbmemused'] . "'";

					$APR['%memused'][$key] = "'" . $value['%memused'] . "'";
					$APR['%commit'][$key] = "'" . $value['%commit'] . "'";

					$AVGWT['kbbuffers'][$key] = "'" . $value['kbbuffers'] . "'";
					$AVGWT['kbcached'][$key] = "'" . $value['kbcached'] . "'";
					$AVGWT['kbcommit'][$key] = "'" . $value['kbcommit'] . "'";

				}
				$block2->addFormField(
				        $factory->addBXDivider(str_replace("-","_",$diskDev), ""),
				        $factory->getLabel("[[base-am.amMemStats]]", false),
				        $diskDevLbl
				        );	

				$cleanDev = str_replace("-","",$diskDev);
				$varname = "myGraph" . $outStatsnum;
				$$varname = $factory->getBarGraph("A$outStatsnum$cleanDev", $TPR, $seenTimes);
				$$varname->setPoints('kbmemfree', FALSE);
				$$varname->setPoints('kbmemused', FALSE);
				$block2->addFormField(
					$$varname,
					"",
					$diskDevLbl);

				$varname = "myGraph2" . $outStatsnum;
				$$varname = $factory->getBarGraph("B$outStatsnum$cleanDev", $APR, $seenTimes);
				$$varname->setPoints('%memused', FALSE);
				$$varname->setPoints('%commit', FALSE);
				$block2->addFormField(
					$$varname,
					"",
					$diskDevLbl);

				$varname = "myGraph3" . $outStatsnum;
				$$varname = $factory->getBarGraph("C$outStatsnum$cleanDev", $AVGWT, $seenTimes);
				$$varname->setPoints('kbbuffers', FALSE);
				$$varname->setPoints('kbcached', FALSE);
				$$varname->setPoints('kbcommit', FALSE);
				$block2->addFormField(
					$$varname,
					"",
					$diskDevLbl);

				$outStatsnum++;
			}
		}

		//
		//--- Prepare Swap Utilization Block:
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
			$ret = $serverScriptHelper->shell("/usr/bin/sar -S -f /var/log/sa/sa$gestern", $saStatsGestern, 'root', $sessionId);

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
			$ret = $serverScriptHelper->shell("/usr/bin/sar -S -f /var/log/sa/sa$heute", $saStatsHeute, 'root', $sessionId);

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
				if ((!in_array('Average:', $line)) && (!in_array('ArrayLinux', $line)) && (!in_array('kbswpfree', $line)) && (!in_array('Linux', $line)) && (!in_array('LINUX', $line))) {
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
					$PostProcessing['amSwapUsed'][$line[0]] = array(
														'kbswpfree' => str_replace(",",".",$line[1]), 
														'kbswpused' => str_replace(",",".",$line[2]), 
														'%swpused' => str_replace(",",".",$line[3]), 
														'kbswpcad' => str_replace(",",".",$line[4]), 
														'%swpcad' => str_replace(",",".",$line[5]), 
														'TimeStamp' => $line[0] 
														);
				}
				// Add seen netDevices:
				if (!in_array('amSwapUsed', $netDevices)) {
					$netDevices[] = 'amSwapUsed';
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

			// Sort Array of netDevices:
			natcasesort($netDevices);
			foreach ($netDevices as $diskDev) {
				$diskDevLbl = 'amSwapUsed';
				$block2->addPage('amSwapUsed', $factory->getLabel("[[base-am.amSwapUsed]]"));
				foreach ($Listo[$diskDev] as $key => $value) {
					$TPRZ['kbswpfree'][$key] = "'" . $value['kbswpfree'] . "'";
					$TPRZ['kbswpused'][$key] = "'" . $value['kbswpused'] . "'";

					$APRZ['%swpused'][$key] = "'" . $value['%swpused'] . "'";
					$APRZ['%swpcad'][$key] = "'" . $value['%swpcad'] . "'";

					$AVGWTZ['kbswpcad'][$key] = "'" . $value['kbswpcad'] . "'";
				}
				$block2->addFormField(
				        $factory->addBXDivider(str_replace("-","_",$diskDev), ""),
				        $factory->getLabel("[[base-am.amSwapUsed]]", false),
				        $diskDevLbl
				        );	

				$cleanDev = str_replace("-","",$diskDev);
				$varname = "myGraph4" . $outStatsnum;
				$$varname = $factory->getBarGraph("D$outStatsnum$cleanDev", $TPRZ, $seenTimes);
				$$varname->setPoints('kbswpfree', FALSE);
				$$varname->setPoints('kbswpused', FALSE);
				$block2->addFormField(
					$$varname,
					"",
					$diskDevLbl);

				$varname = "myGraph5" . $outStatsnum;
				$$varname = $factory->getBarGraph("E$outStatsnum$cleanDev", $APRZ, $seenTimes);
				$$varname->setPoints('%swpused', FALSE);
				$$varname->setPoints('%swpcad', FALSE);
				$block2->addFormField(
					$$varname,
					"",
					$diskDevLbl);

				$varname = "myGraph6" . $outStatsnum;
				$$varname = $factory->getBarGraph("F$outStatsnum$cleanDev", $AVGWTZ, $seenTimes);
				$$varname->setPoints('kbswpcad', FALSE);
				$block2->addFormField(
					$$varname,
					"",
					$diskDevLbl);

				$outStatsnum++;
			}
		}

		if (isset($PostProcessing)) {
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