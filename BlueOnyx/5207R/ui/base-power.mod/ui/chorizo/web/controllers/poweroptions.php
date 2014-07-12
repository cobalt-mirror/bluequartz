<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Poweroptions extends MX_Controller {

	/**
	 * Index Page for this controller.
	 *
	 * Past the login page this loads the page for /power/poweroptions.
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
		$i18n = new I18n("base-system", $user['localePreference']);
		$system = $cceClient->getObject("System");

		// Initialize Capabilities so that we can poll the access rights as well:
		$Capabilities = new Capabilities($cceClient, $loginName, $sessionId);

		// -- Actual page logic start:

		// Only users with 'serverPower' capability should be here
		if (!$Capabilities->getAllowed('serverPower')) {
			// Nice people say goodbye, or CCEd waits forever:
			$cceClient->bye();
			$serverScriptHelper->destructor();
			Log403Error("/gui/Forbidden403");
		}

		//
		//--- Handle form validation:
		//

	    // We start without any active errors:
	    $errors = array();
	    $extra_headers =array();
	    $ci_errors = array();
	    $my_errors = array();

		//
		//--- Own error checks:
		//

		$get_form_data = $CI->input->get(NULL, TRUE);
		if (isset($get_form_data['p'])) {
			if ($get_form_data['p'] == "reboot") {
				$attributes = array("reboot" => time());
				$ci_errors[] = '<div class="alert alert_navy"><img width="28" height="28" src="/.adm/images/icons/small/white/alert_2.png"><strong>' . $i18n->getHtml("[[base-power.rebooting]]") . '</strong></div>';
			}
			if ($get_form_data['p'] == "shutdown") {
				$attributes = array("halt" => time());
				$ci_errors[] = '<div class="alert alert_red"><img width="28" height="28" src="/.adm/images/icons/small/white/alert.png"><strong>' . $i18n->getHtml("[[base-power.shutting-down]]") . '</strong></div>';
			}			

			if (!is_file("/etc/DEMO")) {
		  		// Actual submit to CODB - But we won't do it in DEMO-Mode:
				$cceClient->setObject("System", $attributes, "Power");
			}
			else {
				$ci_errors[] = '<div class="alert alert_green"><img width="28" height="28" src="/.adm/images/icons/small/white/alert.png"><strong>' . $i18n->getHtml("[[palette.demo_mode]]") . '</strong></div>';
			}

			// CCE errors that might have happened during submit to CODB:
			$CCEerrors = $cceClient->errors();
			foreach ($CCEerrors as $object => $objData) {
				// When we fetch the CCE errors it tells us which field it bitched on. And gives us an error message, which we can return:
				$errors[] = ErrorMessage($i18n->get($objData->message, true, array('key' => $objData->key)) . '<br>&nbsp;');
			}
		}

		// Join the various error messages:
		$errors = array_merge($ci_errors, $my_errors);

		//
	    //-- Generate page:
	    //

		// Prepare Page:
		$factory = $serverScriptHelper->getHtmlComponentFactory("base-power", "/power/poweroptions");
		$BxPage = $factory->getPage();
		$BxPage->setErrors($errors);
		$i18n = $factory->getI18n();

		// Set Menu items:
		$BxPage->setVerticalMenu('base_serverconfig');
		$page_module = 'base_sysmanage';

		// Note to self: Yes. I could extend the Button Class to do this all for me instead of doing it on foot. Makes later
		// troubleshooting of that S.O.A.B real fun. And we don't really have that many places where we pester the users for
		// confirmations. TL;DR: No, thanks. This will do:

		// Reboot button:
		$rebootbutton = $factory->getRawHTML("reboot", '<a class="reboot" href="' . "/power/poweroptions?p=reboot". '"><button class="no_margin_bottom div_icon tooltip hover dialog_button" title="' . $i18n->getWrapped("reboot_help") . '"><div class="ui-icon ui-icon-refresh"></div><span>' . $i18n->getHtml("reboot") . '</span></button></a>');

		// confirmation string
		$confirm =  $i18n->get("askRebootConfirmation");

		// Shutdown button:
		$shutdownbutton = $factory->getRawHTML("shutdown_menu", '<a class="shutdown_menu" href="' . "/power/poweroptions?p=shutdown". '"><button class="no_margin_bottom img_icon has_text tooltip hover dialog_button" title="' . $i18n->getWrapped("shutdown_menu_help") . '"><img src="/.adm/images/icons/small/white/electricity_plug.png"></img><span>' . $i18n->getHtml("shutdown_menu") . '</span></button></a>');

		// confirmation string
		$confirm =  $i18n->get("askShutdownConfirmation");

		// Add Button-Container:
		$buttonContainer = $factory->getButtonContainer("", array($rebootbutton, $shutdownbutton));

		// Nice people say goodbye, or CCEd waits forever:
		$cceClient->bye();
		$serverScriptHelper->destructor();

	    // Extra header for Reboot confirmation dialog:
	    $BxPage->setExtraHeaders('
	        <script type="text/javascript">
	        $(document).ready(function () {

	          $("#dialog_reboot").dialog({
	            modal: true,
	            bgiframe: true,
	            width: 500,
	            height: 300,
	            autoOpen: false
	          });

	          $(".reboot").click(function (e) {
	            e.preventDefault();
	            var hrefAttribute = $(this).attr("href");

	            $("#dialog_reboot").dialog(\'option\', \'buttons\', {
	              "' . $i18n->getHtml("[[base-power.reboot]]") . '": function () {
	                window.location.href = hrefAttribute;
	              },
	              "' . $i18n->getHtml("[[palette.cancel]]") . '": function () {
	                $(this).dialog("close");
	              }
	            });

	            $("#dialog_reboot").dialog("open");

	          });
	        });
	        </script>');

	    // Extra header for Shutdown confirmation dialog:
	    $BxPage->setExtraHeaders('
	        <script type="text/javascript">
	        $(document).ready(function () {

	          $("#dialog_shutdown").dialog({
	            modal: true,
	            bgiframe: true,
	            width: 500,
	            height: 300,
	            autoOpen: false
	          });

	          $(".shutdown_menu").click(function (e) {
	            e.preventDefault();
	            var hrefAttribute = $(this).attr("href");

	            $("#dialog_shutdown").dialog(\'option\', \'buttons\', {
	              "' . $i18n->getHtml("[[base-power.shutdown_menu]]") . '": function () {
	                window.location.href = hrefAttribute;
	              },
	              "' . $i18n->getHtml("[[palette.cancel]]") . '": function () {
	                $(this).dialog("close");
	              }
	            });

	            $("#dialog_shutdown").dialog("open");

	          });
	        });
	        </script>');

		// Add hidden Modal for Reboot / Shutdown - Confirmation:
        $page_body[] = '
			<div class="display_none">
			    		<div id="dialog_reboot" class="dialog_content narrow no_dialog_titlebar" title="' . $i18n->getHtml("[[base-power.askRebootConfirmation]]") . '">
			                <div class="block">
			                        <div class="section">
			                                <h1>' . $i18n->getHtml("[[base-power.reboot]]") . '</h1>
			                                <div class="dashed_line"></div>
			                                <p>' . $i18n->getHtml("[[base-power.askRebootConfirmation]]") . '</p>
			                                <p>' . $i18n->getHtml("[[base-power.rebootMessage]]") . '</p>
			                        </div>
			                </div>
			        	</div>
			</div>
			<div class="display_none">
			    		<div id="dialog_shutdown" class="dialog_content narrow no_dialog_titlebar" title="' . $i18n->getHtml("[[base-power.askShutdownConfirmation]]") . '">
			                <div class="block">
			                        <div class="section">
			                                <h1>' . $i18n->getHtml("[[base-power.shutdown_menu]]") . '</h1>
			                                <div class="dashed_line"></div>
			                                <p>' . $i18n->getHtml("[[base-power.askShutdownConfirmation]]") . '</p>
			                                <p>' . $i18n->getHtml("[[base-power.shutdown_menu_help]]") . '</p>
			                        </div>
			                </div>
			        	</div>
			</div>';

		$page_body[] = $buttonContainer->toHtml();

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