<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Console_logfiles extends MX_Controller {

    /**
     * Index Page for this controller.
     *
     * Past the login page this loads the page for /console/console_logfiles.
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

        $i18n = new I18n("base-console", $CI->BX_SESSION['loginUser']['localePreference']);
        $system = $CI->getSystem();
        $user = $CI->BX_SESSION['loginUser'];

        // Not 'serverConfig'? Bye, bye!
        if (!$CI->serverScriptHelper->getAllowed('serverConfig')) {
            // Nice people say goodbye, or CCEd waits forever:
            $CI->cceClient->bye();
            $CI->serverScriptHelper->destructor();
            Log403Error("/gui/Forbidden403");
        }

        //
        //-- Generate page:
        //

        // Prepare Page:
        $factory = $CI->serverScriptHelper->getHtmlComponentFactory("base-console", "/console/console_logfiles");
        $BxPage = $factory->getPage();
        $i18n = $factory->getI18n();

        // Set Menu items:
        $BxPage->setVerticalMenu('base_security');
        $BxPage->setVerticalMenuChild('base_console_logfiles');
        $page_module = 'base_sysmanage';

        //
        //--- Basic Tab
        //

        $a_button = $factory->getFancyButton("/console/console_logfile_viewer?type=1", '/var/log/cron', "DEMO-OVERRIDE");
        $b_button = $factory->getFancyButton("/console/console_logfile_viewer?type=2", '/var/log/maillog', "DEMO-OVERRIDE");
        $c_button = $factory->getFancyButton("/console/console_logfile_viewer?type=3", '/var/log/messages', "DEMO-OVERRIDE");
        $d_button = $factory->getFancyButton("/console/console_logfile_viewer?type=4", '/var/log/secure', "DEMO-OVERRIDE");
        $buttonContainer_a = $factory->getButtonContainer("", array($a_button, $b_button, $c_button, $d_button));

        $e_button = $factory->getFancyButton("/console/console_logfile_viewer?type=5", '/var/log/httpd/access_log', "DEMO-OVERRIDE");
        $f_button = $factory->getFancyButton("/console/console_logfile_viewer?type=6", '/var/log/httpd/error_log', "DEMO-OVERRIDE");
        $buttonContainer_b = $factory->getButtonContainer("", array($e_button, $f_button));

        $g_button = $factory->getFancyButton("/console/console_logfile_viewer?type=7", '/var/log/admserv/adm_access', "DEMO-OVERRIDE");
        $h_button = $factory->getFancyButton("/console/console_logfile_viewer?type=8", '/var/log/admserv/adm_error', "DEMO-OVERRIDE");
        $buttonContainer_c = $factory->getButtonContainer("", array($g_button, $h_button));

        $page_body[] = $buttonContainer_a->toHtml();
        $page_body[] = $buttonContainer_b->toHtml();
        $page_body[] = $buttonContainer_c->toHtml();

        // Out with the page:
        $BxPage->render($page_module, $page_body);

    }       
}
/*
Copyright (c) 2015 Michael Stauber, SOLARSPEED.NET
Copyright (c) 2015 Team BlueOnyx, BLUEONYX.IT
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