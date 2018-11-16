<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Telnet_amdetails extends MX_Controller {

    /**
     * Index Page for this controller.
     *
     * Past the login page this loads the page for /telnet/telnet_amdetails.
     *
     */

    public function index() {

        $CI =& get_instance();
        
        // We load the BlueOnyx helper library first of all, as we heavily depend on it:
        $this->load->helper('blueonyx');
        init_libraries();

        // Need to load 'BxPage' for page rendering:
        $this->load->library('BxPage');
        
        // Load AM Detail Helper:
        $this->load->helper('amdetail');

        // Get $CI->BX_SESSION['sessionId'] and $CI->BX_SESSION['loginName'] from Cookie (if they are set):
        $CI->BX_SESSION['sessionId'] = $CI->input->cookie('sessionId');
        $CI->BX_SESSION['loginName'] = $CI->input->cookie('loginName');

        // Line up the ducks for CCE-Connection:
        include_once('ServerScriptHelper.php');
        $CI->serverScriptHelper = new ServerScriptHelper($CI->BX_SESSION['sessionId'], $CI->BX_SESSION['loginName']);
        $CI->cceClient = $CI->serverScriptHelper->getCceClient();
        $user = $CI->BX_SESSION['loginUser'];
        $i18n = new I18n("base-telnet", $CI->BX_SESSION['loginUser']['localePreference']);
        $system = $CI->getSystem();

        // Initialize Capabilities so that we can poll the access rights as well:
        $Capabilities = new Capabilities($CI->cceClient, $CI->BX_SESSION['loginName'], $CI->BX_SESSION['sessionId']);

        // -- Actual page logic start:

        // Not 'serverShowActiveMonitor'? Bye, bye!
        if (!$Capabilities->getAllowed('serverShowActiveMonitor')) {
            // Nice people say goodbye, or CCEd waits forever:
            $CI->cceClient->bye();
            $CI->serverScriptHelper->destructor();
            Log403Error("/gui/Forbidden403");
        }

        // -- Actual page logic start:

        // We start without any active errors:
        $errors = array();
        $extra_headers =array();
        $ci_errors = array();
        $my_errors = array();

        // Find out if we display without menu or with menu:
        $get_form_data = $CI->input->get(NULL, TRUE);
        $fancy = FALSE;
        if ($get_form_data['short'] == "1") {
            $fancy = TRUE;
        }

        // Prepare Page:
        $factory = $CI->serverScriptHelper->getHtmlComponentFactory("base-telnet");
        $BxPage = $factory->getPage();
        $i18n = $factory->getI18n();

        // Set Menu items:
        $BxPage->setVerticalMenu('base_monitor');
        $BxPage->setVerticalMenuChild('base_amStatus');
        if ($fancy == TRUE) {       
            $BxPage->setOutOfStyle(TRUE);
        }
        $page_module = 'base_sysmanage';
        $defaultPage = "basicSettingsTab";

        if ($fancy == TRUE) {
            $page_body[] = '<br><div id="main_container" class="container_16">';
        }

        //
        //--- Print Detail Block:
        //

        $page_body[] = am_detail_block($factory, $CI->cceClient, "Telnet", "[[base-telnet.amDetailsTitle]]");

        if ($fancy == TRUE) {
            $page_body[] = '</div>';
        }
        else {
            // Full page display. Show "Back" Button:
            $page_body[] = am_back($factory);
        }

        // Out with the page:
        $BxPage->setErrors($errors);
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