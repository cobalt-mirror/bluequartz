<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class NewSoftware extends MX_Controller {

    /**
     * Index Page for this controller.
     *
     * Past the login page this loads the page for /swupdate/newSoftware.
     *
     */

    public function index() {

        $CI =& get_instance();

        // We load the BlueOnyx helper library first of all, as we heavily depend on it:
        $this->load->helper('blueonyx');
        // This page also needs the helpers/updateLib_helper.php:
        $this->load->helper('updatelib');
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
        $i18n = new I18n("base-swupdate", $user['localePreference']);
        $system = $cceClient->getObject("System");

        // Initialize Capabilities so that we can poll the access rights as well:
        $Capabilities = new Capabilities($cceClient, $loginName, $sessionId);

        // -- Actual page logic start:

        // Not 'managePackage'? Bye, bye!
        if (!$Capabilities->getAllowed('managePackage')) {
            // Nice people say goodbye, or CCEd waits forever:
            $cceClient->bye();
            $serverScriptHelper->destructor();
            Log403Error("/gui/Forbidden403");
        }

        //
        //--- Check if we got here via Auto-Install:
        //

        $ai_check = $CI->input->cookie('ai');
        $shopemail = $CI->input->cookie('shopemail');

        // Be really sure to delete the cookie:
        delete_cookie("ai");

        if ($ai_check == "1") {
            $targetLocation = '/swupdate/autoinstall';
            if ($shopemail != "") {
                $targetLocation .= '?em=' . urlencode($shopemail);
            }

            // Nice people say goodbye, or CCEd waits forever:
            $cceClient->bye();
            $serverScriptHelper->destructor();

            // Redirect to continue Auto-Install of shop purchases:
            header('Location: ' . $targetLocation);
        }

        //
        //--- Get CODB-Object of interest: 
        //

        $CODBDATA = $cceClient->getObject("System", array(), "yum");

        //
        //--- Handle form validation:
        //

        // We start without any active errors:
        $errors = array();
        $extra_headers =array();
        $ci_errors = array();
        $my_errors = array();

        // Check for new PKGs on NewLinQ:
        $refresh = '300';
        $nl_check = $CI->input->cookie('nl_check');
        if (($nl_check == "") || ($nl_check <= time()-$refresh)) {
            $new_msg = array();
            $i = $serverScriptHelper->shell("/usr/sausalito/sbin/grab_updates.pl -u", $ret, 'root', $sessionId);
            setcookie("nl_check", time(), "0", "/");
        }

        $search = array('installState' => 'Available', 'new' => '1', 'isVisible' => '1');
        $oids = $cceClient->findNSorted("Package", 'version', $search);
        if (count($oids) > "0") {
            $msg = '[[base-swupdate.NewUpdatesSubject]]';
            $color = 'alert_green';
            $new_msg[] = '<div class="alert dismissible ' . $color . '"><img width="40" height="36" src="/.adm/images/icons/small/white/alarm_bell.png"><strong>' . $i18n->interpolateHtml($msg) . '<br><br><br></strong></div>';
            $my_errors = array_merge($new_msg, $errors);          
        }
        else {
            $color = 'alert_green';
            $msg = '[[base-swupdate.NoPackagesBody]]';
            $new_msg[] = '<div class="alert dismissible ' . $color . '"><img width="40" height="36" src="/.adm/images/icons/small/white/alarm_bell.png"><strong>' . $i18n->interpolateHtml($msg) . '<br><br><br></strong></div>';
            $my_errors = array_merge($new_msg, $errors);          
        }

        // Shove submitted input into $form_data after passing it through the XSS filter:
        $form_data = $CI->input->post(NULL, TRUE);

        // Form fields that are required to have input:
        $required_keys = array();
        // Set up rules for form validation. These validations happen before we submit to CCE and further checks based on the schemas are done:

        // Empty array for key => values we want to submit to CCE:
        $attributes = array();
        // Items we do NOT want to submit to CCE:
        $ignore_attributes = array("BlueOnyx_Info_Text");
        if (is_array($form_data)) {
            // Function GetFormAttributes() walks through the $form_data and returns us the $parameters we want to
            // submit to CCE. It intelligently handles checkboxes, which only have "on" set when they are ticked.
            // In that case it pulls the unticked status from the hidden checkboxes and addes them to $parameters.
            // It also transformes the value of the ticked checkboxes from "on" to "1". 
            //
            // Additionally it generates the form_validation rules for CodeIgniter.
            //
            // params: $i18n                i18n Object of the error messages
            // params: $form_data           array with form_data array from CI
            // params: $required_keys       array with keys that must have data in it. Needed for CodeIgniter's error checks
            // params: $ignore_attributes   array with items we want to ignore. Such as Labels.
            // return:                      array with keys and values ready to submit to CCE.
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
            if (isset($form_data['_serialized_errors'])) {
                $my_errors = unserialize($form_data['_serialized_errors']);
            }
        }

        $get_form_data = $CI->input->get(NULL, TRUE);

        // Get the return message from the URL string - if present:
        if (isset($get_form_data['msg'])) {
            $redir_msg[] = '<div class="alert dismissible alert_green"><img width="40" height="36" src="/.adm/images/icons/small/white/alarm_bell.png"><strong>' . $i18n->interpolateHtml(urldecode($get_form_data['msg'])) . '<br><br><br></strong></div>';
            $my_errors = array_merge($redir_msg, $errors);          
        }

        //
        //--- At this point all checks are done. If we have no errors, we can submit the data to CODB:
        //

        // Join the various error messages:
        $errors = array_merge($ci_errors, $my_errors);

        //
        //-- Own page logic:
        //

        //
        //-- Generate page:
        //

        // Prepare Page:
        $factory = $serverScriptHelper->getHtmlComponentFactory("base-swupdate", "/swupdate/newSoftware");
        $BxPage = $factory->getPage();
        $BxPage->setErrors($errors);
        $i18n = $factory->getI18n();

        $product = new Product($cceClient);

        // Set Menu items:
        $BxPage->setVerticalMenu('base_software');
        $BxPage->setVerticalMenuChild('base_softwareNew');
        $page_module = 'base_software';

        $defaultPage = "yumTitle";

        $block =& $factory->getPagedBlock("availableListNew", array($defaultPage));

        $block->setToggle("#");
        $block->setSideTabs(FALSE);
        $block->setShowAllTabs("#");
        $block->setDefaultPage($defaultPage);

        // Add ButtonContainer and button to manually check for updates:
        $yumCheck[] =  $factory->getRawHTML("checkNow", '<button title="' . $i18n->getWrapped("checkNow_help") . '" class="close_dialog tooltip right link_button waiter" data-link="/swupdate/checkHandler?backUrl=/swupdate/newSoftware" target="_self"><img src="/.adm/images/icons/small/white/refresh_3.png"><span>' . $i18n->getHtml("checkNow") . '</span></button>');
        $manualInstallButton = $factory->getButton("/swupdate/manualInstall?backUrl=/swupdate/newSoftware", "manualInstall", "DEMO-OVERRIDE");
        $manualInstallButton->setWaiter(FALSE);
        $yumCheck[] = $manualInstallButton;

        $buttonContainer = $factory->getButtonContainer("", $yumCheck);
        $block->addFormField(
            $buttonContainer,
            $factory->getLabel("yumCheck"),
            $defaultPage
        );

        //
        //--- Available YUM updates:
        //

        // Set up ScrollList:
        $ScrollList = $factory->getScrollList("availableListNew", array("typeField", "nameField", "versionField", "vendorField", "descriptionField", "installField"), array()); 
        $ScrollList->setAlignments(array("center", "left", "left", "left", "left", "center"));
        $ScrollList->setDefaultSortedIndex('0');
        $ScrollList->setSortOrder('ascending');
        $ScrollList->setSortDisabled(array());
        $ScrollList->setPaginateDisabled(FALSE);
        $ScrollList->setSearchDisabled(FALSE);
        $ScrollList->setSelectorDisabled(FALSE);
        $ScrollList->enableAutoWidth(FALSE);
        $ScrollList->setInfoDisabled(FALSE);
        $ScrollList->setDisplay(25);
        $ScrollList->setColumnWidths(array("75", "115", "150", "180", "183", "35")); // Max: 739px

        // Do we have any updates or complete PKGs to install?
        $search = array('installState' => 'Available', 'isVisible' => 1);
        $oids = $cceClient->findNSorted("Package", 'version', $search);

        // Find all installed PKGs:
        $i_search = array('installState' => 'Installed');
        $i_oids = $cceClient->findNSorted("Package", 'version', $i_search);
        $installed_package = array();
        foreach ($i_oids as $key => $i_pkg_oid) {
            $inst_pkg = $cceClient->get($i_pkg_oid);
            // Build an array with all already installed PKGs and their versions:
            $installed_package[$inst_pkg['name']] = $inst_pkg['version'];
        }

        for($i = 0; $i < count($oids); $i++) {
          $package = $cceClient->get($oids[$i]);
          $oid = &$oids[$i];
          $new = $package["new"] ? "new" : "old";
          $packageName = $package["nameTag"] ? $i18n->interpolate($package["nameTag"]) : $package["name"];
          $version = $package["versionTag"] ? $i18n->interpolate($package["versionTag"]) : substr($package["version"], 1);
          $vendorName = $package["vendorTag"] ? $i18n->interpolate($package["vendorTag"]) : $package["vendor"];
          $packageType = $package["packageType"];
          $description = $i18n->interpolate($package["shortDesc"]);
          $url = $package["url"];
          $options = updates_geturloptions($cceClient, $package["urloptions"]);

          if (preg_match("/^file:/", $package["location"])) {
            $removeButton = $factory->getRemoveButton("/swupdate/removeHandler?backUrl=/swupdate/newSoftware&packageOID=$oid");
            $removeButton->setImageOnly(TRUE);
          }
          $detailButton = $factory->getDetailButton("/swupdate/download?backUrl=/swupdate/newSoftware&packageOID=$oid", "installField");
          $detailButton->setImageOnly(TRUE);

          $composite = isset($removeButton) ? array($detailButton, $removeButton) : array($detailButton);

          // Is this a new complete package? Or a new update?
          if (($new == "new") && ($packageType == "complete") && (!isset($installed_package[$package['name']]))) {
            $status = '<button class="tiny text_only has_text tooltip hover" title="' . $i18n->getWrapped("[[base-swupdate.BXnewpkg_help]]") . '">' . $i18n->getHtml("[[base-swupdate.BXnewpkg]]") . '</button>';
          }
          elseif (($new == "new") && ($packageType == "update")) {
            $status = '<button class="tiny text_only has_text tooltip hover" title="' . $i18n->getWrapped("[[base-swupdate.BXnewupdate_help]]") . '">' . $i18n->getHtml("[[base-swupdate.BXnewupdate]]") . '</button>';
          }
          elseif (($new == "new") && ($packageType == "complete") && (isset($installed_package[$package['name']]))) {
            $status = '<button class="tiny text_only has_text tooltip hover" title="' . $i18n->getWrapped("[[base-swupdate.BXnewupdate_help]]") . '">' . $i18n->getHtml("[[base-swupdate.BXnewupdate]]") . '</button>';
          }
          elseif (($new == "old") && ($packageType == "complete")) {
            $status = '<button class="tiny text_only has_text tooltip hover" title="' . $i18n->getWrapped("[[base-swupdate.BXpkg_help]]") . '">' . $i18n->getHtml("[[base-swupdate.BXpkg]]") . '</button>';
          }
          else {
            $status = '<button class="tiny text_only has_text tooltip hover" title="' . $i18n->getWrapped("[[base-swupdate.BXupdate_help]]") . '">' . $i18n->getHtml("[[base-swupdate.BXupdate]]") . '</button>';
          }

          $ScrollList->addEntry(array(
            $status,
            $packageName,
            $version,
            $vendorName,
            $description,
            $factory->getCompositeFormField($composite)
          ));
        }

        // Show the ScrollList for the Updates:
        $block->addFormField(
            $factory->getRawHTML("availableListNew", $ScrollList->toHtml()),
            $factory->getLabel("availableListNew"),
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
/*
Copyright (c) 2016 Michael Stauber, SOLARSPEED.NET
Copyright (c) 2016 Team BlueOnyx, BLUEONYX.IT
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