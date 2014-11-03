<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Pusher extends MX_Controller {

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
		$i18n = new I18n("base-disk", $user['localePreference']);
		$system = $cceClient->getObject("System");

		// Initialize Capabilities so that we can poll the access rights as well:
		$Capabilities = new Capabilities($cceClient, $loginName, $sessionId);		

		// Required array setup:
		$errors = array();
		$extra_headers = array();

		// Get URL params:
		$get_form_data = $CI->input->get(NULL, TRUE);

		// -- Actual page logic start:

		if ($Capabilities->getAllowed('systemAdministrator')) {
		    $systemOid = $cceClient->getObject("System", array(), "mysql");
		    $db_username = $systemOid{'mysqluser'};
		    $mysqlOid = $cceClient->find("MySQL");
		    $mysqlData = $cceClient->get($mysqlOid[0]);
		    $db_pass = $mysqlData{'sql_rootpassword'};
		    $db_host = $mysqlData{'sql_host'};
		}
		elseif ($Capabilities->getAllowed('siteAdmin')) {
		    // get user:
		    $oids = $cceClient->find("User", array("name" => $loginName));
		    $useroid = $oids[0];

		    // determine site he belongs to:
		    $user = $cceClient->get($oids[0]);

		    if ($Capabilities->getAllowed('manageSite')) {
		    	if (isset($get_form_data['group'])) {
		    		$group = $get_form_data['group'];
		    	}
		    }
		    else {
			    $group = $user["site"];
		    }

			if (isset($group)) {
			    // Get MYSQL_Vsite settings for this site:
			    list($sites) = $cceClient->find("Vsite", array("name" => $group));
			    $MYSQL_Vsite = $cceClient->get($sites, 'MYSQL_Vsite');

			    // Fetch MySQL details for this site:
			    $db_enabled = $MYSQL_Vsite['enabled'];
			    $db_username = $MYSQL_Vsite['username'];
			    $db_pass = $MYSQL_Vsite['pass'];
			    $db_host = $MYSQL_Vsite['host'];
			}
			else {
				$db_enabled = "0";
			}

		    if ($db_enabled == "0") {
		        $db_host = "localhost";
		        $db_username = "";
		        $db_pass = "";
		    }
		}
		else {
		  $loginName = "";
		}

		// Sanity checks:
		if (!isset($db_host)) {
			// Nice people say goodbye, or CCEd waits forever:
			$cceClient->bye();
			$serverScriptHelper->destructor();
			header("Location: /phpmyadmin/signon");
			exit;
		}

	    //-- Generate page:

	    // Tell BxPage which module we are currently in:
		$page_module = 'base_programs';

		// Assemble page_body:
		$BxPage = new BxPage();

		$BxPage->setVerticalMenu('base_phpmyadmin');
		$BxPage->setOutOfStyle('yes');		

		// Page body for auto-logins
		$page_body[] = '
			<form action="signon" method="post" name="frm" onLoad="document.frm.submit()">
			<input type="hidden" name="PMA_user" value="' . $db_username . '">
			<input type="hidden" name="PMA_password" value="' . $db_pass . '">
			<input type="hidden" name="hostname" value="' . $db_host . '">
			<input type="image" name="" value="">
			</form>
			<script language="JavaScript">
			       document.frm.submit();
			</script>
		';

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