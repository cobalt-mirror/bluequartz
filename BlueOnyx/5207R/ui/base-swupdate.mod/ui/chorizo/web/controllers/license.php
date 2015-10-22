<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class License extends MX_Controller {

    /**
     * Index Page for this controller.
     *
     * Past the login page this loads the page for /swupdate/license.
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
        $i18n = new I18n("base-swupdate", $user['localePreference']);
        $system = $cceClient->getObject("System");

        // Initialize Capabilities so that we can poll the access rights as well:
        $Capabilities = new Capabilities($cceClient, $loginName, $sessionId);

        // -- Actual page logic start:

        // Not 'managePackage'? Bye, bye!
        if (!$Capabilities->getAllowed('managePackage')) {
            // Nice people say goodbye, or CCEd waits forever:
            $cceClient->bye();
            $serverScriptHelper->destructor();
            Log403Error("/gui/Forbidden403");
        }

        // Get URL params:
        $get_form_data = $CI->input->get(NULL, TRUE);

        if ((!isset($get_form_data['packageOID'])) || (!isset($get_form_data['backUrl']))) {
            // Nice people say goodbye, or CCEd waits forever:
            $cceClient->bye();
            $serverScriptHelper->destructor();
            Log403Error("/gui/Forbidden403");
        }

        $packageOID = $get_form_data['packageOID'];
        $backUrl = $get_form_data['backUrl'];

        //
        //--- Get CODB-Object of interest: 
        //

        $package = $cceClient->get($packageOID);
        $license = $package["licenseDesc"];
        $splash = strstr($package["splashPages"], 'pre-install');

        //
        //-- Own page logic:
        //

        // Redirect if we don't have license info:
        $location = "/swupdate/downloadHandler?packageOID=$packageOID&backUrl=$backUrl";
        if (!($license || $splash)) {
            $cceClient->bye();
            $serverScriptHelper->destructor();          
            header("Location: $location");
            exit;
        }

        // We got a splash page.
        // Note: Not sure if this will still work. We don't actually have PKGs with splash pages at
        // the moment. 
        if ($splash) {
            $splashdir = updates_splashdir();
            $stage = 'pre-install';
            $name = updates_splashname($package["vendor"], $package["name"], $package["version"], $stage);
            if (file_exists("$splashdir/$name") && $dhandle = opendir("$splashdir/$name")) {
                while ($file = readdir($dhandle)) {
                    if (strstr($file, 'index.')) {
                        $submit = urlencode($location);
                        header("location: /$name/?submitURL=$submit&cancelURL=$backUrl\n\n");
                        // echo " <ul><li>$name <li>$submit <li> $backUrl </ul>\n";
                        exit;
                    }
                }
                closedir($dhandle);
            }

            if (!$license) {
                $cceClient->bye();
                $serverScriptHelper->destructor();              
                header("Location: $location");
                exit;
            }
        }

        // Otherwise, we generate a standard license page:

        //
        //-- Generate page:
        //

        // We start without any active errors:
        $errors = array();

        // Prepare Page:
        $factory = $serverScriptHelper->getHtmlComponentFactory("base-swupdate", "/swupdate/newSoftware");
        $BxPage = $factory->getPage();
        $BxPage->setErrors($errors);
        $i18n = $factory->getI18n();

        $product = new Product($cceClient);

        // Set Menu items:
        $BxPage->setVerticalMenu('base_software');
        $BxPage->setVerticalMenuChild('base_softwareNew');
        $page_module = 'base_software';

        $defaultPage = "licenseField";

        $block =& $factory->getPagedBlock("licenseField", array($defaultPage));

        $block->setToggle("#");
        $block->setSideTabs(FALSE);
        $block->setShowAllTabs("#");
        $block->setDefaultPage($defaultPage);

        $block->addFormField($factory->getTextField("nameField", $package['name'], ""), $defaultPage);
        $block->addFormField($factory->getTextField("backUrl", $backUrl, ""), $defaultPage);
        $block->addFormField($factory->getTextField("packageOID", $packageOID, ""), $defaultPage);

        $AcceptButton = $factory->getButton($location, "accept");
        $AcceptButton->setIcon("ui-icon-check");

        $block->addButton($AcceptButton);
        $block->addButton($factory->getCancelButton($backUrl, "decline"));

        $stage = 'pre-install';
        updates_prependsrc($license, $package['vendor'], $package['name'], $package['version'], $stage);

        $LICENSE = $i18n->interpolate($license);
        $LICENSE_info_text = $factory->getTextField("_", $LICENSE, 'r');
        $LICENSE_info_text->setLabelType("nolabel");
        $block->addFormField(
            $LICENSE_info_text,
            $factory->getLabel(" "),
            $defaultPage
            );

        // Nice people say goodbye, or CCEd waits forever:
        $cceClient->bye();
        $serverScriptHelper->destructor();

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