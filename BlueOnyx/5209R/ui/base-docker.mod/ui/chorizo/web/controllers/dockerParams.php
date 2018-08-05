<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');


class DockerParams extends MX_Controller {

    /**
     * Index Page for this controller.
     *
     * Past the login page this loads the page for the docker access.
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

        $cookie = array('name' => 'docker', 'path' => '/', 'value' => $CI->BX_SESSION['sessionId'], 'expire' => '0');
        $this->input->set_cookie($cookie);

        // Line up the ducks for CCE-Connection:
        include_once('ServerScriptHelper.php');
        $CI->serverScriptHelper = new ServerScriptHelper($CI->BX_SESSION['sessionId'], $CI->BX_SESSION['loginName']);
        $CI->cceClient = $CI->serverScriptHelper->getCceClient();
        $user = $CI->BX_SESSION['loginUser'];
        $i18n = new I18n("base-docker", $CI->BX_SESSION['loginUser']['localePreference']);
        $system = $CI->getSystem();
        $systemDocker = $CI->cceClient->get($system['OID'], "Docker");

        // Initialize Capabilities so that we can poll the access rights as well:
        $Capabilities = new Capabilities($CI->cceClient, $CI->BX_SESSION['loginName'], $CI->BX_SESSION['sessionId']);

        // No 'serverNetwork' access? Bye, bye!
        if (!$Capabilities->getAllowed('serverNetwork')) {
            // Nice people say goodbye, or CCEd waits forever:
            $CI->cceClient->bye();
            $CI->serverScriptHelper->destructor();
            Log403Error("/gui/Forbidden403");
        }

        // Get URL strings:
        $get_form_data = $CI->input->get(NULL, TRUE);

        $prep_image = '';
        if (isset($get_form_data['prep'])) {
            $prep_image = urldecode($get_form_data['prep']);
        }

        $my_errors = array();
        if (isset($get_form_data['error'])) {
            $my_errors[] = ErrorMessage($i18n->get("[[base-docker.ErrorImagePrep]]"));
        }

        // Required array setup:
        $extra_headers = array();

        //
        //--- DockerLibs integration:
        //

        include_once('/usr/sausalito/ui/chorizo/ci/application/modules/base/docker/controllers/DockerLibs.php');
        $DockerLibs = new DockerLibs($CI->serverScriptHelper, $CI->cceClient, $CI->BX_SESSION['loginName'], $CI->BX_SESSION['sessionId']);
        $DockerImages = $DockerLibs->GetDockerImages();

        //
        //--- Handle form validation:
        //

        // We start without any active errors:
        $errors = array();
        $ci_errors = array();

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
            $attributes = GetFormAttributes($i18n, $form_data, $required_keys, $ignore_attributes);
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
            if ((isset($attributes['Image'])) && (isset($attributes['Name'])) && (isset($attributes['LaunchParameters']))) {
                $DockerRun = $DockerLibs->RunDockerImage($attributes['Image'], $attributes['LaunchParameters'], $attributes['Name']);
                if ($DockerRun != "0") {
                    $errors[] = $DockerRun;
                }
                else {
                    header('Location: /docker/dockerList');
                }
            }
            $systemDocker = $CI->cceClient->get($system['OID'], "Docker");
        }

        //
        //-- Generate page:
        //

        // Prepare Page:
        $url_params = '';
        if (isset($attributes['Image'])) {
            $url_params .= '?prep=' . $attributes['Image'];
            $prep_image = $attributes['Image'];
        }

        $prep_name = '';
        if (isset($attributes['Name'])) {
            $prep_name = $attributes['Name'];
        }
        else {
            $prep_name = $prep_image;
        }

        $LaunchParameters = '';
        if (isset($attributes['LaunchParameters'])) {
            $LaunchParameters = $attributes['LaunchParameters'];
        }

        $factory = $CI->serverScriptHelper->getHtmlComponentFactory("base-docker", "/docker/dockerParams$url_params");
        $BxPage = $factory->getPage();
        $BxPage->setErrors($errors);
        $i18n = $factory->getI18n();

        // Set Menu items:
        $BxPage->setVerticalMenu('base_sysmanage');
        $page_module = 'base_sysmanage';
        if ($system['productBuild'] == "6109R") {
            $BxPage->setVerticalMenu('base_sitemanageVSL');
            $page_module = 'base_sitemanageVSL';
        }
        $BxPage->setVerticalMenuChild('docker_Images');
        $defaultPage = "basicSettingsTab";

        $block =& $factory->getPagedBlock("dockerInstanceCreate", array($defaultPage));

        $block->setToggle("#");
        $block->setSideTabs(FALSE);
        $block->setDefaultPage($defaultPage);

        if ($systemDocker['enabled'] == "0") {
                $disabled_TEXT = "<div class='flat_area grid_16'><br>" . $i18n->getClean("[[base-docker.enabledField_help]]") . "</div>";
                $disabledtext = $factory->getHtmlField("admin_text", $disabled_TEXT, 'r');
                $disabledtext->setLabelType("nolabel");
                $block->addFormField(
                  $disabledtext,
                  $factory->getLabel(" ", false),
                  $defaultPage
                );
        }
        else {

            $block->addFormField(
                $factory->getTextField('Image', $prep_image, 'r'),
                $factory->getLabel('Image'),
                $defaultPage
                );

            $set_name = $factory->getTextField('Name', $prep_name, 'rw');
            $set_name->setOptional(TRUE);
            $block->addFormField(
                $set_name,
                $factory->getLabel('Name'),
                $defaultPage
                );

            $launch_params = $factory->getTextList("LaunchParameters", $LaunchParameters, 'rw');
            $launch_params->setOptional(TRUE);
            //$launch_params->setType('alphanum_plus_space_multiline');
            $block->addFormField(
                $launch_params,
                $factory->getLabel("LaunchParameters"),
                $defaultPage
                );            

        }

        // Add the buttons
        $block->addButton($factory->getSaveButton($BxPage->getSubmitAction()));
        $block->addButton($factory->getCancelButton("/docker/dockerImages"));

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