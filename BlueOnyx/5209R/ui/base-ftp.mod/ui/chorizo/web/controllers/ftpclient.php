<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Ftpclient extends MX_Controller {

    /**
     * Index Page for this controller.
     *
     * Past the login page this loads the page for /ftp/ftpclient.
     *
     */

    function encryptPassword($password) {
        $CI =& get_instance();
        $password_encrypted = "";
        $encryption_string = sha1($CI->config->config['encryption_key']);
        if ($encryption_string % 2 == 1) { // we need even number of characters
            $encryption_string .= $encryption_string{0};
        }
        for ($i=0; $i < strlen($password); $i++) { // encrypts one character - two bytes at once
            $password_encrypted .= sprintf("%02X", hexdec(substr($encryption_string, 2*$i % strlen($encryption_string), 2)) ^ ord($password{$i}));
        }
        return $password_encrypted;
    }

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

        $i18n = new I18n("base-ftp", $CI->BX_SESSION['loginUser']['localePreference']);
        $system = $CI->getSystem();
        $user = $CI->BX_SESSION['loginUser'];

        // Initialize Capabilities so that we can poll the access rights as well:
        $Capabilities = new Capabilities($CI->cceClient, $CI->BX_SESSION['loginName'], $CI->BX_SESSION['sessionId']);

        // Get URL strings:
        $get_form_data = $CI->input->get(NULL, TRUE);
        if (isset($get_form_data['group'])) {
            // We have a group:
            $group = $get_form_data['group'];

            // ACL checks:
            if ((!$Capabilities->getAllowed('systemAdministrator')) && ($group == 'server')) {
                // Not siteAdmin, but trying to reach 'System Management' entry:
                Log403Error("/ftp/ftpclient");
            }
            if ((!$Capabilities->getGroupAdmin($group)) && ($group != 'server')) {
                // Is Reseller, but tries to reach a Vsite that he doesn't own to.
                Log403Error("/ftp/ftpclient");
            }

        }

        $fullscreen = '0';
        if (isset($get_form_data['fullscreen'])) {
            if ($get_form_data['fullscreen'] == '1') {
                $fullscreen = '1';
            }
        }

        // Get Auth-token and generate encrypted FTP/SSH password:
        $this->load->library('encrypt');
        $authkey = $this->encrypt->decode($CI->input->cookie('XSSkey'));
        $ftpPass = Ftpclient::encryptPassword($authkey);

        //
        // Do we use FTP, SSH or is none available for this user due to ACL or services configuration?
        //

        // Check FTP state:
        $ServerFTP = $CI->cceClient->get($system['OID'], "Ftp");
        $FTP_Available = '0';
        if (($ServerFTP['enabled'] == '1') || ($ServerFTP['ftpsEnabled'] == '1')) {
            $FTP_Available = '1';
        }

        // Check SSH state:
        $SSH_Available = '0';
        $ServerSSH = $CI->cceClient->get($system['OID'], "SSH");
        if (($ServerSSH['enabled'] == '1') && ($ServerSSH['XPasswordAuthentication'] == '1')) {
            $SSH_Available = '1';
        }

        // Check if current User has Shell:
        $User_Shell = $CI->cceClient->get($user['OID'], 'Shell'); // Item of interest: $User_Shell['enabled'] = 0|1

        // Get FTP settings for this site:
        if ($user['site'] != "") {
            $Vsite_FTP = $CI->cceClient->getObject("Vsite", array("name" => $user['site']), "FTPNONADMIN"); // Item of interest: $Vsite_FTP['enabled'] = 0|1
            $Vsite_Shell = $CI->cceClient->getObject("Vsite", array("name" => $user['site']), "Shell");     // Item of interest: $Vsite_Shell['enabled'] = 0|1
            if (($User_Shell['enabled'] == '1') && ($Vsite_Shell['enabled'] == '0')) {
                $User_Shell['enabled'] = '0';
            }
        }
        elseif (isset($group)) {
            $Vsite_FTP = $CI->cceClient->getObject("Vsite", array("name" => $group), "FTPNONADMIN"); // Item of interest: $Vsite_FTP['enabled'] = 0|1
            $Vsite_Shell = $CI->cceClient->getObject("Vsite", array("name" => $group), "Shell");     // Item of interest: $Vsite_Shell['enabled'] = 0|1
            if (($User_Shell['enabled'] == '1') && ($Vsite_Shell['enabled'] == '0')) {
                $User_Shell['enabled'] = '0';
            }
        }
        else {
            $Vsite_FTP['enabled'] = '0';
            $Vsite_Shell['enabled'] = '0';
        }

        //
        //--- Privilege Check:
        //

        $service_available = '0';

        if ($Capabilities->getAllowed('systemAdministrator')) {
            //
            //-- User is systemAdministrator
            //
        }
        elseif ((!$Capabilities->getAllowed('systemAdministrator')) && ($Capabilities->getAllowed('adminUser'))) {
            //
            //-- User is Reseller
            //
            $service_available = '0';
        }
        elseif ($Capabilities->getAllowed('siteAdmin')) {
            // Check if that's the same group he requested access to:
            if (isset($get_form_data['group'])) {
                if ($user["site"] != $get_form_data['group']) {
                    // Sneaky Bastard:
                    Log403Error("/ftp/ftpclient");
                }
            }
            //
            //-- User is siteAdmin
            //
        }
        else {
            // Check if that's the same group he requested access to:
            if (isset($get_form_data['group'])) {
                if ($user["site"] != $get_form_data['group']) {
                    // Sneaky Bastard:
                    Log403Error("/ftp/ftpclient");
                }
            }
            //
            //-- User is regular User
            //

            // Check if Shell or FTP are enabled for this User:
            if (($User_Shell['enabled'] == '0') && ($Vsite_FTP['enabled'] == '0')) {
                $service_available = '0';
            }
        }

        //
        //--- Determine what kind of access (FTP or SSH) we now perform:
        //

        $use_SSH = '0';
        $use_FTP = '0';
        if ((($User_Shell['enabled'] == '1') && ($SSH_Available == '1')) && ($Capabilities->getAllowed('systemAdministrator'))) {
            $use_SSH = '1';
            $service_available = '1';
            $protocol = 'FTP-SSH';
            $port = $ServerSSH['Port'];
        }
        elseif ((($User_Shell['enabled'] == '0') && ($FTP_Available == '1')) && ($Capabilities->getAllowed('systemAdministrator'))) {
            $use_FTP = '1';
            $service_available = '1';
            $protocol = 'FTP';
            $port = '21';
        }
        elseif (($Capabilities->getAllowed('manageSite')) || ($Capabilities->getAllowed('adminUser'))) {
            // Reseller access or access of Admins w/o 'systemAdministrator' flag not allowed:
            $service_available = '0';
        }
        elseif ($Capabilities->getAllowed('siteAdmin')) {
            $use_FTP = '1';
            $service_available = '1';
            $protocol = 'FTP';
            $port = '21';
        }
        elseif (($FTP_Available == '1') && ($Vsite_FTP['enabled'] == '1')) {
            $use_FTP = '1';
            $service_available = '1';
            $protocol = 'FTP';
            $port = '21';
        }
        else {
            $service_available = '0';
        }

        $ftplangs = array(
            'da_DK' => 'da',
            'de_DE' => 'de',
            'en_US' => 'en-utf',
            'es_ES' => 'es',
            'fr_FR' => 'fr',
            'it_IT' => 'it',
            'ja_JP' => 'ja',
            'nl_NL' => 'nl',
            'pt_PT' => 'pt'
        );

        if (in_array($CI->BX_SESSION['loginUser']['localePreference'], array_keys($ftplangs))) {
            $ftplocale = $ftplangs[$CI->BX_SESSION['loginUser']['localePreference']];
        }
        else {
            $ftplocale = 'en-utf';
        }

        // Set encrypted net2ftp pass in Cookie:
        setcookie("XSStoken", $ftpPass, "0", "/");

        if ((isset($protocol)) && (isset($port))) {
            $uri_full = 'https://' . $_SERVER['SERVER_NAME'] . ':81/ftpclient/index.php?protocol=' . $protocol . '&ftpserver=' . $_SERVER['SERVER_NAME'] . '&ftpserverport=' . $port . '&sshfingerprint=&language=' . $ftplocale . '&skin=shinra&ftpmode=automatic&passivemode=no&viewmode=list&sort=&sortorder=&state=browse&state2=main&directory=&entry=';
        }
        else {
            if (!isset($group)) {
                $uri_full = '/ftp/ftpclient/';
            }
            else {
                $uri_full = '/ftp/ftpclient/?group=' . $group;
            }
        }

        if (!isset($group)) {
            $uri_short = '/ftp/ftpclient/';
        }
        else {
            $uri_short = '/ftp/ftpclient/?group=' . $group;
        }

        // -- Actual page logic start:

        //
        //-- Generate page:
        //

        // Prepare Page:
        $factory = $CI->serverScriptHelper->getHtmlComponentFactory("base-ftp", "/ftp/ftpclient");
        $BxPage = $factory->getPage();
        $BxPage->setErrors(array());
        $i18n = $factory->getI18n();

        if (($user['capabilities'] == '') && (!$Capabilities->getAllowed('reseller'))) {
            unset($group);
        }

        // Set Menu items:
        if (!isset($group)) {
            $BxPage->setVerticalMenu('base_programsPersonal');
            $BxPage->setVerticalMenuChild('ftpc_personal');
            $page_module = 'base_personalProfile';
            $url_suffix = '';
        }
        else {
            if ($group == "server") {
                $BxPage->setVerticalMenu('base_programs');
                $BxPage->setVerticalMenuChild('ftpc_server');
                $page_module = 'base_sysmanage';
            }
            else {
                $BxPage->setVerticalMenu('base_programsSite');
                $BxPage->setVerticalMenuChild('ftpc_vsite');
                $page_module = 'base_sitemanage';
            }
            $url_suffix = '?group=' . $group;
        }

        $defaultPage = "basicSettingsTab";

        $block =& $factory->getPagedBlock("connect", array($defaultPage));

        $block->setToggle("#");
        $block->setSideTabs(FALSE);
        $block->setDefaultPage($defaultPage);

        if ($service_available == "0") {
                $disabled_TEXT = "<div class='flat_area grid_16'><br>" . $i18n->getClean("[[base-ftp.service_disabled]]") . "</div>";
                $disabledtext = $factory->getHtmlField("no_service_available", $disabled_TEXT, 'r');
                $disabledtext->setLabelType("nolabel");
                $block->addFormField(
                  $disabledtext,
                  $factory->getLabel(" ", false),
                  $defaultPage
                );
        }
        else {

            if ($fullscreen == "0") {

                //
                //-- Show in iframe:
                //

                if (isset($get_form_data['group'])) {
                    $block->setSelf("/ftp/ftpclient?group=" . $get_form_data['group'] . "&fullscreen=1");
                }
                else {
                    $block->setSelf("/ftp/ftpclient?fullscreen=1");
                }

                if ($CI->input->cookie('layout_switcher_php-style') == "layout_fixed.css") {
                    $sty_width = '720';
                }
                else {
                    $sty_width = '1024';
                }

                $applet = '<iframe height=800 width=' . $sty_width . ' src="' . $uri_full . '" scrolling="yes"></iframe>';

                $block->addFormField(
                    $factory->getRawHTML("applet", $applet),
                    $factory->getLabel("AllowOverride_OptionsField"),
                    $defaultPage
                );

            }
            else {

                $BxPage->setExtraBodyTag('<body onload="javascript: poponload()">');

                $BxPage->setExtraHeaders('<script type="text/javascript">');
                $BxPage->setExtraHeaders('function poponload() {');
                $BxPage->setExtraHeaders("  window.open('$uri_full','_blank','toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=yes, resizable=yes, copyhistory=yes, width=1024, height=800');");
                $BxPage->setExtraHeaders('}');
                $BxPage->setExtraHeaders('</script>');

                $my_TEXT = "<div class='flat_area grid_16'><br>" . $i18n->getClean("[[base-ftp.info_text]]") . "</div>";
                $infotext = $factory->getHtmlField("info_text", $my_TEXT, 'r');
                $infotext->setLabelType("nolabel");
                $block->addFormField(
                  $infotext,
                  $factory->getLabel(" ", false),
                  $defaultPage
                );

            }
        }

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