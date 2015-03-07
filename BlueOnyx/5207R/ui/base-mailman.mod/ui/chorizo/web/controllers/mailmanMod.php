<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class mailmanMod extends MX_Controller {

	/**
	 * Index Page for this controller.
	 *
	 * Past the login page this loads the page for /mailman/mailmanMod.
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
		$i18n = new I18n("base-mailman", $user['localePreference']);
		$system = $cceClient->getObject("System");

		// Initialize Capabilities so that we can poll the access rights as well:
		$Capabilities = new Capabilities($cceClient, $loginName, $sessionId);

		// -- Actual page logic start:

		// Get URL strings:
		$get_form_data = $CI->input->get(NULL, TRUE);

		// Get posted FORM data:
		$form_data = $CI->input->post(NULL, TRUE);

		//
		//-- Validate GET data:
		//

		if (isset($get_form_data['group'])) {
			// We have a delete transaction:
			$group = $get_form_data['group'];
		}
		else {
			// Don't play games with us!
			// Nice people say goodbye, or CCEd waits forever:
			$cceClient->bye();
			$serverScriptHelper->destructor();
			Log403Error("/gui/Forbidden403#1");
		}

		$_TARGET = "";
		if (isset($get_form_data['_TARGET'])) {
			$_TARGET = $get_form_data['_TARGET'];

			if (intval($_TARGET) > 0) {
				// we're editing an existing mailing list
				// but not saving just yet
				$obj = $cceClient->get($_TARGET);

				// Verify if it's an 'MailList' Object:
				if ($obj['CLASS'] != "MailList") { 
					// Yeah, it was a nice try. There is the door!
					// Nice people say goodbye, or CCEd waits forever:
					$cceClient->bye();
					$serverScriptHelper->destructor();
					Log403Error("/gui/Forbidden403#2");
				}

				$listName = $obj['name'];
				$apassword = $obj['apassword'];
				$internal_name = $obj['internal_name'];
				$localSubs = stringToArray($obj['local_recips']);
				$remote_recips = $obj['remote_recips'];
				$remote_recips_digest = $obj['remote_recips_digest'];
				$postPolicy = $obj['postPolicy'];
				$subPolicy = $obj['subPolicy'];
				$moderator = $obj['moderator'];
				$maxlength = $obj['maxlength'];
				$replyToList = $obj['replyToList'];
				$description = $obj['description'];
				$group = $obj['site'];
			}
		}
		else {
				$listName = "";
				$apassword = "";
				$internal_name = "";
				$localSubs = array();
				$local_recips = "";
				$remote_recips = "";
				$remote_recips_digest = "";
				$postPolicy = "members";
				$subPolicy = "open";
				$moderator = "admin";
				$maxlength = "50";
				$replyToList = "1";
				$description = "";
				$group = $group;
		}

		// If we have POST data, we might have a $_TARGET there:
		if (isset($form_data['_TARGET'])) {
			$_TARGET = $form_data['_TARGET'];
		}

		$mode = "";
		if (isset($get_form_data['mode'])) {
			$mode = $get_form_data['mode'];
		}

		// Used for local subscriber User selector:
		$viewer = "showall";
		$viewerOptions = array("showall", "showselected");
		if (isset($get_form_data['viewer'])) {
			if (in_array($get_form_data['viewer'], $viewerOptions)) {
				$viewer = $get_form_data['viewer'];
			}
		}

		// Map for "replyToList":
		$replyListMap = 
		    array(
				"0" => $i18n->getClean("replySender", false), 
				"1" => $i18n->getClean("replyList", false)
		    );
		$replyListMapRev = 
		    array(
				$i18n->getClean("replySender", false) => "0", 
				$i18n->getClean("replyList", false) => "1"
		    );


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
			Log403Error("/gui/Forbidden403#3");
		}

		//
		//-- Prepare data:
		//

		// Get data for the Vsite:
		$vsite = $cceClient->getObject('Vsite', array('name' => $group));
		$display_fqdn = $vsite['fqdn'];

		//
		//--- Handle form validation:
		//

	    // We start without any active errors:
	    $errors = array();
	    $extra_headers =array();
	    $ci_errors = array();
	    $my_errors = array();

		// Form fields that are required to have input:
		$required_keys = array();

    	// Set up rules for form validation. These validations happen before we submit to CCE and further checks based on the schemas are done:

		// Empty array for key => values we want to submit to CCE:
    	$attributes = array();

    	// Items we do NOT want to submit to CCE:
    	$ignore_attributes = array("BlueOnyx_Info_Text", "localSubscribers");
		if (is_array($form_data)) {
			// Function GetFormAttributes() walks through the $form_data and returns us the $parameters we want to
			// submit to CCE. It intelligently handles checkboxes, which only have "on" set when they are ticked.
			// In that case it pulls the unticked status from the hidden checkboxes and addes them to $parameters.
			// It also transformes the value of the ticked checkboxes from "on" to "1". 
			//
			// Additionally it generates the form_validation rules for CodeIgniter.
			//
			// params: $i18n				i18n Object of the error messages
			// params: $form_data			array with form_data array from CI
			// params: $required_keys		array with keys that must have data in it. Needed for CodeIgniter's error checks
			// params: $ignore_attributes	array with items we want to ignore. Such as Labels.
			// return: 						array with keys and values ready to submit to CCE.
			$attributes = GetFormAttributes($i18n, $form_data, $required_keys, $ignore_attributes, $i18n);
		}
		//Setting up error messages:
		$CI->form_validation->set_message('required', $i18n->get("[[palette.val_is_required]]", false, array("field" => "\"%s\"")));		

	    // Do we have validation related errors?
	    if ($CI->form_validation->run() == FALSE) {

			if (validation_errors()) {
				// Set CI related errors:
				$ci_errors = array(validation_errors('<div class="alert dismissible alert_red"><img width="40" height="36" src="/.adm/images/icons/small/white/alarm_bell.png"><strong>', '</strong></div>'));
			}		    
			else {
				// No errors. Pass empty array along:
				$ci_errors = array();
			}
		}

		//
		//--- Own error checks:
		//

		if ($CI->input->post(NULL, TRUE)) {
			if (!isset($attributes['localSubs'])) {
				$attributes['localSubs'] = "";
			}
			if (!isset($attributes['remote_recips'])) {
				$attributes['remote_recips'] = "";
			}
			if (!isset($attributes['remote_recips_digest'])) {
				$attributes['remote_recips_digest'] = "";
			}
			if (!isset($attributes['description'])) {
				$attributes['description'] = " ";
			}
		}

		//
		//--- At this point all checks are done. If we have no errors, we can submit the data to CODB:
		//

		// Join the various error messages:
		$errors = array_merge($ci_errors, $my_errors);

		// If we have no errors and have POST data, we submit to CODB:
		if ((count($errors) == "0") && ($CI->input->post(NULL, TRUE))) {

			$vals = array(
				'name' => $attributes['listName'],
				'local_recips' => $attributes['localSubs'],
				'remote_recips' => $attributes['remote_recips'],
				'remote_recips_digest' => $attributes['remote_recips_digest'],
				'postPolicy' => $attributes['postPolicy'],
				'subPolicy' => $attributes['subPolicy'],
				'moderator' => $attributes['moderator'],
				'maxlength' => $attributes['maxlength'],
				'replyToList' => $replyListMapRev[$attributes['replyToList']],
				'description' => $attributes['description'],
				'site' => $group);

			// Only update password if we have one:
			if (isset($attributes['apassword'])) {
				if ($attributes['apassword'] != "") {
					$vals['apassword'] = $attributes['apassword'];
				}
			}

			if (isset($_TARGET) && intval($_TARGET) > 0) {
				// Saving new settings for existing mailing list
				$cceClient->set($_TARGET, '', $vals);
			}
			else {
				// Creating new mailing list
				$cceClient->create('MailList', $vals);
			}

			// CCE errors that might have happened during submit to CODB:
			$CCEerrors = $cceClient->errors();
			foreach ($CCEerrors as $object => $objData) {
				// When we fetch the CCE errors it tells us which field it bitched on. And gives us an error message, which we can return:
				$errors[] = ErrorMessage($i18n->get($objData->message, true, array('key' => $objData->key)) . '<br>&nbsp;');
			}

			// Need to commit to "System" Object "MailList" as well:
			$ret = $cceClient->setObject("System", array('commit' => time()), "MailList");

			// No errors during submit? Reload page:
			if (count($errors) == "0") {
				$cceClient->bye();
				$serverScriptHelper->destructor();
				$redirect_URL = "/mailman/mailmanList?group=$group";
				header("location: $redirect_URL");
				exit;
			}
		}

		// Get User list assembled:
		$users = $cceClient->findx("User", array('site' => $group), array(), "ascii", "name");
		$numUsers = "0";
		foreach ($users as $oid) {
			$user = $cceClient->get($oid);
			$checker = '';
			if (in_array($user['name'], $localSubs)) {
				$checker = ' checked';
			}
			if ((($viewer == 'showselected') && (in_array($user['name'], $localSubs))) || ($viewer == 'showall')) {
				// We manually construct a checkbox here:
				$localSubsScroll[0][$numUsers] = '<input type="checkbox" name="localSubs[]" class="checkbox uniform" id="' . $user['name'] . '" value="' . $user['name'] . '"' . $checker . '  />';
				$localSubsScroll[1][$numUsers] = $user['name'];
				$localSubsScroll[2][$numUsers] = bx_charsetsafe($user['fullName']);
				$numUsers++;
			}
		}

		// It is possible that someone clicks "Show Only Selected Users" and there are no 
		// local subscribers present. In that case we need an empty $localSubsScroll array
		// for the ScrollList or it barfs:
		if (!isset($localSubsScroll)) {
			$localSubsScroll = array();
		}

		//
	    //-- Generate page:
	    //

		// Prepare Page:
		$factory = $serverScriptHelper->getHtmlComponentFactory("base-mailman", "/mailman/mailmanMod?group=$group");
		$BxPage = $factory->getPage();
		$BxPage->setErrors($errors);
		$i18n = $factory->getI18n();

		// Set Menu items:
		$BxPage->setVerticalMenu('base_siteservices');
		$BxPage->setVerticalMenuChild('base_mailmans');
		$page_module = 'base_sitemanage';

		$defaultPage = "basic";
		$subsId = "subscribers";
		$advancedId = "advanced";

		if (isset($_TARGET) && intval($_TARGET) > 0) {
			//	Title for modifying of a list:
			$createMailList = "0";
			$block =& $factory->getPagedBlock("modifyMailList", array($defaultPage, $subsId, $advancedId));
			$list = $cceClient->get($_TARGET);
			$block->setLabel($factory->getLabel('modifyMailList', false, array('listName' => $listName)));
		}
		else {
			// Title for creating a list:
			$createMailList = "1";
			$block =& $factory->getPagedBlock("createMailList", array($defaultPage, $subsId, $advancedId));
			$block->setLabel($factory->getLabel('createMailList', false, array('fqdn' => $vsite['fqdn'])));
		}

		$block->setToggle("#");
		$block->setSideTabs(FALSE);
		$block->setShowAllTabs("#");
		$block->setDefaultPage($defaultPage);

		// Only 'manageSite' can modify things on this page.
		// Site admins can view it for informational purposes.
		if ($Capabilities->getAllowed('manageSite')) {
		    $is_site_admin = FALSE;
		    $access = 'rw';
		}
		elseif (($Capabilities->getAllowed('siteAdmin')) && ($group == $Capabilities->loginUser['site'])) {
			$access = 'rw';
			$is_site_admin = TRUE;
		}
		else {
			// Nice people say goodbye, or CCEd waits forever:
			$cceClient->bye();
			$serverScriptHelper->destructor();
			Log403Error("/gui/Forbidden403#5");
		}

		//
		//-- Basic Settings:
		//

		$block->addFormField(
			       $factory->getMailListname("listName", $listName), 
			       $factory->getLabel("listName"),
			       $defaultPage);

		// Show List Email Address (if editing, but not during create):
		if (intval($_TARGET) > 0) {
			$listAddress = $internal_name . '@' . $display_fqdn;
			$block->addFormField(
			       $factory->getMailListName("listAddress", $listAddress, 'r'), 
			       $factory->getLabel("listAddress"),
			       $defaultPage);
		}

		$desc = $factory->getTextBlock("description", $description);
		$desc->setOptional("silent");
		$desc->setWidth(40);
		$block->addFormField($desc,
			       $factory->getLabel("description"),
			       $defaultPage);

		//
		//-- Subscribers:
		//

		// Local Subscriber count:
		if (intval($_TARGET) > 0) {
			$num = $factory->getTextField("localSubscribers", 
					$i18n->interpolate("[[base-mailman.numLocals]]", 
					array('num' => count($localSubs), 
					'plural' => '')),
					"r");
			$num->setPreserveData(false);
			$block->addFormField(
				$factory->getRawHTML("localSubscribers", $num->toHtml()),
				$factory->getLabel("localSubscribers"),
				$subsId
			);
		}

		// Button container for user selection:
		$targetAdder = '';
		if (intval($_TARGET) > 0) {
			$targetAdder = '&_TARGET=' . $_TARGET;
		}
		$showall_button = $factory->getButton("/mailman/mailmanMod?group=$group" . $targetAdder . "&viewer=showall&tabs=#tabs-2", 'showall', "DEMO-OVERRIDE");
		$showselected_button = $factory->getButton("/mailman/mailmanMod?group=$group" . $targetAdder . "&viewer=showselected&tabs=#tabs-2", 'showselected', "DEMO-OVERRIDE");
		$buttonContainer = $factory->getButtonContainer("", array($showall_button, $showselected_button));

		// Push out the ButtonContainer:
		$block->addFormField(
			$factory->getRawHTML("none", $buttonContainer->toHtml()),
			$factory->getLabel("none"),
			$subsId
		);

		$scrollList = $factory->getScrollList("userList", array("selected", "name", "fullName"), $localSubsScroll); 
	    $scrollList->setAlignments(array("left", "left", "left"));
	    $scrollList->setDefaultSortedIndex('1');
	    $scrollList->setSortOrder('ascending');
	    $scrollList->setSortDisabled(array('0'));
	    $scrollList->setPaginateDisabled(FALSE);
	    $scrollList->setSearchDisabled(FALSE);
	    $scrollList->setSelectorDisabled(FALSE);
	    $scrollList->enableAutoWidth(FALSE);
	    $scrollList->setInfoDisabled(FALSE);
	    $scrollList->setColumnWidths(array("139", "300", "300")); // Max: 739px

		// Push out the Scrollist:
		$block->addFormField(
			$factory->getRawHTML("userList", $scrollList->toHtml()),
			$factory->getLabel("userList"),
			$subsId
		);

		$remote = $factory->getEmailAddressList("remote_recips", $remote_recips);
		$remote->setOptional("silent");
		$block->addFormField($remote,
				$factory->getLabel("remoteSubscribers"),
				$subsId);

		$remote_digest = $factory->getEmailAddressList("remote_recips_digest", $remote_recips_digest);
		$remote_digest->setOptional("silent");
		$block->addFormField($remote_digest,
				$factory->getLabel("remoteSubscribersDigest"),
				$subsId);

		//
		//-- Advanced:
		//

		$moderatorAddy = $factory->getTextField("moderator", $moderator);
		$moderatorAddy->setType("fq_email_address_or_username");
		$block->addFormField($moderatorAddy, $factory->getLabel("moderator"), $advancedId);
		$pass = $factory->getPassword("apassword", $apassword, false);
		$pass->setOptional("silent");
		$block->addFormField($pass,
				$factory->getLabel("apassword"),
				$advancedId);

		$block->addDivider($factory->getLabel("policies", false), $advancedId);

		$posting = $factory->getMultiChoice("postPolicy");
		$posting->addOption($factory->getOption("members"));
		$posting->addOption($factory->getOption("any"));
		$posting->addOption($factory->getOption("moderated"));
		$posting->setSelected($postPolicy, true);
		$block->addFormField($posting,
				$factory->getLabel("postingPolicy"),
				$advancedId);

		$subscription = $factory->getMultiChoice("subPolicy");
		$subscription->addOption($factory->getOption("open"));
		$subscription->addOption($factory->getOption("confirm"));
		$subscription->addOption($factory->getOption("closed"));
		$subscription->setSelected($subPolicy, true);
		$block->addFormField($subscription,
				$factory->getLabel("subscriptionPolicy", true, array(),
				array('fqdn' => $display_fqdn)),
				$advancedId);

		$length = $factory->getMultiChoice("maxlength");
		$length->addOption($factory->getOption("5"));
		$length->addOption($factory->getOption("50"));
		$length->addOption($factory->getOption("500"));
		$length->addOption($factory->getOption("10000"));
		$length->addOption($factory->getOption("100000"));
		$length->setSelected($maxlength, true);
		$block->addFormField($length,
				$factory->getLabel("maxlength"),
				$advancedId);

		$reply = $factory->getMultiChoice("replyToList", array_values($replyListMap));
		$reply->setSelected($replyListMap[$replyToList], true);
		$block->addFormField($reply,
				$factory->getLabel("replyToList"),
				$advancedId);

		// Add hiden values for form processing:
		if (intval($_TARGET) > 0) {
			$block->addFormField($factory->getTextField("_TARGET", $_TARGET, ""),
					$factory->getLabel("_TARGET"),
					$subsId);
		}

		// Add the buttons for those who can edit this page:
		if ($access == 'rw') {
			$block->addButton($factory->getSaveButton($BxPage->getSubmitAction()));
			$block->addButton($factory->getCancelButton("/mailman/mailmanList?group=$group"));
		}

		// Add ButtonContainer with buttons to MailMans internal pages:
		if (intval($_TARGET) > 0) {
			$redirect = "http://" . $_SERVER['SERVER_NAME'] . "/mailman/admin/$internal_name";
			$admin_button = $factory->getFancyButton("$redirect", "vsiteMailMan_Admin");
		    $list_archive = "http://" . $_SERVER['SERVER_NAME'] . "/pipermail/$internal_name/";
			$list_archive_button = $factory->getFancyButton("$list_archive", "MailMan_Archive");
			$buttonContainer = $factory->getButtonContainer("", array($admin_button, $list_archive_button), "rw");
			$block->addFormField(
				$buttonContainer,
				$factory->getLabel("MailMan_Admin"),
				$defaultPage
			);
		}

		// Nice people say goodbye, or CCEd waits forever:
		$cceClient->bye();
		$serverScriptHelper->destructor();

		$page_body[] = $block->toHtml();

		// Out with the page:
	    $BxPage->render($page_module, $page_body);

	}		
}

/*
Copyright (c) 2015 Michael Stauber, SOLARSPEED.NET
Copyright (c) 2015 Team BlueOnyx, BLUEONYX.IT
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