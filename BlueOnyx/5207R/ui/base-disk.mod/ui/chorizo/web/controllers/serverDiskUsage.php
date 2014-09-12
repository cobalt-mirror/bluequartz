<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class ServerDiskUsage extends MX_Controller {

	/**
	 * Index Page for this controller.
	 *
	 * Past the login page this loads the page for /disk/serverDiskUsage.
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
		$i18n = new I18n("base-disk", $user['localePreference']);
		$system = $cceClient->getObject("System");

		// Initialize Capabilities so that we can poll the access rights as well:
		$Capabilities = new Capabilities($cceClient, $loginName, $sessionId);

		// -- Actual page logic start:

		// Not 'serverStatsServerDisk'? Bye, bye!
		if (!$Capabilities->getAllowed('serverStatsServerDisk')) {
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
		if (isset($get_form_data['short'])) {
			if ($get_form_data['short'] == "1") {
				$fancy = TRUE;
			}
		}
		$serverDiskUsage = "0";
		$activeMonitor = "0";
		if (isset($get_form_data['activeMonitor'])) {
			if ($get_form_data['activeMonitor'] == "1") {
				$activeMonitor = "1";
			}
		}
		if (isset($get_form_data['serverDiskUsage'])) {
			if ($get_form_data['serverDiskUsage'] == "1") {
				$serverDiskUsage = "1";
			}
		}
		//
		//--- Handle form validation:
		//

		// Shove submitted input into $form_data after passing it through the XSS filter:
		$form_data = $CI->input->post(NULL, TRUE);

		// Form fields that are required to have input:
		$required_keys = array("");
    	// Set up rules for form validation. These validations happen before we submit to CCE and further checks based on the schemas are done:

		// Empty array for key => values we want to submit to CCE:
    	$attributes = array();
    	// Items we do NOT want to submit to CCE:
    	$ignore_attributes = array("BlueOnyx_Info_Text");
		if (is_array($form_data)) {
			// Function GetFormAttributes() walks through the $form_data and returns us the $parameters we want to
			// submit to CCE. It intelligently handles checkboxes, which only have "on" set when they are ticked.
			// In that case it pulls the unticked status from the hidden checkboxes and addes them to $parameters.
			// It also transformes the value of the ticked checkboxes from "on" to "1". 
			//
			// Additionally it generates the form_validation rules for CodeIgniter.
			//
			// params: $i18n				i18n Object of the error messages
			// params: $form_data			array with form_data array from CI
			// params: $required_keys		array with keys that must have data in it. Needed for CodeIgniter's error checks
			// params: $ignore_attributes	array with items we want to ignore. Such as Labels.
			// return: 						array with keys and values ready to submit to CCE.
			$attributes = GetFormAttributes($i18n, $form_data, $required_keys, $ignore_attributes, $i18n);
		}
		//Setting up error messages:
		$CI->form_validation->set_message('required', $i18n->get("[[palette.val_is_required]]", false, array("field" => "\"%s\"")));		

	    // Do we have validation related errors?
	    if ($CI->form_validation->run() == FALSE) {

			if (validation_errors()) {
				// Set CI related errors:
				$ci_errors = array(validation_errors('<div class="alert dismissible alert_red"><img width="40" height="36" src="/.adm/images/icons/small/white/alarm_bell.png"><strong>', '</strong></div>'));
			}		    
			else {
				// No errors. Pass empty array along:
				$ci_errors = array();
			}
		}

		// If we have no errors and have POST data, we submit to CODB:
		if ((count($errors) == "0") && ($CI->input->post(NULL, TRUE))) {

			// We have no errors. We submit to CODB.

	  		// Actual submit to CODB:
			$cceClient->setObject("ActiveMonitor", $attributes, "Disk");

			// CCE errors that might have happened during submit to CODB:
			$CCEerrors = $cceClient->errors();
			foreach ($CCEerrors as $object => $objData) {
				// When we fetch the CCE errors it tells us which field it bitched on. And gives us an error message, which we can return:
				$errors[] = ErrorMessage($i18n->get($objData->message, true, array('key' => $objData->key)) . '<br>&nbsp;');
			}
			// Replace the CODB obtained values in our Form with the one we just posted to CCE:
			$email = $form_data;
		}

		//
		//--- Prepare page header:
		//

		// Prepare Page:
		$factory =& $serverScriptHelper->getHtmlComponentFactory('base-disk', $_SERVER['PHP_SELF']);
		$BxPage = $factory->getPage();
		$i18n = $factory->getI18n();

		// Set Menu items:
		if ($serverDiskUsage == "0") {
			$BxPage->setVerticalMenu('base_monitor');
			$BxPage->setVerticalMenuChild('base_amStatus');
			$page_module = 'base_sysmanage';
			$defaultPage = "basicSettingsTab";
		}
		else {
			$BxPage->setVerticalMenu('base_serverusage');
			$BxPage->setVerticalMenuChild('base_server_disk_usage');
			$page_module = 'base_sysmanage';
			$defaultPage = "basicSettingsTab";
		}

		if ($fancy == TRUE) {		
			$BxPage->setOutOfStyle(TRUE);
		}

		if ($fancy == TRUE) {
			$page_body[] = '<br><div id="main_container" class="container_16">';
		}

//---

		//
		//--- Define the PageBlock and our Tabs:
		//
		$block =& $factory->getPagedBlock('serverDiskUsage', array('summary', 'summaryVsite', 'users', 'settings'));
		$block->setToggle("#");
		$block->setSideTabs(FALSE);
		$block->setShowAllTabs("#");

		// Define some defaults as I removed the Qube3 compatability, which 
		// would have complicated this page even more than it already is:
		$hasWorkgroups = 0;
		$group_class = 'Vsite';
		$name_sort = 'fqdn';
		$group_name = 'name';  //internal CCE name of Vsite
		$groupcolumn_name = 'vsiteName';
		$group_used = 'vsiteUsed';
		$group_quota = 'vsiteQuota';

		//
		//--- Server Disk Usage:
		//

		// setup the scroll list
	    $usage_list =& $factory->getScrollList('summary', array(' ', 'partition', 'diskUsed', 'total', 'percentage'), array());

	    $usage_list->setAlignments(array('center', 'left', 'right', 'right', 'left'));
	    $usage_list->setDefaultSortedIndex('1');
	    $usage_list->setSortOrder('ascending');
	    $usage_list->setSortDisabled(array('0', '4'));
	    $usage_list->setPaginateDisabled(TRUE);
	    $usage_list->setSearchDisabled(TRUE);
	    $usage_list->setSelectorDisabled(FALSE);
	    $usage_list->enableAutoWidth(FALSE);
	    $usage_list->setInfoDisabled(TRUE);
	    $usage_list->setColumnWidths(array("20", "50", "120", "120", "449")); // Max: 739px
	    
	    // only display partitions that are mounted
	    $sort_index = $usage_list->getSortedIndex();
	    if ($sort_index == 1) {
	        $partitions = $cceClient->findSorted('Disk', 'mountPoint', array('mounted' => true));
	    }
	    else {
	        $sort_map = array(2 => 'used', 3 => 'total');
	        $partitions = $cceClient->findNSorted('Disk', $sort_map[2], array('mounted' => true));
	    }
	        
	    if ($usage_list->getSortOrder() == 'descending') {
	        $partitions = array_reverse($partitions);
	    }

	    // get AM object for quota percents and stuff
	    $am_obj = $cceClient->getObject('ActiveMonitor', array(), 'Disk');

	    for ($i = 0; ($i < count($partitions)); $i++) {
	      	//refresh partition info first
			$cceClient->set($partitions[$i], '', array('refresh' => time()));
	        $disk = $cceClient->get($partitions[$i]);
	        
	        if ($disk['used'] && $disk['total']) {
	    	    $percent = round(100 * ($disk['used'] / $disk['total']));
	    	    $used = simplify_number($disk['used']*1024, "KB", "1");
	    	    $total = simplify_number($disk['total']*1024, "KB", "1");
	    	}

	        $label =& $factory->getTextField($i, ($disk['label'] ? $disk['label'] : $disk['mountPoint']), 'r');
	        $label->setPreserveData(false);
	        $used_field =& $factory->getInteger("used$i", $used, '', '', 'r');
	        $used_field->setPreserveData(false);
	        $total_field =& $factory->getInteger("total$i", $total, '', '', 'r');
	        $total_field->setPreserveData(false);
	        
			if ($percent > $am_obj['red_pcnt']) {
			  	$status =& $factory->getStatusSignal('severeProblem');
			}
			elseif ($percent > $am_obj['yellow_pcnt']) {
			  	$status =& $factory->getStatusSignal('problem');
			}
			else {
			  	$status =& $factory->getStatusSignal('normal');
			} 

			// On an OpenVZ VPS or on AWS we only have one partition, although for sake of 
			// compatability we also have "/home" as Object "Disk". But this is not a real 
			// partition. Hence we want to hide it here:
			if ($disk['mountPoint'] == '/home') {
				$unlimited = $disk['total'];
			}
			if ((is_file("/proc/user_beancounters")) && ($disk['mountPoint'] == '/home')) {
			    continue;
			}
			elseif ((is_file("/etc/is_aws")) && ($disk['mountPoint'] == '/home')) {
			    continue;
			}
			else {

				$diskBar = $factory->getBar("bar$i", floor($percent), "");
				$diskBar->setBarText($percent . "%");
				$diskBar->setLabelType("nolabel");
				$diskBar->setHelpTextPosition("right");


	    	    $usage_list->addEntry(
	                array(
	  		    		$status,
	                    ($disk['label'] ? $disk['label'] : $disk['mountPoint']),
	                    $used . "B",
	                    $total . "B",
	                    $diskBar
	                ), '', false, $i);
	        }
	    }

	    // If all fails, assume / and set $unlimited so it's not undefined:
	    if (!isset($unlimited)) {
	    	$unlimited = $disk['total'];
	    }

		// Push out the Scrollist with the partition disk usage:
		$block->addFormField(
		        $factory->addBXDivider("summary", ""),
		        $factory->getLabel("summary", false),
		        'summary'
		        );			
		$block->addFormField(
			$factory->getRawHTML("summary", $usage_list->toHtml()),
			$factory->getLabel("summary"),
			'summary'
		);

		//
		//--- Prepare Disk Utilization Block:
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

		// Summary Stats:
		if (is_file("/var/log/sa/sa$gestern")) {
			$ret = $serverScriptHelper->shell("/usr/bin/sar -b -p -f /var/log/sa/sa$gestern", $saStatsGestern, 'root', $sessionId);

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
				if ((!in_array('Average:', $line)) && (!in_array('ArrayLinux', $line)) && (!in_array('tps', $line)) && (!in_array('Linux', $line)) && (!in_array('LINUX', $line))) {
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

		// Summary Stats:
		if (is_file("/var/log/sa/sa$heute")) {
			$ret = $serverScriptHelper->shell("/usr/bin/sar -b -p -f /var/log/sa/sa$heute", $saStatsHeute, 'root', $sessionId);

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
				if ((!in_array('Average:', $line)) && (!in_array('ArrayLinux', $line)) && (!in_array('tps', $line)) && (!in_array('Linux', $line)) && (!in_array('LINUX', $line))) {
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
					$PostProcessing['Server'][$line[0]] = array(
														'tps' => str_replace(",",".",$line[1]), 
														'rtps' => str_replace(",",".",$line[2]), 
														'wtps' => str_replace(",",".",$line[3]), 
														'bread/s' => str_replace(",",".",$line[4]), 
														'bwrtn/s' => str_replace(",",".",$line[5]), 
														'TimeStamp' => $line[0] 
														);

				}
				// Add seen netDevices:
				if (!in_array('Server', $netDevices)) {
					$netDevices[] = 'Server';
				}
				$num++;
			}
		}

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

		// Logs for all partitions:
		if (is_file("/var/log/sa/sa$gestern")) {
			$ret = $serverScriptHelper->shell("/usr/bin/sar -p -d -f /var/log/sa/sa$gestern", $saStatsGestern, 'root', $sessionId);

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
				if ((!in_array('Average:', $line)) && (!in_array('ArrayLinux', $line)) && (!in_array('tps', $line)) && (!in_array('Linux', $line)) && (!in_array('LINUX', $line))) {
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

		// Logs for all partitions:
		if (is_file("/var/log/sa/sa$heute")) {
			$ret = $serverScriptHelper->shell("/usr/bin/sar -p -d -f /var/log/sa/sa$heute", $saStatsHeute, 'root', $sessionId);

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
				if ((!in_array('Average:', $line)) && (!in_array('ArrayLinux', $line)) && (!in_array('tps', $line)) && (!in_array('Linux', $line)) && (!in_array('LINUX', $line))) {
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

		// Preserve previous number of timestamps:
		$timesIhad = $timeIhave;

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

				// Remove VolGroup from the device name:
				if (preg_match('/-/', $line[1])) {
					$devPieces = explode('-', $line[1]);
					$line[1] = $devPieces[1];
				}

				if (in_array($line[0], $time24h)) {
					// Build Output array:
					$PostProcessing[$line[1]][$line[0]] = array(
															'tps' => str_replace(",",".",$line[2]), 
															'rd_sec/s' => str_replace(",",".",$line[3]), 
															'wr_sec/s' => str_replace(",",".",$line[4]), 
															'avgrq-sz' => str_replace(",",".",$line[5]), 
															'avgqu-sz' => str_replace(",",".",$line[6]), 
															'await' => str_replace(",",".",$line[7]), 
															'svctm' => str_replace(",",".",$line[8]), 
															'%util' => str_replace(",",".",$line[9]), 
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
				if (($num == "5") || ($labelNum > "10") || ($timesIhad < "20")) {
					$seenTimes[$key] = "'" . $value . "'";
					$labelNum = "0";
				}
				else {
					$seenTimes[$key] = "''";
					$labelNum++;
				}
				$num++;
			}

			$block2 =& $factory->getPagedBlock('amDetails', "");
			$block2->setLabel($factory->getLabel('amDetails'));
			$block2->setToggle("#");
			$block2->setSideTabs(FALSE);
			$block2->setShowAllTabs("#");

			//
			//-- Summary Stats:
			//

			$block2->addPage('#', $factory->getLabel('#'));
			foreach ($Listo['Server'] as $key => $value) {
				$TPR['tps'][$key] = $value['tps'];
				$TPR['rtps'][$key] = $value['rtps'];
				$TPR['wtps'][$key] = $value['wtps'];

				$AVGWT['bread/s'][$key] = $value['bread/s'];
				$AVGWT['bwrtn/s'][$key] = $value['bwrtn/s'];
			}

			$block2->addFormField(
			        $factory->addBXDivider("summary"),
			        $factory->getLabel("summary", false),
			        "#"
			        );	

			$summary1 = $factory->getBarGraph("ServSum1", $TPR, $seenTimes);
			$summary1->setPoints('tps', FALSE);
			$summary1->setPoints('rtps', FALSE);
			$summary1->setPoints('wtps', FALSE);
			$block2->addFormField(
				$summary1,
				"",
				"#");

			$summary2 = $factory->getBarGraph("ServSum2", $AVGWT, $seenTimes);
			$summary2->setPoints('bread/s', FALSE);
			$summary2->setPoints('bwrtn/s', FALSE);
			$block2->addFormField(
				$summary2,
				"",
				"#");

			//
			//-- Other Partitions:
			//

			$outStatsnum = "0";

			// Sort Array of diskDevices:
			natcasesort($netDevices);
			foreach ($netDevices as $diskDev) {
				$block2->addPage($diskDev, $factory->getLabel($diskDev));
				foreach ($Listo[$diskDev] as $key => $value) {
					$BTPR['tps'][$key] = $value['tps'];
					$BTPR['rd_sec/s'][$key] = $value['rd_sec/s'];
					$BTPR['wr_sec/s'][$key] = $value['wr_sec/s'];

					$BAVGWT['avgrq-sz'][$key] = $value['avgrq-sz'];
					$BAVGWT['avgqu-sz'][$key] = $value['avgqu-sz'];
					$BAVGWT['await'][$key] = $value['await'];

				}

				$block2->addFormField(
				        $factory->addBXDivider(str_replace("-","_",$diskDev), ""),
				        $factory->getLabel($diskDev, false),
				        $diskDev
				        );	

				$cleanDev = str_replace("-","",$diskDev);
				$varname = "myGraph" . $outStatsnum;
				$$varname = $factory->getBarGraph("A$outStatsnum$cleanDev", $BTPR, $seenTimes);
				$$varname->setPoints('tps', FALSE);
				$$varname->setPoints('rd_sec/s', FALSE);
				$$varname->setPoints('wr_sec/s', FALSE);
				$block2->addFormField(
					$$varname,
					"",
					$diskDev);

				$varname = "myGraph2" . $outStatsnum;
				$$varname = $factory->getBarGraph("B$outStatsnum$cleanDev", $BAVGWT, $seenTimes);
				$$varname->setPoints('avgrq-sz', FALSE);
				$$varname->setPoints('avgqu-sz', FALSE);
				$$varname->setPoints('await', FALSE);
				$block2->addFormField(
					$$varname,
					"",
					$diskDev);

				$outStatsnum++;
			}

		}
		//-- Yay!

		//
		//--- Vsites: Over Quota Section:
		//

	    // Setup the scroll list
	    $vsite_list_oq =& $factory->getScrollList('OverQuota_Sites', array(' ', $groupcolumn_name, $group_used, $group_quota, 'percentage', ' '), array());

	    $vsite_list_oq->setAlignments(array('center', 'left', 'right', 'right', 'right', 'left'));
	    $vsite_list_oq->setDefaultSortedIndex('1');
	    $vsite_list_oq->setSortOrder('ascending');
	    $vsite_list_oq->setSortDisabled(array('0', '5'));
	    $vsite_list_oq->setPaginateDisabled(FALSE);
	    $vsite_list_oq->setSearchDisabled(FALSE);
	    $vsite_list_oq->setSelectorDisabled(FALSE);
	    $vsite_list_oq->enableAutoWidth(FALSE);
	    $vsite_list_oq->setInfoDisabled(FALSE);
	    $vsite_list_oq->setColumnWidths(array("20", "110", "180", "160", "249", "20")); // Max: 739px

	    $cmd = "/usr/sausalito/sbin/get_quotas.pl --sites --sort=name --descending | /bin/awk '{if ($3 != 0 && ($2 > $3 || $2 * 1.11 > $3)) print $1,$2,$3;}'";

	    $handle = $serverScriptHelper->popen($cmd, "r", "root");
	    $sites = array();
	    $numsites = 0;
		while (!feof($handle)) {
			$string = fgets($handle, 256);
			$string = chop($string);
			if (!$string) {
				// empty lines don't count
				continue;
			}
			$pieces = preg_split("/\s+/", $string);

			// If we don't have three pieces, the info is faulty and will be ignored:
			if (count($pieces) != '3') {
				continue;
			}

			// put into hash by name...
			$sites[$pieces[0]] = $pieces;
			// and by number
			$sites[$numsites] = $pieces;
			$numsites++;
		}

	    // this is used only when sites are sorted by name
	    $cce_vsites = $cceClient->findx('Vsite', array(), array(), 'hostname', 'fqdn');

	    $am_obj = $cceClient->getObject('ActiveMonitor', array(), 'Disk');

	    for ($i = 0; ($i < $numsites); $i++) {
		    // repquota results are sorted correctly
		    // so we take the sitename from there...
		    $site_info = $sites[$i];
		    $name = $site_info[0];
		    // lookup the CCE object corresponding to that sitename...
		    list($oid) = $cceClient->find('Vsite', array('name' => $name));
		    if (!$oid) {
				error_log("couldn't find CCE object for site name $name");
				continue;
		    }
		    $site_obj = $cceClient->get($oid);
		    // so we can get the fqdn...
		    $fqdn = $site_obj['fqdn'];
		    
		    $site_obj = $cceClient->get($oid, 'Disk');
		    // and their over_quota status
		    $user_over_quota = $site_obj['user_over_quota'];

		    // then use the repquota results for usage and quota
		    $used = $site_info[1];
		    $quota = $site_info[2];

			$used = sprintf("%.2f", $used / 1024); // convert into megs
			$quota = sprintf("%.2f", $quota / 1024); // convert into megs

			$total = $quota;
	    	// Prevent possible 'division by zero':
			if ($quota == "0") {
				$quota = $unlimited;
			}			
	        if ($quota > 0) {
	            $percent = round(100 * ($used / $quota));
	        }
	        else {
	            $percent = 0;
	        }

			$url = "/disk/groupDiskUsage?group=" . urlencode($name) . ($activeMonitor == 1 ? '&activeMonitor=1' : '&serverDiskUsage=1');
	        $label =& $factory->getUrl($i, $url, $fqdn, '', 'r');

	        if ($total > 0) {
	            $total_field =& $factory->getInteger("total$i", $total, '', '', 'r');
	        }
	        else {
	            $total_field =& $factory->getTextField("total$i", $i18n->interpolateHtml('[[base-disk.unlimited]]'), 'r');
	        }

			if ($percent > $am_obj['red_pcnt']) {
			  $status =& $factory->getStatusSignal('severeProblem');
			}
			elseif ($user_over_quota || ($percent > $am_obj['yellow_pcnt'])) {
				$status =& $factory->getStatusSignal('problem');
			}
			else {
			  	$status =& $factory->getStatusSignal('normal');
			}

			$diskBar = $factory->getBar("barOQVS$i", floor($percent), "");
			$diskBar->setBarText($percent . "%");
			$diskBar->setLabelType("nolabel");
			$diskBar->setHelpTextPosition("right");

	        $vsite_list_oq->addEntry(
	                array(
			    		$status,
	                    $label,
	                    $used . "MB",
	                    $total . "MB",
	                    $diskBar,
	                    $percent
	                ),
	                '', false, $i);
	    }

		// Push out the Scrollist with the Over-Quota stuff:
		$block->addFormField(
	        $factory->addBXDivider("OverQuota_Sites", ""),
	        $factory->getLabel("OverQuota_Sites", false),
	        'summaryVsite'
        );
		$block->addFormField(
			$factory->getRawHTML("OverQuota_Sites", $vsite_list_oq->toHtml()),
			$factory->getLabel("OverQuota_Sites"),
			'summaryVsite'
		);

		//
		//--- Vsites: All Vsites Quota Section:
		//

	    // Setup the scroll list
	    $vsite_list_all =& $factory->getScrollList('All_Sites', array(' ', $groupcolumn_name, $group_used, $group_quota, 'percentage', ' '), array());

	    $vsite_list_all->setAlignments(array('center', 'left', 'right', 'right', 'right', 'left'));
	    $vsite_list_all->setDefaultSortedIndex('1');
	    $vsite_list_all->setSortOrder('ascending');
	    $vsite_list_all->setSortDisabled(array('0', '4'));
	    $vsite_list_all->setPaginateDisabled(FALSE);
	    $vsite_list_all->setSearchDisabled(FALSE);
	    $vsite_list_all->setSelectorDisabled(FALSE);
	    $vsite_list_all->enableAutoWidth(FALSE);
	    $vsite_list_all->setInfoDisabled(FALSE);
	    $vsite_list_all->setColumnWidths(array("20", "110", "180", "160", "249", "20")); // Max: 739px

	    $cmd = "/usr/sausalito/sbin/get_quotas.pl --sites --sort=name descending";

	    $handle = $serverScriptHelper->popen($cmd, "r", "root");
	    $sites = array();
	    $numsites = 0;
		while (!feof($handle)) {
			$string = fgets($handle, 256);
			$string = chop($string);
			if (!$string) {
				// empty lines don't count
				continue;
			}
			$pieces = preg_split("/\s+/", $string);

			// If we don't have three pieces, the info is faulty and will be ignored:
			if (count($pieces) != '3') {
				continue;
			}

			// put into hash by name...
			$sites[$pieces[0]] = $pieces;
			// and by number
			$sites[$numsites] = $pieces;
			$numsites++;
		}

	    // this is used only when sites are sorted by name
	    $cce_vsites = $cceClient->findx('Vsite', array(), array(), 'hostname', 'fqdn');

	    $am_obj = $cceClient->getObject('ActiveMonitor', array(), 'Disk');
	    
	    for ($i = 0; ($i < $numsites); $i++){
		    // CCE find results are sorted correctly
		    // so we get the CCE object from there...
		    $oid = $cce_vsites[$i];
		    $site_obj = $cceClient->get($oid);
		    // to find the fqdn...
		    $fqdn = $site_obj['fqdn'];
		    // and the sitename...
		    $name = $site_obj['name'];

		    // and the over_quota status...
		    $site_obj = $cceClient->get($oid, 'Disk');
		    $user_over_quota = $site_obj['user_over_quota'];

		    // we then use the sitename to figure out which
		    // entry from the repquota results we need...
		    $site_info = $sites[$name];
		    // and lookup the usage info
		    $used = $site_info[1];
		    $quota = $site_info[2];

			$used = sprintf("%.2f", $used / 1024); // convert into megs
			$quota = sprintf("%.2f", $quota / 1024); // convert into megs

			$total = $quota;
	    	// Prevent possible 'division by zero':
			if ($quota == "0") {
				$quota = $unlimited;
			}	
	        if ($quota > 0) {
	            $percent = round(100 * ($used / $quota));
	        }
	        else {
	            $percent = 0;
	        }

			$url = "/disk/groupDiskUsage?group=" . urlencode($name) . ($activeMonitor == 1 ? '&activeMonitor=1' : '&serverDiskUsage=1');
			
			if ($fqdn != "") { 
			
	    	    $label =& $factory->getUrl($i, $url, $fqdn, '', 'r');
	    	    $label->setPreserveData(false);

	    	    if ($total > 0) {
	            	$total_field =& $factory->getInteger("total$i", $total, '', '', 'r');
	    	    }
	    	    else  {
	        		$total_field =& $factory->getTextField("total$i", $i18n->interpolateHtml('[[base-disk.unlimited]]'),'r');
	    	    }
	        
	    	    $total_field->setPreserveData(false);

			    if ($percent > $am_obj['red_pcnt']) {
					$status =& $factory->getStatusSignal('severeProblem');
			    }
			    elseif ($user_over_quota || ($percent > $am_obj['yellow_pcnt'])) {
			    	$status =& $factory->getStatusSignal('problem');
			    }
			    else {
					$status =& $factory->getStatusSignal('normal');
			    }

				$diskBar = $factory->getBar("barVSusage$i", floor($percent), "");
				$diskBar->setBarText($percent . "%");
				$diskBar->setLabelType("nolabel");
				$diskBar->setHelpTextPosition("right");

	    	    $vsite_list_all->addEntry(
	                array(
			    		$status,
	                    $label,
	                    $used . "MB",
	                    $total . "MB",
	                    $diskBar,
	                    $percent
	                ),
	                '', false, $i);
			}
	    }

		// Push out the Scrollist with all Vsites:
		$block->addFormField(
	        $factory->addBXDivider("All_Sites", ""),
	        $factory->getLabel("All_Sites", false),
	        'summaryVsite'
        );
		$block->addFormField(
			$factory->getRawHTML("All_Sites", $vsite_list_all->toHtml()),
			$factory->getLabel("All_Sites"),
			'summaryVsite'
		);

//---

		//
		//--- All Users: Over Quota Section:
		//

	    // Setup the scroll list
	    $usage_list_oq =& $factory->getScrollList('OverQuota_Users', array(' ', 'user', 'vsiteName', 'used', 'quota', 'percentage', ' '), array());

	    $usage_list_oq->setAlignments(array('center', 'left', 'left', 'right', 'right', 'left', 'center'));
	    $usage_list_oq->setDefaultSortedIndex('1');
	    $usage_list_oq->setSortOrder('ascending');
	    $usage_list_oq->setSortDisabled(array('0', '4'));
	    $usage_list_oq->setPaginateDisabled(FALSE);
	    $usage_list_oq->setSearchDisabled(FALSE);
	    $usage_list_oq->setSelectorDisabled(FALSE);
	    $usage_list_oq->enableAutoWidth(FALSE);
	    $usage_list_oq->setInfoDisabled(FALSE);
	    $usage_list_oq->setColumnWidths(array("20", "110", "180", "80", "80", "249", "20")); // Max: 739px
	    
	    $cmd = "/usr/sausalito/sbin/get_quotas.pl --sort=name --descending | /bin/awk '{if ($3 != 0 && ($2 > $3 || $2 * 1.11 > $3)) print $1,$2,$3;}'";
	    $handle = $serverScriptHelper->popen($cmd, "r", "root");
	    
	    $users = array();
		while (!feof($handle)) {
			$string = fgets($handle, 256);
			$string = chop($string);
			if (!$string) {
				// empty lines don't count
				continue;
			}
			$pieces = preg_split("/\s+/", $string);

			// If we don't have three pieces, the info is faulty and will be ignored:
			if (count($pieces) != '3') {
				continue;
			}

			$users[] = $pieces;
		}
	    
	    $am_obj = $cceClient->getObject('ActiveMonitor', array(), 'Disk');

	    for ($i = 0; ($i < count($users)); $i++) {
	        
	        $user_info = $users[$i];
			$name = $user_info[0];
			$used = $user_info[1];
			$quota = $user_info[2];

			// Get the Vsite of this user:
	        list($users_vsite) = $cceClient->find("User", array("name" => $name));
	        $user_siteObj = $cceClient->get($users_vsite);
	        $users_site = $user_siteObj["site"];
	        if ($users_site) {
	            list($the_vsite) = $cceClient->find("Vsite", array("name" => $user_siteObj["site"]));
			    $the_siteObj = $cceClient->get($the_vsite);
			    $fqdn = $the_siteObj["fqdn"];
			    $url = "/disk/groupDiskUsage?group=" . urlencode($users_site) . ($activeMonitor == 1 ? '&activeMonitor=1' : '&serverDiskUsage=1');
	    	    $site =& $factory->getUrl($i, $url, $fqdn, '', 'r');
	    	    $NameUrl = "/user/userMod?group=" . urlencode($users_site) . '&name=' . urlencode($name);
	    	    $name =& $factory->getUrl($i, $NameUrl, $name, '', 'r');
	    	}
	    	else {
	    	    $site = "-- Server --";
	    	}
	
    	    $percent = round(100 * ($used / $quota));
    	    $used_simple = simplify_number($used*1024, "KB", "2");
    	    $total_simple = sprintf("%.2f", $quota / 1024) . "MB"; // convert into megs

	        $total = $quota;
	        if ($quota > 0) {
	            $percent = round(100 * ($used / $quota));
	        }
	        else {
	            $percent = 0;
	        }

	        if ($total > 0) {
	            $total_field =& $factory->getInteger("total$i", $total, '', '', 'r');
			    if ($percent >= $am_obj['red_pcnt']) {
			      $status =& $factory->getStatusSignal('severeProblem');
			    }
			    elseif ($percent >= $am_obj['yellow_pcnt']) {
			      $status =& $factory->getStatusSignal('problem');
			    }
			    else {
			      $status =& $factory->getStatusSignal('normal');
			    } 
	        }
	        else {
	            $i18n =& $factory->getI18n();
	            $total_field =& $factory->getTextField("total$i", $i18n->interpolateHtml('[[base-disk.unlimited]]'), 'r');
		    	$status =& $factory->getStatusSignal('normal');
	        }
	        
			$user_diskBar = $factory->getBar("userOQbar$i", floor($percent), "");
			$user_diskBar->setBarText($percent . "%");
			$user_diskBar->setLabelType("nolabel");
			$user_diskBar->setHelpTextPosition("right");

	        $usage_list_oq->addEntry(
	                array(
	            	    $status,
					    $name,
					    $site,
	                    $used_simple,
	                    $total_simple,
	                    $user_diskBar,
	                    $percent
	                ),
	                '', false, $i);
	    }

		// Push out the Scrollist with the Over-Quota stuff:
		$block->addFormField(
	        $factory->addBXDivider("OverQuota_Users", ""),
	        $factory->getLabel("OverQuota_Users", false),
	        'users'
        );
		$block->addFormField(
			$factory->getRawHTML("OverQuota_Users", $usage_list_oq->toHtml()),
			$factory->getLabel("OverQuota_Users"),
			'users'
		);

		//
		// Full Quota section:
		//

	    // setup the scroll list
	    $usage_list_full =& $factory->getScrollList('All_Users', array(' ', 'user', 'vsiteName', 'used', 'quota', 'percentage', ' '), array());

	    $usage_list_full->setAlignments(array('center', 'left', 'left', 'right', 'right', 'left', 'center'));
	    $usage_list_full->setDefaultSortedIndex('1');
	    $usage_list_full->setSortOrder('ascending');
	    $usage_list_full->setSortDisabled(array('0', '5'));
	    $usage_list_full->setPaginateDisabled(FALSE);
	    $usage_list_full->setSearchDisabled(FALSE);
	    $usage_list_full->setSelectorDisabled(FALSE);
	    $usage_list_full->enableAutoWidth(FALSE);
	    $usage_list_full->setInfoDisabled(FALSE);
	    $usage_list_full->setColumnWidths(array("20", "110", "180", "80", "80", "249", "20")); // Max: 739px

	    $cmd = "/usr/sausalito/sbin/get_quotas.pl --sort=name --descending";
	    $handle = $serverScriptHelper->popen($cmd, "r", "root");
	    
	    $users = array();
		while (!feof($handle)) {
			$string = fgets($handle, 256);
			$string = chop($string);
			if (!$string) {
				// empty lines don't count
				continue;
			}
			$pieces = preg_split("/\s+/", $string);

			// If we don't have three pieces, the info is faulty and will be ignored:
			if (count($pieces) != '3') {
				continue;
			}

			$users[] = $pieces;
		}
    
	    $am_obj = $cceClient->getObject('ActiveMonitor', array(), 'Disk');

	    for ($i = 0; ($i < count($users)); $i++){
	        
	        $user_info = $users[$i];
			$name = $user_info[0];
			$used = $user_info[1];
			$quota = $user_info[2];

			// Get the Vsite of this user:
			$users_vsite = '';
	        @list($users_vsite) = $cceClient->find("User", array("name" => $name));
	        if (isset($users_vsite)) {

		        $user_siteObj = $cceClient->get($users_vsite);
		        $users_site = $user_siteObj["site"];
		        if ($users_vsite) {
		        	$the_vsite = "";
		        	@list($the_vsite) = $cceClient->find("Vsite", array("name" => $user_siteObj["site"]));

			        if (isset($the_vsite)) {
					    $the_siteObj = $cceClient->get($the_vsite);
					    $fqdn = $the_siteObj["fqdn"];
					    $url = "/disk/groupDiskUsage?group=" . urlencode($users_site) . ($activeMonitor == 1 ? '&activeMonitor=1' : '&serverDiskUsage=1');
			    	    $site =& $factory->getUrl($i, $url, $fqdn, '', 'r');
			    	    $NameUrl = "/user/userMod?group=" . urlencode($users_site) . '&name=' . urlencode($name);
			    	    $name =& $factory->getUrl($i, $NameUrl, $name, '', 'r');
			    	}
			    	else {
			    		// This ought to be an 'adminUser' or 'systemAdministrator':
			    		$site = "-- Server --";
			    		if ($name == "admin") {
			    			// It's 'admin' himself. Which honor.
				    	    $NameUrl = "/user/personalAccount";
				    	    $name =& $factory->getUrl($i, $NameUrl, $name, '', 'r');
			    		}
			    		else {
			    			// Not 'admin'? Ok, then it is an 'adminUser' or 'systemAdministrator':
				    	    $NameUrl = "/vsite/manageAdmin?MODIFY=1&_oid=" . urlencode($user_siteObj['OID']);
				    	    $name =& $factory->getUrl($i, $NameUrl, $name, '', 'r');
			    		}
			    	}
		    	}
		    }
	    	else {
	    		// This is not a Vsite user OR a 'systemAdministrator':
	    	    $site = "-- Server --";
	    	    $NameUrl = "javascript: void(0)";
	    	    $name =& $factory->getUrl($i, $NameUrl, $name, '', 'r');
	    	}

	    	// Prevent possible 'division by zero':
			if ($quota == "0") {
				$quota = $unlimited;
			}
		    $percent = round(100 * ($used / $quota));
    	    //$used_simple = simplify_number($used*1024, "KB", "2");
    	    $used_simple = sprintf("%.2f", $used / 1024); // convert into megs
    	    $total_simple = sprintf("%.2f", $quota / 1024); // convert into megs

	        $total = $quota;
	        if ($quota > 0) {
	            $percent = round(100 * ($used / $quota));
	        }
	        else {
	            $percent = 0;
	        }

	        if ($total > 0) {
	            $total_field =& $factory->getInteger("total$i", $total, '', '', 'r');
			    if ($percent >= $am_obj['red_pcnt']) {
			      $status =& $factory->getStatusSignal('severeProblem');
			    } else if ($percent >= $am_obj['yellow_pcnt']) {
			      $status =& $factory->getStatusSignal('problem');
			    } else {
			      $status =& $factory->getStatusSignal('normal');
			    } 
	        }
	        else {
	            $i18n =& $factory->getI18n();
	            $total_field =& $factory->getTextField("total$i", $i18n->interpolateHtml('[[base-disk.unlimited]]'), 'r');
		    	$status =& $factory->getStatusSignal('normal');
	        }

			$userF_diskBar = $factory->getBar("userFullbar$i", floor($percent), "");
			$userF_diskBar->setBarText($percent . "%");
			$userF_diskBar->setLabelType("nolabel");
			$userF_diskBar->setHelpTextPosition("right");
	        
	        $usage_list_full->addEntry(
	                array(
	            	    $status,
					    $name,
					    $site,
	                    $used_simple . "MB",
	                    $total_simple. "MB",
	                    $userF_diskBar,
	                    $percent
	                ),
	                '', false, $i);
	    }		

		// Push out the Scrollist with the Over-Quota stuff:
		$block->addFormField(
	        $factory->addBXDivider("All_Users", ""),
	        $factory->getLabel("All_Users", false),
	        'users'
        );		
		$block->addFormField(
			$factory->getRawHTML("All_Users", $usage_list_full->toHtml()),
			$factory->getLabel("All_Users"),
			'users'
		);

		//
		//--- Notification Settings:
		//

		$block->addFormField(
		        $factory->addBXDivider("user_exceeds", ""),
		        $factory->getLabel("user_exceeds", false),
		        'settings'
		        );		

		$block->addFormField(
		  $factory->getBoolean("mail_admin_on_user", $am_obj['mail_admin_on_user']),
		  $factory->getLabel("mail_admin_on_user"),
		  'settings'
		);

		$block->addFormField(
		  $factory->getBoolean("mail_user", $am_obj['mail_user']),
		  $factory->getLabel("mail_user"),
		  'settings'
		);

		$block->addFormField(
		        $factory->addBXDivider("site_exceeds", ""),
		        $factory->getLabel("site_exceeds", false),
		        'settings'
		        );		

		$block->addFormField(
		  $factory->getBoolean("mail_admin_on_vsite", $am_obj['mail_admin_on_vsite']),
		  $factory->getLabel("mail_admin_on_vsite"),
		  'settings'
		);

		// Add the Save-Button. We use a getButtonContainer() so that we can limit the appearance
		// of the Save-Button to the "Settings"-tab:
		$settings_save = $factory->getSaveButton($BxPage->getSubmitAction());
		$block->addFormField($factory->getButtonContainer("", $settings_save), "", 'settings');

//---

		// Print Page:
		$page_body[] = $block->toHtml();

		if (isset($PostProcessing)) {
			$page_body[] = $block2->toHtml();
		}

		if ($fancy == TRUE) {
			$page_body[] = '</div>';
		}
		elseif ($serverDiskUsage = "1") {
			// Don't show "Back" Button:
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