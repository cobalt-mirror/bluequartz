<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class AmStatus extends MX_Controller {

	/**
	 * Index Page for this controller.
	 *
	 * Past the login page this loads the page for /am/amStatus.
	 *
	 * This is based on some pretty good code from Phil Ploquin & Tim Hockin.
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

		//
		//--- Get CODB-Object of interest: 
		//

		$CODBDATA = $cceClient->getObject("ActiveMonitor");

		//
		//--- Handle form validation:
		//

	    // We start without any active errors:
	    $errors = array();
	    $extra_headers =array();
	    $ci_errors = array();
	    $my_errors = array();

		//
		//--- User initiated update of AM-Status:
		//
		$get_form_data = $CI->input->get(NULL, TRUE);
		if ($get_form_data['UPDATE'] == "1") {
			// Shell out a Swatch run:
			$ret = $serverScriptHelper->shell("/usr/sbin/swatch -c /etc/swatch.conf", $output, "root", $sessionId);
			// Nice people say goodbye, or CCEd waits forever:
			$cceClient->bye();
			$serverScriptHelper->destructor();
			header("Location: /am/amStatus");
			exit;
		}

		//
		//--- Own error checks:
		//

		if ($CI->input->post(NULL, TRUE)) {

		}

		//
		//--- At this point all checks are done. If we have no errors, we can submit the data to CODB:
		//

		// Join the various error messages:
		$errors = array_merge($ci_errors, $my_errors);

		//
	    //-- Generate page:
	    //

		// Prepare Page:
		$factory = $serverScriptHelper->getHtmlComponentFactory("base-am", "/am/amStatus");
		$BxPage = $factory->getPage();
		$BxPage->setErrors($errors);
		$i18n = $factory->getI18n();

		// Set Menu items:
		$BxPage->setVerticalMenu('base_monitor');
		$BxPage->setVerticalMenuChild('base_amStatus');
		$page_module = 'base_sysmanage';

		$defaultPage = "basicSettingsTab";

		$block =& $factory->getPagedBlock("amSysClients", array($defaultPage));
		$block->setToggle("#");
		$block->setSideTabs(FALSE);
		$block->setDefaultPage($defaultPage);

		// Generate "Check Status Now" button:
		$updateButton =& $factory->getButton('/am/amStatus?UPDATE=1', 'amUpdateNow', 'DEMO-OVERRIDE');
		$updateButton->setWaiter(TRUE);

		// If Active Monitor is disabled, we disable the Update button, too:
		if ($CODBDATA['enabled'] == '0') {
		    $updateButton->setDisabled(true);
		}

		// Add "Check Status Now" Button-Container:
		$buttonContainer = $factory->getButtonContainer("", array($updateButton));

		// Create System-ScrollList:
  		$syslist = $factory->getScrollList("amSysClients", array(' ', 'amClientName', 'action'), array()); 
	    $syslist->setAlignments(array("center", "left", "center"));
	    $syslist->setDefaultSortedIndex('1');
	    $syslist->setSortOrder('ascending');
	    $syslist->setSortDisabled(array('0', '2'));
	    $syslist->setPaginateDisabled(TRUE);
	    $syslist->setSearchDisabled(TRUE);
	    $syslist->setSelectorDisabled(FALSE);
	    $syslist->enableAutoWidth(FALSE);
	    $syslist->setInfoDisabled(FALSE);
	    $syslist->setColumnWidths(array("100", "500", "120")); // Max: 739px

		$block2 =& $factory->getPagedBlock("amServClients", array($defaultPage));
		$block2->setToggle("#");
		$block2->setSideTabs(FALSE);
		$block2->setDefaultPage($defaultPage);


		// Create Service-ScrollList:
  		$servlist = $factory->getScrollList("amServClients", array(' ', 'amClientName', 'action'), array()); 
	    $servlist->setAlignments(array("center", "left", "center"));
	    $servlist->setDefaultSortedIndex('1');
	    $servlist->setSortOrder('ascending');
	    $servlist->setSortDisabled(array('0', '2'));
	    $servlist->setPaginateDisabled(TRUE);
	    $servlist->setSearchDisabled(TRUE);
	    $servlist->setSelectorDisabled(FALSE);
	    $servlist->enableAutoWidth(FALSE);
	    $servlist->setInfoDisabled(FALSE);
	    $servlist->setColumnWidths(array("100", "500", "120")); // Max: 739px	    

	    $am_names = $cceClient->names("ActiveMonitor");

	    $stmap = array(
	        "N" => "none", 
	        "G" => "normal", 
	        "Y" => "problem", 
	        "R" => "severeProblem");

	    $colormap = array(
	        "N" => "light", 
	        "G" => $BxPage->getPrimaryColor(), 
	        "Y" => "orange", 
	        "R" => "red");

	    $iconmap = array(
	        "N" => "ui-icon-radio-on", 
	        "G" => "ui-icon-check", 
	        "Y" => "ui-icon-notice", 
	        "R" => "ui-icon-alert");

	    $descmap = array(
	        "N" => "amKeyGrey", 
	        "G" => "amKeyGreen", 
	        "Y" => "amKeyYellow", 
	        "R" => "amKeyRed");

	    for ($i=0; $i < count($am_names); ++$i) {
	        $nspace = $cceClient->get($CODBDATA['OID'], $am_names[$i]);

	        if (!isset($nspace["hideUI"])) {
            	$iname = $i18n->interpolate($nspace["nameTag"]);

			    if (($CODBDATA['enabled'] == '0') || (!$nspace["enabled"]) || (!$nspace["monitor"])) {
					$icon = '<button class="light small icon_only tooltip hover" title="' . $i18n->getHtml("amKeyGrey") . '"><div class="ui-icon ui-icon-radio-on"></div></button>';
			    } 
			    else {
					$icon = '<button class="' . $colormap[$nspace["currentState"]] . ' small icon_only tooltip hover" title="' . $i18n->getHtml($descmap[$nspace["currentState"]]) . '"><div class="ui-icon ' . $iconmap[$nspace["currentState"]] . '"></div></button>';
	            }

			    $namefield = $iname;

			    // The NameSpace URL is the URL to the BlueOnyx 510XR version of that page.
			    // For 520XR we need to adjust that to the CodeIgniter URL format:
			    $URL_elements = explode('/', $nspace["URL"]);

			    // Get Vendor of the AM-page:
			    if ($URL_elements[1] == "base") {
			    	$vendor = '';
			    }
			    else {
			    	$vendor = $URL_elements[1] . '/';
			    }

			    // Get Module of the AM-Page:
			    $module = $URL_elements[2];

			    // Get Filename of the AM-Page:
				$fileName_elements = explode('.', $URL_elements[3]);
				$filename = $fileName_elements[0];

				// Put it back together:
				$nspace["URL"] = '/' . $vendor . $module . '/' . $filename;

				$nspace_helper = explode('/', $nspace["URL"]);
				$module_dirs = array("base", "Compass", "solarspeed", "other");

				if (in_array($nspace_helper[1], $module_dirs)) {
					$nspace_helper[0] = $nspace_helper[1];
					$nspace_helper[1] = $nspace_helper[2];
					$nspace_helper[2] = $nspace_helper[3];
					unset($nspace_helper[3]);
					$nspace["URL"] = '/' . $module . '/' . $filename;
				}
				else {
					$nspace_helper[0] = "base";
				}

				$fancy_button = $factory->getFancyButton($nspace["URL"] . "?short=1", "", "DEMO-OVERRIDE");
				$fancy_button->setImageOnly(TRUE);
				$link_button = $factory->getLinkButton($nspace["URL"], "[[palette.detail]]", "DEMO-OVERRIDE");
				$link_button->setImageOnly(TRUE);

				$details = $factory->getCompositeFormField(array($fancy_button, $link_button));

	            if (($nspace["URL"] == "") || (!is_file("/usr/sausalito/ui/chorizo/ci/application/modules/" . $nspace_helper[0] . "/" . $nspace_helper[1] . "/controllers/" . $nspace_helper[2] . ".php"))) {
					$fancy_button->setDisabled(TRUE);
					$link_button->setDisabled(TRUE);
			    }

	            if ($nspace["UIGroup"] == "system") {
	                $syslist->addEntry(array($icon, $namefield, $details));
	            }
	            elseif ($nspace["UIGroup"] == "service") {
	                $servlist->addEntry(array($icon, $namefield, $details));
	            }
	            else {
					if (!$otherlist) { 
						// Create Other-ScrollList:
				  		$otherlist = $factory->getScrollList("amOtherClients", array(' ', 'amClientName', 'action'), array()); 
					    $otherlist->setAlignments(array("center", "left", "center"));
					    $otherlist->setDefaultSortedIndex('1');
					    $otherlist->setSortOrder('ascending');
					    $otherlist->setSortDisabled(array('2'));
					    $otherlist->setPaginateDisabled(TRUE);
					    $otherlist->setSearchDisabled(TRUE);
					    $otherlist->setSelectorDisabled(FALSE);
					    $otherlist->enableAutoWidth(FALSE);
					    $otherlist->setInfoDisabled(FALSE);
					    $otherlist->setColumnWidths(array("100", "500", "120")); // Max: 739px	 
					}
					$otherlist->addEntry(array($icon, $namefield, $details));
			    }
	        }
	    }

		// Push out the System-Scrollist:
		$block->addFormField(
			$factory->getRawHTML("amSysClients", $syslist->toHtml()),
			$factory->getLabel("amSysClients"),
			$defaultPage
		);

		// Push out the Service-Scrollist:
		$block2->addFormField(
			$factory->getRawHTML("amServClients", $servlist->toHtml()),
			$factory->getLabel("amServClients"),
			$defaultPage
		);

		if (isset($otherlist)) {
			// Push out the Other-Scrollist:
			$block3->addFormField(
				$factory->getRawHTML("amOtherClients", $servlist->toHtml()),
				$factory->getLabel("amOtherClients"),
				$defaultPage
			);
		}

		// Nice people say goodbye, or CCEd waits forever:
		$cceClient->bye();
		$serverScriptHelper->destructor();

		$page_body[] = $buttonContainer->toHtml();
		$page_body[] = $block->toHtml();
		$page_body[] = $block2->toHtml();
		if (isset($otherlist)) {
			$page_body[] = $block3->toHtml();
		}

		// Out with the page:
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