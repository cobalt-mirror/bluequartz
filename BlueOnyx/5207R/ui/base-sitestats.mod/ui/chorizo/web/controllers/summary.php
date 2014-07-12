<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Summary extends MX_Controller {

	/**
	 * Index Page for this controller.
	 *
	 * Past the login page this loads the page for /sitestats/summary.
	 *
	 */

	// Note to self: This ain't finished by a long shot. But after wasting four days
	// of making sense of the old code and trying to clean it up I've simply got nuff
	// for now. The ScrollList statistics for email usage by domain or web traffic by
	// domain are only as good as the underlying Analog data. And that's already where
	// the fish is stinking. So do I really want to figure out a way to present the
	// faulty stats in a nice looking way? I don't think so. Not now.

	public function index() {

		$CI =& get_instance();
		
	    // We load the BlueOnyx helper library first of all, as we heavily depend on it:
	    $this->load->helper('blueonyx');
	    init_libraries();
		$this->load->helper('selector_helper');
		$this->load->library('ReportHelper');

  		// Need to load 'BxPage' for page rendering:
  		$this->load->library('BxPage');
		$MX =& get_instance();

	    // Get $sessionId and $loginName from Cookie (if they are set):
	    $sessionId = $CI->input->cookie('sessionId');
	    $loginName = $CI->input->cookie('loginName');
	    $locale = $CI->input->cookie('locale');

	    // Line up the ducks for CCE-Connection:
	    include_once('ServerScriptHelper.php');
		$serverScriptHelper = new ServerScriptHelper($sessionId, $loginName);
		$cceClient = $serverScriptHelper->getCceClient();
		$user = $cceClient->getObject("User", array("name" => $loginName));
		$i18n = new I18n("base-sitestats", $user['localePreference']);
		$system = $cceClient->getObject("System");

		// Initialize Capabilities so that we can poll the access rights as well:
		$Capabilities = new Capabilities($cceClient, $loginName, $sessionId);

		// -- Actual page logic start:

	    // We start without any active errors:
	    $errors = array();
	    $extra_headers =array();
	    $ci_errors = array();
	    $my_errors = array();

		//
		//--- URL String parsing:
		//
		$type = '';
		$group = '';
		$noback = '';
		$io = '';
		$period = '';
		$get_form_data = $CI->input->get(NULL, TRUE);
		if (isset($get_form_data['type'])) {
			$type = $CI->security->xss_clean($get_form_data['type']);
			$type = $CI->security->sanitize_filename($type);
		}
		if (isset($get_form_data['group'])) {
			$group = $CI->security->xss_clean($get_form_data['group']);
			$group = $CI->security->sanitize_filename($group);
		}
		if (isset($get_form_data['inframe'])) {
			$inframe = $get_form_data['inframe'];
		}
		if (isset($get_form_data['noback'])) {
			$noback = $get_form_data['noback'];
		}
		if (isset($get_form_data['io'])) {
			$io = $get_form_data['io'];
		}
		if (isset($get_form_data['period'])) {
			$period = $get_form_data['period'];
		}

		// Only menuServerServerStats, manageSite and siteAdmin should be here:
		if (!$Capabilities->getAllowed('menuServerServerStats') &&
			!$Capabilities->getAllowed('manageSite') &&
		    !($Capabilities->getAllowed('siteAdmin') &&
		      $group == $Capabilities->loginUser['site'])) {
			// Nice people say goodbye, or CCEd waits forever:
			$cceClient->bye();
			$serverScriptHelper->destructor();
			Log403Error("/gui/Forbidden403");
		}

		if (!isset($group)) {
			$group = "server";
		}
		if (!isset($type)) {
			$type = "network";
		}

		// Shove submitted input into $form_data after passing it through the XSS filter:
		$form_data = $CI->input->post(NULL, TRUE);

		// Form fields that are required to have input:
		$required_keys = array("");

    	// Set up rules for form validation. These validations happen before we submit to CCE and further checks based on the schemas are done:

		// Empty array for key => values we want to submit to CCE:
    	$attributes = array();
    	// Items we do NOT want to submit to CCE:
    	$ignore_attributes = array();
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

		//
		//--- Own error checks:
		//

		if ($CI->input->post(NULL, TRUE)) {
			// Cheesily strip leading zeroes.  
			$attributes['_endDate_month']  = preg_replace('/^0/', '', $attributes['_endDate_month']);
			$attributes['_startDate_month']  = preg_replace('/^0/', '', $attributes['_startDate_month']);
			$attributes['_endDate_day']  = preg_replace('/^0/', '', $attributes['_endDate_day']);
			$attributes['_startDate_day']  = preg_replace('/^0/', '', $attributes['_startDate_day']);
		}

		//
		//--- At this point all checks are done. If we have no errors, we can submit the data to CODB:
		//

		// Join the various error messages:
		$errors = array_merge($ci_errors, $my_errors);

		// If we have no errors and have POST data, we submit to CODB:
		if ((count($errors) == "0") && ($CI->input->post(NULL, TRUE))) {

			// We have no errors. We submit to CODB.
			$config = array(
				"startDay" => $attributes['_startDate_day'],
				"startMonth" => $attributes['_startDate_month'],
				"startYear" => $attributes['_startDate_year'],
				"endDay" => $attributes['_endDate_day'],
				"endMonth" => $attributes['_endDate_month'],
				"endYear" => $attributes['_endDate_year'],
				"report" => $type,
				"update" => time(),
				"site" => $group,
			);

	  		// Actual submit to CODB:
			$cceClient->setObject("System", $config, "Sitestats");

			// CCE errors that might have happened during submit to CODB:
			$CCEerrors = $cceClient->errors();
			foreach ($CCEerrors as $object => $objData) {
				// When we fetch the CCE errors it tells us which field it bitched on. And gives us an error message, which we can return:
				$errors[] = ErrorMessage($i18n->get($objData->message, true, array('key' => $objData->key)) . '<br>&nbsp;');
			}

			// No errors during submit? Reload page:
			if (count($errors) == "0") {
				$cceClient->bye();
				$serverScriptHelper->destructor();
				$redirect_URL = "/sitestats/summary?group=$group&type=$type";
				header("location: $redirect_URL");
				exit;
			}

		}

		//
		//--- Get CODB Item of Interest:
		//

		$Sitestats = $cceClient->getObject("System", array(), "Sitestats");

//---

		$defaultPage = "basicSettingsTab";
		$factory =& $serverScriptHelper->getHtmlComponentFactory('base-sitestats', "/sitestats/summary?group=$group&type=$type");

		// Prepare Page:
		$BxPage = $factory->getPage();
		$BxPage->setErrors($errors);
		$i18n = $factory->getI18n();

		//
		//--- Configure $type Reporting Options:
		//

		$block = $factory->getPagedBlock("generateSettings", array($defaultPage));
		$block->setToggle("#");
		$block->setSideTabs(FALSE);
		$block->setDefaultPage($defaultPage);

		// Set Menu items:
		if ($group == "server") {
			$BxPage->setVerticalMenu('base_serverusage');
			$page_module = 'base_sysmanage';

			if (($type == "net") && ($group == "server")) {
				$BxPage->setVerticalMenuChild('base_netusage');
			}
			elseif (($type == "web") && ($group == "server")) {
				$BxPage->setVerticalMenuChild('base_server_webusage');
			}
			elseif (($type == "mail") && ($group == "server")) {
				$BxPage->setVerticalMenuChild('base_server_mailusage');
			}
			elseif (($type == "ftp") && ($group == "server")) {
				$BxPage->setVerticalMenuChild('base_server_ftpusage');
			}
			else {
				$BxPage->setVerticalMenuChild('base_netusage');
			}
		}
		else {

			$BxPage->setVerticalMenu('base_siteusage');
			$BxPage->setVerticalMenuChild('base_webusage');
			$page_module = 'base_sitemanage';
			if (($type == "net") && ($group != "server")) {
				$BxPage->setVerticalMenuChild('base_netusage');
			}
			elseif (($type == "web") && ($group != "server")) {
				$BxPage->setVerticalMenuChild('base_webusage');
			}
			elseif (($type == "mail") && ($group!= "server")) {
				$BxPage->setVerticalMenuChild('base_mailusage');
			}
			elseif (($type == "ftp") && ($group != "server")) {
				$BxPage->setVerticalMenuChild('base_ftpusage');
			}
			else {
				$BxPage->setVerticalMenuChild('base_webusage');
			}
		}

		$typestring = $i18n->interpolate("[[base-sitestats." . $type . "usage]]");
		$i18nvars['type'] = $typestring;
		if (isset($group) && $group != 'server') {
			list($vsite) = $cceClient->find('Vsite', array('name' => $group));
			$vsiteObj = $cceClient->get($vsite);
			$i18nvars['fqdn'] = $vsiteObj['fqdn'];
			$block->setLabel($factory->getLabel('generateSettingsVsite', false, $i18nvars));
		}
		else {
			$block->setLabel($factory->getLabel('generateSettings', false, $i18nvars));
		}

		$sdate = time();
		if (isset($Sitestats['startYear'])) {
			$sdate = $Sitestats['startYear'] . ":" . $Sitestats['startMonth'] . ":" . $Sitestats['startDay'] . ":00:00:00";
		}
		$edate = time();
		if (isset($Sitestats['endYear'])) {
			$edate = $Sitestats['endYear'] . ":" . $Sitestats['endMonth'] . ":" . $Sitestats['endDay'] . ":23:59:59";
		}

		$block->addFormField(
			$factory->getTimeStamp("startDate", $sdate, "date"),
			$factory->getLabel("startDate"),
			$defaultPage);

		$block->addFormField(
			$factory->getTimeStamp("endDate", $edate, "date"),
		    $factory->getLabel("endDate"),
		    $defaultPage);

		// Add the buttons
		$block->addButton($factory->getSaveButton($BxPage->getSubmitAction()));

		//
		//--- We have NO data to present for the specified time frame:
		//

		$nodata = FALSE;
		$report = new ReportHelper($type, $group, $user['localePreference'], "");
		$myData = $report->getData("/^x\s+(\S+)\s+(\S+)(\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+))?/");

		// Report that no data has been found for the given period:
		if ((!isset($myData)) || (!is_array($myData))) {
				$block->addFormField(
					$factory->getRawHTML("filler", stats_no_data($type, $group, $factory)),
					$factory->getLabel(" "),
					$defaultPage
				);
				$nodata = TRUE;
		}

//---

		if ($nodata != TRUE) {
			//
			//--- Data collection and assembly phase:
			//
			$label = "label";
			$field = "field";

			if ($type == "ftp") {	
				$totals = getInOut($report);
				$entry["NR"][$label] = $factory->getLabel("totalReceived");
				$entry["NR"][$field] = $factory->getTextField("totalReceivedVal", number_format($totals["in"]), "r");
				$entry["NS"][$label] = $factory->getLabel("totalSent");
				$entry["NS"][$field] = $factory->getTextField("totalSentVal", number_format($totals["out"]), "r");
			}

			for ($i = 0; $i < count($myData); $i++) {
				switch ($myData[$i][1]) {
				case "PS": 
					$entry["PS"][$label] = $factory->getLabel("reportGenerated");
					$BxPage->setLabel('generated', $i18n->getClean("reportGenerated"), $i18n->getWrapped("reportGenerated_help"));
					$entry["PS"][$field] = $factory->getTimeStamp("generated", mktime($myData[$i][6], $myData[$i][7], 0, $myData[$i][4], $myData[$i][5], $myData[$i][2]), "datetime", "r");			
					break;
					
				case "FR":
					$entry["FR"][$label] = $factory->getLabel("firstRequest");
					$BxPage->setLabel('first', $i18n->getClean("firstRequest"), $i18n->getWrapped("firstRequest_help"));
					$entry["FR"][$field] = $factory->getTimeStamp("first", mktime($myData[$i][6], $myData[$i][7], 0, $myData[$i][4], $myData[$i][5], $myData[$i][2]), "datetime", "r" );  
					break;
					
				case "LR":	
					$entry["LR"][$label] = $factory->getLabel("lastRequest");
					$BxPage->setLabel('last', $i18n->getClean("lastRequest"), $i18n->getWrapped("lastRequest_help"));
					$entry["LR"][$field] = $factory->getTimeStamp("last", mktime($myData[$i][6], $myData[$i][7], 0, $myData[$i][4], $myData[$i][5], $myData[$i][2]), "datetime", "r" );
					break;
					
				case "NH":
					$entry["NH"][$label] = $factory->getLabel("uniqueHosts");
					$BxPage->setLabel('hosts', $i18n->getClean("uniqueHosts"), $i18n->getWrapped("uniqueHosts_help"));
					$entry["NH"][$field] = $factory->getTextField("hosts", number_format($myData[$i][2]), "r");
					break;
					
				case "SR":
					$entry["SR"][$label] = $factory->getLabel("successfulRequests");
					$BxPage->setLabel('requests', $i18n->getClean("successfulRequests"), $i18n->getWrapped("successfulRequests_help"));
					$entry["SR"][$field] = $factory->getTextField("requests", number_format($myData[$i][2]), "r");
					if (!$myData[$i][2]) {
						$noData = true;
					}
					break;
					
				case "PR":
					$entry["PR"][$label] = $factory->getLabel("pageRequests");	
					$BxPage->setLabel('pgrequests', $i18n->getClean("pageRequests"), $i18n->getWrapped("pageRequests_help"));
					$entry["PR"][$field] = $factory->getTextField("pgrequests", number_format($myData[$i][2]), "r");
					break;
					
				case "FL":
					$entry["FL"][$label] = $factory->getLabel("failedRequests");
					$BxPage->setLabel('failrequests', $i18n->getClean("failedRequests"), $i18n->getWrapped("failedRequests_help"));
					$entry["FL"][$field] = $factory->getTextField("failrequests", number_format($myData[$i][2]), "r");
					break;
					
				case "NF":
					$entry["NF"][$label] = $factory->getLabel("distinctFiles");
					$BxPage->setLabel('distinctFiles', $i18n->getClean("distinctFiles"), $i18n->getWrapped("distinctFiles_help"));
					$entry["NF"][$field] = $factory->getTextField("distinctFiles", number_format($myData[$i][2]), "r");
					break;
					
				case "BT":
					$entry["BT"][$label] = $factory->getLabel("bytesTransfered");
					$BxPage->setLabel('bytesTransfered', $i18n->getClean("bytesTransfered"), $i18n->getWrapped("bytesTransfered_help"));
					$entry["BT"][$field] = $factory->getTextField("bytesTransfered", $report->formatBytes($myData[$i][2]), "r");
					break;
				}
			}

			if (!isset($entry["FL"])) {
				$entry["FL"][$label] = $factory->getLabel("failedRequests");
				$BxPage->setLabel('failedRequests', $i18n->getClean("failedRequests"), $i18n->getWrapped("failedRequests_help"));
				$entry["FL"][$field] = $factory->getTextField("failedRequests", "0", "r" );
			}
			// Might be empty:
			if (!isset($entry["FR"])) {
				$entry["FR"][$label] = $factory->getLabel("firstRequest");
				$BxPage->setLabel('first', $i18n->getClean("firstRequest"), $i18n->getWrapped("firstRequest_help"));
				$entry["FR"][$field] = $factory->getTextField("first", "-/-", "r" );  
			}
			if (!isset($entry["LR"])) {
				$entry["LR"][$label] = $factory->getLabel("lastRequest");
				$BxPage->setLabel('last', $i18n->getClean("lastRequest"), $i18n->getWrapped("lastRequest_help"));
				$entry["LR"][$field] = $factory->getTextField("last", "-/-", "r" );
			}
//			if (!isset($entry["BT"])) {
//				$entry["BT"][$label] = $factory->getLabel("bytesTransfered");
//				$BxPage->setLabel('bytesTransfered', $i18n->getHtml("bytesTransfered"), $i18n->getWrapped("bytesTransfered_help"));
//				$entry["BT"][$field] = $factory->getTextField("bytesTransfered", "0", "r");
//			}

//-------------@@@: NOTE: Either one of these two needs to be enabled or disabled ^ or ->

			// Only report no data on generate report submission (via $nodump GET flag)

			//
			//--- Report that no data has been found for the given period:
			//

			if (!isset($entry["BT"])) {
				$block->addFormField(
					$factory->getRawHTML("filler", stats_no_data($type, $group, $factory)),
					$factory->getLabel(" "),
					$defaultPage
				);
				$nodata = TRUE;
			}
		}

		$page_body[] = $block->toHtml();

//---

		if ($nodata != TRUE) {

			//
			//--- $type Statistics Summary:
			//

			// Define PagedBlock for our Statistics Summary:
			$blocksummaryStats = $factory->getPagedBlock("summaryStats", array($defaultPage));
			$blocksummaryStats->setToggle("#");
			$blocksummaryStats->setSideTabs(FALSE);

			$typestring = $i18n->interpolate("[[base-sitestats." . $type . "usage]]");
			$i18nvars['type'] = $typestring;

			// figure out if this is the server or site stats
			if (isset($group) && ($group != 'server')) {
				$cceClient = $serverScriptHelper->getCceClient();
				list($vsite) = $cceClient->find('Vsite', array('name' => $group));
				$vsiteObj = $cceClient->get($vsite);
				$i18nvars['fqdn'] = $vsiteObj['fqdn'];
				$blocksummaryStats->setLabel($factory->getLabel('summaryStatsVsite', false, $i18nvars));
			} else {
				$blocksummaryStats->setLabel($factory->getLabel('summaryStats', false, $i18nvars));
			}

			$items = $report->getItems();
			for ($i = 0; $i < count($items); $i++) {
				if ($entry[$items[$i]][$field] && $entry[$items[$i]][$label]) {
					$blocksummaryStats->addFormField($entry[$items[$i]][$field], 
							     $entry[$items[$i]][$label], $defaultPage);
				}
			}

			$page_body[] = $blocksummaryStats->toHtml();

			// Set up Array with our available Statistics items:
			$menu =& stats_build_menu_tabs($type, $group, $report, $factory);

			// Remove 'summary' as we already printed it above:
			unset($menu['summary']);

	//---
			//
			//--- Generate ScrollList related statistics:
			//

			// ScrollList statistics:
			$SSshorts = array(
					"requestorDomain" => "rpt",
					"typesReportBut" => "rpt",
					"sendReceiveByDomain" => "io",
					"vsitesReportBut" => "io",
					"requestReportButmail" => "io",
					"requestReportBut" => "io"
					);

			// ScrollList statistics:
			$SSvals = array(
					"requestorDomain" => "o",
					"typesReportBut" => "t",
					"sendReceiveByDomain" => "o",
					"vsitesReportBut" => "o",
					"requestReportButmail" => "o",
					"requestReportBut" => "o"
					);

			// ScrollList statistics:
			$SSPeriods = array(
					"requestorDomain" => "o",
					"typesReportBut" => "t",
					"sendReceiveByDomain" => "v",
					"vsitesReportBut" => "v",
					"requestReportButmail" => "o",
					"requestReportBut" => "o"
					);

//			foreach ($menu as $key => $value) {
//				if (in_array($key, array_keys($SSPeriods))) {
//					$myStats = genScrollListStats($SSPeriods[$key], $SSshorts[$key], $SSvals[$key], $type, $report, $factory);
//
//					$AutoBlock = $factory->getPagedBlock($i18n->interpolate($value), array($defaultPage));
//					$AutoBlock->setToggle("#");
//					$AutoBlock->setSideTabs(FALSE);
//
//					$AutoBlock->addFormField(
//						$factory->getRawHTML($SSPeriods[$key], $myStats->toHtml()),
//						$factory->getLabel($i18n->interpolate($value)),
//						$defaultPage
//					);	
//
//					$page_body[] = $AutoBlock->toHtml();
//					unset($menu[$key]);
//				}
//			}

	//---

			// Remove 'byIP' as we just printed it above:
			unset($menu['byIP']);

			//
			//--- Generate Graphical statistics:
			//

			$Periods = array(
					"daily" => "D",
					"weekly" => "W",
					"monthly" => "m",
					"hourly" => "h",
					"useByDayOfWeek" => "d"
					);

			foreach ($menu as $key => $value) {
				if (in_array($key, array_keys($Periods))) {
					$myStats = getTrafficStats($Periods[$key], $type, $report, $factory);
					$AutoBlock = $factory->getPagedBlock($value, array($defaultPage));
					$AutoBlock->setToggle("#");
					$AutoBlock->setSideTabs(FALSE);
					$AutoBlock->addFormField(
						$myStats,
						$factory->getLabel($i18n->interpolate($value), NULL),
						$defaultPage);
					$page_body[] = $AutoBlock->toHtml();
					unset($menu[$key]);
				}
			}
		}
//---

		// Nice people say goodbye, or CCEd waits forever:
		$cceClient->bye();
		$serverScriptHelper->destructor();


		// Out with the page:
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