<?php

/**
 * BxPage($page_module, $page_body)
 *
 * A Class that renders the entire GUI page past the login procedure.
 *
 * This is the main course. Pretty much every sensible page of the new GUI
 * uses this class to render GUI pages. The exception only being supporting
 * pages for dynamically generated 'fluff' loaded in the HTML headers of 
 * BxPage generated pages.
 *
 * BxPage is a complete rewrite from scratch, but takes over functions that
 * the original Cobalt Networks Page.php carried out. Namely the output of 
 * the body of a page. But as we no longer have a frameset, our new BxPage 
 * also needs to render the menu, needs to open menu entries to expose 
 * sub-menu entries (and mark one as active!) if we're on a page that is 
 * located in a sub-menu. Furthermore it needs to check and show Active
 * Monitor status.
 *
 * Another complication is the localization. BxPage determines the locale of
 * the user and based on it does the localization into the desired language
 * and sets the correct page headers for the page to render correctly.
 *
 * One complication arose out of the Label Objects for FormFields. In the old
 * Cobalt Networks code Labels and FormFields are separate Object entities.
 * We had to keep it that way, but as we no longer can rely on Register Globals
 * to act as 'scratchpad' this temporary data had to be stored somewhere else.
 * Otherwise we would loose the association between Labels and FormFields.
 * Therefore BxPage has been modified to act as temporary storage for these 
 * object associations. Whenever an UIFC Class looses track of which Label it
 * is supposed to use, it can ask BxPage for the Label (and Description) 
 * associated with the ID of the FormField. If that turns up blank, UIFC 
 * Classes will - as last resort - use their ID as a Label instead.
 *
 * BxPage can also be called in a way that supresses the output of the 
 * supporting menu structure. If called with setOutOfStyle(TRUE) BxPage
 * will not show the supporting menu-framework. Instead the page payload will
 * be embedded in the respective HTML framework without any eye-candy.
 *
 * Lastly BxPage also does the error handling. Any page that uses BxPage will
 * pass it's errors to BxPage for visualization. BxPage then decides if the 
 * error is either shown inline in GUI elements that have their own area for 
 * displaying such errors. The commonly used pagedBlock() has such an area, 
 * for example. If no pagedBlock (or other Class with error display area) is
 * used, then BxPage will display the error message on top of the page output.
 *
 * Last but not least there are ACLs. Users can have finely tuned Capabilities.
 * Which define which menu entries, submenu entries and pages they are allowed
 * to see. On an indivdual page level this is checked via calls to the
 * Capability Class. But BxPage renders the menus, so our entire mechanism for
 * rendering menus inside BxPage needs to take the ACL's into account as well.
 * That is done via the functions generateSiteMap(), MenuChildren() and 
 * getURLofFirstChild(), which partially use other functions as well.
 *
 * So BxPage is pretty much our new Swiss Army knife and therefore the heart and
 * soul of the new 'Chorizo GUI'. Which is a Colombian sausage and the name was
 * chosen to keep in theme with the original engine name of 'Sausalito'.
 *
 * @param VAR 	$page_module	: module that the page belongs to
 * @param ARR	$page_body		: An array containing HTML output
 */

// To do list:
//
// Create mechanism that hides "Add Vsite" menu entry if user has exceeded creation limits

class BxPage extends MX_Controller {

	public $page_body;
	public $locale;
	public $charset;
	public $vertical_menu_override;
	public $horizontal_menu_override;
	public $ActiveMenuItem;
	public $extra_debug;
	private $extra_headers;
	private $delete_dialog;
	public $form;
	public $i18n;
	public $stylist;
	public $onLoad;
	public $BXLabel;
	public $BXErrors;
	public $ff_extra_headers;
	public $BXErrorDisplayArea;
	public $Overlay;
	public $PrimaryColor;

	// description: constructor
	// param: stylist: a Stylist object that defines the style
	// param: i18n: an I18n object for internationalization
	// param: formAction: the action of the Form object this Page has. Optional

	public function BxPage(&$stylist = array(), &$i18n = array(), $formAction = "") {

		$this->setStylist($stylist);
		$this->setI18n($i18n);
		include_once("uifc/Form.php");
		$this->form = new Form($this, $formAction);
		$this->onLoad = false;
		$this->body_open_tag = "<body>";

		// Set default Wait Overlay:
		$this->Overlay = "			<script>
				$(document).ready(function() {
				  $('#fade_overlay').popup({
					  	blur: false
				  	});
				});
			</script>
			<!-- End: Wait overlay -->
			<!-- Start: Wait overlay -->
			<div id=\"fade_overlay\" class=\"display_none\">
				<img src=\"/.adm/images/wait/loading_bar.gif\" id=\"loading_image\" height=\"75\" width=\"450\">
			</div>\n";
	}

	// Set an Array containing extra header information for a particular page:
	public function setExtraHeaders($val) {
		$this->extra_headers[] = $val;
		$this->extra_headers = array_unique($this->extra_headers);
	}

	// Sometimes the standard <body> opening tag won't do and we might want to add stuff to it:
	public function setExtraBodyTag($val) {
		$this->body_open_tag = $val;
	}

	// Set an Array containing extra information for the delete dialog in the page footer:
	public function setDeleteDialog($key, $val) {
		$this->delete_dialog[$key] = $val;
	}

	// Set an Array containing Label information of FormFields:
	public function setLabel($id, $label, $description) {
		$this->BXLabel[$id] = array($label => $description);
	}

	// Return the Array containing Label information of FormFields:
	public function getLabel($id) {
		if (isset($this->BXLabel[$id])) {			
			return $this->BXLabel[$id];
		}
		else {
			return NULL;
		}
	}

	// Override for setting the active page in the horizontal Menu. This is needed when we view 
	// a page that's not defined in the menu.schema of the respective menu. Or a page which has
	// an URL that ends with [[VAR....]].
	public function setHorizontalMenu($val) {
		$this->horizontal_menu_override = $val;
	}

	// Override for setting the active page in the vertical Menu. This is needed when we view 
	// a page that's not defined in the menu.schema of the respective menu.
	public function setVerticalMenu($val) {
		$this->vertical_menu_override = $val;
	}

	// Override for setting the active child page in the vertical Menu. This is needed when we view 
	// a page that's not defined in the menu.schema of the respective menu. Like /email/secondarymx,
	// which is a subpage reachable only through a button in a tab of /email/emailsettings#tabs-3
	public function setVerticalMenuChild($val) {
		$this->vertical_menu_child_override = $val;
	}

	// There are cases when we want to display page content without our usual theme, but still
	// need some of the logic that BxPage provides. Using setOutOfStyle() allows us to do so.
	public function setOutOfStyle($val) {
		$this->style_override = $val;
	}

	// Pass om extra debug information:
	public function setDebug($val) {
		$this->extra_debug = $val;
	}

	public function &getDefaultStyle(&$stylist) {
		return $stylist->getStyle("Page");
	}

	// description: get the form embedded in the page
	// returns: a Form object
	public function getForm() {
		return $this->form;
	}

	// description: get the I18n object used to internationalize this page
	// returns: an I18n object
	// see: setI18n()
	public function getI18n() {
		return $this->i18n;
	}

	// description: set the I18n object used to internationalize this page
	// param: i18n: an I18n object
	// see: getI18n()
	public function setI18n(&$i18n) {
		$this->i18n =& $i18n;
	}

	// description: set Javascript to be performed when the page loads
	// param: js: a string of Javascript code
	public function setOnLoad($js) {
		$this->onLoad = $js;
		$this->body_open_tag = '<BODY onLoad=\"$this->onLoad\">';
	}

	// description: get the stylist that stylize the page
	// returns: a Stylist object
	// see: setStylist()
	public function getStylist() {
		return $this->stylist;
	}

	// description: set the stylist that stylize the page
	// param: stylist: a Stylist object
	// see: getStylist()
	public function setStylist(&$stylist) {
		$this->stylist =& $stylist;
	}

	// description: get the submit action that submits the form in this page
	// returns: a string
	public function getSubmitAction() {
		$form =& $this->getForm();
		return $form->getSubmitAction();
	}

	// description: get the target of the embedded form to submit to
	// returns: a string
	// see: setSubmitTarget()
	public function getSubmitTarget() {
		$form =& $this->getForm();
		return $form->getTarget();
	}

	// description: set the target of the embedded form to submit to
	// returns: a string
	// see: getSubmitTarget()
	public function setSubmitTarget($target) {
		$this->form->setTarget($target);
	}

	// Set an Array containing the errors:
	public function setErrors($errors) {
		$this->BXErrors = $errors;
	}

	// Return the Array containing our Errors:
	public function getErrors() {
		return $this->BXErrors;
	}

	// Set the Wait Overlay that shows on Saving:
	public function setOverlay($ovl) {
		$this->Overlay = $ovl;
	}

	// Return the Wait Overlay that shows on Saving:
	public function getOverlay() {
		return $this->Overlay;
	}

	// Set primary color of the GUI based on the used Theme:
	public function setPrimaryColor($color) {
		$this->PrimaryColor = $color;
	}

	// Return the Wait Overlay that shows on Saving:
	public function getPrimaryColor() {
	    // Get Theme information from Cookie:
	    if (isset($_COOKIE['theme_switcher_php-style'])) {
	    	$primaryColor = $_COOKIE['theme_switcher_php-style'];
		    if ($primaryColor != "") {
			    if (preg_match('/^theme_(.*)\.css$/', $primaryColor, $treffer)) {
		    		$colorArray = array("blue", "navy", "red", "green", "magenta", "brown");
		    		if (in_array($treffer[1], $colorArray)) {
		    			$this->setPrimaryColor($treffer[1]);
		    		}
		    		else {
		    			$this->setPrimaryColor('blue');
		    		}
			    }
			    if (preg_match('/^switcher\.css$/', $primaryColor)) {
			    	$this->setPrimaryColor('black');
			    }
			}
		}
		else {
			// No cookie for color. Return default color:
			return 'blue';
		}
		return $this->PrimaryColor;
	}

	// We have certain display elements (pagedBlock) which have a built in and pre-defined location for 
	// displaying error messages. However, we're not using these elements everywhere. So if we're not
	// having an element on the page with built in display of error messages, we need to display them 
	// in front of the page body. This function allows us to keep track if an element has such a 
	// built in error message area or not:
	public function HaveErrorMsgDisplayArea($display) {
		$this->BXErrorDisplayArea = $display;
	}

	// Check if we have elements with error message display area:
	public function getErrorMsgDisplayArea() {
		if (isset($this->BXErrorDisplayArea)) {			
			return $this->BXErrorDisplayArea;
		}
		else {
			return FALSE;
		}
	}

	//
	//--- These *are* the droids you're looking for:
	//
	public function render ($page_module = "", $page_body = "") {

		// Start with blank debug info:
		$debug = "";

		$CI =& get_instance();

	    // We load the BlueOnyx helper library first of all, as we heavily depend on it:
	    $CI->load->helper('blueonyx');
	    init_libraries();

	    // Profiling and Benchmarking:
	    bx_profiler(FALSE);

		// Extra Header handling:
		if (!isset($this->extra_headers)) {
			$this->extra_headers = array();
		}
		if (!is_array($this->extra_headers)) {
			$this->extra_headers = array();
		}

		// Delete Dialog handling:
		if (!isset($this->delete_dialog)) {
			$this->delete_dialog = array();
		}
		if (!is_array($this->delete_dialog)) {
			$this->delete_dialog = array();
		}

		//
		//-- 	Carabine Module for CSS minification.
		//		We're not using this at the moment as
		//		It has a few drawbacks.
		//
		//$this->load->library('carabiner');
		//$this->load->library('cssmin');
		//$this->load->library('jsmin');

	    // Find out if CCEd is running. If it is not, we display an error message and quit:
	    $this->cceClient = new CceClient();

	    // Get $sessionId and $loginName from Cookie (if they are set):
	    $sessionId = $CI->input->cookie('sessionId');
	    $loginName = $CI->input->cookie('loginName');

	    // Get the IP address of the user accessing the GUI:
	    $userip = $CI->input->ip_address();

	    // Call 'ServerScriptHelper.php' and check if the login is still valid:
	    // And bloody hell! We can't use the load->helper() function for this one or it blows up:
	    include_once('ServerScriptHelper.php');
	    $serverScriptHelper = new ServerScriptHelper($sessionId, $loginName);
	    $this->cceClient->authkey($loginName, $sessionId);
	    $this->cceClient = $serverScriptHelper->getCceClient();
		$user = $this->cceClient->getObject("User", array("name" => $loginName));
		$access = $serverScriptHelper->getAccessRights($this->cceClient);

		// In our menus we have [[VAR.hostname]] and [[VAR.group]] which need to be 
		// substituted with the correct values. This is done by the subroutine
		// fixInternalURLs() which is called via generateSiteMap(). To do so, we 
		// need to find out which hostname should be used and which group we want to
		// set. The group is determined based on the URL parameter 'group'. The
		// hostname is set to the FQDN of the Vsite that the user belongs to.
		// If the user doesn't belong to a Vsite, we leave it empty:
		$get_form_data = $CI->input->get(NULL, TRUE);
		if (isset($get_form_data['group'])) {
			// We have a group set via URL parameter:
			$vsite = $this->cceClient->getObject("Vsite", array("name" => $get_form_data['group']));
			$hostName = $vsite['fqdn'];
			$group = $get_form_data['group'];
		}
		else {
			// We don't have the URL parameter set via URL. So we just determine the FQDN
			// based on the Vsite the user belongs to and set the group based on the Vsite
			// the user belongs to:
			$vsite = $this->cceClient->getObject("Vsite", array("name" => $user['site']));
			if (isset($vsite['fqdn'])) {
				$hostName = $vsite['fqdn'];
			}
			else {
				// User doesn't belong to a Vsite. So we leave this empty:
				$hostName = "";
			}
			$group = $user['site'];
		}

		// Initialize Capabilities so that we can poll the access rights as well:
		$Capabilities = new Capabilities($this->cceClient, $loginName, $sessionId);
		$this->cceClient->bye();

	    // Find out the browser locale and display a message in our supported languages:
	    $ini_langs = initialize_languages(FALSE);
	    $locale = $ini_langs['locale'];
	    $localization = $ini_langs['localization'];
	    $charset = $ini_langs['charset'];

	    // Now set the locale based on the users localePreference - if specified and known:
	    if ($user['localePreference']) {
	    	$locale = $user['localePreference'];
	    }

	    // Set headers:
	    $CI->output->set_header("Content-Type: text/html; charset=$charset");
	    $CI->output->set_header("Cache-Control: no-store, no-cache, must-revalidate");
	    $CI->output->set_header("Cache-Control: post-check=0, pre-check=0");
	    $CI->output->set_header("Pragma: no-cache"); 
	    $CI->output->set_header("Content-language: $localization");

	    // Set page title:
	    preg_match("/^([^:]+)/", $_SERVER['HTTP_HOST'], $matches);
	    $hostname = $matches[0];
	    // Strip out the :444 or :81 from the hostname - if present:
	    if (preg_match('/:/', $hostname)) {
			$hn_pieces = explode(":", $hostname);
			$hostname = $hn_pieces[0];
	    }
	    $i18n = new I18n("palette", $locale);
	    preg_match("/([^:]+):?.*/", $hostname, $matches);
	    $hostname_new = $matches[1] ? $matches[1] : `/bin/hostname --fqdn`;
	    $page_title = $i18n->getHtml("navigationTitle", "", array("hostName" => $hostname_new, "userName" => $serverScriptHelper->getLoginName()));

	    // Connect to CCE if possible. If not, display that CCE is down:
	    if(!$this->cceClient->connect()) {
	      if($this->locale == "") {
			$CI->load->library('System');
			$system = new System();
			$defaultLocale = $system->getConfig("defaultLocale");
	      }
	      $i18n = new I18n("palette", $defaultLocale);
	      // Display the error message and quit:
	      $cceDown = "<div style=\"text-align: center;\"><br><br><br><br><span style=\"color: #990000;\">"
			  . $i18n->getHtml("cceDown") . "</span></div>";
	      	echo "$cceDown";
	      	error_log("loginHandler.php: $cceDown");
			$this->cceClient->bye();
			$serverScriptHelper->destructor();
	      	exit;
	    }

	    // Get 'System' object
	    $this->cceClient->authkey($loginName, $sessionId);
	    $this->cceClient = $serverScriptHelper->getCceClient();	    
	    $system = $this->cceClient->getObject('System');

	    // Get 'Support' object:
		$Support = $this->cceClient->getObject("System", array(), "Support");

		// Get Active Monitor Alerts:
		$activeMonitorObj = $this->cceClient->getObject("ActiveMonitor");
		$AMnames = $this->cceClient->names($activeMonitorObj["OID"]);

		// See if any monitored item is in bad state
		if ($activeMonitorObj["enabled"] == "0") {
			// If AM is disabled, then we don't show the AM-Status:
			$isAlert = "light";
		}
		else {
			// If AM is enabled, then we start without active error and everything in the blue:
			$colorBGarray = array(
								"black" => "alert_black",
								"blue" => "alert_blue",
								"navy" => "alert_navy",
								"red" => "alert_magenta",
								"green" => "alert_green",
								"magenta" => "alert_magenta",
								"brown" => "alert_brown"
								);
			$isAlert = $colorBGarray[$this->getPrimaryColor()];
			for ($i = 0; $i < count($AMnames); $i++) {
			  $monitoredObj = $this->cceClient->get($activeMonitorObj["OID"], $AMnames[$i]);
			  if (!isset($monitoredObj["hideUI"]) && $monitoredObj["enabled"] && $monitoredObj["monitor"]) {
			  	if ($monitoredObj["currentState"] == "R") {
			  		// We have at least one 'red' item. Stop further checks and show the red alert:
			    	$isAlert = "alert_red";
			    	break;
			    }
			  	if ($monitoredObj["currentState"] == "Y") {
			  		// No red alert? Check deeper if something is giving a yellow warning:
			    	$isAlert = "alert_orange";
			    }
			  }
			}
			// Start: RAID work-around:
			// Yes. This is dirty. Remind me to fix /usr/sausalito/swatch/bin/raid_amdetails.pl, though.
			if (is_file("/proc/mdstat")) {
		      $this->load->helper('raid_helper');
		      list($array_health, $array_fail) = fast_raid_check($this->cceClient, $serverScriptHelper);
		      if ((count($array_fail) > 0) && (count($array_health) == 0)) {
		        $state = "fail";
		        $isAlert = "alert_red";
		      }
		      elseif ((count($array_fail) == 0) && (count($array_health) > 0)) {
		        $state = "syncing";
		        $isAlert = "alert_orange";
		      }
		      elseif ((count($array_fail) == 0) && (count($array_health) == 0)) {
		        $state = "raidOK";
		      }
		      else {
		        // Both are >1:
		        $state = "syncing";
		        $isAlert = "alert_orange";
		      }
		    }
			// End: RAID work-around:
		}

		// If web based setup has not been completed, then redirect to /wizard
	    if ( ! $system['isLicenseAccepted'] ) {
		    $this->cceClient->bye();
			$serverScriptHelper->destructor();
			header("Location: /wizard");
			exit;
	    }

	    // auth failed? Yeah it is redundant. Just to be really sure!
	    if($sessionId == "") {
	      	// Login failed. We need to show the login form again with error message.
		    $this->cceClient->bye();
			$serverScriptHelper->destructor();
	      	header("Location: /");
	      	exit;
	    }
	    else {
			//
			// If we get this far, the auth based on the cookie still works and we're good.
			// Gandalf is not shouting "Thou shallst not pass!", so we proceed:
	    	//

			//---- Start: Menu Display

			// Load the Menu XML files and generate the $_SiteMap_items object:
			$_SiteMap_items = generateSiteMap(FALSE, $access, $this->cceClient, array('group' => $group, 'fqdn' => $hostName));

			//
			//-- Beyond this point we do NOT need cceClient anymore!
			//
			//-- Therefore we disconnect from CCE.
			//
			// I cannot stress how important this is: Say 'bye' and use the deconstructor() whenever
			// you are done talking to CCE. If you don't and the script buggers out, the cced-child
			// process will hang around forever. So we do this religiously here, just to be damn sure:
		    $this->cceClient->bye();
			$serverScriptHelper->destructor();

			// Populate output:
			$profile_text = $i18n->getHtml("[[base-alpine.base-personalProfile]]");

			$profile_link = '/user/personalAccount';
			$settings_text = $i18n->getHtml("[[base-vsite.sitemail]]");
			$settings_link = '#';
			$logout_text = $i18n->get("logout", "palette");
			$logout_link = '/logout/true';
			$parent_root = '';

			if ((in_array("adminUser", $access)) || ((in_array("siteAdmin", $access)) && (count($access) >= "2"))) {
				$parent_root = 'root';
				$ignore_items = array('base_manualButton', 'base_updateLight');
				// Use function MenuChildren to get a sorted list of children for our menu entries:
				$root_children_sort_order = MenuChildren($parent_root, $ignore_items, $_SiteMap_items, $access);

				if ($page_module == "gui") {
					$url = getURLofFirstChild($root_children_sort_order, $ignore_items, $_SiteMap_items, $access);
					header("Location: $url");
					exit;
				}
				else {
					$active_menu_item = $page_module;
				}
			}
			elseif (in_array("siteAdmin", $access)) { 
				if ($page_module == "gui") {
					$parent_root = 'root';
					$ignore_items = array('base_manualButton', 'base_updateLight');
					$url = getURLofFirstChild($parent_root, $ignore_items, $_SiteMap_items, $access);
					// Redirect:
					header("Location: $url");
					exit;
				}
				else {
					$active_menu_item = $page_module;	
				}
				$parent_root = 'root';
				$ignore_items = array('base_manualButton');
				// Use function MenuChildren to get a sorted list of children for our menu entries:
				$root_children_sort_order = MenuChildren($parent_root, $ignore_items, $_SiteMap_items, $access);
			}
			else {
				$active_menu_item = 'base_personalProfile';
				if ($page_module == "gui") {
					$ignore_items = array();
					$url = getURLofFirstChild($active_menu_item, $ignore_items, $_SiteMap_items, $access);
					header("Location: $url");
					exit;
				}
				else {
					$active_menu_item = $page_module;
				}
				$parent_root = $active_menu_item;
				$ignore_items = array();
				// Use function MenuChildren to get a sorted list of children for our menu entries:
				$root_children_sort_order = MenuChildren($active_menu_item, $ignore_items, $_SiteMap_items, $access);
			}

			//
			//- Start: Horizontal Menu Assembly
			//

			// This is a stupid and brain-dead work around:
			// Please recall: Horizontal menus can have only one branch. To get around
			// this limitation we have two "Site Management" menu entries. One is named
			// 'base_sitemanage' and has all the submenu entries you see when editing a
			// Vsite. The other one is named 'base_sitemanageVSL' and has the menu entries
			// "Site Management", "Add Site" and "Template". Of course we only want to show
			// *one* "Site Management" entry in the horizontal menu. So with the code below
			// we remove the unwanted one based on which $page_module is selected. If it's
			// stupid and works ... then maybe it ain't *that* stupid:
			if ($page_module == "base_sitemanageVSL") {
				if (isset($root_children_sort_order['base_sitemanage'])) {
				    unset($root_children_sort_order['base_sitemanage']);
				}
			}
			else {
				if (isset($root_children_sort_order['base_sitemanageVSL'])) {
				    unset($root_children_sort_order['base_sitemanageVSL']);
				}
			}

			// Now loop through $root_children_sort_order and print out the horizontal
			// menu. As it has no sub-elements we can do it in a really simple fashion:
			$nav_html_menu = '';

			$num_of_entry = "1";
			$active_top_menu = "1";
			$active_side_menu = "1";
			$active_menu_item_for_display = "";
			$root_children_sort_order_internal = array();

			// Find out what page we're currently on. For this we match the active URL against the URLs in the menu schemas:
			$currently_active_page = "/" . uri_string();

			foreach ($_SiteMap_items as $key => $value) {
				if ((isset($value['url'])) && ($active_menu_item_for_display == "") && (uri_string() != "gui")) {
					if ($value['url'] == $currently_active_page) {
						$active_menu_item_for_display = $value['id'];
						if (isset($value['parents']['id'])) {
							$active_menu_item_parent_for_display = $value['parents']['id'];
						}
						else {
							$active_menu_item_parent_for_display = $parent_root;
						}
					}
				}
			}

			if (isset($this->vertical_menu_child_override)) {
				$active_menu_item_for_display = $this->vertical_menu_child_override;
			}

			if (!$access == "") {
				foreach ($root_children_sort_order as $MenuItem => $MenuSort) {
					$menutext = $i18n->getHtml($_SiteMap_items[$MenuItem]['label']);
					if (isset($_SiteMap_items[$MenuItem]['url'])) {
					  	$u = $_SiteMap_items[$MenuItem]['url'];
					}
					else {
						$u = getURLofFirstChild($_SiteMap_items[$MenuItem]['id'], array("base_siteName"), $_SiteMap_items, $access);
					}
					// Set active horizontal menu based on what the $page_module says:
					if ($page_module == $_SiteMap_items[$MenuItem]['id']) {
						// Set the active top-menu entry (via $active_top_menu) based on the $page_module that the module's controller passed to us:
					  	$active_top_menu = $num_of_entry;
					  	// Also set the $active_menu_item to the ID of the currently viewed menu root, so that we see the children in the lefthand menu:
					  	//$active_menu_item = $_SiteMap_items[$MenuItem]['id']; // <-- Redundant. We have this now.
					}
					// HTML-Output for Menu entry:
					if ($_SiteMap_items[$MenuItem]['icononly']) {
						// Menu entry is "icononly":
						if ($_SiteMap_items[$MenuItem]['id'] == "base_logout") {
							// For 'base_logout' we also need to fire the dialog for logout confirmation:
							$xtra_class = 'class="dialog_button" data-dialog="dialog_logout" ';
						}
						else {
							// Keep that empty for anything else:
							$xtra_class = '';
						}

						// Wiggle in Active Monitor status:
						$AMtopLight = "";
						if ($_SiteMap_items[$MenuItem]['id'] == "base_monitorLight") {
							// Active Monitor Light for first level Menu entry:
							if ($isAlert != "light") {
								$AMtopLight .= '<span class="display_none"></span><div class="alert badge ' . $isAlert . '">&#9733</div>';
							}
						}

						$nav_html_menu .= "<li><a href='$u' " . $xtra_class . "title=\"" . $menutext . "\n" . $i18n->getHtml($_SiteMap_items[$MenuItem]['description']) . "\"><img width='24' height='24' src='/.adm/images/icons/small/grey/" . $_SiteMap_items[$MenuItem]['icon'] . ".png'/>" . '<IMG BORDER="0" WIDTH="11" HEIGHT="0" SRC="/libImage/spaceHolder.gif">' . $AMtopLight . "</a></li>" . "\n";
					}
					elseif ($_SiteMap_items[$MenuItem]['icon']) {
						// Menu entry has a custom icon:
						$nav_html_menu .= "<li><a href='$u' title=\"" . $i18n->getHtml($_SiteMap_items[$MenuItem]['description']) . "\"><img width='24' height='24' src='/.adm/images/icons/small/grey/" . $_SiteMap_items[$MenuItem]['icon'] . ".png'/><span>$menutext</span></a></li>" . "\n";
					}
					else {
						// Regular menu entry with the stock icon:
						$nav_html_menu .= "<li><a href='$u' title=\"" . $i18n->getHtml($_SiteMap_items[$MenuItem]['description']) . "\"><img width='24' height='24' src='/.adm/images/icons/small/grey/coverflow.png'/><span>$menutext</span></a></li>" . "\n";
					}

					// Bump the number:
					$num_of_entry++;
				}
			}

  			//- End: Horizontal Menu Assembly

			//- Start: Vertical Menu Assembly
			// Use function MenuChildren to get a sorted list of children for our menu entries:
			$root_children_sort_order = MenuChildren($active_menu_item, array(), $_SiteMap_items, $access);

			// Now loop through $root_children_sort_order and print out the horizontal
			// menu. As it has no sub-elements we can do it in a really simple fashion:
			$side_html_menu = '';
			$iteration = '1';
			$active_side_menu_entry = " ";
			$active_nav_inner_entry = " ";

      		foreach ($root_children_sort_order as $MenuItem => $MenuSort) {
				$menutext = $i18n->getHtml($_SiteMap_items[$MenuItem]['label'], "", array("hostname" => $vsite['fqdn']));
				if (isset($_SiteMap_items[$MenuItem]['url'])) {
				  $u = $_SiteMap_items[$MenuItem]['url'];
				}
				else {
				  $u = getURLofFirstChild($_SiteMap_items[$MenuItem]['id'], array(), $_SiteMap_items, $access);
				}

				// Get the second level children of the currently active horizontal menu entry:
				$submenu_entry = MenuChildren($MenuItem, array(), $_SiteMap_items, $access);

				// Check if the current menu item requires children and has none:
				if (($_SiteMap_items[$MenuItem]['requiresChildren'] == "1") && (count($submenu_entry) == "0")) {
					// Being lazy
				}
				else {

					if (!in_array($MenuItem, $ignore_items)) {

						//FIXME: This needs work. Need to poll the active menu entry somehow set it. Prolly via routing table:
						// Defines if this is the active menu entry and removes clickability:
						if ($iteration == "1") { 
							//$my_data_dialog = "class=\"dialog_button\" data-dialog=\"dialog_welcome\""; 
							$my_data_dialog = "class=\"pjax tooltip hover\""; 
						}
						else {
							// Clickable link:
							$my_data_dialog = "class=\"pjax tooltip hover\""; 
						}

						// Define menu classes. For example to show active subentries. Goes in front of the final </a>
						//<div class="alert badge alert_red">5</div>
						//<div class="alert badge alert_grey">2</div>
						//<div class="alert badge alert_black">2</div>

						if ($menutext) {

							// Store order of the menu entry and it's ID in $root_children_sort_order_internal as well:
							$z = $_SiteMap_items[$MenuItem]['id'];
							$root_children_sort_order_internal[$z] = $iteration;

							if ($_SiteMap_items[$MenuItem]['icon']) {
								$horiz_icon = $_SiteMap_items[$MenuItem]['icon'];
							}
							else {
								$horiz_icon = 'speech_bubble';
							}

							// Description may contain variables. Deal with them:
							$description_cleaned = fixInternalURLs($i18n->getWrapped($_SiteMap_items[$MenuItem]['description']), array('group' => $group, 'fqdn' => $hostName));
							if ($_SiteMap_items[$MenuItem]['id'] == 'base_siteSpacer') {
								$side_html_menu .= "<li><a href='javascript: void 0' $my_data_dialog>&nbsp;</a>"; // </li>								
							}
							elseif (!array_key_exists($active_menu_item_for_display, $submenu_entry)) {
								$side_html_menu .= "<li><a href='$u' $my_data_dialog title=\"" . $description_cleaned . "\"><img width='24' height='24' src='/.adm/images/icons/small/grey/" . $horiz_icon . ".png'/>$menutext</a>"; // </li>
							}
							else {
								$side_html_menu .= "<li><a href='$u' class=\"pjax btn btn-modal tooltip hover\" title=\"" . $description_cleaned . "\"><img width='24' height='24' src='/.adm/images/icons/small/grey/" . $horiz_icon . ".png'/>$menutext</a>\n"; // </li>
								// JavaScript sub-menu opener:
								$side_html_menu .= "<script type=\"text/javascript\">";
								$side_html_menu .= "$('.btn-modal').click();"; // <- This works better.
								$side_html_menu .= "</script>";
							}

							// Active Monitor Light for first level Menu entry:
							if ($isAlert != "light") {
								if (($_SiteMap_items[$MenuItem]['id'] == "base_monitor") || ($_SiteMap_items[$MenuItem]['id'] == "base_amStatus")) {
									$side_html_menu .= '<div class="alert badge ' . $isAlert . '">&#9733</div>';
								}
							}

							$iteration++;
							// Menu entry has no children, so close the <li> tag for it:
							if (count($submenu_entry) == "0") { 
							  $side_html_menu .= "</li>\n"; // Final </li> that was opened when the toplevel menu entry was created
							}
						}

						// Get the second level children of the currently active horizontal menu entry:
						$submenu_entry = MenuChildren($MenuItem, array(), $_SiteMap_items, $access);

						// Print the HTML for second level menu entries (if there are any):
						$sme = "1";
						if (!count($submenu_entry) == "0") {
	                        $side_html_menu .= "\n	<ul class='drawer'>\n";
							foreach ($submenu_entry as $MenuItem => $MenuSort) {
								$menutext = $i18n->getHtml($_SiteMap_items[$MenuItem]['label']);
								if (isset($_SiteMap_items[$MenuItem]['url'])) {
									$u = $_SiteMap_items[$MenuItem]['url'];
								}
								else {
									$u = getURLofFirstChild($_SiteMap_items[$MenuItem]['id'], array(), $_SiteMap_items, $access);
								}

								// Wiggle an Active Monitor Icon into the submenu context of the "base_amStatus":
								$alert_icon = "";
								if (($isAlert != "light") && ($isAlert != "alert_blue")) {
									if (($_SiteMap_items[$MenuItem]['id'] == "base_monitor") || ($_SiteMap_items[$MenuItem]['id'] == "base_amStatus")) {
										$alert_icon = "<img src='/.adm/images/icons/small/white/alert.png' width='24' height='24'>";
									}
								}
								if ($_SiteMap_items[$MenuItem]['id'] == $active_menu_item_for_display) {
							  		$active_side_menu_entry = ' data-adminica-side-inner="' . $sme . '"';
							  		$side_html_menu .= "		<li><a href='$u' class='tooltip hover' title=\"" . $i18n->getWrapped($_SiteMap_items[$MenuItem]['description']) . "\"><img width='24' height='24' src='/.adm/images/icons/small/white/pencil.png' width='24' height='24'>$menutext</a></li>\n";
							  	}
							  	else {
							  		$side_html_menu .= "		<li><a href='$u' class='tooltip hover' title=\"" . $i18n->getWrapped($_SiteMap_items[$MenuItem]['description']) . "\">$alert_icon $menutext</a></li>\n";
							  	}
							  	// At this time we do not want to support more than two layers of menus. Deal with it.
							}
							$side_html_menu .= "	</ul>\n";
							$side_html_menu .= "</li>\n"; // Final </li> that was opened when the toplevel menu entry was created
							$sme++;
						}
					} // Ignore check
				} // Children check
     		}

     		// Define which menu entry is active in the leftside menu and set $active_side_menu accordingly:
			$just_the_keys = array_keys($root_children_sort_order);

			if (in_array($active_menu_item_for_display, $just_the_keys)) {
				$active_side_menu = $root_children_sort_order_internal[$active_menu_item_for_display];
			}
			else {
				// If we have an override set via BxPage->setVerticalMenu(), then we use that one instead:
				if (isset($this->vertical_menu_override)) {
					if (in_array($this->vertical_menu_override, $just_the_keys)) {
						$active_side_menu = $root_children_sort_order_internal[$this->vertical_menu_override];
					}					
				}
			}

			if (!$active_menu_item_for_display) {
				$active_menu_item_for_display = $active_menu_item;
			}
			//- Stop: Vertical Menu Assembly

			// Hard coded for the moment - need to fix this later:
			if (isset($_SiteMap_items[$active_menu_item_for_display]['label'])) {
				$active_page_title = $i18n->getHtml($_SiteMap_items[$active_menu_item_for_display]['label']);
			}
			else {
				$active_page_title = "";
			}
			if (isset($_SiteMap_items[$active_menu_item_for_display]['description'])) {
				$active_page_help = $i18n->getHtml($_SiteMap_items[$active_menu_item_for_display]['description']);
			}
			else {
				$active_page_help = "";
			}

			// Check if the visitor is using a browser or a mobile device:
			$mobile = '';
			if ($CI->agent->is_browser()) {
			    $layout = "layout_fixed.css";
			    $agent = $CI->agent->is_browser();
			}
			else {
			    $layout = "layout_fixed.css";
			}
			// Special (nut)case: is_browser() always overrides is_mobile(). See: https://github.com/EllisLab/CodeIgniter/issues/1347
			if ($CI->agent->is_mobile()) {
				$mobile = TRUE;
			    $layout = "layout_fluid.css";
			    $agent = $CI->agent->is_mobile();
			    //$this->extra_headers[] = '<script src="/.adm/scripts/adminica/adminica_mobile-min.js"></script>';
			}

			// More detailed UAD Detection. At the moment only used for debugging purpose:
			if ($CI->agent->is_browser()) {
			    $agent = 'Browser: ' . $CI->agent->browser() . ' ' . $CI->agent->version();
			}
			if ($CI->agent->is_robot()) {
			    $agent = 'Robot: ' . $CI->agent->robot();
			}
			if ($CI->agent->is_mobile()) {
			    $agent = 'Mobile: ' . $CI->agent->mobile() . ' ' . $CI->agent->version();
			}
			if (!isset($agent)) {
			    $agent = 'Unidentified User Agent';
			}
			//$debug .= "<p>Debug: " . $agent . "</p>";

			// Make the users fullName safe for all charsets:
			$user['fullName'] = bx_charsetsafe($user['fullName']);

			// Extra debugging output:
			if (is_array($this->extra_debug)) {
				foreach ($this->extra_debug as $key => $value) {
					$debug .= "<p>" . $key . " - " . $value . "<br></p>";
				}
			}

			// Merge 'extra_headers' and 'ff_extra_headers':
			// Note: ff_extra_headers are generated by uifc/MultiChoice.php
			// where we (for one reason or another) cannot use $BxPage->setExtraHeaders()
			// So we use the more direct method of ...
			//	$this->BxPage->ff_extra_headers[$id] = $extraheader;
			// ... for that. Hence this work around here:
			if (isset($this->ff_extra_headers)) {
				if (is_array($this->ff_extra_headers)) {
					$this->total_extra_headers = array_merge($this->extra_headers, $this->ff_extra_headers);
					$this->extra_headers = $this->total_extra_headers;
				}
			}

			// Wiki Support:
			if ($Support['wiki_enabled'] == '1') {
				if ($Support['wiki_tabbed'] == '1') {
					// Use FancyButton:
					$this->setExtraHeaders('
					            <script>
					              $(document).ready(function() {
					                $(".various").fancybox({
					                  overlayColor: "#000",
					                  fitToView : false,
					                  width   : "80%",
					                  height    : "80%",
					                  autoSize  : false,
					                  fixed   : false,
					                  closeClick  : false,
					                  openEffect  : "none",
					                  closeEffect : "none"
					                });
					              });
					            </script>');

					$wiki = '<a class="various" target="_self" href="http://' . $Support['wiki_baseURL'] . '/userguide/' . uri_string() . '" data-fancybox-type="iframe">' . "\n";
					$wiki .= '<button class="fancybox light small has_text img_icon tooltip hover" title="' . $i18n->getWrapped("[[base-support.wiki_help]]") . '"><img width="24" height="24" src="/.adm/images/icons/small/grey/info_about.png"><span>' . $i18n->getHtml("[[base-support.wiki]]") . '</span></button>' . "\n";
					$wiki .= '</a>' . "\n";
				}
				else {
					// Use Link-Button to open in new tab:
					$wiki = '<a target="_blank" href="http://' . $Support['wiki_baseURL'] . '/userguide/' . uri_string() . '">' . "\n";
					$wiki .= '<button class="light small has_text img_icon tooltip hover" title="' . $i18n->getWrapped("[[base-support.wiki_help]]") . '"><img width="24" height="24" src="/.adm/images/icons/small/grey/info_about.png"><span>' . $i18n->getHtml("[[base-support.wiki]]") . '</span></button>' . "\n";
					$wiki .= '</a>' . "\n";
				}
			}
			else {
				$wiki = '&nbsp;';
			}

			// Assemble header data:
			$data_head = array(
				'charset' => $charset,
				'localization' => $localization,
				'loginName' => $user['name'],
				'sessionId' => $sessionId,
				'page_title' => $page_title,
				'fullName' => $user['fullName'],
				'layout' => $layout,
				'page_title' => $page_title,
				'extra_headers' => implode("\n", $this->extra_headers),
				'body_open_tag' => $this->body_open_tag,
				'profile_text' => $profile_text,
				'profile_link' => $profile_link,
				'settings_text' => $settings_text,
				'settings_link' => $settings_link,
				'logout_text' => $logout_text,
				'logout_link' => $logout_link,
				'nav_html_menu' => $nav_html_menu,
				'side_html_menu' => $side_html_menu,
				'active_top_menu' => $active_top_menu,
				'active_side_menu' => $active_side_menu,
				'active_nav_inner_entry' => $active_nav_inner_entry,
				'active_side_menu_entry' => $active_side_menu_entry,
				'active_page_title' => $active_page_title,
				'active_page_help' => $active_page_help,
				'overlay' => $this->getOverlay(),
				'debug' => $debug
			);
			//---- End: Menu Stuff

			// Merge error messages onto the top of the page body. But only do so if 
			// the rendered elements don't have their own location for showing them:
			if (isset($this->BXErrors)) {
				if (count($this->BXErrors >= "1")) {
					if ($this->getErrorMsgDisplayArea() == FALSE) {
						$page_body = array_merge($this->BXErrors, $page_body);
					}
				}
			}

			// Assemble Body data:
			$data_body = array(
				'loginName' => $loginName,
				'sessionId' => $sessionId,
				'page_body' => implode("", $page_body)
			);

			// Populate output:
			$logoutConfirm = $i18n->getHtml("[[palette.logoutConfirm]]");
			$cancel_text = $i18n->getHtml("[[palette.cancel]]");

			$data_foot = array(
				'loginName' => $loginName,
				'sessionId' => $sessionId,
				'logout_text' => $logout_text,
				'logoutConfirm' => $logoutConfirm,
				'page_title' => $page_title,
				'cancel_text' => $cancel_text,
				'wiki' => $wiki,
				'page_render_part_one' => $i18n->getHtml("[[palette.page_render_part_one]]"),
				'page_render_part_two' => $i18n->getHtml("[[palette.page_render_part_two]]")
			);

			// Publish page:
			if (isset($this->style_override)) {
				// Display using the alternate theme without menu ballast:
				$CI->load->view('neutral_header_view', $data_head);
				$CI->load->view('gui_view', $data_body);
				$CI->load->view('neutral_footer_view', $data_foot);
			}
			else {
				// Display using the usual BlueOnyx theming:
				$CI->load->view('header_view', $data_head);
				$CI->load->view('gui_view', $data_body);
				$CI->load->view('footer_view', $data_foot);
			}
	    }
	}
}

/*
Copyright (c) 2015 Michael Stauber, SOLARSPEED.NET
Copyright (c) 2015 Team BlueOnyx, BLUEONYX.IT
Copyright (c) 2003 Sun Microsystems, Inc. 
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