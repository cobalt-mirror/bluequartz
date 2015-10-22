<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Autoinstall extends MX_Controller {

    /**
     * Index Page for this controller.
     *
     * Past the login page this loads the page for /swupdate/autoinstall.
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
        //--- Load autolib.php, which is part of Compass-base to check the linked username:
        //

        // Get URL params:
        $get_form_data = $CI->input->get(NULL, TRUE);

        if (file_exists('/usr/sausalito/ui/chorizo/ci/application/modules/Compass/base/controllers/autolib.php')) {
            include_once("/usr/sausalito/ui/chorizo/ci/application/modules/Compass/base/controllers/autolib.php");
            $SerialNumber = $system["serialNumber"];
            $shopEmail = get_nl_username($SerialNumber);
        }
        else {
            $shopEmail = '';
            if (isset($get_form_data['em'])) {
                $shopEmail = urldecode($get_form_data['em']);
            }
        }

        // Set 1Y cookie for the shopEmail:
        setcookie("shopemail", $shopEmail, time()+60*10*24*365, "/");

        // ShopEmail doesn't match. Redirect to the right URL:
        if (isset($get_form_data['em'])) {
            if ($get_form_data['em'] != $shopEmail) {
                // Install the NewLinQ PKG and come back here once that's done:
                header('Location: /swupdate/autoinstall?em=' . urlencode($shopEmail));
            }
        }

        //
        //--- Get CODB-Object of interest: 
        //

        // Get settings
        $swUpdate = $cceClient->getObject("System", array(), "SWUpdate");

        //
        //--- Check if the NewLinQ PKG is installed. If not, install it:
        //
        $BasePKG = $cceClient->getObject("Package", array("name" => 'base', 'vendor' => 'Compass', 'installState' => 'Installed'));
        if (!$BasePKG) {
            // NewLinQ PKG not installed! We refresh the list of available updates first:
            $ret = $serverScriptHelper->shell("/usr/sausalito/sbin/grab_updates.pl -u", $result, 'root', $sessionId);

            // Now find out what OID NewLinQ has:
            $BasePKG = $cceClient->getObject("Package", array("name" => 'base', 'vendor' => 'Compass', 'installState' => 'Available'));

            // Set 30 minute 'ai' cookie:
            setcookie("ai", '1', time()+60*30, "/");

            // If we now have an OID, we do a little round-about to install the PKG
            if (isset($BasePKG['OID'])) {
                $backUrl = '/swupdate/autoinstall?ai=true';
                if (isset($get_form_data['em'])) {
                    $backUrl .= '&em=' . urlencode($shopEmail);
                }

                // Nice people say goodbye, or CCEd waits forever:
                $cceClient->bye();
                $serverScriptHelper->destructor();

                // Install the NewLinQ PKG and come back here once that's done:
                header('Location: /swupdate/download?backUrl=' . $backUrl . '&packageOID=' . $BasePKG['OID']);
            }
        }
        else {
            // Delete old 'ai' cookie if present:
            delete_cookie("ai");

            // Set new 30 minute 'ai' cookie:
            setcookie("ai", '2', time()+60*30, "/");
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

        }

        //
        //--- At this point all checks are done. If we have no errors, we can submit the data to CODB:
        //

        // Join the various error messages:
        $errors = array_merge($ci_errors, $my_errors);

        // If we have no errors and have POST data, we submit to CODB:
        if ((count($errors) == "0") && ($CI->input->post(NULL, TRUE))) {

            if ((isset($attributes['ShopEmail'])) && (isset($attributes['ShopPass']))) {

                // Nice people say goodbye, or CCEd waits forever:
                $cceClient->bye();
                $serverScriptHelper->destructor();

                // Next step: Link the packages:
                header('Location: /base/link');
            }
        }

        //
        //-- Own page logic:
        //

        //
        //-- Generate page:
        //

        // Prepare Page:
        $factory = $serverScriptHelper->getHtmlComponentFactory("base-swupdate", "/base/link");
        $BxPage = $factory->getPage();
        $BxPage->setErrors($errors);
        $i18n = $factory->getI18n();

        $product = new Product($cceClient);

        // Set Menu items:
        $BxPage->setVerticalMenu('base_software');
        $BxPage->setVerticalMenuChild('base_autoinstall');
        $page_module = 'base_software';

        $defaultPage = "basic";

        $block =& $factory->getPagedBlock("AutoInstallPKGheader", array($defaultPage));

        $block->setToggle("#");
        $block->setSideTabs(FALSE);
        $block->setDefaultPage($defaultPage);

        //
        //--- Basic:
        //

        // Shop-Email:
        $shopemailField = $factory->getTextField("ShopEmail", $shopEmail, 'rw');
        $shopemailField->setOptional (FALSE);
        $shopemailField->setType ('email');
        $block->addFormField(
          $shopemailField,
          $factory->getLabel("ShopEmail"),
          "basic"
        );

        // Shop-Password
        $shopPassField = $factory->getPassword("ShopPass", "", FALSE, 'rw');
        $shopPassField->setOptional(FALSE);
        $shopPassField->setConfirm(FALSE);
        $shopPassField->setCheckPass(FALSE);
        $block->addFormField(
            $shopPassField,
            $factory->getLabel("ShopPass"),
            "basic");

        //
        //--- Add the buttons
        //

        $block->addButton($factory->getSaveButton($BxPage->getSubmitAction()));

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