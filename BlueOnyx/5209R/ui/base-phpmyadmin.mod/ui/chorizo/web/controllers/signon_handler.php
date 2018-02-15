<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class SignonHandler extends MX_Controller {

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
		
	    // Get $CI->BX_SESSION['sessionId'] and $CI->BX_SESSION['loginName'] from Cookie (if they are set):
	    $CI->BX_SESSION['sessionId'] = $CI->input->cookie('sessionId');
	    $CI->BX_SESSION['loginName'] = $CI->input->cookie('loginName');

	    // Line up the ducks for CCE-Connection:
	    include_once('ServerScriptHelper.php');
		$CI->serverScriptHelper = new ServerScriptHelper($CI->BX_SESSION['sessionId'], $CI->BX_SESSION['loginName']);
		$CI->cceClient = $CI->serverScriptHelper->getCceClient();
		$user = $CI->BX_SESSION['loginUser'];
		$i18n = new I18n("base-disk", $CI->BX_SESSION['loginUser']['localePreference']);
		$system = $CI->getSystem();

		// Required array setup:
		$errors = array();
		$extra_headers = array();

		// -- Actual page logic start:

		if ($CI->BX_SESSION['loginName'] == "admin") {
		    $systemOid = $CI->cceClient->get($system['OID'], "mysql");
		    $db_username = $systemOid{'mysqluser'};
		    $mysqlOid = $CI->cceClient->find("MySQL");
		    $mysqlData = $CI->cceClient->get($mysqlOid[0]);
		    $db_pass = $mysqlData{'sql_rootpassword'};
		    $db_host = $mysqlData{'sql_host'};
		}
		elseif ($CI->serverScriptHelper->getAllowed('siteAdmin')) {

		}
		else {
		  $CI->BX_SESSION['loginName'] = "";
		}

		// Sanity checks:
		if (!isset($db_host)) {
		    $db_host = "localhost";
		}

	    //-- Generate page:

	    // Tell BxPage which module we are currently in:
		$page_module = 'base_programs';

		// Assemble page_body:
		$BxPage = new BxPage();

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