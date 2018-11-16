<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Logconfig extends MX_Controller {

    /**
     * Index Page for this controller.
     *
     * Past the login page this loads the page for /sitestats/logconfig.
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
        $i18n = new I18n("base-sitestats", $CI->BX_SESSION['loginUser']['localePreference']);
        $system = $CI->getSystem();

        // Initialize Capabilities so that we can poll the access rights as well:
        $Capabilities = new Capabilities($CI->cceClient, $CI->BX_SESSION['loginName'], $CI->BX_SESSION['sessionId']);

        // -- Actual page logic start:

        // Not 'serverNetwork'? Bye, bye!
        if (!$Capabilities->getAllowed('serverNetwork')) {
            // Nice people say goodbye, or CCEd waits forever:
            $CI->cceClient->bye();
            $CI->serverScriptHelper->destructor();
            Log403Error("/gui/Forbidden403");
        }

        //
        //--- Get CODB-Object of interest: 
        //

        $CODBDATA = $CI->cceClient->get($system['OID'], "Sitestats");
        $AVSPAM = $CI->cceClient->get($system['OID'], "AVSPAM_Settings");

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
                '2year' =>      732,
                '3year' =>      1096,
                '4year' =>      1462,
                '5year' =>      1827,
                );

        $rotateMap = array(
                '1' =>          1,
                '2' =>          2,
                '3' =>          3,
                '4' =>          4,
                '5' =>          5,
                '6' =>          6,
                '7' =>          7,
                '8' =>          8,
                '9' =>          9,
                '10' =>         10,
                '11' =>         11,
                '12' =>         12,
                '13' =>         13,
                '14' =>         14,
                '15' =>         15,
                '16' =>         16,
                '17' =>         17,
                '18' =>         18,
                '19' =>         19,
                '20' =>         20,
                '21' =>         21,
                '22' =>         22,
                '23' =>         23,
                '24' =>         24,
                '25' =>         25,
                '26' =>         26,
                '27' =>         27,
                '28' =>         28,
                '29' =>         29,
                '30' =>         30,
                '60' =>         60,
                '90' =>         90
                );

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

        //
        //--- At this point all checks are done. If we have no errors, we can submit the data to CODB:
        //

        // Join the various error messages:
        $errors = array_merge($ci_errors, $my_errors);

        // If we have no errors and have POST data, we submit to CODB:
        if ((count($errors) == "0") && ($CI->input->post(NULL, TRUE))) {

            $settings = array();
            $Logrotate_purge = $attributes['GlobalLogRotate'];
            $settings['rotate'] = $rotateMap[$Logrotate_purge];

            if (!isset($settings['rotate'])) {
                $settings['rotate'] = '14';
            }
            if ($settings['rotate'] == '0') {
                $settings['rotate'] = '14';
            }

            $Sitestats_purge = $attributes['GlobalSitestatsPurge'];
            $settings['purge'] = $purgeMap[$Sitestats_purge];

            $internal = '0';
            if ($attributes['internal'] == "1") {
                $internal = time();
            }
            $webalizer = '0';
            if ($attributes['webalizer'] == "1") {
                $webalizer = time();
            }
            $awstats = '0';
            if (isset($attributes['awstats'])) {
                if ($attributes['awstats'] == "1") {
                    $awstats = time();
                }
            }
            $avspam = '0';
            if (isset($attributes['avspam'])) {
                if ($attributes['avspam'] == "1") {
                    $avspam = '1';
                }
            }
            $sendmailanalyzer = '0';
            if ($attributes['sendmailanalyzer'] == "1") {
                $sendmailanalyzer = time();
            }

            // Actual submit to CODB:
            $CI->cceClient->setObject('System', array(
                                                        'rotate' => $settings['rotate'], 
                                                        'purge' => $settings['purge'], 
                                                        'internal' => $internal, 
                                                        'webalizer' => $webalizer, 
                                                        'awstats' => $awstats, 
                                                        'SA_anonymize' => $attributes['SA_anonymize'], 
                                                        'sendmailanalyzer' => $sendmailanalyzer, 
                                                        'avspam' => $avspam), 
                                                        'Sitestats');

            // CCE errors that might have happened during submit to CODB:
            $CCEerrors = $CI->cceClient->errors();
            foreach ($CCEerrors as $object => $objData) {
                // When we fetch the CCE errors it tells us which field it bitched on. And gives us an error message, which we can return:
                $errors[] = ErrorMessage($i18n->get($objData->message, true, array('key' => $objData->key)) . '<br>&nbsp;');
            }

            // Update AV-SPAM as well - if it's present:
            if (isset($AVSPAM['avspam_expiry'])) {
                $CI->cceClient->setObject('System', array('avspam_expiry' => $attributes['avspam']), 'AVSPAM_Settings');
                $CCEerrors = $CI->cceClient->errors();
                foreach ($CCEerrors as $object => $objData) {
                    // When we fetch the CCE errors it tells us which field it bitched on. And gives us an error message, which we can return:
                    $errors[] = ErrorMessage($i18n->get($objData->message, true, array('key' => $objData->key)) . '<br>&nbsp;');
                }
            }

            // No errors. Reload the entire page to load it with the updated values:
            if ((count($errors) == "0")) {
                header("Location: /sitestats/logconfig");
                exit;
            }
            else {
                $CODBDATA = $CI->cceClient->get($system['OID'], "Sitestats");
            }
        }

        //
        //-- Own page logic:
        //

        //
        //-- Generate page:
        //

        // Prepare Page:
        $factory = $CI->serverScriptHelper->getHtmlComponentFactory("base-sitestats", "/sitestats/logconfig");
        $BxPage = $factory->getPage();
        $BxPage->setErrors($errors);
        $i18n = $factory->getI18n();

        $product = new Product($CI->cceClient);

        // Set Menu items:
        $BxPage->setVerticalMenu('base_serverconfig');
        $BxPage->setVerticalMenuChild('logconfig');
        $page_module = 'base_sysmanage';

        $defaultPage = "basic";

        $block =& $factory->getPagedBlock("logconfig", array($defaultPage));

        $block->setToggle("#");
        $block->setSideTabs(FALSE);
        $block->setDefaultPage($defaultPage);

        //
        //--- TAB: basic
        //

        // Add Divider:
        $block->addFormField(
                $factory->addBXDivider("DIV_USAGE_INFORMATION", ""),
                $factory->getLabel("DIV_USAGE_INFORMATION", false),
                $defaultPage
                );

        // Yet again:
        $rotateLabels = array_keys($rotateMap);
        $rotateDays = array_values($rotateMap);
        $revrotateMap = array_flip($rotateMap);

        // Some cleanup logic. It can be that $CODBDATA['purge'] is not
        // set or set to something not matching our $revMap. In that case
        // we need to set a default:
        if (!array_key_exists($CODBDATA['rotate'], $revrotateMap)) {
            $CODBDATA['rotate'] = '14';
        }

        $rotateSelect = $factory->getMultiChoice('GlobalLogRotate', $rotateLabels, array($revrotateMap[$CODBDATA['rotate']]), 'rw');
        $rotateSelect->setSelected($revrotateMap[$CODBDATA['rotate']], true);

        $block->addFormField($rotateSelect, $factory->getLabel("GlobalLogRotate"), $defaultPage);
        $block->addFormField($factory->getTextField('save', 1, ''));

        // Yet again:
        $purgeLabels = array_keys($purgeMap);
        $purgeDays = array_values($purgeMap);
        $revMap = array_flip($purgeMap);

        // Some cleanup logic. It can be that $CODBDATA['purge'] is not
        // set or set to something not matching our $revMap. In that case
        // we need to set a default:
        if (!array_key_exists($CODBDATA['purge'], $revMap)) {
            $CODBDATA['purge'] = '0';
        }

        $purgeSelect = $factory->getMultiChoice('GlobalSitestatsPurge', $purgeLabels, array($revMap[$CODBDATA['purge']]), 'rw');
        $purgeSelect->setSelected($revMap[$CODBDATA['purge']], true);

        $block->addFormField($purgeSelect, $factory->getLabel("GlobalSitestatsPurge"), $defaultPage);
        $block->addFormField($factory->getTextField('save', 1, ''));

        // Add Divider:
        $block->addFormField(
                $factory->addBXDivider("DIV_Purge_Stats", ""),
                $factory->getLabel("DIV_Purge_Stats", false),
                $defaultPage
                );

        // Zap Internal Usage Statistics:
        $block->addFormField(
                $factory->getBoolean("internal", '0'),
                $factory->getLabel("internal"),
                $defaultPage
                );

        // Zap Webalizer:
        $block->addFormField(
                $factory->getBoolean("webalizer", '0'),
                $factory->getLabel("webalizer"),
                $defaultPage
                );

        // Zap AWStats:
        if (file_exists('/usr/sausalito/capstone/solarspeed-awstats.cap')) {
            $block->addFormField(
                    $factory->getBoolean("awstats", '0'),
                    $factory->getLabel("awstats"),
                    $defaultPage
                    );
        }

        // Add Divider:
        $block->addFormField(
                $factory->addBXDivider("DIV_SendmailAnalyzer", ""),
                $factory->getLabel("DIV_SendmailAnalyzer", false),
                $defaultPage
                );

        // SendmailAnalyzer - enable ANONYMIZE:
        $block->addFormField(
                $factory->getBoolean("SA_anonymize", $CODBDATA['SA_anonymize']),
                $factory->getLabel("SA_anonymize"),
                $defaultPage
                );

        // Zap SendmailAnalyzer:
        $block->addFormField(
                $factory->getBoolean("sendmailanalyzer", '0'),
                $factory->getLabel("sendmailanalyzer"),
                $defaultPage
                );

        // AV-SPAM:
        if (isset($AVSPAM['use_sql'])) {

            // Add Divider:
            $block->addFormField(
                    $factory->addBXDivider("DIV_AVSPAM_EXPIRY", ""),
                    $factory->getLabel("DIV_AVSPAM_EXPIRY", false),
                    $defaultPage
                    );

            $avspam_expiry = '0';
            if (isset($CODBDATA['avspam'])) {
                $avspam_expiry = $CODBDATA['avspam'];
            }
            if (isset($AVSPAM['avspam_expiry'])) {
                $avspam_expiry = $AVSPAM['avspam_expiry'];
            }

            $block->addFormField(
                    $factory->getBoolean("avspam", $avspam_expiry),
                    $factory->getLabel("avspam"),
                    $defaultPage
                    );
        }

        //
        //--- Add the buttons
        //

        $block->addButton($factory->getSaveButton($BxPage->getSubmitAction()));
        $block->addButton($factory->getCancelButton("/sitestats/logconfig"));

        $page_body[] = $block->toHtml();

        // Out with the page:
        $BxPage->render($page_module, $page_body);

    }       
}
/*
Copyright (c) 2018 Michael Stauber, SOLARSPEED.NET
Copyright (c) 2018 Team BlueOnyx, BLUEONYX.IT
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