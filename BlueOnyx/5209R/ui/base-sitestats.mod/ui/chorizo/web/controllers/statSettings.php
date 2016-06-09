<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class StatSettings extends MX_Controller {

    /**
     * Index Page for this controller.
     *
     * Past the login page this loads the page for /sitestats/statSettings.
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

        // -- Actual page logic start:

        // Get URL strings:
        $get_form_data = $CI->input->get(NULL, TRUE);

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

        // Only menuServerServerStats, manageSite and siteAdmin should be here:
        if (!$Capabilities->getAllowed('menuServerServerStats') &&
            !$Capabilities->getAllowed('manageSite') &&
            !($Capabilities->getAllowed('siteAdmin') &&
              $group == $Capabilities->loginUser['site'])) {
            // Nice people say goodbye, or CCEd waits forever:
            $cceClient->bye();
            $serverScriptHelper->destructor();
            Log403Error("/gui/Forbidden403#2");
        }

        //
        //-- Prepare data:
        //

        $purgeMap = array(
                'never' =>      0,
                'month' =>      32,
                '2month' =>     62,
                '3month' =>     93,
                '6month' =>     181,
                'year' =>       366,
                '2year' =>      731,
                '3year' =>      1096,
                '4year' =>      1462,
                '5year' =>      1802,
                );

        $detailMap = array(
            'sitestatsConsolidateDaily' =>      0,
            'sitestatsConsolidateMonthly' =>    1,
            );

        // Session is read-only for non-server administrators
        if($Capabilities->getAllowed('adminUser')) {
            $sitestats_access = 'rw';
        }
        else {
            $sitestats_access = 'r';
        }

        // Get data for the Vsite:
        $sitestats =& $cceClient->getObject('Vsite', array('name' => $group), 'SiteStats');
        list($vsite) = $cceClient->find('Vsite', array('name' => $group));
        $vsiteObj = $cceClient->get($vsite);

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

            // Assemble SET data:
            if (!isset($attributes['Sitestats_enabled'])) {
                $Sitestats_enabled = "0";
            }
            else {
                $Sitestats_enabled = $attributes['Sitestats_enabled'];
            }
            if (!isset($attributes['Sitestats_consolidate'])) {
                $Sitestats_consolidate = "0";
            }
            else {
                $Sitestats_consolidate = $attributes['Sitestats_consolidate'];
            }
            if (!isset($attributes['Sitestats_purge'])) {
                $Sitestats_consolidate = "never";
            }
            else {
                $Sitestats_purge = $attributes['Sitestats_purge'];
            }

            $settings = array();
            $settings["enabled"] = $Sitestats_enabled;
            $settings["consolidate"] = $detailMap[$Sitestats_consolidate];
            $settings["purge"] = $purgeMap[$Sitestats_purge];

            // Actual submit to CODB:
            list($vsite) = $cceClient->find('Vsite', array('name' => $group));
            $cceClient->set($vsite, 'SiteStats', $settings);

            // CCE errors that might have happened during submit to CODB:
            $CCEerrors = $cceClient->errors();
            foreach ($CCEerrors as $object => $objData) {
                // When we fetch the CCE errors it tells us which field it bitched on. And gives us an error message, which we can return:
                $errors[] = ErrorMessage($i18n->get($objData->message, true, array('key' => $objData->key)) . '<br>&nbsp;');
            }
            // No errors during submit? Reload page:
            if (count($errors) == "0") {
                $cceClient->bye();
                $serverScriptHelper->destructor();
                $redirect_URL = "/sitestats/statSettings?group=$group";
                header("location: $redirect_URL");
                exit;
            }
        }

        //
        //-- Generate page:
        //

        // Prepare Page:
        $factory = $serverScriptHelper->getHtmlComponentFactory("base-sitestats", "/sitestats/statSettings?group=$group");
        $BxPage = $factory->getPage();
        $BxPage->setErrors($errors);
        $i18n = $factory->getI18n();

        // Set Menu items:
        $BxPage->setVerticalMenu('base_siteusage');
        $BxPage->setVerticalMenuChild('base_vsite_sitestats');
        $page_module = 'base_sitemanage';

        $defaultPage = "pageID";
        $block =& $factory->getPagedBlock("sitestatsSettings", array($defaultPage));
        $block->setLabel($factory->getLabel('sitestatsSettings', false, array('fqdn' => $vsiteObj['fqdn'])));

        $block->setToggle("#");
        $block->setSideTabs(FALSE);
        $block->setDefaultPage($defaultPage);

        // Construct all the form fields needed, note that only simple
        // form fields are allowd.  no composite form fields
        $statsEnable = $factory->getBoolean('Sitestats_enabled', $sitestats['enabled'], $sitestats_access);

        // Simple array setup:
        $detailLabels = array_keys($detailMap);
        $detailDays = array_values($detailMap);
        $detailrevMap = array_flip($detailMap);

        $statsConsolidate = $factory->getMultiChoice('Sitestats_consolidate', $detailLabels, array($detailrevMap[$sitestats['consolidate']]), $sitestats_access);
        $statsConsolidate->setSelected($detailrevMap[$sitestats['consolidate']], true);

        // Yet again:
        $purgeLabels = array_keys($purgeMap);
        $purgeDays = array_values($purgeMap);
        $revMap = array_flip($purgeMap);

        // Some cleanup logic. It can be that $sitestats['purge'] is not
        // set or set to something not matching our $revMap. In that case
        // we need to set a default:
        if (!array_key_exists($sitestats['purge'], $revMap)) {
            $sitestats['purge'] = '366';
        }

        $purgeSelect = $factory->getMultiChoice('Sitestats_purge', $purgeLabels, array($revMap[$sitestats['purge']]), $sitestats_access);
        $purgeSelect->setSelected($revMap[$sitestats['purge']], true);

        $block->addFormField($statsEnable, $factory->getLabel("sitestatsEnable"), $defaultPage);
        $block->addFormField($statsConsolidate, $factory->getLabel("sitestatsConsolidate"), $defaultPage);
        $block->addFormField($purgeSelect, $factory->getLabel("sitestatsPurge"), $defaultPage);

        $block->addFormField($factory->getTextField('save', 1, ''));

        // Add the buttons
        $block->addButton($factory->getSaveButton($BxPage->getSubmitAction()));
        $block->addButton($factory->getCancelButton("/sitestats/statSettings?group=$group"));

        // Nice people say goodbye, or CCEd waits forever:
        $cceClient->bye();
        $serverScriptHelper->destructor();

        $page_body[] = $block->toHtml();

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