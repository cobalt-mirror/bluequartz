<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class SignOn extends MX_Controller {

	/**
	 * Index Page for this controller.
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
		$i18n = new I18n("base-phpmyadmin", $user['localePreference']);
		$system = $cceClient->getObject("System");

		// Required array setup:
		$errors = array();
		$extra_headers = array();

		// -- Actual page logic start:

		// Sanity checks:
		if (isset($db_enabled)) {
			if ($db_enabled == "0") {
				$db_host = "localhost";
				$db_username = "";
				$db_pass = "";
			}
		}
		else {
			$db_username = "";
			$db_pass = "";
		}
		if (!isset($db_host)) {
			$db_host = "localhost";
		}

		// Shove submitted input into $form_data after passing it through the XSS filter:
		$form_data = $CI->input->post(NULL, TRUE);

		/* Was data posted? */
		if ($form_data) {
		    if (isset($form_data['PMA_user'])) {
			/* Need to have cookie visible from parent directory */
			session_set_cookie_params(0, '/', '', 0);
			/* Create signon session */
			$session_name = 'SignonSession';
			session_name($session_name);
			session_start();
			/* Store there credentials */
			$_SESSION['PMA_single_signon_user'] = $form_data['PMA_user'];
			$_SESSION['PMA_single_signon_password'] = $form_data['PMA_password'];
			$_SESSION['PMA_single_signon_host'] = $form_data['hostname'];
			$id = session_id();
			/* Close that session */
			session_write_close();
			/* Redirect to phpMyAdmin (should use absolute URL here!) */
			header('Location: /phpMyAdmin/index.php');
		    } 
		} 
		else {

		    // Tell BxPage which module we are currently in:
			$page_module = 'base_programs';

			// New Page:
			$BxPage = new BxPage();

			// Manually set the correct vertical menu entry:
			$BxPage->setVerticalMenu('base_phpmyadmin');
			$BxPage->setOutOfStyle('yes');

			$page_body[] = "<br><br>" . addInputForm(
											$i18n->get("[[base-phpmyadmin.PMA_logon]]"), 
											array("toggle" => "#"),
											'<IMG BORDER="0" WIDTH="720" HEIGHT="0" SRC="/libImage/spaceHolder.gif">' . 
 											addTextField("PMA_user", "text", $db_username, "base-phpmyadmin", "required", "rw", $i18n) .
											addTextField("PMA_password", "password", $db_pass, "base-phpmyadmin", "required", "rw", $i18n) .
											addTextField("hostname", "text", $db_host, "base-phpmyadmin", "required", "hidden", $i18n),
											addSaveButton($i18n),
											$i18n,
											$BxPage,
											$errors
										) . "<br><br>";

			// Nice people say goodbye, or CCEd waits forever:
			$cceClient->bye();
			$serverScriptHelper->destructor();

			// Out with the page:
		    $BxPage->render($page_module, $page_body);


		}
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