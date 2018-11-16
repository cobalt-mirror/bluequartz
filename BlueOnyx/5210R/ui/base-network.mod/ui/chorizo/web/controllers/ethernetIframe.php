<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class EthernetIframe extends MX_Controller {

    /**
     * Index Page for this controller.
     *
     * Past the login page this loads the page for /network/ethernetIframe.
     *
     * This page loads the CCEd replay file and deploys the stored CCEd transactions
     * that perform the network changes. It does so under a Meta-Refresh, which
     * hopefully will redirect to a working IP.
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
        $i18n = new I18n("base-network", $CI->BX_SESSION['loginUser']['localePreference']);
        $system = $CI->getSystem();

        // Array to store CCEd transactions for later replay:
        $cce_replay = array();

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

        // Protect certain form fields read-only inside VPS's:
        if (is_file("/procx/user_beancounters")) {
            $fieldprot = "r";
        }
        else {
            $fieldprot = "rw";
        }

        // Are we running on AWS?
        if (is_file("/etc/is_aws")) {
            $is_aws = "1";
        }
        else {
            $is_aws = "0";
        }

        $redirect = "";

        //
        //--- Get CODB-Object of interest: 
        //

        $system = $CI->getSystem();

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

        //
        //-- Own page logic:
        //

        //
        //-- Generate page:
        //

        // Prepare Page:
        $factory = $CI->serverScriptHelper->getHtmlComponentFactory("base-network", "/network/ethernetDeploy");
        $BxPage = $factory->getPage();

        // Get errorMsg from URL string.
        $get_form_data = $CI->input->get(NULL, TRUE);
        if (isset($get_form_data['errorMsg'])) {
            $errors[] = @json_decode(urldecode($get_form_data['errorMsg']));
        }

        // Get new IP's from URL params:
        $ipv4 = '';
        $ipv6 = '';
        if (isset($get_form_data['ipv4'])) {
            $ipv4 = $get_form_data['ipv4'];
        }
        if (isset($get_form_data['ipv6'])) {
            $ipv6 = $get_form_data['ipv6'];
        }

        $BxPage->setOutOfStyle('yes');
        $BxPage->setErrors(array());
        $i18n = $factory->getI18n();

        $product = new Product($CI->cceClient);

        // Set Menu items:
        $BxPage->setVerticalMenu('base_serverconfig');
        $BxPage->setVerticalMenuChild('base_ethernet');
        $page_module = 'base_sysmanage';

        $default_page = 'primarySettings';
        $block =& $factory->getPagedBlock("[[palette.wait]]", $default_page);

        $block->setToggle("#");
        $block->setSideTabs(FALSE);
        //$block->setShowAllTabs("#");
        $block->setDefaultPage($default_page);

        $framebreak = '<script language="JavaScript" type="text/javascript">' . "\n";
        $framebreak .= '<!--' . "\n";
        $framebreak .= '    top.location.href = "/network/ethernet";' . "\n";
        $framebreak .= '-->' . "\n";
        $framebreak .= '</script>' . "\n";
        $BxPage->setExtraHeaders($framebreak);

        $BxPage->setExtraBodyTag('<body onload=top.location.href=\'/network/ethernet\'>');

        if (($CI->cceClient->replayStatus() > '-1') ) {

            // If we're done sooner than the meta-equif in ethernetDeploy, then we try to break out of the iframe for an early return:
            $redirect_meta = '<meta http-equiv="refresh" content="0; URL=javascript:window.open(\'/network/ethernet\',\'_top\');">';

            $CI->cceClient->replay();
        }
        else {
            $block->addFormField(
              $factory->getTextField("info", 'No CCE-Replay-File found!', 'r'),
              $factory->getLabel("[[palette.messageField]]"),
              $default_page
            );
            // If we're done sooner than the meta-equif in ethernetDeploy, then we try to break out of the iframe for an early return:
            $redirect_meta = '<meta http-equiv="refresh" content="1; URL=javascript:window.open(\'/network/ethernet\',\'_top\');">';
        }

        // add a hidden field:
        $block->addFormField($factory->getTextField('dummy', 'dummy', ''));

        $page_body[] = $block->toHtml();

        // Out with the page:
        $BxPage->render($page_module, $page_body);

    }       
}

/*
Copyright (c) 2014-2017 Michael Stauber, SOLARSPEED.NET
Copyright (c) 2014-2017 Team BlueOnyx, BLUEONYX.IT
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