<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class ImapSyncLog extends MX_Controller {

	/**
	 * Index Page for this controller.
	 *
	 * Past the login page this loads the page for /user/imapSyncLog.
	 *
	 */

	public function index() {

        $CI =& get_instance();

        // We load the BlueOnyx helper library first of all, as we heavily depend on it:
        $this->load->helper('blueonyx');
        init_libraries();

        // Need to load 'BxPage' for page rendering:
        $this->load->library('BxPage');

        // Get $CI->BX_SESSION['sessionId'] and $loginName from Cookie (if they are set) and store them in $CI->BX_SESSION:
        $CI->BX_SESSION['sessionId'] = $CI->input->cookie('sessionId');
        $CI->BX_SESSION['loginName'] = $CI->input->cookie('loginName');

        // Line up the ducks for CCE-Connection and store them for re-usability in $CI:
        include_once('ServerScriptHelper.php');
        $CI->serverScriptHelper = new ServerScriptHelper($CI->BX_SESSION['sessionId'], $CI->BX_SESSION['loginName']);
        $CI->cceClient = $CI->serverScriptHelper->getCceClient();

        $i18n = new I18n("base-user", $CI->BX_SESSION['loginUser']['localePreference']);
        $system = $CI->getSystem();
        $user = $CI->BX_SESSION['loginUser'];

		// -- Actual page logic start:

		// Get URL strings:
		$get_form_data = $CI->input->get(NULL, TRUE);

		//
		//-- Validate GET data:
		//

		if (isset($get_form_data['userOid'])) {
			// We have a UserOID:
			$userOid = $get_form_data['userOid'];
		}
		if (!isset($userOid)) {
			// Don't play games with us!
			// Nice people say goodbye, or CCEd waits forever:
			$CI->cceClient->bye();
			$CI->serverScriptHelper->destructor();
			Log403Error("/gui/Forbidden403#1");
		}

		// Check if the User is viewing his own logfile:
		if ($user['OID'] != $userOid) {
			// He's not viewing his own logfile. We need to check if 
			// that User has the rights to be here:

			// Get group of the user whose logfile we want to view:
			$TargetUser = $CI->cceClient->get($userOid);

			if ($TargetUser['site'] != "") {
				$group = $TargetUser['site'];
			}
			else {
				$group = "";
			}

			// Initialize Capabilities so that we can poll the access rights as well:
			$Capabilities = new Capabilities($CI->cceClient, $CI->BX_SESSION['loginName'], $CI->BX_SESSION['sessionId']);

			//
			//-- Access Rights Check for Vsite level pages:
			// 
			// 1.) Checks if the Group/Vsite exists.
			// 2.) Checks if the user is systemAdministrator
			// 3.) Checks if the user is Reseller of the given Group/Vsite
			// 4.) Checks if the iser is siteAdmin of the given Group/Vsite
			// Returns Forbidden403 if *none* of that is the case.
			// And: If he's not 'systemAdministrator' he will not be allowed to see logs of non-owned Users.
			if ((!$Capabilities->getGroupAdmin($group)) && (!$Capabilities->getAllowed('systemAdministrator'))) {
				// Nice people say goodbye, or CCEd waits forever:
				$CI->cceClient->bye();
				$CI->serverScriptHelper->destructor();
				Log403Error("/gui/Forbidden403#2");
			}
		}
		else {
			// User views his own log:
			$TargetUser = $user;
		}

		//-- Handle form validation:

	    // We start without any active errors:
	    $errors = array();
	    $ci_errors = array();
	    $my_errors = array();

		//
	    //-- Generate page:
	    //

		// Prepare Page:
		$factory = $CI->serverScriptHelper->getHtmlComponentFactory("base-user", "/user/personalEmail");
		$BxPage = $factory->getPage();
		$BxPage->setErrors($errors);
		$i18n = $factory->getI18n();

		// Set Menu items:
		$BxPage->setVerticalMenu('base_controlpanel');
		$page_module = 'base_personalProfile';

		$logfile = '~' . $TargetUser['name'] . '/.imapsync.log';

		$ret = $CI->serverScriptHelper->shell("/bin/cat $logfile", $output, 'root', $CI->BX_SESSION['sessionId']);
		$output = explode("\n", $output);

		$out = "<pre>";
		foreach($output as $outputline) {
				$out .= formspecialchars($outputline) . "\n";
		}
		$out .= "</pre>";

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