<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class PhpmyadminUser extends MX_Controller {

	/**
	 * Index Page for this controller.
	 *
	 * Note to self: This page has turned into an ugly mess thanks to the
	 * Reseller management. Right now Resellers can view MySQL db's of
	 * Vsites they own. But only under "Personal Profile". If they use
	 * "Programs" / "phpMyAdmin" under Vsite Management, they get
	 * redirected to "Personal Profile" instead. Can't be assed to fix
	 * that now. Live with it.
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

		// Make the users fullName safe for all charsets:
		$user['fullName'] = bx_charsetsafe($user['fullName']);

		// Required array setup:
		$errors = array();
		$extra_headers = array();

		$am_reseller = FALSE;

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
		elseif ((!$Capabilities->getAllowed('systemAdministrator')) && ($Capabilities->getAllowed('manageSite'))) {
		    // If we get here, the user is a Reseller. Get his User object:
		    $oids = $cceClient->find("User", array("name" => $loginName));
		    $useroid = $oids[0];
		    $am_reseller = TRUE;
		}
		elseif ($Capabilities->getAllowed('siteAdmin')) {
		    // get user:
		    $oids = $cceClient->find("User", array("name" => $loginName));
		    $useroid = $oids[0];

		    // Check which site the user belongs to:
		    $user = $cceClient->get($oids[0]);
	    	$group = $user["site"];

	    	// Check if that's the same group he requested access to:
			if (isset($get_form_data['group'])) {
				if ($group != $get_form_data['group']) {
					// Sneaky Bastard:
					$cceClient->bye();
					$serverScriptHelper->destructor();
					Log403Error("/gui/Forbidden403");
				}
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
			// Nice people say goodbye, or CCEd waits forever:
			$cceClient->bye();
			$serverScriptHelper->destructor();
			Log403Error("/gui/Forbidden403");
		}

		// Sanity checks:
		if (!isset($db_host)) {
		    $db_host = "localhost";
		}

	    //-- Generate page:
		if ($am_reseller == TRUE) {
		    //-- Generate page:

		    // Get the Vsites he owns:
		    $sites = $cceClient->findx("Vsite", array("createdUser" => $loginName));
			$redir = 'site';

		    // Get MySQL_Vsite details for all Vsites he owns:
		    foreach ($sites as $key => $oid) {
		    	$Vsite[] = $cceClient->get($oid);
		    	$MYSQL_Vsite[] = $cceClient->get($oid, 'MYSQL_Vsite');
		    	$phpMyAdminList[0][$key] = $Vsite[$key]['fqdn'];
		    	$OwnedVsiteList[] = $Vsite[$key]['name'];

		    	if ($MYSQL_Vsite[$key]['enabled'] == "1") {
					$phpMyAdminList[1][$key] = '<button class="tiny text_only has_text tooltip hover" title="' . $i18n->getHtml("[[palette.Yes]]") . '" disabled>'. $i18n->getHtml("[[palette.Yes]]") . '</button>';
			    	$phpMyAdminList[2][$key] = '<button title="' . $i18n->getHtml("[[palette.modify]]") . '" class="tiny icon_only div_icon tooltip hover right link_button" data-link="/phpmyadmin/' . $redir . '?group=' . $Vsite[$key]['name'] . '&reseller=1" target="_self" formtarget="_self"><div class="ui-icon ui-icon-pencil"></div></button>';
		    	}
		    	else {
		    		$phpMyAdminList[1][$key] = '<button class="tiny light text_only has_text tooltip hover" title="' . $i18n->getHtml("[[palette.No]]") . '" disabled>'. $i18n->getHtml("[[palette.No]]") . '</button>';
			    	$phpMyAdminList[2][$key] = '<button title="' . $i18n->getHtml("[[palette.modify]]") . '" class="tiny icon_only div_icon tooltip hover right link_button" target="_self" formtarget="_self" disabled><div class="ui-icon ui-icon-circle-close"></div></button>';
		    	}
		    }

			if (isset($get_form_data['group'])) {
				// We have a group URL string:
				$ugroup = $get_form_data['group'];
			}
			if (preg_match('/phpmyadmin\/site/', uri_string())) {
				if (in_array($ugroup, $OwnedVsiteList)) {
					$uri = '/phpmyadmin/pusher?group=' . $ugroup;

					// Prepare Page:
					$BxPage = new BxPage();

					if (preg_match('/phpmyadmin\/site/', uri_string())) {
						$BxPage->setVerticalMenu('base_programsSite');
						$BxPage->setVerticalMenuChild('base_phpmyadminSite');
						$page_module = 'base_sitemanage';
					}
					else {
						$BxPage->setVerticalMenu('base_programsPersonal');
						$BxPage->setVerticalMenuChild('base_phpmyadminPersonal');
						$page_module = 'base_personalProfile';
					}

					// Get the FQDN of the Vsite:
					$resVsite = $cceClient->getObject("Vsite", array("name" => $ugroup));

					// Nice people say goodbye, or CCEd waits forever:
					$cceClient->bye();
					$serverScriptHelper->destructor();

					// Page body:
					$page_body[] = addInputForm(
													$i18n->get("[[base-phpmyadmin.PMA_logon]]") . ' - ' . $resVsite['fqdn'],
													array("window" => $uri, "toggle" => "#"), 
													addIframe($uri, "auto", $BxPage),
													"",
													$i18n,
													$BxPage,
													$errors
												);


					// Out with the page:
				    $BxPage->render($page_module, $page_body);

				}
				else {
					// Nice people say goodbye, or CCEd waits forever:
					$cceClient->bye();
					$serverScriptHelper->destructor();
					Log403Error("/gui/Forbidden403");
				}
			}
			else {

				// Prepare Page:
				$factory = $serverScriptHelper->getHtmlComponentFactory("base-phpmyadmin", "/phpmyadmin/$redir");
				$BxPage = $factory->getPage();
				$i18n = $factory->getI18n();

				// Set Menu items:
				$defaultPage = 'pageID';

				if (preg_match('/phpmyadmin\/site/', uri_string())) {
					$BxPage->setVerticalMenu('base_programsSite');
					$BxPage->setVerticalMenuChild('base_phpmyadminSite');
					$page_module = 'base_sitemanage';
				}
				else {
					$BxPage->setVerticalMenu('base_programsPersonal');
					$BxPage->setVerticalMenuChild('base_phpmyadminPersonal');
					$page_module = 'base_personalProfile';
				}

				$block =& $factory->getPagedBlock("phpMyAdmin", array($defaultPage));

				$block->setToggle("#");
				$block->setSideTabs(FALSE);
				$block->setDefaultPage($defaultPage);

				$scrollList = $factory->getScrollList("phpMyAdmin", array("fqdn", "MySQL_menu", " "), $phpMyAdminList); 
			    $scrollList->setAlignments(array("left", "center", "center"));
			    $scrollList->setDefaultSortedIndex('0');
			    $scrollList->setSortOrder('ascending');
			    $scrollList->setSortDisabled(array('2'));
			    $scrollList->setPaginateDisabled(FALSE);
			    $scrollList->setSearchDisabled(FALSE);
			    $scrollList->setSelectorDisabled(FALSE);
			    $scrollList->enableAutoWidth(FALSE);
			    $scrollList->setInfoDisabled(FALSE);
			    $scrollList->setColumnWidths(array("500", "200", "39")); // Max: 739px

				// Push out the Scrollist:
				$block->addFormField(
					$factory->getRawHTML("phpMyAdmin", $scrollList->toHtml()),
					$factory->getLabel("phpMyAdmin"),
					$defaultPage
				);

				// Nice people say goodbye, or CCEd waits forever:
				$cceClient->bye();
				$serverScriptHelper->destructor();

				$page_body[] = $block->toHtml();

				// Out with the page:
			    $BxPage->render($page_module, $page_body);
			}
		}
		else {
			// Prepare Page:
			$BxPage = new BxPage();

			// More dirty hacks to get the correct menu item set. Needed because this page
			// is presented in three different places:
			//
			// - once under 'personal_profile' / 'base_programsPersonal'
			// - once under 'base_siteadmin' / 'base_programs'
			// - once under 'server_management' / 'base_programs'
			//

			if (isset($group)) {
				$uri = '/phpmyadmin/pusher?group=' . $group;
			}
			else {
				$uri = '/phpmyadmin/pusher';
			}

			if ((uri_string() == "phpmyadmin/server") && ($Capabilities->getAllowed('adminUser'))) {
				$BxPage->setVerticalMenu('base_programs');
				$page_module = 'base_sysmanage';
			}
			if ((uri_string() == "phpmyadmin/server") && (!$Capabilities->getAllowed('adminUser'))) {
				// Nice people say goodbye, or CCEd waits forever:
				$cceClient->bye();
				$serverScriptHelper->destructor();
				Log403Error("/gui/Forbidden403");
			}
			if ((uri_string() == "phpmyadmin/site") && ($Capabilities->getAllowed('siteAdmin'))) {
				$BxPage->setVerticalMenu('base_programsSite');
				$page_module = 'base_siteadmin';
			}
			if ((uri_string() == "phpmyadmin/site") && (!$Capabilities->getAllowed('siteAdmin'))) {
				// Nice people say goodbye, or CCEd waits forever:
				$cceClient->bye();
				$serverScriptHelper->destructor();
				Log403Error("/gui/Forbidden403");
			}
			if (uri_string() == "phpmyadmin/user") {
				$BxPage->setVerticalMenu('base_programsPersonal');
				$page_module = 'base_personalProfile';
			}

			// Are we here via "Site Management" / "Programs"?
			if (isset($get_form_data['group'])) {
				// We have a group URL string:
				$ugroup = $get_form_data['group'];
			}

			// If the login credentials in the session data have changed, then we need to do a little
			// round-about. We unset the session data, redirect twice and then get the login form.
			if (!isset($page_module)) {
				$BxPage->setVerticalMenu('base_programsPersonal');
				$page_module = 'base_personalProfile';
				$uri = '/phpmyadmin/signon';

				$session_name = 'SignonSession';
				session_name($session_name);
				session_start();
				/* Store the credentials */
				$_SESSION['PMA_single_signon_user'] = '';
				$_SESSION['PMA_single_signon_password'] = '';
				$_SESSION['PMA_single_signon_host'] = '';
				$id = session_id();
				/* Close that session */
				session_write_close();
				/* Redirect to phpMyAdmin (should use absolute URL here!) */
				header('Location: /phpmyadmin/signon');
			}

			if (isset($ugroup)) {
				// Set Menu items:
				$BxPage->setVerticalMenu('base_siteadmin');
				$BxPage->setVerticalMenuChild('base_phpmyadminSite');
				$page_module = 'base_sitemanage';
			}

			// Nice people say goodbye, or CCEd waits forever:
			$cceClient->bye();
			$serverScriptHelper->destructor();

			// Page body:
			$page_body[] = addInputForm(
											$i18n->get("[[base-phpmyadmin.PMA_logon]]"),
											array("window" => $uri, "toggle" => "#"), 
											addIframe($uri, "auto", $BxPage),
											"",
											$i18n,
											$BxPage,
											$errors
										);


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