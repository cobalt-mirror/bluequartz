<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class StatusFrame extends MX_Controller {

    /**
     * Index Page for this controller.
     *
     * Past the login page this loads the page for /swupdate/status.
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

        // Get $CI->BX_SESSION['sessionId'] and $CI->BX_SESSION['loginName'] from Cookie (if they are set):
        $CI->BX_SESSION['sessionId'] = $CI->input->cookie('sessionId');
        $CI->BX_SESSION['loginName'] = $CI->input->cookie('loginName');
        
        // Line up the ducks for CCE-Connection:
        include_once('ServerScriptHelper.php');
        $CI->serverScriptHelper = new ServerScriptHelper($CI->BX_SESSION['sessionId'], $CI->BX_SESSION['loginName']);
        $CI->cceClient = $CI->serverScriptHelper->getCceClient();
        $user = $CI->BX_SESSION['loginUser'];
        $i18n = new I18n("base-swupdate", $CI->BX_SESSION['loginUser']['localePreference']);
        $system = $CI->getSystem();

        // Initialize Capabilities so that we can poll the access rights as well:
        $Capabilities = new Capabilities($CI->cceClient, $CI->BX_SESSION['loginName'], $CI->BX_SESSION['sessionId']);

        // -- Actual page logic start:

        // Not 'managePackage'? Bye, bye!
        if (!$Capabilities->getAllowed('managePackage')) {
            // Nice people say goodbye, or CCEd waits forever:
            $CI->cceClient->bye();
            $CI->serverScriptHelper->destructor();
            Log403Error("/gui/Forbidden403#1");
        }

        // We start without errors:
        $errors = array();

        // Get URL params:
        $get_form_data = $CI->input->get(NULL, TRUE);

        if ((!isset($get_form_data['packageOID'])) || (!isset($get_form_data['backUrl']))) {
            // Nice people say goodbye, or CCEd waits forever:
            $CI->cceClient->bye();
            $CI->serverScriptHelper->destructor();
            Log403Error("/gui/Forbidden403#2");
        }

        $packageOID = $get_form_data['packageOID'];
        $backUrl = $get_form_data['backUrl'];

        //
        //--- Get CODB-Object of interest: 
        //

        if (!isset($get_form_data['A'])) {
            // "A" is set to "U" during uninstalls to skip the verification at
            // this stage. Because the shell script has already removed the Object:
            $package = $CI->cceClient->get($packageOID);
            if ((!is_array($package)) || ($package['CLASS'] != "Package")) {
                // OID isn't a Package or doesn't exist:
                // This could mean that the command line installer has run,
                // but the PKG didn't install for whatever reason. If so,
                // CODB Object "System" . "SWUpdate" will tell us the error
                // message:

                $swupdate = $CI->cceClient->get($system['OID'], "SWUpdate");

                if (($swupdate["progress"] == "100") && ($swupdate["uiCMD"] == "") && isset($swupdate["message"])) {
                    // When we are here, the install went to 100% and bailed due to success or failure.
                    // 'uiCMD' has been reset and 'message' contains the success or error message.
                    // So we raise an error message with the "message" we've got:
                    $errors[] = new CceError('huh', 0, 'uiCMD', $swupdate["message"]);

                    // We don't know the package name, but know the install failed.
                    // So we set a nameTag that's shown in the PagedBlock header and
                    // informs that the install didn't go through:
                    $package['nameTag'] = $i18n->get('[[base-swupdate.notInstalled]]');
                }
                else {
                    // If we get to this point, something else went wrong. So we bail to a 403.
                    // Nice people say goodbye, or CCEd waits forever:
                    $CI->cceClient->bye();
                    $CI->serverScriptHelper->destructor();
                    Log403Error("/gui/Forbidden403#3");
                }
            }
        }
        else {
            if (isset($get_form_data['nameField'])) {
                // We got passed a nameField on the URL, so we use it as nameTag:
                $package['nameTag'] = $get_form_data['nameField'];
            }
        }

        $swupdate = $CI->cceClient->get($system['OID'], "SWUpdate");
        $swupdate["progress"] = round( $swupdate["progress"] );
        $cmd = &$swupdate["uiCMD"];

        // Prepare Page:
        $newBackURL = "/swupdate/newSoftware";
        if (isset($get_form_data['A'])) {
            $newBackURL = "/swupdate/softwareList";
        }

        $factory = $CI->serverScriptHelper->getHtmlComponentFactory("base-swupdate", $newBackURL);
        $BxPage = $factory->getPage();
        $BxPage->setErrors(array()); // We do have an $errors array set, but intentionally don't use it as we show 'messages' anyway.
        $BxPage->setOutOfStyle(TRUE);
        $i18n = $factory->getI18n();

        // Check to see if we need to redirect to the download page:
        if (preg_match("/packageOID=([0-9]+)/i", $cmd, $reg)) {
            $oid = &$reg[1];
            $CI->cceClient->set($system['OID'], "SWUpdate",  array("uiCMD" => '', 'progress' => '100'));
            // Nice people say goodbye, or CCEd waits forever:
            $CI->cceClient->bye();
            $CI->serverScriptHelper->destructor();          
            header("Location: /swupdate/download?packageOID=$oid&backUrl=$backUrl");
            exit;
        }
        elseif ((strstr($cmd, 'refresh')) || (strstr($cmd, 'reboot'))) {

            $hideBackButton = FALSE;
            $newBackURL = "/swupdate/newSoftware";

            // Get message:
            if (strstr($cmd, 'uninstall')) {
                $hideBackButton = TRUE;
                $newBackURL = "/swupdate/softwareList";
                $message = $i18n->get("uninstallrefresh");
            }
            elseif (strstr($cmd, 'install')) {
                $message = $i18n->get("installrefresh");
            }
            elseif (strstr($cmd, 'reboot')) {
                $hideBackButton = TRUE;
                $CI->cceClient->set($system['OID'], "SWUpdate",  array("uiCMD" => '', 'progress' => '100'));
                $message = $i18n->get("rebooting");
            }

            //-- Generate page:

            // Set Menu items:
            $BxPage->setVerticalMenu('base_software');
            $BxPage->setVerticalMenuChild('base_softwareNew');
            $page_module = 'base_software';

            $defaultPage = "Basic";

            // Spacer at the top:
            $page_body[] = '<div><br></div>';

            $nameTag = $i18n->Interpolate($package['nameTag']);
            $block =& $factory->getPagedBlock("installStatus", array($defaultPage));
            $block->setCurrentLabel($factory->getLabel("installStatus", false, array("fileName" => $nameTag)));

            $block->addFormField(
              $factory->getTextField("statusField", $message, "r"),
              $factory->getLabel("statusField"),
              $defaultPage
            );

            // Stretch the PagedBlock() to a width of 720 pixels:
            $block->addFormField(
                $factory->getRawHTML("Spacer", '<IMG BORDER="0" WIDTH="720" HEIGHT="0" SRC="/libImage/spaceHolder.gif">'),
                $factory->getLabel("Spacer"),
                $defaultPage
            );

            // we need to propagate back the URL.
            $block->addFormField(
                $factory->getTextField("backbackUrl", $backUrl, ""),
                ""
            );

            if ($hideBackButton == TRUE) {
                $doneButton = $factory->getFreeSaveButton($newBackURL, "[[palette.done]]");
                $doneButton->setTarget("_top");
                $block->addButton($doneButton);
            }
            else {
                $CI->cceClient->set($system['OID'], "SWUpdate",  array("uiCMD" => '', 'progress' => '100'));
                // Redirect to backUrl after a delay:
                $BxPage->setExtraHeaders('<SCRIPT LANGUAGE="javascript">setTimeout("top.location.reload();", 7000);</SCRIPT>');
            }

            // Page body:
            $page_body[] = $block->toHtml();

            // Spacer at the bottom:
            $page_body[] = '<div><br></div>';

            // Out with the page:
            $BxPage->render($page_module, $page_body);
        }
        else {

            // Get message:
            $message = $i18n->interpolate($swupdate["message"]);

            //-- Generate page:

            // Set Menu items:
            $BxPage->setVerticalMenu('base_software');
            $BxPage->setVerticalMenuChild('base_softwareNew');
            $page_module = 'base_software';

            $defaultPage = "Basic";
            $newBackURL = "/swupdate/newSoftware";

            // Spacer at the top:
            $page_body[] = '<div><br></div>';

            $hideBackButton = FALSE;
            if (count($errors) != "0") {
                // We do have an active error, so the install/uninstall failed:
                $hideBackButton = FALSE;
            }
            if (($swupdate["message"] != "[[base-swupdate.packageAlreadyInstalled]]") && (count($errors) == "0")) {
                // Package is already installed:
                $hideBackButton = TRUE;
            }
            if (preg_match('/packageInstallSuccess/', $swupdate["message"], $matches, PREG_OFFSET_CAPTURE)) {
                // Install went fine:
                $hideBackButton = FALSE;
            }
            if (($swupdate["message"] == "[[base-swupdate.uninstalled]]") && (count($errors) == "0")) {
                // PKG uninstalled fine:
                $hideBackButton = FALSE;
            }
            if (($hideBackButton == TRUE) && (count($errors) == "0")) {
                // Add a refresh to pages if we don't have a BackButton and if we have no errors:
                $BxPage->setExtraHeaders('<SCRIPT LANGUAGE="javascript">setTimeout("window.location.reload();", 7000);</SCRIPT>');
            }
            if ((strstr($cmd, 'uninstall')) && ($swupdate["message"] != "[[base-swupdate.uninstalled]]")) {
                // Uninstall is running and has not yet finished:
                $hideBackButton = TRUE;
                $newBackURL = "/swupdate/softwareList";
                $message = $i18n->get("uninstallrefresh");
            }

            $nameTag = $i18n->Interpolate($package['nameTag']);
            $block =& $factory->getPagedBlock("installStatus", array($defaultPage));
            $block->setCurrentLabel($factory->getLabel("installStatus", false, array("fileName" => $nameTag)));

            $block->addFormField(
              $factory->getTextField("statusField", $message, "r"),
              $factory->getLabel("statusField"),
              $defaultPage
            );

            if ($hideBackButton == TRUE) {
                $block->addFormField(
                  $factory->getBar("progressField", $swupdate["progress"]),
                  $factory->getLabel("progressField"),
                  $defaultPage
                );
            }

            // Stretch the PagedBlock() to a width of 720 pixels:
            $block->addFormField(
                $factory->getRawHTML("Spacer", '<IMG BORDER="0" WIDTH="720" HEIGHT="0" SRC="/libImage/spaceHolder.gif">'),
                $factory->getLabel("Spacer"),
                $defaultPage
            );

            // we need to propagate back the URL.
            $block->addFormField(
                $factory->getTextField("backbackUrl", $backUrl, ""),
                ""
            );

            if ($hideBackButton == FALSE) {
                $doneButton = $factory->getFreeSaveButton($newBackURL, "[[palette.done]]");
                $doneButton->setTarget("_top");
                $block->addButton($doneButton);
            }

            // Page body:
            $page_body[] = $block->toHtml();

            // Spacer at the bottom:
            $page_body[] = '<div><br></div>';

            // Out with the page:
            $BxPage->render($page_module, $page_body);
        }

    }       
}
/*
Copyright (c) 2016 Michael Stauber, SOLARSPEED.NET
Copyright (c) 2016 Team BlueOnyx, BLUEONYX.IT
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