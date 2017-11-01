<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class WorkFrame extends MX_Controller {

    /**
     * Index Page for this controller.
     *
     * Past the login page this loads the page for /gui/workFrame, which reads the CCE Replay-File,
     * performs one transaction and reloads itself (showing a progress bar) until all transactions are done.
     * Once all transactions are done, it frame-breaks and goes to a return-URL.
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

        $system = $CI->getSystem();

        // We start without errors:
        $errors = array();

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

        $newBackURL = "/network/ethernet";

        // Prepare Page:
        $factory = $this->serverScriptHelper->getHtmlComponentFactory("palette", $newBackURL);
        $BxPage = $factory->getPage();
        $BxPage->setErrors(array()); // We do have an $errors array set, but intentionally don't use it as we show 'messages' anyway.
        $BxPage->setOutOfStyle(TRUE);
        $i18n = $factory->getI18n("palette");

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

        // Spacer at the top:
        $page_body[] = '<div><br></div>';

        // When the title is empty, we use a blank default:
        if (!isset($title)) {
            $title = "[[palette.wait]]";
        }
        $defaultPage = $title;

        //
        //--- Start: Handle CCE-Replay:
        //

        $num_of_trans = $CI->cceClient->replayStatus();
        if (($num_of_trans < '1') || (!is_int($num_of_trans))) {
            $num_of_trans = '1';
        }
        $progress = ceil('100' / $num_of_trans);

        // Perform first replay from Replay-File:
        $CI->cceClient->replay("stepByStep");

        // If there are no more replays in the file after this, then we insert the header to redirect back to our desired URL:
        $num_of_trans = $CI->cceClient->replayStatus();
        if ($num_of_trans <= '0') {

            // Puzzle the redirect URL together:
            if ($redirectType == 'ipv4') {
                $our_redirect_URL = $redirectUrl;
                $NIC = $CI->cceClient->getObject('Network', array('device' => 'eth0'));
                if (isset($NIC['ipaddr'])) {
                    if (is_HTTPS() == FALSE) {
                        $our_redirect_URL = 'http://' . $NIC['ipaddr'] . ':444' . $redirectUrl;
                    }
                    else {
                        $our_redirect_URL = 'https://' . $NIC['ipaddr'] . ':81' . $redirectUrl;
                    }
                }
                else {
                    // Fallback:
                    $our_redirect_URL = $redirectUrl;
                }
            }
            elseif ($redirectType == 'ipv6') {
                $our_redirect_URL = $redirectUrl;
                $NIC = $CI->cceClient->getObject('Network', array('device' => 'eth0'));
                if (isset($NIC['ipaddr_IPv6'])) {
                    if (is_HTTPS() == FALSE) {
                        $our_redirect_URL = 'http://[' . $NIC['ipaddr_IPv6'] . ']:444' . $redirectUrl;
                    }
                    else {
                        $our_redirect_URL = 'https://[' . $NIC['ipaddr_IPv6'] . ']:81' . $redirectUrl;
                    }
                }
                else {
                    // Fallback:
                    $our_redirect_URL = $redirectUrl;
                }
            }
            elseif ($redirectType == 'hn') {
                $our_redirect_URL = $redirectUrl;
                $servername = $system['hostname'] . '.' . $system['domainname'];
                if (is_HTTPS() == FALSE) {
                    $our_redirect_URL = 'http://' . $servername . ':444' . $redirectUrl;
                }
                else {
                    $our_redirect_URL = 'https://' . $servername . ':81' . $redirectUrl;
                }
            }
            else {
                // Default:
                $our_redirect_URL = $redirectUrl;
            }

            // Assemble framebreaker-Script:
            $framebreak = '<script language="JavaScript" type="text/javascript">' . "\n";
            $framebreak .= '<!--' . "\n";
            $framebreak .= '    top.location.href = "' . $our_redirect_URL . '";' . "\n";
            $framebreak .= '-->' . "\n";
            $framebreak .= '</script>' . "\n";
            $BxPage->setExtraHeaders($framebreak);
            $BxPage->setExtraBodyTag('<body onload=top.location.href=\'' . $our_redirect_URL . '\'>');
        }
        else {
            // Add a refresh to reload this page:
            $BxPage->setExtraHeaders('<SCRIPT LANGUAGE="javascript">setTimeout("window.location.reload();", 7000);</SCRIPT>');
        }

        //
        //--- End: Handle CCE-Replay
        //

        $block =& $factory->getPagedBlock($i18n->getHtml($title), array($defaultPage));

        if (isset($message)) {
            // Make sure the $message is not empty!
            if ($message == "") {
                $message = "[[palette.500text]]";
            }

            $block->addFormField(
              $factory->getTextField("messageField", $i18n->interpolate($message), "r"),
              $factory->getLabel("messageField"),
              $defaultPage
            );
        }

        if (isset($progress)) {
            if ($progress != "") {
              $block->addFormField(
                $factory->getBar("progressField", $progress),
                $factory->getLabel("progressField"),
                $defaultPage
              );
            }
        }

        // add sub-status if it is supplied
        if (isset($submessage)) {
            if ($submessage != "") {
              $block->addFormField(
                $factory->getTextField("submessageField", $i18n->interpolate($submessage), "r"),
                $factory->getLabel("submessageField"),
                $defaultPage
              );
            }
        }

        if (isset($subprogress)) {
            if($subprogress != "") {
              $block->addFormField(
                $factory->getBar("subprogressField", $subprogress),
                $factory->getLabel("subprogressField"),
                $defaultPage
              );
            }
        }
        //--

        // Stretch the PagedBlock() to a width of 720 pixels:
        $block->addFormField(
            $factory->getRawHTML("Spacer", '<IMG BORDER="0" WIDTH="720" HEIGHT="0" SRC="/libImage/spaceHolder.gif">'),
            $factory->getLabel("Spacer"),
            $defaultPage
        );


        // Page body:
        $page_body[] = $block->toHtml();

        // Spacer at the bottom:
        $page_body[] = '<div><br></div>';

        // Out with the page:
        $BxPage->render($page_module, $page_body);

    }       
}
/*
Copyright (c) 2015-2017 Michael Stauber, SOLARSPEED.NET
Copyright (c) 2015-2017 Team BlueOnyx, BLUEONYX.IT
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