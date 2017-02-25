<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class VsiteSub extends MX_Controller {

    /**
     * Index Page for this controller.
     *
     * Past the login page this loads the page for /subdomains/vsiteSub.
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
        $i18n = new I18n("base-subdomains", $CI->BX_SESSION['loginUser']['localePreference']);
        $system = $CI->getSystem();

        // Initialize Capabilities so that we can poll the access rights as well:
        $Capabilities = new Capabilities($CI->cceClient, $CI->BX_SESSION['loginName'], $CI->BX_SESSION['sessionId']);

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
            $CI->cceClient->bye();
            $CI->serverScriptHelper->destructor();
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
            $CI->cceClient->bye();
            $CI->serverScriptHelper->destructor();
            Log403Error("/gui/Forbidden403#2");
        }

        //
        //-- Prepare data:
        //

        // Get data for the Vsite:
        $vsite = $CI->cceClient->getObject('Vsite', array('name' => $group));
        $vsiteSub = $CI->cceClient->getObject('Vsite', array('name' => $group), "subdomains");

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
        $required_keys = array("vsite_enabled", "max_subdomains");

        // Set up rules for form validation. These validations happen before we submit to CCE and further checks based on the schemas are done:

        // Empty array for key => values we want to submit to CCE:
        $attributes = array();
        // Items we do NOT want to submit to CCE:
        $ignore_attributes = array("");
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
            // Not needed. Thank you, jQuery!
        }

        //
        //--- At this point all checks are done. If we have no errors, we can submit the data to CODB:
        //

        // Join the various error messages:
        $errors = array_merge($ci_errors, $my_errors);

        // If we have no errors and have POST data, we submit to CODB:
        if ((count($errors) == "0") && ($CI->input->post(NULL, TRUE))) {

            // We have no errors. We submit to CODB.
            $cfg = array(
                "vsite_enabled" => $attributes['vsite_enabled'],
                "max_subdomains" => $attributes['max_subdomains']
            );
            $CI->cceClient->setObject("Vsite", $cfg, "subdomains", array('name' => $attributes['group']));

            // CCE errors that might have happened during submit to CODB:
            $CCEerrors = $CI->cceClient->errors();
            foreach ($CCEerrors as $object => $objData) {
                // When we fetch the CCE errors it tells us which field it bitched on. And gives us an error message, which we can return:
                $errors[] = ErrorMessage($i18n->get($objData->message, true, array('key' => $objData->key)) . '<br>&nbsp;');
            }
            // No errors during submit? Reload page:
            if (count($errors) == "0") {
                $CI->cceClient->bye();
                $CI->serverScriptHelper->destructor();
                $redirect_URL = "/subdomains/vsiteSub?group=$group";
                header("location: $redirect_URL");
                exit;
            }
        }

        //
        //-- Generate page:
        //

        // Determine current user's access rights to view or edit information
        // here.  Only 'manageSite' can modify things on this page. 
        if ($Capabilities->getAllowed('manageSite')) {
            $is_site_admin = TRUE;
            $access = 'rw';
        }
        elseif (($Capabilities->getAllowed('siteAdmin')) && ($group == $Capabilities->loginUser['site'])) {
            $access = 'r';
            $is_site_admin = FALSE;
        }
        else {
            // Nice people say goodbye, or CCEd waits forever:
            $CI->cceClient->bye();
            $CI->serverScriptHelper->destructor();
            Log403Error("/gui/Forbidden403#3");
        }

        // Prepare Page:
        $factory = $CI->serverScriptHelper->getHtmlComponentFactory("base-subdomains", "/subdomains/vsiteSub?group=$group");
        $BxPage = $factory->getPage();
        $BxPage->setErrors($errors);
        $i18n = $factory->getI18n();

        // Set Menu items:
        $BxPage->setVerticalMenu('base_siteservices');
        $BxPage->setVerticalMenuChild('nuonce_subdomain_vsite');
        $page_module = 'base_sitemanage';

        if ($vsiteSub["max_subdomains"] == 0 ) {
            $sys = $CI->cceClient->get($system['OID'], "subdomains");
            $cfg["max_subdomains"] = $vsiteSub["max_subdomains"] = $sys["default_max_subdomains"];
            $CI->cceClient->setObject("Vsite", $cfg, "subdomains", array('name' => $group));
        }

        $defaultPage = "basicSettingsTab";

        $block =& $factory->getPagedBlock("vsite_header", array($defaultPage));

        $block->setToggle("#");
        $block->setSideTabs(FALSE);
        $block->setDefaultPage($defaultPage);

        $block->addFormField(
            $factory->getTextField("group", $vsite["name"], ""),
            $factory->getLabel("group"), 
            $defaultPage);

        $block->addFormField(
            $factory->getBoolean("vsite_enabled", $vsiteSub["vsite_enabled"], $access),
            $factory->getLabel("vsite_enabled"), 
            $defaultPage);

        $max_subdomains = $factory->getInteger("max_subdomains", $vsiteSub["max_subdomains"], 1, 10000, $access);
        $max_subdomains->showBounds(1);
        $max_subdomains->setWidth(4);

        $block->addFormField(
            $max_subdomains,
            $factory->getLabel("max_subdomains"), 
            $defaultPage);

        // Add the buttons
        if ( $access == "rw" ) {
            $block->addButton($factory->getSaveButton($BxPage->getSubmitAction()));
            $block->addButton($factory->getCancelButton("/subdomains/vsiteSub?group=$group"));
        }

        //
        //-- Scrollist:
        //

        $subdomainOIDs = $CI->cceClient->find("Subdomains", array("group" => $group));

        // ScrolList for Subdomain management:
        $scrollList = $factory->getScrollList("sub_title", array("sub_domain", "sub_path", " "), array()); 
        $scrollList->setAlignments(array("left", "left", "center"));
        $scrollList->setDefaultSortedIndex('0');
        $scrollList->setSortOrder('descending');
        $scrollList->setSortDisabled(array('2'));
        $scrollList->setPaginateDisabled(FALSE);
        $scrollList->setSearchDisabled(FALSE);
        $scrollList->setSelectorDisabled(FALSE);
        $scrollList->enableAutoWidth(FALSE);
        $scrollList->setInfoDisabled(FALSE);
        $scrollList->setColumnWidths(array("200", "489", "50")); // Max: 739px

        $domain = $vsite["domain"];
        $count = count($subdomainOIDs);

        foreach ( $subdomainOIDs as $OID ) {
            $subdomain = $CI->cceClient->get($OID);
            $actions = $factory->getCompositeFormField();
            $fqdn = $subdomain["hostname"] . "." . $domain;
            $delButton = $factory->getRemoveButton("javascript:confirmVsiteDel($OID, '$fqdn')");
            if ( ! $subdomain["isUser"] ) {
                $actions = '<a class="lb" href="/subdomains/vsiteDelSub?group=' . $group . '&OID=' . $OID . '&fqdn=' . $fqdn . '"><button class="tiny icon_only div_icon tooltip hover dialog_button" title="' . $i18n->getHtml("[[palette.remove_help]]") . '"><div class="ui-icon ui-icon-trash"></div></button></a><br>';
            }
            $scrollList->addEntry(array($subdomain["hostname"], $subdomain["webpath"], $actions));
        }

        if ( $vsiteSub["vsite_enabled"] ) {
            if ( $count < $vsiteSub["max_subdomains"] ) {
                $scrollList->addButton($factory->getButton("/subdomains/vsiteAddSub?group=$group", "new_sub", "DEMO-OVERRIDE"));
            }
        }

        // Extra header for the "do you really want to delete" dialog:
        $BxPage->setExtraHeaders('
                <script type="text/javascript">
                $(document).ready(function () {

                  $("#dialog").dialog({
                    modal: true,
                    bgiframe: true,
                    width: 500,
                    height: 280,
                    autoOpen: false
                  });

                  $(".lb").click(function (e) {
                    e.preventDefault();
                    var hrefAttribute = $(this).attr("href");

                    $("#dialog").dialog(\'option\', \'buttons\', {
                      "' . $i18n->getHtml("[[palette.remove]]") . '": function () {
                        window.location.href = hrefAttribute;
                      },
                      "' . $i18n->getHtml("[[palette.cancel]]") . '": function () {
                        $(this).dialog("close");
                      }
                    });

                    $("#dialog").dialog("open");

                  });
                });
                </script>');

        $page_body[] = $block->toHtml();
        $page_body[] = "<p>&nbsp;</p>";
        $page_body[] = $scrollList->toHtml();

        // Add hidden Modal for Delete-Confirmation:
        $page_body[] = '
            <div class="display_none">
                        <div id="dialog" class="dialog_content narrow no_dialog_titlebar" title="' . $i18n->getHtml("[[base-subdomains.sub_dom_remove_header]]") . '">
                            <div class="block">
                                    <div class="section">
                                            <h1>' . $i18n->getHtml("[[base-subdomains.sub_dom_remove_header]]") . '</h1>
                                            <div class="dashed_line"></div>
                                            <p>' . $i18n->getHtml("[[base-subdomains.sub_dom_remove_question]]") . '</p>
                                    </div>
                            </div>
                        </div>
            </div>';

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