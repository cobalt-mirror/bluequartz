<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class PersonalEmail extends MX_Controller {

    /**
     * Index Page for this controller.
     *
     * Past the login page this loads the page for /user/personalEmail.
     *
     * This page runs entirely via AutoFeatures ('UserExtraServices').
     * Which allows us to later splice in email related add ons such 
     * as the pages for the AV-SPAM without additional menu entries.
     * Makes things a bit more seamless. Oh, and it hides the terrible
     * mess of extensions/User.Email/10_EmailSettings.php. That one 
     * works, but it's *really* ugly. Don't use it as example.
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

        $i18n = new I18n("base-user", $CI->BX_SESSION['loginUser']['localePreference']);
        $system = $CI->getSystem();

        //-- Handle form validation:

        // We start without any active errors:
        $errors = array();
        $ci_errors = array();
        $my_errors = array();

        // Shove submitted input into $form_data after passing it through the XSS filter:
        $form_data = $CI->input->post(NULL, TRUE);

        // If we have POST data, we run it through AutoFeatures:
        if ($CI->input->post(NULL, TRUE)) {

            // Handle AutoFeatures:
            $autoFeatures = new AutoFeatures($CI->serverScriptHelper, $form_data);
            list($userservices) = $CI->cceClient->find("UserExtraServices");
            $af_errors = $autoFeatures->handle("User.Email", array("CCE_SERVICES_OID" => $userservices, "CCE_OID" => $user['OID'], 'i18n' => $i18n), $form_data);
            $errors = array_merge($errors, $af_errors);

            // No errors during submit? Reload page:
            if (count($errors) == "0") {
                $CI->cceClient->bye();
                $CI->serverScriptHelper->destructor();
                $redirect_URL = "/user/personalEmail";
                header("location: $redirect_URL");
                exit;
            }
        }

        //
        //-- Generate page:
        //

        // Prepare Page:
        $factory = $CI->serverScriptHelper->getHtmlComponentFactory("base-user", "/user/personalEmail");
        $BxPage = $factory->getPage();
        $BxPage->setErrors($errors);
        $i18n = $factory->getI18n();

        // Set Menu items:
        $BxPage->setVerticalMenu('base_controlpanel');
        $page_module = 'base_personalProfile';

        // Set extra headers for fullcalendar and datepicker:
        $BxPage->setExtraHeaders('<script src="/gui/fullcalendar"></script>');
        $BxPage->setExtraHeaders('<script src="/gui/datepicker"></script>');

        // Find out which modules are active and use their names as Tab headers:
        $autoFeatures = new AutoFeatures($CI->serverScriptHelper);
        $TABs = array_values($autoFeatures->ListFeatures("User.Email"));

        // Configure the pagedBlock:
        $block =& $factory->getPagedBlock("emailSettingsFor", $TABs);
        $block->setLabel($factory->getLabel('[[base-user.emailSettingsFor]]', false, array("userName" => $CI->BX_SESSION['loginName'])));

        $block->setToggle("#");
        $block->setSideTabs(FALSE);
        $block->setShowAllTabs("#");
        $block->setDefaultPage($TABs[0]);


        //
        //--- Add AutoFeatures:
        //

        $autoFeatures = new AutoFeatures($CI->serverScriptHelper, $attributes);
        $cce_info = array('CCE_OID' => $user['OID'], 'FIELD_ACCESS' => 'rw');
        list($cce_info['CCE_SERVICES_OID']) = $CI->cceClient->find('UserExtraServices');
        $cce_info['PAGED_BLOCK_DEFAULT_PAGE'] = $TABs[0];
        $autoFeatures->display($block, 'User.Email', $cce_info);

        // Add the buttons
        $block->addButton($factory->getSaveButton($BxPage->getSubmitAction()));
        $block->addButton($factory->getCancelButton("/user/personalEmail"));

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