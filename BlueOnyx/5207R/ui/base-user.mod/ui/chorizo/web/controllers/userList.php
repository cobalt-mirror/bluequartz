<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class UserList extends MX_Controller {

	/**
	 * Index Page for this controller.
	 *
	 * Past the login page this loads the page for /user/userList.
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
		$i18n = new I18n("base-user", $user['localePreference']);
		$system = $cceClient->getObject("System");

		// Initialize Capabilities so that we can poll the access rights as well:
		$Capabilities = new Capabilities($cceClient, $loginName, $sessionId);

		// Required array setup:
		$errors = array();
		$extra_headers = array();

		// Get URL params:
		$get_form_data = $CI->input->get(NULL, TRUE);

		//
		//-- Validate GET data:
		//

		if (isset($get_form_data['group'])) {
			// We have a group URL string:
			$group = $get_form_data['group'];
		}
		else {
			// Don't play games with us!
			// Nice people say goodbye, or CCEd waits forever:
			$cceClient->bye();
			$serverScriptHelper->destructor();
			Log403Error("/gui/Forbidden403#1");
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
			$cceClient->bye();
			$serverScriptHelper->destructor();
			Log403Error("/gui/Forbidden403#2");
		}

		//
		//-- Get Vsite data
		//
		if ($group) {
			$userDefaults = $cceClient->getObject("Vsite", array("name" => $group), "UserDefaults");
			if (count($cceClient->find("Vsite", array("name" => $group))) == "0") {
				// Don't play games with us!
				// Nice people say goodbye, or CCEd waits forever:
				$cceClient->bye();
				$serverScriptHelper->destructor();
				Log403Error("/gui/Forbidden403#3");
			}
			else {
				list($vsite) = $cceClient->find("Vsite", array("name" => $group));
				$vsiteObj = $cceClient->get($vsite);
	    		list($userServices) = $cceClient->find("UserServices", array("site" => $group));
	    	}
		}
		else {
			$userDefaults = $cceClient->getObject("System", array(), "UserDefaults");
		}

		// Second stage of capability check. More thorough here:
		// Only adminUser and siteAdmin should be here
		// NOTE: Needs testing if this is restructive enough (!!!!!!!!!!!!!!!!!!!!!!!!!!)
		if ((!$Capabilities->getAllowed('adminUser')) && 
			(!$Capabilities->getAllowed('siteAdmin')) && 
			(!$Capabilities->getAllowed('manageSite')) && 
			(($user['site'] != $serverScriptHelper->loginUser['site']) && $Capabilities->getAllowed('siteAdmin')) &&
			(($vsiteObj['createdUser'] != $loginName) && $Capabilities->getAllowed('manageSite'))
			) {
			// Nice people say goodbye, or CCEd waits forever:
			$cceClient->bye();
			$serverScriptHelper->destructor();
			Log403Error("/gui/Forbidden403#4");
		}
		else {
			// Start with an empty userList:
			$userList = array();

			if ($group) {
				$exactMatch = array("site" => $group);
			} else {
				$exactMatch = array();
			}

			$Users = $cceClient->findx("User", $exactMatch, array(), 'ascii', "");

			// Find out of non-siteAdmin's can FTP or not:
			$FTPNONADMIN = $cceClient->get($vsiteObj["OID"], "FTPNONADMIN");

			// Auto-detect available features:
			//$autoFeatures = new AutoFeatures($serverScriptHelper);

			$numUsers = "0";
			foreach ($Users as $user) {
				// Get Vsite settings:
				$UserData = $cceClient->get($user);

				// Full name:
				$userList[0][$numUsers] = bx_charsetsafe($UserData['fullName']);

				// Username:
				$userList[1][$numUsers] = $UserData['name'];

				// Email Aliases:
				$userEmail = $cceClient->get($user, 'Email');
				$userList[2][$numUsers] = implode(', ', stringToArray($userEmail['aliases']));

				// Suspend icon:
				if ($UserData['enabled'] == "0") {
					$suspended = '					<button class="red tiny text_only has_text tooltip hover" title="' . $i18n->getHtml("[[palette.Yes]]") . '" disabled><span>' . $i18n->getHtml("[[palette.Yes]]") . '</span></button>';
				}
				else {
					$suspended = '					<button class="light tiny text_only has_text tooltip hover" title="' . $i18n->getHtml("[[palette.No]]") . '" disabled><span>' . $i18n->getHtml("[[palette.No]]") . '</span></button>';
				}
				$userList[3][$numUsers] = $suspended;

				//
				//-- Feature List:
				//
				//	We could use AutoFeatures here. But in reality the items we are 
				//	interested in are not all realized via AutoFeatures. So let us
				// 	break it down to what we need:
				//
				//	- siteAdmin								via capLevels
				//	- DNS Administrator						via capLevels
				//	- Shell access 							via $oid . Shell "enabled" = 0/1
				//	- FTP access 							via Vsite $oid "FTPNONADMIN" = 0/1 and Capabilities
				//	- Email (enabled/disabled)				via $oid "emailDisabled" = 0/1
				//	- Vacation Message (enabled/disabled)	via $oid . Email "vacationOn" = 0/1
				//	- Subdomain (enabled/disabled)			via $oid . subdomains "enabled" = 0/1

				// Is User a siteAdmin?
				$UserData['FEATURE']['siteAdmin'] = "0";
				if (in_array('siteAdmin', scalar_to_array($UserData['capLevels']))) {
					$UserData['FEATURE']['siteAdmin'] = "1";
				}

				// Is User a dnsAdmin?
				$UserData['FEATURE']['siteDNS'] = "0";
				if (in_array('siteDNS', scalar_to_array($UserData['capLevels']))) {
					$UserData['FEATURE']['siteDNS'] = "1";
				}

				// Does User have Shell access?
				$UserData['FEATURE']['siteShell'] = "0";
				if (in_array('siteShell', scalar_to_array($UserData['capLevels']))) {
					$UserData['FEATURE']['siteShell'] = "1";
				}

				// Can User use FTP?
				if (in_array('siteAdmin', scalar_to_array($UserData['capLevels']))) {
					// siteAdmin's can always use FTP:
					$UserData['FEATURE']['FTP'] = "1";
				}
				elseif (($FTPNONADMIN['enabled'] == "1") && (!in_array('siteAdmin', scalar_to_array($UserData['capLevels'])))) {
					// Not siteAdmin, but FTPNONADMIN is enabled:
					$UserData['FEATURE']['FTP'] = "1";
				}
				else {
					// Tough luck. FTPNONADMIN is off and we're just a grunt:
					$UserData['FEATURE']['FTP'] = "0";
				}

				// Does User have Email enabled?
				if ($UserData['emailDisabled'] == "1") {
					$UserData['FEATURE']['Email'] = "0";
				}
				else {
					$UserData['FEATURE']['Email'] = "1";
				}

				// Does user have Vacation Message enabled?
				$UserData['FEATURE']['Vacation'] = "0";
				if ($userEmail['vacationOn'] == "1") {
					$UserData['FEATURE']['Vacation'] = "1";
				}

				// Does User have Subdomain enabled?
				$subdomain = $cceClient->get($user, 'subdomains');
				if ($subdomain['enabled'] == "1") {
					$UserData['FEATURE']['Subdomain'] = "1";
				}
				else {
					$UserData['FEATURE']['Subdomain'] = "0";
				}

				// Feature-List Icons:
				$iconlist = array();
				foreach ($UserData['FEATURE'] as $key => $value) {
					if ($key == "siteAdmin") { $F_text = "siteAdmin"; $F_tooltip = "siteAdmin"; }
					elseif ($key == "siteDNS") { $F_text = "siteDNS"; $F_tooltip = "siteDNS";  }
					elseif ($key == "siteShell") { $F_text = "siteShell"; $F_tooltip = "siteShell";  }
					elseif ($key == "FTP") { $F_text = "FTP"; $F_tooltip = "FTP";  }
					elseif ($key == "Email") { $F_text = "Email"; $F_tooltip = "Email";  }
					elseif ($key == "Vacation") { $F_text = "Vacation"; $F_tooltip = "Vacation";  }
					elseif ($key == "Subdomain") { $F_text = "Subdomain"; $F_tooltip = "Subdomain"; }
					else { $F_text = $key; $F_tooltip = $key; }
					if ($value == "1") {
						$iconlist[] = '<button class="tiny text_only has_text tooltip hover" title="' . $i18n->getHtml($F_tooltip) . '" disabled>'. $F_text . '</button>';
					}
				}
				$totalicons = count($iconlist);
				$numicons = '0';
				$wrapped_iconlist = '';
				foreach ($iconlist as $key => $value) {
					$wrapped_iconlist .= $value;
					$numicons++;
					if ($numicons == '4') {
						$wrapped_iconlist .= "<br>";
						$numicons = '0';
					}
				}
				$userList[4][$numUsers] = $wrapped_iconlist;

				// Add Buttons for Edit, View and Delete:
				$buttons = '<button title="' . $i18n->getHtml("modifyUser", "base-user", array('userName' => $UserData['name'])) . '" class="tiny icon_only div_icon tooltip hover right link_button" data-link="/user/userMod?group=' . $UserData['site'] . '&name=' . $UserData['name'] . '" target="_self" formtarget="_self"><div class="ui-icon ui-icon-pencil"></div></button><br>';
				// Only add 'Delete' button for all users but our current user:
				if ($UserData['name'] != $loginName) {
					$buttons .= '<a class="lb" href="/user/userDel?group=' . $group . '&name=' . $UserData['name'] . '"><button class="tiny icon_only div_icon tooltip hover dialog_button" title="' . $i18n->getHtml("[[palette.remove_help]]") . '"><div class="ui-icon ui-icon-trash"></div></button></a><br>';
				}
				$userList[5][$numUsers] = $buttons;
				$numUsers++;
			}
		}

	    //-- Generate page:

		// Prepare Page:
		$factory = $serverScriptHelper->getHtmlComponentFactory("base-user", "/user/userList");
		$BxPage = $factory->getPage();
		$i18n = $factory->getI18n();

		$BxPage->setExtraHeaders('
				<script>
					$(document).ready(function() {
						$(".various").fancybox({
							overlayColor: "#000",
							fitToView	: false,
							width		: "80%",
							height		: "80%",
							autoSize	: false,
							closeClick	: false,
							openEffect	: "none",
							closeEffect	: "none"
						});
					});
				</script>');

		// Extra header for the "do you really want to delete" dialog:
		$BxPage->setExtraHeaders('
				<script type="text/javascript">
				$(document).ready(function () {

				  $("#dialog").dialog({
				    modal: true,
				    bgiframe: true,
				    width: 500,
				    height: 280,
				    autoOpen: false
				  });

				  $(".lb").click(function (e) {
				    e.preventDefault();
				    var hrefAttribute = $(this).attr("href");

				    $("#dialog").dialog(\'option\', \'buttons\', {
				      "' . $i18n->getHtml("[[palette.remove]]") . '": function () {
				        window.location.href = hrefAttribute;
				      },
				      "' . $i18n->getHtml("[[palette.cancel]]") . '": function () {
				        $(this).dialog("close");
				      }
				    });

				    $("#dialog").dialog("open");

				  });
				});
				</script>');

		// Set Menu items:
		$BxPage->setVerticalMenu('base_siteadmin');
		$BxPage->setVerticalMenuChild('base_userList');
		$page_module = 'base_sitemanage';
		$defaultPage = 'pageID';

		$block =& $factory->getPagedBlock("userList", array($defaultPage));

		$block->setToggle("#");
		$block->setSideTabs(FALSE);
		//$block->setShowAllTabs("#");
		$block->setDefaultPage($defaultPage);

		$scrollList = $factory->getScrollList("userList", array("fullName", "userName", "emailAliases", "userSuspended", "rights", "listAction"), $userList); 
	    $scrollList->setAlignments(array("left", "left", "left", "center", "center", "center"));
	    $scrollList->setDefaultSortedIndex('0');
	    $scrollList->setSortOrder('ascending');
	    $scrollList->setSortDisabled(array('5'));
	    $scrollList->setPaginateDisabled(FALSE);
	    $scrollList->setSearchDisabled(FALSE);
	    $scrollList->setSelectorDisabled(FALSE);
	    $scrollList->enableAutoWidth(FALSE);
	    $scrollList->setInfoDisabled(FALSE);
	    $scrollList->setColumnWidths(array("200", "120", "180", "4", "200", "35")); // Max: 739px

	    // Get VSite object if we don't have it already:
		if (!isset($vsiteObj)) {
			list($vsite) = $cceClient->find("Vsite", array("name" => $group));
			$vsiteObj = $cceClient->get($vsite);
		}

		// How many accounts are set up on this Vsite?
		$CreatedUserAccountsAllSites = count($Users);

		// Show "Add"-button if this Vsite hasn't yet reached max number of accounts:
		if ($CreatedUserAccountsAllSites < $vsiteObj['maxusers']) {
			// Generate +Add button:
			$addAdminUser = "/user/userAdd?group=$group";
			$addbutton = $factory->getAddButton($addAdminUser, '[[base-user.add_user_help]]', "DEMO-OVERRIDE");
			$buttonContainer = $factory->getButtonContainer("userList", $addbutton);
			$block->addFormField(
				$buttonContainer,
				$factory->getLabel("userList"),
				$defaultPage
			);
		}

		// Push out the Scrollist:
		$block->addFormField(
			$factory->getRawHTML("userList", $scrollList->toHtml()),
			$factory->getLabel("userList"),
			$defaultPage
		);

		// Nice people say goodbye, or CCEd waits forever:
		$cceClient->bye();
		$serverScriptHelper->destructor();

		$page_body[] = $block->toHtml();

		// Add hidden Modal for Delete-Confirmation:
        $page_body[] = '
			<div class="display_none">
			    		<div id="dialog" class="dialog_content narrow no_dialog_titlebar" title="' . $i18n->getHtml("[[base-user.removeConfirmQuestion]]") . '">
			                <div class="block">
			                        <div class="section">
			                                <h1>' . $i18n->getHtml("[[base-user.removeConfirmQuestion]]") . '</h1>
			                                <div class="dashed_line"></div>
			                                <p>' . $i18n->getHtml("[[base-user.userRemoveConfirmInfo]]") . '</p>
			                        </div>
			                </div>
			        	</div>
			</div>';

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