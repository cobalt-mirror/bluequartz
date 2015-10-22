<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Download extends MX_Controller {

    /**
     * Index Page for this controller.
     *
     * Past the login page this loads the page for /swupdate/download.
     *
     */

    public function index() {

        $CI =& get_instance();

        // We load the BlueOnyx helper library first of all, as we heavily depend on it:
        $this->load->helper('blueonyx');
        // This page also needs the helpers/updateLib_helper.php:
        $this->load->helper('updatelib');
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

        // Get URL params:
        $get_form_data = $CI->input->get(NULL, TRUE);

        if ((!isset($get_form_data['packageOID'])) || (!isset($get_form_data['backUrl']))) {
            // Nice people say goodbye, or CCEd waits forever:
            $cceClient->bye();
            $serverScriptHelper->destructor();
            Log403Error("/gui/Forbidden403");
        }

        $packageOID = $get_form_data['packageOID'];
        $backUrl = $get_form_data['backUrl'];

        //
        //--- Get CODB-Object of interest: 
        //
        $package = $cceClient->get($packageOID);

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
            if (isset($form_data['_serialized_errors'])) {
                $my_errors = unserialize($form_data['_serialized_errors']);
            }
        }

        $get_form_data = $CI->input->get(NULL, TRUE);

        // Get the return message from the URL string - if present:
        if (isset($get_form_data['msg'])) {
            $redir_msg[] = '<div class="alert dismissible alert_green"><img width="40" height="36" src="/.adm/images/icons/small/white/alarm_bell.png"><strong>' . $i18n->interpolateHtml(urldecode($get_form_data['msg'])) . '<br><br><br></strong></div>';
            $my_errors = array_merge($redir_msg, $errors);          
        }

        //
        //--- At this point all checks are done. If we have no errors, we can submit the data to CODB:
        //

        // Join the various error messages:
        $errors = array_merge($ci_errors, $my_errors);

        //
        //-- Own page logic:
        //

        //
        //-- Generate page:
        //

        // Prepare Page:
        $factory = $serverScriptHelper->getHtmlComponentFactory("base-swupdate", "/swupdate/license");
        $BxPage = $factory->getPage();
        $BxPage->setErrors($errors);
        $i18n = $factory->getI18n();

        $product = new Product($cceClient);

        // Set Menu items:
        $BxPage->setVerticalMenu('base_software');
        $BxPage->setVerticalMenuChild('base_softwareNew');
        $page_module = 'base_software';

        $defaultPage = "downloadSoftware";

        $block =& $factory->getPagedBlock("downloadSoftware", array($defaultPage));

        $block->setToggle("#");
        $block->setSideTabs(FALSE);
        $block->setShowAllTabs("#");
        $block->setDefaultPage($defaultPage);

        $name = $package["nameTag"] ? $i18n->interpolate($package["nameTag"]) : $package["name"];

        $packageName = $package["nameTag"] ? $i18n->interpolate($package["nameTag"]) : $package["name"];
        $vendorName = $package["vendorTag"] ? $i18n->interpolate($package["vendorTag"]) : $package["vendor"];

        $block->addFormField(
          $factory->getTextField("nameField", $packageName, "r"),
          $factory->getLabel("nameField"),
          $defaultPage
        );

        $version = $package["versionTag"] ? $i18n->interpolate($package["versionTag"]) : substr($package["version"], 1);
        $block->addFormField(
          $factory->getTextField("versionField", $version, "r"),
          $factory->getLabel("versionField"),
          $defaultPage
        );

        $name = $package["vendorTag"] ? $i18n->interpolate($package["vendorTag"]) : $package["vendor"];

        $block->addFormField(
          $factory->getTextField("vendorField", $name, "r"),
          $factory->getLabel("vendorField"),
          $defaultPage
        );


        if ($package["copyright"]) {
            $block->addFormField(
                $factory->getTextField("copyrightField", 
                $i18n->interpolate($package["copyright"]), "r"),
                $factory->getLabel("copyrightField"),
                $defaultPage
            );
        }

        $desc = $package['longDesc'] ? $package['longDesc'] : $package['shortDesc'];
        $block->addFormField(
          $factory->getTextField("descriptionField", 
          $i18n->interpolate($desc), "r"),
          $factory->getLabel("descriptionField"),
          $defaultPage
        );

        $location = preg_match('/^file:/', $package['location']) ? $i18n->interpolate('[[base-swupdate.locationLocal]]') : $package['location'];
        $block->addFormField(
          $factory->getTextField("locationField", $location, "r"),
          $factory->getLabel("locationField"),
          $defaultPage
        );

        $size = $package["size"] ? simplify_number($package['size'], "KB", "2") : $i18n->interpolate('[[base-swupdate.unknownSize]]');
        $block->addFormField(
          $factory->getTextField("sizeField", $size . "B", "r"),
          $factory->getLabel("sizeField"),
          $defaultPage
        );

        if (strstr($package['options'], 'uninstallable')) {
          $uninst = "yes";
        }
        else {
          $uninst = "no";
        }

        $block->addFormField(
          $factory->getTextField("uninstallableField", $i18n->get($uninst), "r"),
          $factory->getLabel("uninstallableField"),
          $defaultPage
        );

        $dependency = stringToArray($package["visibleList"]);
        if($dependency) {
            $needed = join(', ', $dependency);
            $needed = str_replace(':', ' ', $needed);
        }
        else {
            $needed = $i18n->get('none');
        }

        $block->addFormField(
            $factory->getTextField("packagesNeededField", $needed, "r"),
            $factory->getLabel("packagesNeededField"),
            $defaultPage
        );

        $block->addFormField($factory->getTextField("backUrl", $backUrl, ""), $defaultPage);
        $block->addFormField($factory->getTextField("packageOID", $packageOID, ""), $defaultPage);

        $action = "/swupdate/license?" . "packageOID=" . $packageOID . "&backUrl=" . $backUrl;

        if (strstr($package['options'], 'reboot')) {

            // Extra header for the "confirm reboot" dialog:
            $BxPage->setExtraHeaders('
                <script type="text/javascript">
                $(document).ready(function () {

                  $("#dialog").dialog({
                    modal: true,
                    bgiframe: true,
                    width: 500,
                    height: 200,
                    autoOpen: false
                  });

                  $(".lb").click(function (e) {
                    e.preventDefault();
                    var hrefAttribute = $(this).attr("href");

                    $("#dialog").dialog(\'option\', \'buttons\', {
                      "' . $i18n->getHtml("[[palette.Yes]]") . '": function () {
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

            // Add hidden Modal for "confirm reboot" dialog:
            $page_body[] = '
                <div class="display_none">
                            <div id="dialog" class="dialog_content narrow no_dialog_titlebar" title="' . $i18n->getHtml("[[base-swupdate.downloadSoftware]]") . '">
                                <div class="block">
                                        <div class="section">
                                                <h1>' . $i18n->getHtml("[[base-swupdate.downloadSoftware]]") . '</h1>
                                                <div class="dashed_line"></div>
                                                <p>' . $i18n->getHtml("[[base-swupdate.rebootpopup]]") . '</p>
                                        </div>
                                </div>
                            </div>
                </div>';

            // Add "Install" button with the confirmation modal in place:
            $block->addButton($factory->getRawHTML("install", '<a class="lb" href="' . $action . '"><button class="no_margin_bottom div_icon tooltip hover dialog_button" title="' . $i18n->getHtml("install_help") . '"><div class="ui-icon ui-icon-wrench"></div><span>' . $i18n->getHtml("install") . '</span></button></a>'));

        }
        else {
            // Package doesn't require reboot. Add normal "Install" button:
            $installButtonNoDialog = $factory->getButton($action, "install");
            $installButtonNoDialog->setIcon("ui-icon-wrench");
            $block->addButton($installButtonNoDialog);
        }

        $block->addButton($factory->getCancelButton($backUrl));

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