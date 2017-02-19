<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class UserDel extends MX_Controller {

	/**
	 * Index Page for this controller.
	 *
	 * Past the login page this loads the page for /user/userDel.
	 *
	 */

	public function index() {

        $CI =& get_instance();

        // We load the BlueOnyx helper library first of all, as we heavily depend on it:
        $this->load->helper('blueonyx');
        init_libraries();

        // Need to load 'BxPage' for page rendering:
        $this->load->library('BxPage');

        // Get $sessionId and $loginName from Cookie (if they are set) and store them in $CI->BX_SESSION:
        $CI->BX_SESSION['sessionId'] = $CI->input->cookie('sessionId');
        $CI->BX_SESSION['loginName'] = $CI->input->cookie('loginName');

        // Line up the ducks for CCE-Connection and store them for re-usability in $CI:
        include_once('ServerScriptHelper.php');
        $CI->serverScriptHelper = new ServerScriptHelper($CI->BX_SESSION['sessionId'], $CI->BX_SESSION['loginName']);
        $CI->cceClient = $CI->serverScriptHelper->getCceClient();

        $i18n = new I18n("base-user", $CI->BX_SESSION['loginUser']['localePreference']);

        // Initialize Capabilities so that we can poll the access rights as well:
        $Capabilities = new Capabilities($CI->cceClient, $CI->BX_SESSION['loginName'], $CI->BX_SESSION['sessionId']);

		// -- Actual page logic start:

		// Get URL params:
		$get_form_data = $CI->input->get(NULL, TRUE);

		//
		//-- Validate GET data:
		//

		if (isset($get_form_data['group'])) {
			// We have a group URL string:
			$group = $get_form_data['group'];
		}
		if (isset($get_form_data['name'])) {
			// We have a username URL string:
			$username = $get_form_data['name'];
		}

		//
		//-- Access Rights Check for Vsite level pages:
		// 
		// 1.) Checks if the Group/Vsite exists.
		// 2.) Checks if the user is systemAdministrator
		// 3.) Checks if the user is Reseller of the given Group/Vsite
		// 4.) Checks if the iser is siteAdmin of the given Group/Vsite
		// Returns Forbidden403 if *none* of that is the case.
		if (!$Capabilities->getGroupAdmin($group)) {
			// Nice people say goodbye, or CCEd waits forever:
			$CI->cceClient->bye();
			$CI->serverScriptHelper->destructor();
			Log403Error("/gui/Forbidden403#1");
		}

		if ((!isset($group)) || (!isset($username))) {
			// No group? No name? Not our kind of game!
			// Nice people say goodbye, or CCEd waits forever:
			$CI->cceClient->bye();
			$CI->serverScriptHelper->destructor();
			Log403Error("/gui/Forbidden403#2");
			// Please note: 'serverAdmin' users have no 'site' set.
			// So this also serves as a check to make sure that we 
			// can only delete minnions and not chieftains.
		}

		//
		//-----	Security checks:
		//
		//		We need to find out if the Vsite with that 'group' exists.
		//		But we also need to make sure that it is under the ownership
		//		of the currently logged in 'createdUser' or 'siteAdmin'. 
		// 		Of course user 'admin' has rights to delete all Users.
		//

		// Admin cannot be deleted. This check is redundant due to our 
		// 'minnion' & 'chieftain' check above. We also don't allow
		// that someone tries to delete his own account.
		if (($username == "admin") || ($username == $CI->BX_SESSION['loginName'])) {
			// No Harakiri allowed here!
			// Nice people say goodbye, or CCEd waits forever:
			$CI->cceClient->bye();
			$CI->serverScriptHelper->destructor();
			Log403Error("/gui/Forbidden403#3");
		}

		// User is a Reseller. Make sure he can only mess with accounts of Vsites
		// that are under his management:
		if (($Capabilities->getAllowed('manageSite')) && ($CI->BX_SESSION['loginName'] != "admin")) {

			// Get a list of Vsite OID's of this Reseller:
			$vsites = $CI->cceClient->findx('Vsite', array('createdUser' => $CI->BX_SESSION['loginName']), array(), "", "");

			// Build an array of groups that this Reseller owns:
			$groups_of_owned_vsites = array();
			foreach ($vsites as $site) {
				// Get Vsite settings:
				$vsiteSettings = $CI->cceClient->get($site);
				$groups_of_owned_vsites[] = $vsiteSettings['name'];
			}

			// Check if the user we want to delete belongs to a group under our control:
			if (!in_array($group, $groups_of_owned_vsites)) {
				// Trying to delete a user that's not yours? Bad boy!
				// Nice people say goodbye, or CCEd waits forever:
				$CI->cceClient->bye();
				$CI->serverScriptHelper->destructor();
				Log403Error("/gui/Forbidden403#4");
			}
		}

		// One more security check: Is siteAdmin, not manageSite, not admin:
		if (($Capabilities->getAllowed('siteAdmin')) && (!$Capabilities->getAllowed('manageSite')) && ($CI->BX_SESSION['loginName'] != "admin")) {
			// So we have a siteAdmin. Is he of the same group as the user he wants to delete?
			if ($user['site'] != $group) {
				// Don't play games with us!
				// Nice people say goodbye, or CCEd waits forever:
				$CI->cceClient->bye();
				$CI->serverScriptHelper->destructor();
				Log403Error("/gui/Forbidden403#5");
			}
		}

		// Get a list of User OID's:
		$users = $CI->cceClient->findx('User', array('name' => $username, 'site' => $group), array(), "", "");

		// At this point we should have one object. Not more and not less:
		if (count($users) != "1") {
			// Don't play games with us!
			// Nice people say goodbye, or CCEd waits forever:
			$CI->cceClient->bye();
			$CI->serverScriptHelper->destructor();
			Log403Error("/gui/Forbidden403#6");
		}
		else {

			// We continue with the deletion if we're not in DEMO mode:
			if (!is_file('/etc/DEMO')) {
				$CI->cceClient->destroyObjects("User", array('name' => $username, 'site' => $group));
			}

			// Nice people say goodbye, or CCEd waits forever:
			$CI->cceClient->bye();
			$CI->serverScriptHelper->destructor();

			// Redirect to the processing page to follow the status of this transaction:
			header("Location: /user/userList?group=$group");
			exit;

		}

		// Nice people say goodbye, or CCEd waits forever:
		$CI->cceClient->bye();
		$CI->serverScriptHelper->destructor();

		// Can't imagine why we would get to this line.
		// But if we do, log a 403 and call it a day:
		Log403Error("/gui/Forbidden403#7");

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