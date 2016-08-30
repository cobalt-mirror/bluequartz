<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class UserMod extends MX_Controller {

    /**
     * Index Page for this controller.
     *
     * Past the login page this loads the page for /user/userMod.
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

        // -- Actual page logic start:

        // Get URL strings:
        $get_form_data = $CI->input->get(NULL, TRUE);

        //
        //-- Validate GET data:
        //

        if (isset($get_form_data['group'])) {
            // We have a group set:
            $group = $get_form_data['group'];
        }
        if (isset($get_form_data['name'])) {
            // We have a name set:
            $name = $get_form_data['name'];
        }
        if ((!isset($group)) || (!isset($name))) {
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
        //-- Prepare data:
        //

        // Get data for the Vsite:
        $vsite = $cceClient->getObject('Vsite', array('name' => $group));

        // Get User:
        $User = $cceClient->getObject("User", array("name" => $name, 'site' => $group));

        // Do we have a $User?
        if (!isset($User)) {
            // Nice people say goodbye, or CCEd waits forever:
            $cceClient->bye();
            $serverScriptHelper->destructor();
            Log403Error("/gui/Forbidden403#3");
        }

        // Get User Disk info:
        $UserDisk = $cceClient->getObject("User", array("name" => $name, 'site' => $group), "Disk");

        // OID of the $user:
        $useroid = $User['OID'];

        // User Email Object:
        $userEmail = $cceClient->getObject("User", array("name" => $name, 'site' => $group), "Email");

        // Find out if FTP access for non-siteAdmins is enabled or disabled for this site:
        list($ftpvsite) = $cceClient->find("Vsite", array("name" => $group));
        $ftpPermsObj = $cceClient->get($ftpvsite, 'FTPNONADMIN');
        $ftpnonadmin = $ftpPermsObj['enabled'];

        //
        //--- Handle form validation:
        //

        // We start without any active errors:
        $errors = array();
        $extra_headers =array();
        $ci_errors = array();
        $my_errors = array();

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
        // Setting up error messages:
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

            // modify user
            $settings = array("fullName" => $attributes['fullNameField']);

            $settings["description"] = $attributes['userDescField'];

            # don't set this attribute now if a siteadmin is trying to demote himself
            if (isset($attributes['siteAdministrator']) && ($attributes['siteAdministrator'] || (!$attributes['siteAdministrator'] && ($loginName != $attributes['userName'])))) {
                $settings["capLevels"] = ($attributes['siteAdministrator'] ? '&siteAdmin&' : '');
            }

            if (isset($attributes['dnsAdministrator']) && ($attributes['dnsAdministrator'] || (!$attributes['dnsAdministrator'] && ($loginName != $attributes['userName'])))) {
                $settings["capLevels"] .= ($attributes['dnsAdministrator'] ? '&siteDNS&' : '');
            }

            // dirty trick
            $settings["capLevels"] = str_replace("&&", "&", $settings["capLevels"]);

            if (isset($attributes['suspendUser'])) {
                $settings['ui_enabled'] = ($attributes['suspendUser']) ? '0' : '1';
            }

            // Handle FTP access clauses:
            if (!isset($ftpnonadmin)) {
                $ftpnonadmin = "0";
            }
            if ($ftpnonadmin == "0") {
                $settings['ftpDisabled'] = "1";
            }
            else {
                $settings['ftpDisabled'] = "0";
            }

            if ($attributes['siteAdministrator'] == "1") {
                $settings['ftpDisabled'] = "0";
            }

            $settings['emailDisabled'] = $attributes['emailDisabled'];

            // Password change?
            if (($attributes['passwordField'] == "") && ($attributes['_passwordField_repeat'] == "")) {
                // No password change:
                $settings["password"] = "";
            }
            else {
                // Password change requested. Check strength and take the new password:
                if (bx_pw_check($i18n, $attributes['passwordField'], $attributes['_passwordField_repeat']) != "") {
                    $my_errors[] = bx_pw_check($i18n, $attributes['passwordField'], $attributes['_passwordField_repeat']);
                }
                $settings['password'] = $attributes['passwordField'];
            }

            // Username = Password? Baaaad idea!
            if (strcasecmp($attributes['userName'], $attributes['passwordField']) == 0) {
                $settings["password"] = "1";
                $error_msg = "[[base-user.error-password-equals-username]] [[base-user.error-invalid-password]]";
                $my_errors[] = new Error($error_msg);
            }
        }

        //
        //--- At this point all checks are done. If we have no errors, we can submit the data to CODB:
        //

        // Join the various error messages:
        $errors = array_merge($ci_errors, $my_errors);

        // If we have no errors and have POST data, we submit to CODB:
        if ((count($errors) == "0") && ($CI->input->post(NULL, TRUE))) {

            // Modify the User:
            $big_ok = $cceClient->set($useroid, "", $settings);
            $errors = array_merge($errors, $cceClient->errors());

            // Get the OID of this transaction:
            if ($big_ok) {

                // Set quota:
                if (!isset($attributes['maxDiskSpaceField'])) {
                    // Somehow no quota was set. Assume unlimited:
                    $quota = "-1";
                }
                else {
                    // Quota is set. Check if it has a unit or not:
                    $pattern = '/^(\d*\.{0,1}\d+)(K|M|G|T)$/';
                    if (preg_match($pattern, $attributes['maxDiskSpaceField'], $matches, PREG_OFFSET_CAPTURE)) {
                        // Quota has a unit:
                        $quota = (unsimplify_number($attributes['maxDiskSpaceField'], "K")/1000);
                    }
                    else {
                        // Quota has no unit:
                        $quota = $attributes['maxDiskSpaceField'];
                    }
                }

                $cceClient->set($useroid, "Disk", array("quota" => $quota));
                $errors = array_merge($errors, $cceClient->errors());

                //
                //-- Handle AutoFeatures for UserServices:
                //
                list($userservices) = $cceClient->find("UserServices", array("site" => $group));
                $autoFeatures = new AutoFeatures($serverScriptHelper, $attributes);
                $af_errors = $autoFeatures->handle("modify.User", array("CCE_SERVICES_OID" => $userservices, "CCE_OID" => $useroid), $attributes);
                $errors = array_merge($errors, $af_errors);

                //
                //-- Handle AutoFeatures for UserExtraServices:
                //
                list($userservices) = $cceClient->findx("UserExtraServices");
                $autoFeatures = new AutoFeatures($serverScriptHelper, $form_data);
                $af_errors = $autoFeatures->handle("User.Email", array("CCE_SERVICES_OID" => $userservices, "CCE_OID" => $useroid, 'i18n' => $i18n), $form_data);
                $errors = array_merge($errors, $af_errors);

                //
                //-- Set email aliases info
                //

                //Prune the duplicate email aliases
                $emailAliasesFieldArray = $cceClient->scalar_to_array($attributes['emailAliasesField']);
                $emailAliasesFieldArray = array_unique($emailAliasesFieldArray);
                $emailAliasesField = $cceClient->array_to_scalar($emailAliasesFieldArray);

                // replace && with &, to avoid always getting a blank alias in the field
                // in cce, this also skirts around dealing with browser issues
                $emailAliasesField = str_replace("&&", "&", $emailAliasesField);
                if ($emailAliasesField == '&') {
                  $emailAliasesField = '';
                }
                $settings = array("aliases" => $emailAliasesField);

                $cceClient->set($useroid, "Email", $settings);
                $errors = array_merge($errors, $cceClient->errors());
                // At this point we're done. We may have errors, though.
            }

            // No errors during submit? Reload page:
            if (count($errors) == "0") {
                $cceClient->bye();
                $serverScriptHelper->destructor();
                $redirect_URL = "/user/userList?group=$group";
                header("location: $redirect_URL");
                exit;
            }
        }

        //
        //-- Generate page:
        //

        // Prepare Page:
        $factory = $serverScriptHelper->getHtmlComponentFactory("base-user", "/user/userMod?group=$group&name=$name");
        $BxPage = $factory->getPage();
        $BxPage->setErrors($errors);
        $i18n = $factory->getI18n();

        // Set Menu items:
        $BxPage->setVerticalMenu('base_siteadmin');
        $BxPage->setVerticalMenuChild('base_userList');
        $page_module = 'base_sitemanage';

        // Set extra headers for fullcalendar and datepicker:
        $BxPage->setExtraHeaders('<script src="/gui/fullcalendar"></script>');
        $BxPage->setExtraHeaders('<script src="/gui/datepicker"></script>');

        // Find out which modules are active and use their names as Tab headers:
        $autoFeatures = new AutoFeatures($serverScriptHelper);
        $defaultPage = "account";
        $TABs = array_merge(array($defaultPage), array_values($autoFeatures->ListFeatures("User.Email")));
        $block =& $factory->getPagedBlock("modifyUser", $TABs);
        $block->setLabel($factory->getLabel('modifyUser', false, array('userName' => $User['name'])));

        $block->setToggle("#");
        $block->setSideTabs(FALSE);
        $block->setShowAllTabs("#");
        $block->setDefaultPage($defaultPage);

        // Add hidden field for Group:
        $block->addFormField(
                $factory->getTextField("group", $group, ''),
                $factory->getLabel("group"), 
                $defaultPage
        );

        // Full name:
        $block->addFormField(
            $factory->getFullName("fullNameField", $User["fullName"]),
            $factory->getLabel("fullNameField"),
            $defaultPage
        );

        // # Username - start
        $userNameField = $factory->getTextField("userName", $User["name"], 'r');
        $block->addFormField( 
            $userNameField, 
            $factory->getLabel("userNameField"),
            $defaultPage
        ); 
        // # Username - end

        // Password:
        $pw_change = $factory->getPassword("passwordField", "");
        $pw_change->setOptional(TRUE);
        $block->addFormField(
            $pw_change,
            $factory->getLabel("passwordField"),
            $defaultPage
        );

        // Load site quota
        list($vsite_oid) = $cceClient->find('Vsite', array("name" => $group));
        $disk = $cceClient->get($vsite_oid, 'Disk');
        $vsite_quota = $disk['quota']*1000*1000;
        $default_quota = $UserDisk['quota']*1000*1000;

        $site_quota = $factory->getInteger('maxDiskSpaceField', simplify_number($default_quota, "K", "0"), 1, $vsite_quota, 'rw'); 
        $site_quota->showBounds('dezi');
        $site_quota->setType('memdisk');
        $block->addFormField(
                $site_quota,
                $factory->getLabel('maxDiskSpaceField'),
                $defaultPage
                );

        //
        //--- Email related stuff:
        //

        $block->addFormField(
          $factory->getBoolean("emailDisabled", $User["emailDisabled"]),
          $factory->getLabel("emailDisabled"),
          "EmailSettings"
        );

        // Suspend / Unsuspend:
        $block->addFormField(
          $factory->getBoolean("suspendUser", !$User["ui_enabled"]),
          $factory->getLabel("suspendUser"),
          $defaultPage
        );

        $emailAliases = $factory->getEmailAliasList("emailAliasesField", $userEmail["aliases"]);
        $emailAliases->setOptional(true);
        $block->addFormField(
            $emailAliases,
            $factory->getLabel("emailAliasesField"),
            "EmailSettings"
        );

        $textblock = $factory->getTextBlock("userDescField", Utf8Encode($User["description"]));
        $textblock->setWidth(2*$textblock->getWidth());
        $textblock->setOptional(true);
        $block->addFormField(
            $textblock,
            $factory->getLabel("userDescField"),
            $defaultPage
        );

        //
        //--- Add other features
        //

        $autoFeatures = new AutoFeatures($serverScriptHelper);

        if (isset($group) && $group != "") {
            list($userServices) = $cceClient->find("UserServices", array("site" => $group));
            list($vsite) = $cceClient->find("Vsite", array("name" => $group));
            $autoFeatures->display($block, "modify.User", array("CCE_SERVICES_OID" => $userServices, 'CCE_OID' => $useroid, "VSITE_OID" => $vsite));

            $block->addFormField(
                $factory->getBoolean("siteAdministrator", $Capabilities->getAllowed('siteAdmin', $useroid)),
                $factory->getLabel("siteAdministratorField"),
                $defaultPage
            );
            $block->addFormField(
                $factory->getBoolean("dnsAdministrator", $Capabilities->getAllowed('siteDNS', $useroid)),
                $factory->getLabel("dnsAdministratorField"),
                $defaultPage
            );
         
        }
        else {
            list($userServices) = $cceClient->find("UserServices");
            $autoFeatures->display($block, "modify.User", array("CCE_SERVICES_OID" => $userServices));
        }

        //
        //---- Start: Email Forwarding and Vacation Message
        //

        $autoFeatures = new AutoFeatures($serverScriptHelper, $attributes);
        $cce_info = array('CCE_OID' => $useroid, 'FIELD_ACCESS' => 'rw');
        list($cce_info['CCE_SERVICES_OID']) = $cceClient->find('UserExtraServices');
        $autoFeatures->display($block, 'User.Email', $cce_info);

        //
        //---- End: Email Forwarding and Vacation Message
        //

        // Add the buttons for those who can edit this page:
        $block->addButton($factory->getSaveButton($BxPage->getSubmitAction()));
        $block->addButton($factory->getCancelButton("/user/userList?group=$group"));

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