<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Template extends MX_Controller {

    /**
     * Index Page for this controller.
     *
     * Past the login page this loads the page for /vsite/template.
     *
     */

    public function index() {

        $CI =& get_instance();

        // We load the BlueOnyx helper library first of all, as we heavily depend on it:
        $this->load->helper('blueonyx');
        init_libraries();

        // Need to load 'BxPage' for page rendering:
        $this->load->library('BxPage');

        // Get $sessionId and $loginName from Cookie (if they are set) and store them in $CI->BX_SESSION:
        $CI->BX_SESSION['sessionId'] = $CI->input->cookie('sessionId');
        $CI->BX_SESSION['loginName'] = $CI->input->cookie('loginName');

        // Line up the ducks for CCE-Connection and store them for re-usability in $CI:
        include_once('ServerScriptHelper.php');
        $CI->serverScriptHelper = new ServerScriptHelper($CI->BX_SESSION['sessionId'], $CI->BX_SESSION['loginName']);
        $CI->cceClient = $CI->serverScriptHelper->getCceClient();

        $i18n = new I18n("base-vsite", $CI->BX_SESSION['loginUser']['localePreference']);
        $system = $CI->getSystem();

        // Not 'admin'? Bye, bye!
        if (!$CI->serverScriptHelper->getAllowed('admin')) {
            // Nice people say goodbye, or CCEd waits forever:
            $CI->cceClient->bye();
            $CI->serverScriptHelper->destructor();
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
        $required_keys = array( "", "");
        // Set up rules for form validation. These validations happen before we submit to CCE and further checks based on the schemas are done:

        // Empty array for key => values we want to submit to CCE:
        $attributes = array();
        // Items we do NOT want to submit to CCE:
        $ignore_attributes = array("BlueOnyx_Info_Text", "_serialized_errors");
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
            if (!isset($attributes['ipAddr'])) {
                $attributes['ipAddr'] = "";
            }
            if (!isset($attributes['ipaddrIPv6'])) {
                $attributes['ipaddrIPv6'] = "";
            }                
        }

        //
        //--- At this point all checks are done. If we have no errors, we can submit the data to CODB:
        //

        // Join the various error messages:
        $errors = array_merge($ci_errors, $my_errors);

        // If we have no errors and have POST data, we submit to CODB:
        if ((count($errors) == "0") && ($CI->input->post(NULL, TRUE))) {

            // Check if our quota has a unit:
            $pattern = '/^(\d*\.{0,1}\d+)(K|M|G|T)$/';
            if (preg_match($pattern, $attributes['quota'], $matches, PREG_OFFSET_CAPTURE)) {
                // Quota has a unit:
                $quota = (unsimplify_number($attributes['quota'], "K")/1000);
            }
            else {
                // Quota has no unit:
                $quota = simplify_number($attributes['quota'], "K", "0");
            }

            // We have no errors. We submit to CODB.
            $CI->cceClient->setObject("System",
                    array(
                        "ipaddr" => $attributes['ipAddr'],
                        "ipaddrIPv6" => $attributes['ipaddrIPv6'],
                        "domain" => $attributes['domain'],
                        "quota" => $quota,
                        "maxusers" => $attributes['maxusers'],
                        "emailDisabled" => $attributes['emailDisabled'],
                        "mailCatchAll" => $attributes['mailCatchAll'],
                        "dns_auto" => $attributes['dns_auto'],
                        "webAliasRedirects" => $attributes['webAliasRedirects'],
                        "site_preview" => $attributes['site_preview']
                    ),
                    "VsiteDefaults"
                );

            // CCE errors that might have happened during submit to CODB:
            $CCEerrors = $CI->cceClient->errors();
            foreach ($CCEerrors as $object => $objData) {
                // When we fetch the CCE errors it tells us which field it bitched on. And gives us an error message, which we can return:
                $errors[] = ErrorMessage($i18n->get($objData->message, true, array('key' => $objData->key)) . '<br>&nbsp;');
            }

            //
            //-- Handle submit for AutoFeatures:
            //
            if (count($errors) == "0") {
                // Handle automatically detected services
                $autoFeatures = new AutoFeatures($CI->serverScriptHelper, $attributes);
                $cce_info = array();
                list($cce_info["CCE_SERVICES_OID"]) = $CI->cceClient->find("VsiteServices");
                $af_errors = $autoFeatures->handle("defaults.Vsite", $cce_info); 
                $errors = array_merge($errors, $af_errors);
            }

            // No errors? Great, reload page:
            if (count($errors) == "0") {
                // Nice people say goodbye, or CCEd waits forever:
                $CI->cceClient->bye();
                $CI->serverScriptHelper->destructor();
                header("Location: /vsite/template");
                exit;
            }

        }

        //
        //-- Generate page:
        //

        // Prepare Page:
        $factory = $CI->serverScriptHelper->getHtmlComponentFactory("base-vsite", "/vsite/template");
        $BxPage = $factory->getPage();
        $BxPage->setErrors($errors);
        $i18n = $factory->getI18n();

        $product = new Product($CI->cceClient);

        // Set Menu items:
        $BxPage->setVerticalMenu('base_sitemanage');
        $page_module = 'base_sitemanageVSL';

        // Read the current defaults from cce, so they can be substituted
        list($sysoid) = $CI->cceClient->find("System");
        $vsiteDefaults = $CI->cceClient->get($sysoid, "VsiteDefaults");

        $pageId = "siteDefaultsTab";

        $block =& $factory->getPagedBlock("vsiteDefaults", array($pageId, 'otherServices'));

        $block->setToggle("#");
        $block->setSideTabs(FALSE);
        $block->setShowAllTabs("#");
        $block->setDefaultPage($pageId);

        //
        //--- Basic Tab
        //

        //default IPv4 ip address
        $ipAddrField = $factory->getIpAddress("ipAddr", $vsiteDefaults["ipaddr"]);
        $ipAddrField->setOptional('silent');
        $block->addFormField(
                    $ipAddrField,
                    $factory->getLabel("defaultIpAddr"),
                    $pageId
                    );

        //default IPv6 ip address
        $ipv6_address = $factory->getIpAddress("ipaddrIPv6", $vsiteDefaults["ipaddrIPv6"]);
        $ipv6_address->setType("ipaddrIPv6");
        $ipv6_address->setOptional('silent');
        $block->addFormField(
                    $ipv6_address,
                    $factory->getLabel("ipaddrIPv6"),
                    $pageId
                    );

        // default domain
        $domainField = $factory->getDomainName("domain", $vsiteDefaults["domain"]);
        $domainField->setOptional('silent');
        $block->addFormField(
                    $domainField,
                    $factory->getLabel("defaultDomain"),
                    $pageId
                    );

        // default site quota
        $quotaField = $factory->getInteger("quota", simplify_number($vsiteDefaults["quota"]*1000*1000, "K", "0"), '1000000', 0);
        $quotaField->showBounds(FALSE);
        $quotaField->setType('memdisk');
        $block->addFormField(
                    $quotaField,
                    $factory->getLabel("quota"),
                    $pageId
                    );

        // default maxusers
        $block->addFormField(
                $factory->getInteger("maxusers", $vsiteDefaults["maxusers"], 1),
                $factory->getLabel("maxUsers"),
                $pageId
                );

        // enable & disable Email
        $block->addFormField(
                $factory->getBoolean("emailDisabled", $vsiteDefaults["emailDisabled"]),
                $factory->getLabel("emailDisabled"),
                $pageId
                );

        // default email catch-all
        $mailCatchAllField = $factory->getEmailAddress("mailCatchAll",
            $vsiteDefaults["mailCatchAll"], 1);
        $mailCatchAllField->setOptional('silent');
        $block->addFormField(
                $mailCatchAllField,
                $factory->getLabel("mailCatchAll"),
                $pageId
                );

        // auto dns option
        $block->addFormField(
                $factory->getBoolean("dns_auto", $vsiteDefaults["dns_auto"]),
                $factory->getLabel("dns_auto"),
                $pageId
                    );

        // preview site option
        $block->addFormField(
                $factory->getBoolean("site_preview", $vsiteDefaults["site_preview"]),
                $factory->getLabel("site_preview"),
                $pageId
                );

        // webAliasRedirects (to main site) option
        $block->addFormField(
                $factory->getBoolean("webAliasRedirects", $vsiteDefaults["webAliasRedirects"]),
                $factory->getLabel("webAliasRedirects"),
                $pageId
                );

        //
        //--- Services and Features
        //

        // Add automatically detected features
        $autoFeatures = new AutoFeatures($CI->serverScriptHelper);
        $cce_info = array();
        list($cce_info['CCE_SERVICES_OID']) = $CI->cceClient->find('VsiteServices');
        $cce_info['PAGED_BLOCK_DEFAULT_PAGE'] = 'otherServices';
        $autoFeatures->display($block, 'defaults.Vsite', $cce_info);

        // Add the buttons
        $block->addButton($factory->getSaveButton($BxPage->getSubmitAction()));
        $block->addButton($factory->getCancelButton("/vsite/template"));

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