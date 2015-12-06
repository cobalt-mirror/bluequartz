<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class LetsencryptCert extends MX_Controller {

    /**
     * Index Page for this controller.
     *
     * Past the login page this loads the page for /ssl/letsencryptCert.
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
        $i18n = new I18n("base-ssl", $user['localePreference']);
        $system = $cceClient->getObject("System");

        // Initialize Capabilities so that we can poll the access rights as well:
        $Capabilities = new Capabilities($cceClient, $loginName, $sessionId);

        // -- Actual page logic start:

        //
        //--- Get CODB-Object of interest: 
        //

        // We get our $get_form_data early, as this page handles both Vsite and AdmServ SSL certs.
        // Depending on what we modify, we have the "group" information on the URL string - or not.

        $get_form_data = $CI->input->get(NULL, TRUE);
        if ($get_form_data['group'] != '') {

            // Extra check to make sure a siteAdmin isn't messing with the URL param for "group"
            // and then tries to get access to another Vsites certs:
            if (!$Capabilities->getAllowed('manageSite')) {
                if (($Capabilities->getAllowed('siteAdmin')) && ($get_form_data['group'] != $Capabilities->loginUser['site'])) {
                    // Nice people say goodbye, or CCEd waits forever:
                    $cceClient->bye();
                    $serverScriptHelper->destructor();
                    Log403Error("/gui/Forbidden403#ohcomeon");
                }
            }

            $CODBDATA =& $cceClient->getObject('Vsite', array('name' => $get_form_data['group']), 'SSL');
            $CODBDATA['group'] = $get_form_data['group'];
        }
        else {
            $CODBDATA =& $cceClient->getObject('System', array(), 'SSL');
            $CODBDATA['group'] = "";
        }

        $group = $CODBDATA['group'];
        $form_url = '/ssl/letsencryptCert';
        $return_url = '/ssl/siteSSL';
        if ($group != "") {
            $form_url .= '?group=' . $group;
            $return_url .= '?group=' . $group;
        }

        // Only 'serverSSL', 'manageSite' and 'siteAdmin' should be here
        if (!$Capabilities->getAllowed('serverSSL') && !$Capabilities->getAllowed('manageSite') && 
            !($Capabilities->getAllowed('siteAdmin') && $CODBDATA['group'] == $user['site'])) {
            // Nice people say goodbye, or CCEd waits forever:
            $cceClient->bye();
            $serverScriptHelper->destructor();
            Log403Error("/gui/Forbidden403");
        }

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
        $ignore_attributes = array("BlueOnyx_Info_Text", "_");
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

        }

        //
        //--- At this point all checks are done. If we have no errors, we can submit the data to CODB:
        //

        // Join the various error messages:
        $errors = array_merge($ci_errors, $my_errors);

        // If we have no errors and have POST data, we submit to CODB:
        if ((count($errors) == "0") && ($CI->input->post(NULL, TRUE))) {

            // We have no errors. We submit to CODB.
            if ($attributes['save']) {
                // actually save the information

                // use the same ui for admin server and vhosts, so assume System
                // if $attributes['group'] is empty
                if ($attributes['group'] != '') {
                    list($vsite) = $cceClient->find('Vsite', array('name' => $attributes['group']));
                }
                else {
                    list($vsite) = $cceClient->find('System');
                }

                // Always push these out to CODB:
                $settings = array(
                            'LEemail' => strtolower($attributes['LEemail']),
                            'autoRenew' => $attributes['autoRenew'],
                            'autoRenewDays' => $attributes['autoRenewDays'],
                            );

                // Only set these during install transaction:
                if ($attributes['LErequestCert'] == "1") {
                    $settings['uses_letsencrypt'] = '1';
                    $settings['performLEinstall'] = time();
                }

                $ok = $cceClient->set($vsite, 'SSL', $settings);
                if ($ok) {
                    // Redirect the web browser
                    if ($attributes['type'] == 'csr') {
                        //$url = "/ssl/siteSSL?group=" . $attributes['group'] . "&export=csr";
                        $url = "/ssl/siteSSL?group=" . $attributes['group'] . "&action=export&type=csr";
                    }
                    else {
                        $url = '/ssl/siteSSL?group=' . $attributes['group'];
                    }
                    $cceClient->bye();
                    $serverScriptHelper->destructor();
                    header("location: $url");
                    exit;           
                }
            }

            // CCE errors that might have happened during submit to CODB:
            $CCEerrors = $cceClient->errors();
            foreach ($CCEerrors as $object => $objData) {
                // When we fetch the CCE errors it tells us which field it bitched on. And gives us an error message, which we can return:
                $errors[] = ErrorMessage($i18n->get($objData->message, true, array('key' => $objData->key)) . '<br>&nbsp;');
            }

            // No errors. Reload the entire page to load it with the updated values:
            if ((count($errors) == "0")) {
                header("Location: $form_url");
                exit;
            }
            else {
                if ($group != "") {
                    $CODBDATA =& $cceClient->getObject('Vsite', array('name' => $group), 'SSL');
                    $CODBDATA['group'] = $get_form_data['group'];
                }
                else {
                    $CODBDATA =& $cceClient->getObject('System', array(), 'SSL');
                    $CODBDATA['group'] = "";
                }
            }
        }

        //
        //-- Own page logic:
        //

        //
        //-- Generate page:
        //

        // Prepare Page:
        $factory = $serverScriptHelper->getHtmlComponentFactory("base-ssl", $form_url);
        $BxPage = $factory->getPage();
        $BxPage->setErrors($errors);
        $i18n = $factory->getI18n();

        $product = new Product($cceClient);

        // Set Menu items:
        if ($CODBDATA['group'] != "") {
            // We are in "Site Management" / "SSL":
            $BxPage->setVerticalMenu('base_sitemanage');
            $BxPage->setVerticalMenuChild('base_ssl');
            $page_module = 'base_sitemanage';
        }
        else {
            // We are in "Security" / "SSL"
            $BxPage->setVerticalMenu('base_security');
            $BxPage->setVerticalMenuChild('base_admin_ssl');
            $page_module = 'base_sysmanage';
        }

        //
        // -- Add PagedBlock with Cert Info:
        //

        $header = 'sslCertInfo';
        if (isset($get_form_data['type'])) {
            $header = 'requestInformation';
        }

        if ($CODBDATA['group']) {
            list($vsite) = $cceClient->find("Vsite", array("name" => $CODBDATA['group']));
            $vsiteObj = $cceClient->get($vsite);
            $fqdn = $vsiteObj['fqdn'];
        }
        else {
            $fqdn = '[[base-ssl.serverDesktop]]';
        }

        $defaultPage = "basic";
        $block =& $factory->getPagedBlock("sslCertInfo", array($defaultPage));
        $block->setCurrentLabel($factory->getLabel($header, false, array('fqdn' => $fqdn)));

        $block->setToggle("#");
        $block->setSideTabs(FALSE);
        $block->setShowAllTabs("#");
        $block->setDefaultPage($defaultPage);

        //
        //--- Tab: basic
        //

        if (isset($get_form_data['type'])) {
            $type = $get_form_data['type'];
            if ($get_form_data['type'] == 'csr') {
                $block->addFormField(
                    $factory->getBoolean('genCert', 0),
                    $factory->getLabel('genSSCert'),
                    $defaultPage);
            }
        }
        else {
            $type = '';
        }

        // Add divider:
        $block->addFormField(
                $factory->addBXDivider("DIVIDER_INTRO", ""),
                $factory->getLabel("DIVIDER_INTRO", false),
                $defaultPage
                );

        $my_TEXT = "<div class='flat_area grid_16'><br>" . $i18n->getClean("[[base-ssl.LetsEncrypt_info_text]]") . "</div>";
        $infotext = $factory->getHtmlField("LetsEncrypt_info_text", $my_TEXT, 'r');
        $infotext->setLabelType("nolabel");
        $block->addFormField(
          $infotext,
          $factory->getLabel(" ", false),
          $defaultPage
        );

        // Add divider:
        $block->addFormField(
                $factory->addBXDivider("DIVIDER_OPTIONS", ""),
                $factory->getLabel("DIVIDER_OPTIONS", false),
                $defaultPage
                );

        // Email:
        $email_field =& $factory->getEmailAddress('LEemail', $CODBDATA['LEemail']);
        $email_field->setOptional(false);
        $block->addFormField(
            $email_field,
            $factory->getLabel('email'),
            $defaultPage
            );

        // Perform SSL Cert request:
        if (($CODBDATA['uses_letsencrypt'] == "0") || ($CODBDATA['enabled'] == "0")) {
            $request_one = "1";
        }
        else {
            $request_one = "0";
        }
        $LErequestCert =& $factory->getBoolean('LErequestCert', $request_one, 'rw');
        $block->addFormField(
            $LErequestCert,
            $factory->getLabel('LErequestCert'),
            $defaultPage
            );

        //
        //--- Auto-Renew:
        //

        $AutorRenew_Field =& $factory->getMultiChoice('autoRenew');
        $autoRenew =& $factory->getOption('autoRenew', $CODBDATA['autoRenew'], 'rw');
        $autoRenew->setLabel($factory->getLabel('autoRenew', false));
        $AutorRenew_Field->addOption($autoRenew);

        // autoRenewDays:
        $autoRenewDays = $factory->getInteger("autoRenewDays", $CODBDATA["autoRenewDays"], "30", "90", 'rw');
        $autoRenewDays->setOptional(FALSE);
        $autoRenewDays->setWidth(4);
        $autoRenewDays->showBounds(1);
        $autoRenew->addFormField($autoRenewDays, $factory->getLabel('autoRenewDays'));

        // Out with the Element:
        $block->addFormField($AutorRenew_Field, $factory->getLabel('autoRenew'), $defaultPage);

        // Add some hidden fields that we need later:
        $fftype =& $factory->getTextField('type', $type, '');
        $block->addFormField(
            $fftype,
            $factory->getLabel('type'),
            $defaultPage
        );
        $ffsave =& $factory->getTextField('save', '1', '');
        $block->addFormField(
            $ffsave,
            $factory->getLabel('save'),
            $defaultPage
        );
        $ffgroup =& $factory->getTextField('group', $CODBDATA['group'], '');
        $block->addFormField(
            $ffgroup,
            $factory->getLabel('group'),
            $defaultPage
        );

        //
        //--- Add the Save/Cancel buttons (not for AdmServ-Cert, though)
        //
        $block->addButton($factory->getSaveButton($BxPage->getSubmitAction()));
        $block->addButton($factory->getCancelButton($return_url));

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