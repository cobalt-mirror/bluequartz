<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Settings extends MX_Controller {

    /**
     * Index Page for this controller.
     *
     * Past the login page this loads the page for /swupdate/settings.
     *
     */

    public function index() {

        $CI =& get_instance();

        // We load the BlueOnyx helper library first of all, as we heavily depend on it:
        $this->load->helper('blueonyx');
        init_libraries();

        // Need to load 'BxPage' for page rendering:
        $this->load->library('BxPage');

        // Get $CI->BX_SESSION['sessionId'] and $CI->BX_SESSION['loginName'] from Cookie (if they are set):
        $CI->BX_SESSION['sessionId'] = $CI->input->cookie('sessionId');
        $CI->BX_SESSION['loginName'] = $CI->input->cookie('loginName');

        // Line up the ducks for CCE-Connection:
        include_once('ServerScriptHelper.php');
        $CI->serverScriptHelper = new ServerScriptHelper($CI->BX_SESSION['sessionId'], $CI->BX_SESSION['loginName']);
        $CI->cceClient = $CI->serverScriptHelper->getCceClient();
        $user = $CI->BX_SESSION['loginUser'];
        $i18n = new I18n("base-swupdate", $CI->BX_SESSION['loginUser']['localePreference']);
        $system = $CI->getSystem();

        // Initialize Capabilities so that we can poll the access rights as well:
        $Capabilities = new Capabilities($CI->cceClient, $CI->BX_SESSION['loginName'], $CI->BX_SESSION['sessionId']);

        // -- Actual page logic start:

        // Not 'managePackage'? Bye, bye!
        if (!$Capabilities->getAllowed('managePackage')) {
            // Nice people say goodbye, or CCEd waits forever:
            $CI->cceClient->bye();
            $CI->serverScriptHelper->destructor();
            Log403Error("/gui/Forbidden403");
        }

        //
        //--- Get CODB-Object of interest: 
        //

        // Get settings
        $swUpdate = $CI->cceClient->get($system['OID'], "SWUpdate");

        // We use the first server object as the default for properties like proxies
        // because they have the same value anyway. These properties should actually be
        // in System.SWUpdate
        $oids = $CI->cceClient->findNSorted("SWUpdateServer", "orderPreference");
        $servers = array();

        for($i = 0; $i < count($oids); $i++) {
            $servers[] = $CI->cceClient->get($oids[$i]);
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

            //$newScheduleMap = array("never" => "Never", "hourly" => "Hourly", "daily" => "Daily", "weekly" => "Weekly", "monthly" => "Monthly");
            $newScheduleMap = array("hourly" => "Hourly", "daily" => "Daily", "weekly" => "Weekly", "monthly" => "Monthly");
            if ($attributes['updateInterval'] == 'never') {
                $attributes['updateInterval'] = 'monthly';
            }
            $attributes['updateInterval'] = $newScheduleMap[$attributes['updateInterval']];

            $notificationLightField = $attributes['notificationLightField'];
            unset($attributes['notificationLightField']);

        }

        //
        //--- At this point all checks are done. If we have no errors, we can submit the data to CODB:
        //

        // Join the various error messages:
        $errors = array_merge($ci_errors, $my_errors);

        // If we have no errors and have POST data, we submit to CODB:
        if ((count($errors) == "0") && ($CI->input->post(NULL, TRUE))) {

            // We have no errors. We submit to CODB after making sure we have the minimum of data:
            $AutoUpdateList = array();
            $BasePKG = $CI->cceClient->getObject("Package", array("name" => 'base', 'vendor' => 'Compass', 'installState' => 'Installed'));
            $webappPKG = $CI->cceClient->getObject("Package", array("name" => 'webapp', 'vendor' => 'Compass', 'installState' => 'Installed'));
            if (isset($attributes['AutoUpdateList'])) {
                $AutoUpdateList = $CI->cceClient->scalar_to_array($attributes['AutoUpdateList']);
                if ($BasePKG) {
                    if (!in_array('base', $AutoUpdateList)) {
                        // Base PKG installed but not selected. Set it to autoupdate:
                        $AutoUpdateList[] = 'base';
                    }
                }
                if ($webappPKG) {
                    if (!in_array('webapp', $AutoUpdateList)) {
                        // WebApp PKG installed but not selected. Set it to autoupdate:
                        $AutoUpdateList[] = 'webapp';
                    }
                }
                $attributes['AutoUpdateList'] = $CI->cceClient->array_to_scalar($AutoUpdateList);
            }
            else {
                // AutoUpdatesList was empty. We populate it with the required minimums:
                if ($BasePKG) {
                    // Base PKG installed. Set it to autoupdate:
                    $AutoUpdateList[] = 'base';
                }
                if ($webappPKG) {
                    // WebApp PKG installed. Set it to autoupdate:
                    $AutoUpdateList[] = 'webapp';
                }
                $attributes['AutoUpdateList'] = $CI->cceClient->array_to_scalar($AutoUpdateList);
            }

            // Actual submit to CODB:
            $CI->cceClient->set($system['OID'], "SWUpdate",  $attributes);

            // CCE errors that might have happened during submit to CODB:
            $CCEerrors = $CI->cceClient->errors();
            foreach ($CCEerrors as $object => $objData) {
                // When we fetch the CCE errors it tells us which field it bitched on. And gives us an error message, which we can return:
                $errors[] = ErrorMessage($i18n->get($objData->message, true, array('key' => $objData->key)) . '<br>&nbsp;');
            }

            // Remove all the existing servers first
            $CI->cceClient->destroyObjects("SWUpdateServer");

            // Add back all the specified ones
            $notifyModeMap = array("all" => "AllNew", "updates" => "UpdatesOnly");
            $servers = stringToArray($attributes['servers']);
            if (!count($servers)) { $servers = array(""); }
            for($i = 0; $i < count($servers); $i++) {
              $CI->cceClient->create("SWUpdateServer", array("location" => $servers[$i],
                "notificationMode" => $notifyModeMap[$notificationLightField], "orderPreference" => $i+1));
              $errors = array_merge($errors, $CI->cceClient->errors());
            }

            // No errors. Reload the entire page to load it with the updated values:
            if ((count($errors) == "0")) {
                header("Location: /swupdate/settings");
                exit;
            }
            else {
                $swUpdate = $attributes;
            }
        }

        //
        //-- Own page logic:
        //

        //
        //-- Generate page:
        //

        // Prepare Page:
        $factory = $CI->serverScriptHelper->getHtmlComponentFactory("base-swupdate", "/swupdate/settings");
        $BxPage = $factory->getPage();
        $BxPage->setErrors($errors);
        $i18n = $factory->getI18n();

        $product = new Product($CI->cceClient);

        // Set Menu items:
        $BxPage->setVerticalMenu('base_software');
        $BxPage->setVerticalMenuChild('base_softwareSettings');
        $page_module = 'base_software';

        $defaultPage = "basic";

        $block =& $factory->getPagedBlock("softwareInstallSettings", array($defaultPage, 'advanced'));

        $block->setToggle("#");
        $block->setSideTabs(FALSE);
        $block->setShowAllTabs("#");
        $block->setDefaultPage($defaultPage);

        //
        //--- Basic:
        //

        $scheduleMap = array("Hourly" => "hourly", "Daily" => "daily", "Weekly" => "weekly", "Monthly" => "monthly");
        $scheduleField = $factory->getMultiChoice("updateInterval", array("hourly", "daily", "weekly", "monthly"), array($scheduleMap));
        $scheduleField->setSelected($scheduleMap[$swUpdate['updateInterval']], true);
        $block->addFormField(
          $scheduleField,
          $factory->getLabel("scheduleField"),
          "basic"
        );

        $notifyMap = array("AllNew" => "all", "UpdatesOnly" => "updates");
        $notificationLightField = $factory->getMultiChoice("notificationLightField", array("all", "updates"), array($notifyMap));
        $notificationLightField->setSelected($notifyMap[$servers[0]["notificationMode"]], true);
        $block->addFormField(
          $notificationLightField,
          $factory->getLabel("notificationLightField"),
          "basic"
        );

        // Use ActiveMonitor's email contact list if possible
        $am_obj = $CI->cceClient->getObject('ActiveMonitor', array(), '');
        if( ! $am_obj["alertEmailList"] ) {
          $email = $factory->getEmailAddressList("emailField", $swUpdate["updateEmailNotification"]);
          $email->setOptional(true);
          $block->addFormField(
            $email,
            $factory->getLabel("emailField"),
            "basic"
          );
        }

        //$block->addFormField(
        //  $factory->getBoolean("AutoUpdate", $swUpdate["AutoUpdate"], 'rw'),
        //  $factory->getLabel("AutoUpdate"),
        //  'basic'
        //);

        //
        //--- Selector for PHPs with AutoUpdate enabled:
        //

        $allowed_labels = array();
        $possible_labels = array();
        $allowed_caps = array();
        $possible_caps = array();

        $BasePKG = $CI->cceClient->getObject("Package", array("name" => 'base', 'vendor' => 'Compass', 'installState' => 'Installed'));
        if ($BasePKG) {
            $allowed_labels[] = $i18n->get($BasePKG['nameTag']);
            $allowed_caps[] = $BasePKG['name'];
        }

        $AutoUpdatePKGs = $CI->cceClient->scalar_to_array($swUpdate["AutoUpdateList"]);
        foreach ($AutoUpdatePKGs as $key => $AU_PKG_Name) {
            $PKG = $CI->cceClient->getObject("Package", array("name" => $AU_PKG_Name));
            $allowed_labels[] = $i18n->get($PKG['nameTag']);
            $allowed_caps[] = $AU_PKG_Name;
        }

        $search = array('installState' => 'Installed', 'isVisible' => '1');
        $oids = $CI->cceClient->findNSorted("Package", 'version', $search);
        foreach ($oids as $key => $OID) {
            $PKG = $CI->cceClient->get($OID);
            if (($PKG['vendor'] != "BlueOnyx") && ($PKG['vendor'] != "Project_BlueOnyx")) {
                $possible_labels[] = $i18n->get($PKG['nameTag']);
                $possible_caps[] = $PKG['name'];
            }
        }

        $select_caps =& $factory->getSetSelector('AutoUpdateList',
                                $CI->cceClient->array_to_scalar($allowed_labels), 
                                $CI->cceClient->array_to_scalar($possible_labels),
                                'allowedAbilities', 'disallowedAbilities',
                                'rw', 
                                $CI->cceClient->array_to_scalar($allowed_caps),
                                $CI->cceClient->array_to_scalar($possible_caps)
                            );

        $select_caps->setOptional(true);

        $block->addFormField($select_caps, 
                    $factory->getLabel('AutoUpdateList'),
                    'basic'
                    );

        //
        //--- Advanced
        //

        $locations = array();
        for($i = 0; $i < count($servers); $i++) {
            $locations[] = $servers[$i]["location"];
        }
        $updateServer = $factory->getUrlList("servers", arrayToString($locations));
        $updateServer->setOptional(true);
        $block->addFormField(
          $updateServer,
          $factory->getLabel("serverField"),
          "advanced"
        );

        $httpProxy = $factory->getUrl("httpProxy", $swUpdate["httpProxy"], "", "", "rw");
        $httpProxy->setOptional(true);
        $httpProxy->setType("");
        $block->addFormField(
          $httpProxy,
          $factory->getLabel("httpProxyField"),
          "advanced"
        );

        $ftpProxy = $factory->getUrl("ftpProxy", $swUpdate["ftpProxy"]);
        $ftpProxy->setOptional(true);
        $ftpProxy->setType("");
        $block->addFormField(
          $ftpProxy,
          $factory->getLabel("ftpProxyField"),
          "advanced"
        );

        /*
        $typeMap = array("All" => "all", "Updates" => "updates");
        $block->addFormField(
          $factory->getMultiChoice("checkSetField", array("all", "updates"), array($typeMap[$swUpdate["updateType"]])),
          $factory->getLabel("checkSetField")
        );
        */

        /*
        $block->addFormField(
          $factory->getBoolean("autoField", $servers[0]["autoUpdate"]),
          $factory->getLabel("autoField"),
          "advanced"
        );
        */

        $block->addFormField(
          $factory->getBoolean("requireSignature", $swUpdate["requireSignature"]),
          $factory->getLabel("requireSignatureField"),
          "advanced"
        );


        //
        //--- Add the buttons
        //

        $block->addButton($factory->getSaveButton($BxPage->getSubmitAction()));
        $block->addButton($factory->getCancelButton("/swupdate/settings"));

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