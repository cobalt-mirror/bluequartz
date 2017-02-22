<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Emailsettings extends MX_Controller {

    /**
     * Index Page for this controller.
     *
     * Past the login page this loads the page for /email/emailsettings.
     *
     */

    public function index() {

        $CI =& get_instance();

        // We load the BlueOnyx helper library first of all, as we heavily depend on it:
        $this->load->helper('blueonyx');
        init_libraries();

        // Need to load 'BxPage' for page rendering:
        $this->load->library('BxPage');

        // Get $CI->BX_SESSION['sessionId'] and $CI->BX_SESSION['loginName'] from Cookie (if they are set) and store them in $CI->BX_SESSION:
        $CI->BX_SESSION['sessionId'] = $CI->input->cookie('sessionId');
        $CI->BX_SESSION['loginName'] = $CI->input->cookie('loginName');

        // Line up the ducks for CCE-Connection and store them for re-usability in $CI:
        include_once('ServerScriptHelper.php');
        $CI->serverScriptHelper = new ServerScriptHelper($CI->BX_SESSION['sessionId'], $CI->BX_SESSION['loginName']);
        $CI->cceClient = $CI->serverScriptHelper->getCceClient();

        $i18n = new I18n("base-email", $CI->BX_SESSION['loginUser']['localePreference']);
        $system = $CI->getSystem();
        $user = $CI->BX_SESSION['loginUser'];

        // Not 'serverEmail'? Bye, bye!
        if (!$CI->serverScriptHelper->getAllowed('serverEmail')) {
            // Nice people say goodbye, or CCEd waits forever:
            $CI->cceClient->bye();
            $CI->serverScriptHelper->destructor();
            Log403Error("/gui/Forbidden403");
        }

        // -- Actual page logic start:

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
        $required_keys = array( "maxRecipientsPerMessage", "queueTime");
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

            // Data transformation:
            if (isset($attributes['maxMessageSize'])) {
                $max = $attributes['maxMessageSize'] ? $attributes['maxMessageSize']*1024 : "";
                $attributes['maxMessageSize'] = $max;
            }

            $queueTimeMap = array("queue0" => "immediate", "queue15" => "quarter-hourly", "queue30" => "half-hourly", "queue60" => "hourly", "queue360" => "quarter-daily", "queue1440" => "daily");
            $attributes['queueTime'] = $queueTimeMap[$attributes['queueTime']];

            $maxRecipientsPerMessageMap = 
                array(
                "unlimited" => "0", 
                    "5" => "5", 
                    "10" => "10", 
                    "15" => "15", 
                    "20" => "20", 
                    "25" => "25", 
                    "50" => "50", 
                    "75" => "75", 
                    "100" => "100", 
                    "125" => "125", 
                    "150" => "150", 
                    "175" => "175", 
                    "200" => "200"
                );
            $attributes['maxRecipientsPerMessage'] = $maxRecipientsPerMessageMap[$attributes['maxRecipientsPerMessage']];
        }

        //
        //--- At this point all checks are done. If we have no errors, we can submit the data to CODB:
        //

        // Join the various error messages:
        $errors = array_merge($ci_errors, $my_errors);

        // If we have no errors and have POST data, we submit to CODB:
        if ((count($errors) == "0") && ($CI->input->post(NULL, TRUE))) {

            // We have no errors. We submit to CODB.

            // Actual submit to CODB:
            $CI->cceClient->set($system['OID'], "Email",  $attributes);

            // CCE errors that might have happened during submit to CODB:
            $CCEerrors = $CI->cceClient->errors();
            foreach ($CCEerrors as $object => $objData) {
                // When we fetch the CCE errors it tells us which field it bitched on. And gives us an error message, which we can return:
                $errors[] = ErrorMessage($i18n->get($objData->message, true, array('key' => $objData->key)) . '<br>&nbsp;');
            }
            // Replace the CODB obtained values in our Form with the one we just posted to CCE:
            $email = $form_data;
        }

        //
        //-- Generate page:
        //

        // Prepare Page:
        $factory = $CI->serverScriptHelper->getHtmlComponentFactory("base-email", "/email/emailsettings");
        $BxPage = $factory->getPage();
        $BxPage->setErrors($errors);
        $i18n = $factory->getI18n();

        // Set Menu items:
        $BxPage->setVerticalMenu('base_controlpanel');
        $page_module = 'base_sysmanage';

        // get $email:
        $email = $CI->cceClient->get($system['OID'], "Email");

        $defaultPage = "basic";

        $block =& $factory->getPagedBlock("emailSettings", array($defaultPage, "advanced", "mx", "blacklist"));

        $block->setToggle("#");
        $block->setSideTabs(FALSE);
        $block->setShowAllTabs("#");
        $block->setDefaultPage($defaultPage);

        //
        //--- Basic Tab
        //

        // Add divider:
        $block->addFormField(
                $factory->addBXDivider("SMTP", ""),
                $factory->getLabel("SMTP", false),
                $defaultPage
                );

        $block->addFormField(
            $factory->getBoolean("enableSMTP", $email["enableSMTP"]),
            $factory->getLabel("enableServersField"),
            $defaultPage
        );

        $block->addFormField(
          $factory->getBoolean("enableSMTPS", $email["enableSMTPS"]),
          $factory->getLabel("enableSMTPSField"),
          "basic"
        );

        $block->addFormField(
          $factory->getBoolean("enableSMTPAuth", $email["enableSMTPAuth"]),
          $factory->getLabel("enableSMTPAuthField"),
          "basic"
        );

        $block->addFormField(
          $factory->getBoolean("enableSubmissionPort", $email["enableSubmissionPort"]),
          $factory->getLabel("enableSubmissionPortField"),
          "basic"
        );

        // imap
        $block->addFormField(
                $factory->addBXDivider("IMAP", ""),
                $factory->getLabel("IMAP", false),
                $defaultPage
                );      

        $block->addFormField(
          $factory->getBoolean("enableImap", $email["enableImap"]),
          $factory->getLabel("enableImapField"),
          "basic"
        );

        $block->addFormField(
          $factory->getBoolean("enableImaps", $email["enableImaps"]),
          $factory->getLabel("enableImapsField"),
          "basic"
        );

        // pop
        $block->addFormField(
                $factory->addBXDivider("POP", ""),
                $factory->getLabel("POP", false),
                $defaultPage
                );      

        $block->addFormField(
          $factory->getBoolean("enablePop", $email["enablePop"]),
          $factory->getLabel("enablePopField"),
          "basic"
        );

        $block->addFormField(
          $factory->getBoolean("enablePops", $email["enablePops"]),
          $factory->getLabel("enablePopsField"),
          "basic"
        );

        // Z-Push
        $block->addFormField(
                $factory->addBXDivider("ZPushActiveSync", ""),
                $factory->getLabel("ZPushActiveSync", false),
                $defaultPage
                );      

        $block->addFormField(
          $factory->getBoolean("enableZpush", $email["enableZpush"]),
          $factory->getLabel("enableZpushField"),
          "basic"
        );

        //
        //--- Advanced Tab
        //

        $queueTimeMap = array("immediate" => "queue0", "quarter-hourly" => "queue15", "half-hourly" => "queue30", "hourly" => "queue60", "quarter-daily" => "queue360", "daily" => "queue1440");
        $queueSelectedMap = array("immediate" => "queue0", "quarter-hourly" => "queue15", "half-hourly" => "queue30", "hourly" => "queue60", "quarter-daily" => "queue360", "daily" => "queue1440");

        $maxRecipientsPerMessageMap = 
            array(
            "0" => "unlimited", 
                "5" => "5", 
                "10" => "10", 
                "15" => "15", 
                "20" => "20", 
                "25" => "25", 
                "50" => "50", 
                "75" => "75", 
                "100" => "100", 
                "125" => "125", 
                "150" => "150", 
                "175" => "175", 
                "200" => "200" 
            );
          
        $queue_select = $factory->getMultiChoice("queueTime", array_values($queueTimeMap));
        $queue_select->setSelected($queueSelectedMap[$email['queueTime']], true);
        $block->addFormField($queue_select, $factory->getLabel("queueTimeField"), 'advanced');

        // convert from KB to MB
        $max = $email["maxMessageSize"]/1024;

        // No maximum size limit if it is 0
        $max = $max == 0 ? "" : $max;

        $maxEmailSize = $factory->getInteger("maxMessageSize", $max, 1);
        $maxEmailSize->setOptional(true);
        $block->addFormField(
          $maxEmailSize,
          $factory->getLabel("maxEmailSizeField"),
          "advanced"
        );

        // maxRecipientsPerMessage
        $maxRecipientsPerMessage_select = $factory->getMultiChoice("maxRecipientsPerMessage", array_values($maxRecipientsPerMessageMap));
        $maxRecipientsPerMessage_select->setSelected($maxRecipientsPerMessageMap[$email['maxRecipientsPerMessage']], true);
        $block->addFormField($maxRecipientsPerMessage_select, $factory->getLabel("maxRecipientsPerMessageField"), 'advanced');

        // Enable delay_checks
        $block->addFormField(
          $factory->getBoolean("delayChecks", $email["delayChecks"]),
          $factory->getLabel("delayChecksField"),
          "advanced"
        );

        $masqAddress = $factory->getTextField("masqAddress", $email["masqAddress"]);
        $masqAddress->setType('IP_or_FQDN');
        $masqAddress->setOptional(true);
        $block->addFormField(
          $masqAddress,
          $factory->getLabel("masqAddressField"),
          "advanced"
        );

        $smartRelay = $factory->getDomainName("smartRelay", $email["smartRelay"]);
        $smartRelay->setType("fqdn");
        $smartRelay->setOptional(true);
        $block->addFormField(
          $smartRelay, 
          $factory->getLabel("smartRelayField"),
          "advanced"
        );

        // Hide prior received headers:
        $block->addFormField(
          $factory->getBoolean("hideHeaders", $email["hideHeaders"]),
          $factory->getLabel("hideHeadersField"),
          "advanced"
        );

        $poprelay = $factory->getBoolean("popRelay", $email["popRelay"]);
        $poprelay->setOptional(true);
        $block->addFormField(
          $poprelay,
          $factory->getLabel("popRelayField"),
          "advanced"
        );

        $relay = $factory->getNetAddressList("relayFor", $email["relayFor"]);
        $relay->setOptional(true);
        $block->addFormField(
          $relay,
          $factory->getLabel("relayField"),
          "advanced"
        );

        //if ( ! $product->isRaq() ) { // This is no longer needed and never was needed for BlueOnyx.
        //  $receive = $factory->getDomainNameList("acceptFor", $email["acceptFor"]);
        //  $receive->setOptional(true);
        //  $block->addFormField(
        //    $receive,
        //    $factory->getLabel("receiveField"),
        //    "advanced"
        //  );
        //}

        $blockHost = $factory->getDomainNameList("deniedHosts", $email["deniedHosts"]);
        $blockHost->setOptional(true);
        $block->addFormField(
          $blockHost,
          $factory->getLabel("blockHostField"),
          "advanced"
        );

        $blockUser = $factory->getEmailAddressList("deniedUsers", $email["deniedUsers"]);
        $blockUser->setOptional(true);
        $block->addFormField(
          $blockUser,
          $factory->getLabel("blockUserField"),
          "advanced"
        );

        //
        //-- Secondary MX
        //

        $oids = $CI->cceClient->findx("mx2",array(),array(), 'ascii', 'domain');
        $oidsNum = count($oids);

        $addmod = '/email/secondarymx';

        $scrollList = $factory->getScrollList("mx2List", array("secondaryDomain", " ", " "), array()); 
        $scrollList->setAlignments(array("left", "center", "center"));
        $scrollList->setDefaultSortedIndex('0');
        $scrollList->setSortOrder('ascending');
        $scrollList->setSortDisabled(array('1', '2'));
        $scrollList->setPaginateDisabled(FALSE);
        $scrollList->setSearchDisabled(FALSE);
        $scrollList->setSelectorDisabled(FALSE);
        $scrollList->enableAutoWidth(FALSE);
        $scrollList->setInfoDisabled(FALSE);
        $scrollList->setColumnWidths(array("498", "120", "120")); // Max: 739px

        for($i=0; $i < count($oids); $i++) {
            $oid = $oids[$i];
            $domains = $CI->cceClient->get($oid);
            $domain = $domains['domain'];
            $mapto = $domains['mapto'];

            $scrollList->addEntry(array(
                        $domain,
                        $factory->getModifyButton("$addmod?_TARGET=$oid"),
                        $factory->getRemoveButton("$addmod?_RTARGET=$oid")
                        ));
        }

        // generate add mx button:
        $script_siteAdd = "/email/secondarymx";
        $settings = $factory->getAddButton($script_siteAdd, '[[base-email.addmx]]', "DEMO-OVERRIDE");

        $buttonContainer = $factory->getButtonContainer("mx2List", $settings);

        $block->addFormField(
            $buttonContainer,
            $factory->getLabel("mx2List"),
            "mx"
        );

        $block->addFormField(
            $factory->getRawHTML("mx2List", $scrollList->toHtml()),
            $factory->getLabel("mx2List"),
            "mx"
        );

        //
        //-- Blacklisting
        //

        $oids = $CI->cceClient->findx("dnsbl",array(),array(), 'ascii', 'blacklistHost');
        $oidsNum = count($oids);

        $addmod = '/email/blacklist';

        $blacklist = $factory->getScrollList("blackList", array("blackList", "activated", " ", " "), array()); 
        $blacklist->setAlignments(array("left", "left", "center", "center"));
        $blacklist->setDefaultSortedIndex('0');
        $blacklist->setSortOrder('ascending');
        $blacklist->setSortDisabled(array('2', '3'));
        $blacklist->setPaginateDisabled(FALSE);
        $blacklist->setSearchDisabled(FALSE);
        $blacklist->setSelectorDisabled(FALSE);
        $blacklist->enableAutoWidth(FALSE);
        $blacklist->setInfoDisabled(FALSE);
        $blacklist->setColumnWidths(array("398", "100", "120", "120")); // Max: 739px

        for($i=0; $i < count($oids); $i++) {
            $oid = $oids[$i];
            $hosts = $CI->cceClient->get($oid);
            $host = $hosts['blacklistHost'];
            $active = $hosts['active'];

            if( $active) {
                $activeStatus = '<button class="blue tiny text_only has_text tooltip hover" title="' . $i18n->getHtml("[[palette.enabled]]") . '"><span>' . $i18n->getHtml("[[palette.enabled_short]]") . '</span></button>';
            }
            else {
                $activeStatus = '<button class="light tiny text_only has_text tooltip hover" title="' . $i18n->getHtml("[[palette.disabled]]") . '"><span>' . $i18n->getHtml("[[palette.disabled_short]]") . '</span></button>';
            }

            $blacklist->addEntry(array(
                        $host,
                        $activeStatus,
                        $factory->getModifyButton( "$addmod?_TARGET=$oid"),
                        $factory->getRemoveButton( "$addmod?_RTARGET=$oid" )
                        ));
        }

        // generate add blacklist button:
        $rblscript_siteAdd = "/email/blacklist";
        $rblsettings = $factory->getAddButton($rblscript_siteAdd, '[[base-email.addmx]]', "DEMO-OVERRIDE");

        $rblbuttonContainer = $factory->getButtonContainer("blackList", $rblsettings);

        $block->addFormField(
            $rblbuttonContainer,
            $factory->getLabel("blackList"),
            "blacklist"
        );

        $block->addFormField(
            $factory->getRawHTML("blackList", $blacklist->toHtml()),
            $factory->getLabel("blackList"),
            "blacklist"
        );


        // Add the buttons
        $block->addButton($factory->getSaveButton($BxPage->getSubmitAction()));
        $block->addButton($factory->getCancelButton("/email/emailsettings"));

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