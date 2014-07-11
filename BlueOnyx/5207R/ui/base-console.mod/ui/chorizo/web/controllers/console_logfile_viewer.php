<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Console_logfile_viewer extends MX_Controller {

	/**
	 * Index Page for this controller.
	 *
	 * Past the login page this loads the page for /console/console_logfile_viewer.
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
		$i18n = new I18n("base-console", $user['localePreference']);
		$system = $cceClient->getObject("System");

		// Initialize Capabilities so that we can poll the access rights as well:
		$Capabilities = new Capabilities($cceClient, $loginName, $sessionId);

		// -- Actual page logic start:

		// Not serverConfig? Bye, bye!
		if (!$Capabilities->getAllowed('serverConfig')) {
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

		$get_form_data = $CI->input->get(NULL, TRUE);

		// Build selector:
		$logfile = "/usr/bin/tail -200 /var/log/messages";
		if (isset($get_form_data['type'])) {
			$logfile_choices = array(
			                "1" => "/usr/bin/tail -200 /var/log/cron",
			                "2" => "/usr/bin/tail -200 /var/log/maillog",
			                "3" => "/usr/bin/tail -200 /var/log/messages",
			                "4" => "/usr/bin/tail -200 /var/log/secure",
			                "5" => "/usr/bin/tail -200 /var/log/httpd/access_log",
			                "6" => "/usr/bin/tail -200 /var/log/httpd/error_log",
			                "7" => "/usr/bin/tail -200 /var/log/admserv/adm_access",
			                "8" => "/usr/bin/tail -200 /var/log/admserv/adm_error"
			            );
			$logfile = $logfile_choices[$get_form_data['type']];
		}
		else {
			// This is not what we're looking for! Stop poking around!
			// Nice people say goodbye, or CCEd waits forever:
			$cceClient->bye();
			$serverScriptHelper->destructor();
			Log403Error("/gui/Forbidden403#FU");
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
		$factory = $serverScriptHelper->getHtmlComponentFactory("base-console", "/console/console_logfile_viewer");
		$BxPage = $factory->getPage();
		$BxPage->setErrors($errors);
		$i18n = $factory->getI18n();

		$product = new Product($cceClient);

		// Set Menu items:
		$BxPage->setVerticalMenu('base_console_logfiles');
		$BxPage->setVerticalMenuChild('base_console_logfiles');
		$page_module = 'base_sysmanage';

		//
		//-- A bit more security doesn't hurt. Especially before we pass through shell commands!
		//

		// Build array with allowed shell commands:
		$allowed_execs = array_values($logfile_choices);

		// Check if command we wanna pass through is one of the few allowed ones 
		// specified in the array values of $logfile_choices:
		if (in_array($logfile, $allowed_execs)) {
			// It is, so do the exec():
			if (is_file('/etc/DEMO')) {
				$output = "\n";
				$output .= "\n";
				$output .= "\n";
				$output .= "\n";
				$output .= "\n";
				$output .= "\n";
				$output .= "\n";
				$output .= "\n";
				$output .= "\n";
				$output .= "\n";
				$output .= "\n";
				$output .= "\n";
				$output .= "\n";
				$output .= $i18n->getHtml("[[palette.detail]]") . ': ' . $i18n->getHtml("[[palette.demo_mode_short]]") . ' ' . $i18n->getHtml("[[palette.enabled_short]]");
				$output .= "\n=====================\n";
				$output .= "\n\n" . $i18n->getHtml("[[palette.403text]]");
			}
			else {
				$ret = $serverScriptHelper->shell("$logfile", $output, 'root', $sessionId);
			}
			$output = explode("\n", $output);
		}
		else {
			// It is not? Go away, you fine Sir!
			Log403Error("/gui/Forbidden403#FU2");
		}
		$out = "<pre>";
		foreach($output as $outputline) {
				$out .= formspecialchars($outputline) . "\n";
		}
		$out .= "</pre>";

		// Nice people say goodbye, or CCEd waits forever:
		$cceClient->bye();
		$serverScriptHelper->destructor();

		// Page body:
		$page_body[] = $out;

		// Out with the page:
	    $BxPage->setOutOfStyle(TRUE);
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