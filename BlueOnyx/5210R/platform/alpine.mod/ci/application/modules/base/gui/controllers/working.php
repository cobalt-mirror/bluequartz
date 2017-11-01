<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Working extends MX_Controller {

    /**
     * Index Page for this controller.
     *
     * Past the login page this loads the page for /gui/working. This loads the iFrame /gui/workFrame
     * which in itself runs the CCE Replay-Transactions.
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

        // Get $sessionId and $loginName from Cookie (if they are set) and store them in $CI->BX_SESSION:
        $CI->BX_SESSION['sessionId'] = $CI->input->cookie('sessionId');
        $CI->BX_SESSION['loginName'] = $CI->input->cookie('loginName');

        // Line up the ducks for CCE-Connection and store them for re-usability in $CI:
        include_once('ServerScriptHelper.php');
        $CI->serverScriptHelper = new ServerScriptHelper($CI->BX_SESSION['sessionId'], $CI->BX_SESSION['loginName']);
        $CI->cceClient = $CI->serverScriptHelper->getCceClient();

        $i18n = new I18n("base-swupdate", $CI->BX_SESSION['loginUser']['localePreference']);

        // Not 'manageSite'? Bye, bye!
        if (!$CI->serverScriptHelper->getAllowed('manageSite')) {
            // Nice people say goodbye, or CCEd waits forever:
            $CI->cceClient->bye();
            $CI->serverScriptHelper->destructor();
            Log403Error("/gui/Forbidden403#1");
        }

        // -- Actual page logic start:

        // Get URL params:
        $get_form_data = $CI->input->get(NULL, TRUE);

        if (!isset($get_form_data['statusId'])) {
            // Nice people say goodbye, or CCEd waits forever:
            $this->cceClient->bye();
            $this->serverScriptHelper->destructor();
            Log403Error("/gui/Forbidden403#2");
        }
        else {
            $statusId = $get_form_data['statusId'];
        }

        if (!isset($get_form_data['redirectUrl'])) {
            $redirectUrl = '/network/ethernet';
        }
        else {
            $redirectUrl = $get_form_data['redirectUrl'];
        }

        $known_redirect_types = array('ipv4', 'ipv6', 'hn', 'standard');
        if (!isset($get_form_data['redirectType'])) {
            $redirectType = 'standard';
        }
        else {
            $redirectType = $get_form_data['redirectType'];
        }
        if (!in_array($redirectType, $known_redirect_types)) {
            $redirectType = 'standard';
        }

        // Prepare Page:
        $errors = array();
        $factory = $this->serverScriptHelper->getHtmlComponentFactory("base-swupdate", "/gui/working");
        $BxPage = $factory->getPage();
        $BxPage->setErrors($errors);
        $i18n = $factory->getI18n();

        //-- Generate page:

        // Set Menu items:
        if ((!isset($get_form_data['VM'])) && (!isset($get_form_data['VMC'])) && (!isset($get_form_data['PM']))) {
            $BxPage->setVerticalMenu('base_serverconfig');
            $BxPage->setVerticalMenuChild('base_ethernet');
            $page_module = 'base_sysmanage';
        }
        else {
            $BxPage->setVerticalMenu($get_form_data['VM']);
            $BxPage->setVerticalMenuChild($get_form_data['VMC']);
            $page_module = $get_form_data['PM'];
        }

        // Assemble iFrame URL:
        $uri = "/gui/workFrame?statusId=" . $statusId . '&=redirectType=' . $redirectType . '&redirectUrl=' . $redirectUrl . '&redirectType=' . $redirectType;
        // Page body:
        $page_body[] = addInputForm(
                                        "&nbsp;",
                                        array("window" => $uri, "toggle" => "#"), 
                                        addIframe($uri, "auto", $BxPage),
                                        "",
                                        $i18n,
                                        $BxPage,
                                        $errors
                                    );


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