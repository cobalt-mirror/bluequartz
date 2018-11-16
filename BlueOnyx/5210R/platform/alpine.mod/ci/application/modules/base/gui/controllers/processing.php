<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Processing extends MX_Controller {

    /**
     * Index Page for this controller.
     *
     * Past the login page this loads the page for /gui/processing.
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

        // Not 'serverEmail'? Bye, bye!
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

        // Start sane:
        $title = "";
        $message = "";
        $progress = "";
        $submessage = "";
        $subprogress = "";
        $backUrl = "";
        $cancelUrl = "";
        $redirectUrl = "";
        $isNoRefresh = "";
        $isNoGarbageCollect = "";

        if (isset($get_form_data['title'])) {
            $title = $get_form_data['title'];
        }
        if (isset($get_form_data['message'])) {
            $message = $get_form_data['message'];
        }
        if (isset($get_form_data['progress'])) {
            $progress = $get_form_data['progress'];
        }
        if (isset($get_form_data['submessage'])) {
            $submessage = $get_form_data['submessage'];
        }
        if (isset($get_form_data['subprogress'])) {
            $subprogress = $get_form_data['subprogress'];
        }
        if (isset($get_form_data['backUrl'])) {
            $backUrl = $get_form_data['backUrl'];
        }
        if (isset($get_form_data['cancelUrl'])) {
            $cancelUrl = $get_form_data['cancelUrl'];
        }
        if (isset($get_form_data['redirectUrl'])) {
            $redirectUrl = $get_form_data['redirectUrl'];
        }
        if (isset($get_form_data['isNoRefresh'])) {
            $isNoRefresh = $get_form_data['isNoRefresh'];
        }
        if (isset($get_form_data['isNoGarbageCollect'])) {
            $isNoGarbageCollect = $get_form_data['isNoGarbageCollect'];
        }

        // Get the full path of the status file
        include_once("System.php");
        $system = new System();
        $statusDir = $system->getConfig("statusDir");
        $statusPath = "$statusDir/$statusId";

        // initialize status file if these variables exist
        if (!isset($title) || !isset($message) || !isset($progress) || !isset($submessage) || !isset($subprogress) || !isset($backUrl) || !isset($cancelUrl) || !isset($redirectUrl) || !isset($isNoRefresh) || !isset($isNoGarbageCollect)) {
            // make sure directory exist
            if(!is_dir($statusDir)) {
                mkdir($statusDir, 0755);
            }

            $handle = fopen($statusPath, "w");
            fwrite($handle, "title: $title\n");
            fwrite($handle, "message: $message\n");
            fwrite($handle, "progress: $progress\n");
            fwrite($handle, "submessage: $submessage\n");
            fwrite($handle, "subprogress: $subprogress\n");
            fwrite($handle, "backUrl: $backUrl\n");
            fwrite($handle, "cancelUrl: $cancelUrl\n");
            fwrite($handle, "redirectUrl: $redirectUrl\n");
            fwrite($handle, "isNoRefresh: $isNoRefresh\n");
            fwrite($handle, "isNoGarbageCollect: $isNoGarbageCollect\n");
            fclose($handle);
        }
        else {
            // initialize values because they may not be obtained from the status file
            $title = "";
            $message = "";
            $progress = "";
            $submessage = "";
            $subprogress = "";
            $backUrl = "";
            $cancelUrl = "";
            $redirectUrl = "";
            $isNoRefresh = "false";
            $isNoGarbageCollect = "false";

            if(is_file($statusPath)) {
                // read status file
                $handle = fopen($statusPath, "r");
                while(!feof($handle)) {
                    $line = fgets($handle, 1024);

                    // skip empty lines
                    if(strlen($line) == 0) continue;

                    // skip comments
                    if(preg_match("/^\s*\#/", $line)) continue;

                    // read all the values
                    if (preg_match("/^title:\s(.+)$/", $line, $matches)) {
                        $title = $matches[1];
                    }
                    if (preg_match("/^message:\s(.+)$/", $line, $matches)) {
                        $message = $matches[1];
                    }
                    if (preg_match("/^progress:\s(.+)$/", $line, $matches)) {
                        $progress = $matches[1];
                    }
                    if (preg_match("/^submessage:\s(.+)$/", $line, $matches)) {
                        $submessage = $matches[1];
                    }
                    if (preg_match("/^subprogress:\s(.+)$/", $line, $matches)) {
                        $subprogress = $matches[1];
                    }
                    if (preg_match("/^backUrl:\s(.+)$/", $line, $matches)) {
                        $backUrl = $matches[1];
                    }
                    if (preg_match("/^cancelUrl:\s(.+)$/", $line, $matches)) {
                        $cancelUrl = $matches[1];
                    }
                    if (preg_match("/^redirectUrl:\s(.+)$/", $line, $matches)) {
                        $redirectUrl = $matches[1];
                    }
                    if (preg_match("/^isNoRefresh:\s(.+)$/", $line, $matches)) {
                        $isNoRefresh = $matches[1];
                    }
                    if (preg_match("/^isNoGarbageCollect:\s(.+)$/", $line, $matches)) {
                        $isNoGarbageCollect = $matches[1];
                    }
                }
                fclose($handle);
            }
        }

        // Garbage collect
        if ($isNoRefresh == "true" && $isNoGarbageCollect != "true") {
            unlink($statusPath);
        }

        // Redirect
        if ($redirectUrl != "") {
            // Nice people say goodbye, or CCEd waits forever:
            $this->cceClient->bye();
            $this->serverScriptHelper->destructor();
            header("location: $redirectUrl");
            return;
        }

        // Prepare Page:
        $errors = array();
        $factory = $this->serverScriptHelper->getHtmlComponentFactory("base-swupdate", "/gui/processing");
        $BxPage = $factory->getPage();
        $BxPage->setErrors($errors);
        $i18n = $factory->getI18n();

        //-- Generate page:

        // Set Menu items:
        $BxPage->setVerticalMenu('base_siteList1');
        $page_module = 'base_sitemanageVSL';

        // Assemble iFrame URL:
        $uri = "/gui/processFrame?statusId=" . $statusId . "&title=" . $title . "&message=" . $message . "&progress=" . $progress . "&submessage=" . $submessage . "&subprogress=" . $subprogress . "&backUrl=" . $backUrl . "&cancelUrl=" . $cancelUrl . "&redirectUrl=" . $redirectUrl . "&isNoRefresh=" . $isNoRefresh . "&isNoGarbageCollect=" . $isNoGarbageCollect;
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