<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class AdminList extends MX_Controller {

    /**
     * Index Page for this controller.
     *
     * Past the login page this loads the page for /vsite/adminList.
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

        // Only "systemAdministrator" should be here. This is important. Boot anyone else:
        if (!$Capabilities->getAllowed('systemAdministrator')) {
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
        $required_keys = array("");
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

        }

        //
        //-- Generate page:
        //

        // Prepare Page:
        $factory = $serverScriptHelper->getHtmlComponentFactory("base-vsite", "/vsite/adminList");
        $BxPage = $factory->getPage();
        $BxPage->setErrors($errors);
        $i18n = $factory->getI18n();

        $product = new Product($cceClient);

        // Set Menu items:
        $BxPage->setVerticalMenu('base_controlpanel');
        $page_module = 'base_sysmanage';

        $defaultPage = "basic";

        $block =& $factory->getPagedBlock("adminUsersList", array($defaultPage));

        $block->setToggle("#");
        $block->setSideTabs(FALSE);
        $block->setShowAllTabs("#");
        $block->setDefaultPage($defaultPage);

        //
        //--- Basic Tab
        //

        $adminList = $factory->getScrollList("adminUsersList", array('fullName', 'userName', 'userSuspended', 'actions'), array()); 
        $adminList->setAlignments(array("left", "left", "center", "center"));
        $adminList->setDefaultSortedIndex('0');
        $adminList->setSortOrder('ascending');
        $adminList->setSortDisabled(array('3'));
        $adminList->setPaginateDisabled(FALSE);
        $adminList->setSearchDisabled(FALSE);
        $adminList->setSelectorDisabled(FALSE);
        $adminList->enableAutoWidth(FALSE);
        $adminList->setInfoDisabled(FALSE);
        $adminList->setColumnWidths(array("320", "178", "120", "120")); // Max: 739px

        // Get a list of all 'adminUser'. As is this excludes user 'admin':
        $admins = $cceClient->findx('User', 
                        array("capLevels" => 'adminUser'),
                        array(), 
                        "",
                        "");

        for($i=0; $i < count($admins); $i++) {
            $oid = $admins[$i];
            $current = $cceClient->get($admins[$i]);

            // Add the Modify / Delete buttons. The Delete button is done manually as
            // we need to wiggle a confirm dialog into it.
            $actions = $factory->getCompositeFormField();
            $modify = $factory->getModifyButton("/vsite/manageAdmin?MODIFY=1&_oid=$admins[$i]");
            $modify->setImageOnly(TRUE);

            $actions->addFormField($modify);
            $remove = $factory->getRawHTML("dialog_admindel", '<a class="dialog_admindel" href="' . "/vsite/manageAdmin?DELETE=1&_oid=$admins[$i]". '"><button class="no_margin_bottom img_icon icon_only tooltip hover dialog_button" title="' . $i18n->getWrapped("remove_help", "palette") . '"><div class="ui-icon ui-icon-trash icon_only"></div></button></a>');
            $actions->addFormField($remove);

            // Enabled / Disabled display:
            if ($current['ui_enabled'] == "1") {
                $activeStatus = '<button class="blue tiny text_only has_text tooltip hover" title="' . $i18n->getHtml("[[palette.enabled]]") . '"><span>' . $i18n->getHtml("[[palette.enabled_short]]") . '</span></button>';
            }
            else {
                $activeStatus = '<button class="light tiny text_only has_text tooltip hover" title="' . $i18n->getHtml("[[palette.disabled]]") . '"><span>' . $i18n->getHtml("[[palette.disabled_short]]") . '</span></button>';
            }

            if ($current['name'] == $loginName) {
                // We don't allow a systemAdministrator to edit or delete himself.
                // Remove the buttons and add s spacer to stretch to correct height.
                $actions = '<IMG BORDER="0" WIDTH="0" HEIGHT="45" SRC="/libImage/spaceHolder.gif">';
            }

            if ($current['name'] != 'admin') {
                // Populate Scrollist:
                $adminList->addEntry(array(
                            bx_charsetsafe($current['fullName']),
                            bx_charsetsafe($current['name']),
                            $activeStatus,
                            $actions
                            ));
            }
        }

        // Generate +Add button:
        $addAdminUser = "/vsite/manageAdmin";
        $addbutton = $factory->getAddButton($addAdminUser, '[[base-vsite.addAdminHelp]]', "DEMO-OVERRIDE");
        $buttonContainer = $factory->getButtonContainer("adminUsersList", $addbutton);
        $block->addFormField(
            $buttonContainer,
            $factory->getLabel("adminUsersList"),
            $defaultPage
        );

        // Push out the Scrollist with the admin-users:
        $block->addFormField(
            $factory->getRawHTML("adminUsersList", $adminList->toHtml()),
            $factory->getLabel("adminUsersList"),
            $defaultPage
        );

        if (isset($current['name'])) {

            // Extra header for Admin delete confirmation dialog:
            $BxPage->setExtraHeaders('
                <script type="text/javascript">
                $(document).ready(function () {

                  $("#dialog_admindel").dialog({
                    modal: true,
                    bgiframe: true,
                    width: 500,
                    height: 200,
                    autoOpen: false
                  });

                  $(".dialog_admindel").click(function (e) {
                    e.preventDefault();
                    var hrefAttribute = $(this).attr("href");

                    $("#dialog_admindel").dialog(\'option\', \'buttons\', {
                      "' . $i18n->getHtml("[[palette.remove]]") . '": function () {
                        window.location.href = hrefAttribute;
                      },
                      "' . $i18n->getHtml("[[palette.cancel]]") . '": function () {
                        $(this).dialog("close");
                      }
                    });

                    $("#dialog_admindel").dialog("open");

                  });
                });
                </script>');

            // Add hidden Modal for Admin delete confirmation:
            $page_body[] = '
                <div class="display_none">
                            <div id="dialog_admindel" class="dialog_content narrow no_dialog_titlebar" title="' . $i18n->getHtml("[[base-vsite.adminOptions]]") . '">
                                <div class="block">
                                        <div class="section">
                                                <h1>' . $i18n->getHtml("[[base-vsite.adminOptions]]") . '</h1>
                                                <div class="dashed_line"></div>
                                                <p>' . $i18n->getHtml("[[base-vsite.deleteQuestion]]", false, array('name' => $current['name'])) . '</p>
                                        </div>
                                </div>
                            </div>
                </div>';
        }

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