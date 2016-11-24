<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');


class Ssh extends MX_Controller {

    /**
     * Index Page for this controller.
     *
     * Past the login page this loads the page for the console access.
     *
     */

    public function index() {

        $CI =& get_instance();
        
        // We load the BlueOnyx helper library first of all, as we heavily depend on it:
        $this->load->helper('blueonyx');
        init_libraries();

        // Need to load 'BxPage' for page rendering:
        $this->load->library('BxPage');
        $MX =& get_instance();

        // Get $sessionId and $loginName from Cookie (if they are set):
        $sessionId = $CI->input->cookie('sessionId');
        $loginName = $CI->input->cookie('loginName');
        $locale = $CI->input->cookie('locale');

        // Line up the ducks for CCE-Connection:
        include_once('ServerScriptHelper.php');
        $serverScriptHelper = new ServerScriptHelper($sessionId, $loginName);
        $cceClient = $serverScriptHelper->getCceClient();
        $user = $cceClient->getObject("User", array("name" => $loginName));
        $userShell = $cceClient->getObject("User", array("name" => $loginName), "Shell");
        $i18n = new I18n("base-disk", $user['localePreference']);
        $system = $cceClient->getObject("System");
        $systemRemote = $cceClient->get($system['OID'], "Remote");

        // Initialize Capabilities so that we can poll the access rights as well:
        $Capabilities = new Capabilities($cceClient, $loginName, $sessionId);

        // No Shell access? Bye, bye!
        if ((!$Capabilities->getAllowed('serverShell')) || (!$Capabilities->getAllowed('siteShell')) || (!$Capabilities->getAllowed('resellerShell'))) {
            // Nice people say goodbye, or CCEd waits forever:
            $cceClient->bye();
            $serverScriptHelper->destructor();
            Log403Error("/gui/Forbidden403");
        }

        // Required array setup:
        $errors = array();
        $extra_headers = array();

        // -- Actual page logic start:

        //-- Generate page:

        // Prepare Page:
        $factory = $serverScriptHelper->getHtmlComponentFactory("base-remote", "/remote/ssh/");
        $BxPage = $factory->getPage();
        $BxPage->setErrors($errors);
        $i18n = $factory->getI18n();

        // Set Menu items:
        $BxPage->setVerticalMenu('base_programsPersonal');
        $BxPage->setVerticalMenuChild('2nuonce_base_ssh2');
        $page_module = 'base_personalProfile';

        $defaultPage = "basicSettingsTab";

        $block =& $factory->getPagedBlock("header", array($defaultPage));

        $block->setToggle("#");
        $block->setSideTabs(FALSE);
        $block->setDefaultPage($defaultPage);

        $uri_full = 'https://' . $_SERVER['SERVER_NAME'] . ':81/bxshell/?' . $loginName . '=' . time();
        $uri_short = '/bxshell/?$loginName=' . time();

        if (!isset($userShell['enabled'])) {
            $uri_full = 'https://' . $_SERVER['SERVER_NAME'] . ':81/remote/noaccess/?' . $loginName . '=' . time();
            $uri_short = '/remote/noaccess/?' . $loginName . '=' . time();
        }

        if (uri_string() != "remote/ssh/full") {

            if ($systemRemote['enabled'] == "0") {
                    $disabled_TEXT = "<div class='flat_area grid_16'><br>" . $i18n->getClean("[[base-remote.service_disabled]]") . "</div>";
                    $disabledtext = $factory->getHtmlField("admin_text", $disabled_TEXT, 'r');
                    $disabledtext->setLabelType("nolabel");
                    $block->addFormField(
                      $disabledtext,
                      $factory->getLabel(" ", false),
                      $defaultPage
                    );
            }
            else {

                $my_TEXT = "<div class='flat_area grid_16'><br>" . $i18n->getClean("[[base-remote.info_text]]") . "</div>";
                $infotext = $factory->getHtmlField("info_text", $my_TEXT, 'r');
                $infotext->setLabelType("nolabel");
                $block->addFormField(
                  $infotext,
                  $factory->getLabel(" ", false),
                  $defaultPage
                );

                if ($loginName == 'admin') {

                    $admin_TEXT = "<div class='flat_area grid_16'><br>" . $i18n->getClean("[[base-remote.admin_text]]") . "</div>";
                    $admintext = $factory->getHtmlField("admin_text", $admin_TEXT, 'r');
                    $admintext->setLabelType("nolabel");
                    $block->addFormField(
                      $admintext,
                      $factory->getLabel(" ", false),
                      $defaultPage
                    );
                }

                $block->setSelf("/remote/ssh/full");
                $applet = '<iframe height=600 width=720 src="' . $uri_short . '" scrolling="no"></iframe>';

                $block->addFormField(
                    $factory->getRawHTML("applet", $applet),
                    $factory->getLabel("AllowOverride_OptionsField")
                );
            }

            // Nice people say goodbye, or CCEd waits forever:
            $cceClient->bye();
            $serverScriptHelper->destructor();
            $page_body[] = $block->toHtml();
        }
        else {

            if ($systemRemote['enabled'] == "0") {
                    $disabled_TEXT = "<div class='flat_area grid_16'><br>" . $i18n->getClean("[[base-remote.service_disabled]]") . "</div>";
                    $disabledtext = $factory->getHtmlField("admin_text", $disabled_TEXT, 'r');
                    $disabledtext->setLabelType("nolabel");
                    $block->addFormField(
                      $disabledtext,
                      $factory->getLabel(" ", false),
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

                $my_TEXT = "<div class='flat_area grid_16'><br>" . $i18n->getClean("[[base-remote.info_text]]") . "</div>";
                $infotext = $factory->getHtmlField("info_text", $my_TEXT, 'r');
                $infotext->setLabelType("nolabel");
                $block->addFormField(
                  $infotext,
                  $factory->getLabel(" ", false),
                  $defaultPage
                );

                if ($loginName == 'admin') {

                    $admin_TEXT = "<div class='flat_area grid_16'><br>" . $i18n->getClean("[[base-remote.admin_text]]") . "</div>";
                    $admintext = $factory->getHtmlField("admin_text", $admin_TEXT, 'r');
                    $admintext->setLabelType("nolabel");
                    $block->addFormField(
                      $admintext,
                      $factory->getLabel(" ", false),
                      $defaultPage
                    );
                }
            }

            // Nice people say goodbye, or CCEd waits forever:
            $cceClient->bye();
            $serverScriptHelper->destructor();
            $page_body[] = $block->toHtml();

        }
        // Out with the page:
        $BxPage->render($page_module, $page_body);

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