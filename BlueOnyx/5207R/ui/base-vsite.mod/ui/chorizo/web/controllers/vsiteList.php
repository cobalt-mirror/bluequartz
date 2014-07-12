<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class VsiteList extends MX_Controller {

	/**
	 * Index Page for this controller.
	 *
	 * Past the login page this loads the page for /vsite/vsiteList.
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
		$i18n = new I18n("base-vsite", $user['localePreference']);
		$system = $cceClient->getObject("System");

		// Initialize Capabilities so that we can poll the access rights as well:
		$Capabilities = new Capabilities($cceClient, $loginName, $sessionId);

		// Required array setup:
		$errors = array();
		$extra_headers = array();

		// -- Actual page logic start:

		// Not 'manageSite'? Bye, bye!
		if (!$Capabilities->getAllowed('manageSite')) {
			// Nice people say goodbye, or CCEd waits forever:
			$cceClient->bye();
			$serverScriptHelper->destructor();
			Log403Error("/gui/Forbidden403");
		}
		else {

			// Start with an empty siteList:
			$siteList = array();

			$exact = array();
			if (!$Capabilities->getAllowed('systemAdministrator')) {
					// If the user is not 'admin', then we only show Vsites that this user owns:
			        $exact = array_merge($exact, array('createdUser' => $loginName));  
			}

			// Get a list of Vsite OID's:
			$vsites = $cceClient->findx('Vsite', $exact, array(), "", "");

			// Auto-detect available features:
			$autoFeatures = new AutoFeatures($cceClient);
			$AutoFeaturesList = $autoFeatures->ListFeatures('modifyWeb.Vsite');

			$numsite = "0";
			foreach ($vsites as $site) {
				// Get Vsite settings:
				$vsiteSettings = $cceClient->get($site);
				$vsiteSettings['FEATURE'] = array();
				foreach ($AutoFeaturesList as $key => $value) {
					$featureOID = $cceClient->get($site, $value);
					if ($value == "PHP") {
						if ($featureOID['suPHP_enabled'] == "1") {
							$vsiteSettings['FEATURE']['suPHP'] = $featureOID['suPHP_enabled'];
						}
						else {
							$vsiteSettings['FEATURE']['PHP'] = $featureOID['enabled'];
						}
					}
					else {
						$vsiteSettings['FEATURE'][$value] = $featureOID['enabled'];
					}
				}

				// Manually add the following features as well, although they are not auto-features:
				$vsiteSettings['FEATURE']['Email'] = $vsiteSettings['emailDisabled'];
				$vsiteSettings['FEATURE']['DNS'] = $vsiteSettings['dns_auto'];

				// SSL:
				$vsiteSSLSettings = $cceClient->get($site, 'SSL');
				if ($vsiteSSLSettings['enabled'] == '1') {
					$vsiteSettings['FEATURE']['SSL'] = $vsiteSSLSettings['enabled'];
				}

				$siteList[0][$numsite] = $vsiteSettings['fqdn'];
				$siteList[1][$numsite] = $vsiteSettings['ipaddr'];

		        // Display the Owner of the Vsite:
		        if ($vsiteSettings['createdUser'] == "") {
		        		$createdUser = "admin";
		        }
		        else {
		        	$createdUser = $vsiteSettings['createdUser'];
		        } 
				$siteList[2][$numsite] = $createdUser;

				// Suspend icon:
				if ($vsiteSettings['suspend']) {
					$suspended = '					<button class="red tiny text_only has_text tooltip hover" title="' . $i18n->getHtml("[[palette.Yes]]") . '" disabled><span>' . $i18n->getHtml("[[palette.Yes]]") . '</span></button>';
				}
				else {
					$suspended = '					<button class="light tiny text_only has_text tooltip hover" title="' . $i18n->getHtml("[[palette.No]]") . '" disabled><span>' . $i18n->getHtml("[[palette.No]]") . '</span></button>';
				}
				$siteList[3][$numsite] = $suspended;

				// Feature-List Icons:
				$iconlist = array();
				foreach ($vsiteSettings['FEATURE'] as $key => $value) {
					if ($key == "SSL") { $F_text = "SSL"; $F_tooltip = "SSL"; }
					elseif ($key == "MYSQL_Vsite") { $F_text = "SQL"; $F_tooltip = "MySQL"; }
					elseif ($key == "Java") { $F_text = "JSP"; $F_tooltip = "JSP";  }
					elseif ($key == "USERWEBS") { $F_text = "~"; $F_tooltip = "User owned webs";  }
					elseif ($key == "CGI") { $F_text = "CGI"; $F_tooltip = "CGI";  }
					elseif ($key == "SSI") { $F_text = "SSI"; $F_tooltip = "SSI";  }
					elseif ($key == "ApacheBandwidth") { $F_text = "Limit"; $F_tooltip = "Bandwidth Limits";  }
					elseif ($key == "PHP") { $F_text = "PHP"; $F_tooltip = "PHP"; }
					elseif ($key == "suPHP") { $F_text = "suPHP"; $F_tooltip = "suPHP"; }
					elseif ($key == "FTPNONADMIN") { $F_text = "FTP"; $F_tooltip = "FTP"; }
					elseif ($key == "AnonFtp") { $F_text = "anonFTP"; $F_tooltip = "Anonymous FTP"; }
					else { $F_text = $key; $F_tooltip = $key; }
					if ($value == "1") {
						$iconlist[] = '<button class="tiny text_only has_text tooltip hover" title="' . $i18n->getHtml($F_tooltip) . '" disabled>'. $F_text . '</button>';
					}
					else {
						// Hide inactive icons for now. That way we can use the search form to find sites with certain active features:
						//$iconlist[] = '<button class="light tiny text_only has_text tooltip hover" title="' . $i18n->getHtml($F_tooltip) . ' disabled">'. $F_text . '</button>';
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
				$siteList[4][$numsite] = $wrapped_iconlist;

				// Add Buttons for Edit, View and Delete:
				//$buttons = '<a href="/user/userList?group=' . $vsiteSettings['name'] . '"><button class="tiny icon_only div_icon tooltip hover" title="' . $i18n->getHtml("generalSettings_help") . '"><div class="ui-icon ui-icon-pencil"></div></button></a><br>';
				$buttons = '<button title="' . $i18n->getHtml("generalSettings_help") . '" class="tiny icon_only div_icon tooltip hover right link_button" data-link="/user/userList?group=' . $vsiteSettings['name'] . '" target="_self" formtarget="_self"><div class="ui-icon ui-icon-pencil"></div></button>';

				$buttons .= '<a class="various" target="_blank" href="' . "http://" . $vsiteSettings['fqdn'] . '" data-fancybox-type="iframe">' . '<button class="fancybox tiny icon_only div_icon tooltip hover" title="' . $i18n->getHtml("sitePreview") .'"><div class="ui-icon ui-icon-newwin"></button>' . '</a><br>';
				$buttons .= '<a class="lb" href="/vsite/vsiteDel?group=' . $vsiteSettings['name'] . '"><button class="tiny icon_only div_icon tooltip hover dialog_button" title="' . $i18n->getHtml("siteRemove") . '"><div class="ui-icon ui-icon-trash"></div></button></a><br>';
				$siteList[5][$numsite] = $buttons;
				$numsite++;
			}
		}

	    //-- Generate page:

		// Prepare Page:
		$factory = $serverScriptHelper->getHtmlComponentFactory("base-vsite", "/vsite/vsiteList");
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
		$BxPage->setVerticalMenu('base_siteList1');
		$page_module = 'base_sitemanageVSL';
		$defaultPage = 'pageID';

		$block =& $factory->getPagedBlock("virtualSiteList", array($defaultPage));

		$scrollList = $factory->getScrollList("virtualSiteList", array("fqdn", "ipAddr", "createdUser", "listSuspended", "Features", " "), $siteList); 
	    $scrollList->setAlignments(array("left", "right", "center", "center", "center", "right"));
	    $scrollList->setDefaultSortedIndex('0');
	    $scrollList->setSortOrder('ascending');
	    $scrollList->setSortDisabled(array('5'));
	    $scrollList->setPaginateDisabled(FALSE);
	    $scrollList->setSearchDisabled(FALSE);
	    $scrollList->setSelectorDisabled(FALSE);
	    $scrollList->enableAutoWidth(FALSE);
	    $scrollList->setInfoDisabled(FALSE);
	    $scrollList->setColumnWidths(array("200", "120", "80", "80", "223", "35")); // Max: 739px

	    // Print administrative information for resellers:
	    if (!$Capabilities->getAllowed('systemAdministrator')) {
			$vsite_disk = 0;  
			$vsite_user = 0;
			$num_vsites = 0;  
			foreach($vsites as $vsites_oid) {  
			    $vsite = $cceClient->get($vsites_oid);  
			    $vsite2 = $cceClient->get($vsites_oid, "Disk");
			    $vsite_user += $vsite['maxusers'];  
			    $vsite_disk += $vsite2['quota'];
			    $num_vsites++;
			}  
			list($user_oid) = $cceClient->find('User', array('name' => $loginName));  
			$sites = $cceClient->get($user_oid, 'Sites');  
			$sites['quota'] = simplify_number($sites['quota']*1000, "K", "1") . "B";
			$ResellerStats = '						<div class="columns">
							<div class="col_33 no_border_top no_border_right">
								<div class="info_box">
									<label title="'. $i18n->getWrapped("userSitesMax_help") . '" class="tooltip right">'. $i18n->getClean("userSitesMax") . '</label>
									<div class="split three"><small><b>' . $sites['max'] . '</b></small></div>
									<div class="split three"><small>' . $num_vsites . '</small></div>
								</div>
							</div>
							<div class="col_33 no_border_top no_border_right">
								<div class="info_box">
									<label title="'. $i18n->getWrapped("userSitesUser_help") . '" class="tooltip right">'. $i18n->getClean("userSitesUser") . '</label>
									<div class="split three"><small><b>'. $sites['user'] . '</b></small></div>
									<div class="split three"><small>' . $vsite_user . '</small></div>
								</div>
							</div>
							<div class="col_33 no_border_top no_border_right">
								<div class="info_box">
									<label title="'. $i18n->getWrapped("userSitesQuota_help") . '" class="tooltip right">'. $i18n->getClean("userSitesQuota") . '</label>
									<div class="split three"><small><b>' . $sites['quota'] . '</b></small></div>
									<div class="split three"><small>' . simplify_number($vsite_disk*1000, "K", "1") . "B" . '</small></div>
								</div>
							</div>
					</div>' . "\n";

			// Push out the ResellerStats in our improvised Columns:
			$block->addFormField(
				$factory->getRawHTML("ResellerStats", $ResellerStats),
				$factory->getLabel("ResellerStats"),
				$defaultPage
			);
		}  	    

		// Check vsite max for administrator 
		list($user_oid) = $cceClient->find('User', array('name' => $loginName)); 
		$sites = $cceClient->get($user_oid, 'Sites'); 

		$user_sites = $cceClient->find('Vsite', array('createdUser' => $loginName));
		// Show "Add"-button if this Vsite hasn't yet reached max number of accounts:
		if ((($sites['max'] > 0) && (count($user_sites) < $sites['max'])) || ($Capabilities->getAllowed('systemAdministrator'))) {
			// Generate +Add button:
			$addAdminUser = "/vsite/vsiteAdd";
			$addbutton = $factory->getAddButton($addAdminUser, '[[base-vsite.siteaddbut_help]]', "DEMO-OVERRIDE");
			$buttonContainer = $factory->getButtonContainer("virtualSiteList", $addbutton);
			$block->addFormField(
				$buttonContainer,
				$factory->getLabel("virtualSiteList"),
				$defaultPage
			);
		}

		// Push out the Scrollist:
		$block->addFormField(
			$factory->getRawHTML("virtualSiteList", $scrollList->toHtml()),
			$factory->getLabel("virtualSiteList"),
			$defaultPage
		);

		// Nice people say goodbye, or CCEd waits forever:
		$cceClient->bye();
		$serverScriptHelper->destructor();

		$page_body[] = $block->toHtml();

		// Add hidden Modal for Delete-Confirmation:
        $page_body[] = '
			<div class="display_none">
			    		<div id="dialog" class="dialog_content narrow no_dialog_titlebar" title="' . $i18n->getHtml("[[base-vsite.siteRemoveConfirmNeutral]]") . '">
			                <div class="block">
			                        <div class="section">
			                                <h1>' . $i18n->getHtml("[[base-vsite.siteRemoveConfirmNeutral]]") . '</h1>
			                                <div class="dashed_line"></div>
			                                <p>' . $i18n->getHtml("[[base-vsite.removeConfirmInfo]]") . '</p>
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