<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class SoftwareList extends MX_Controller {

    /**
     * Index Page for this controller.
     *
     * Past the login page this loads the page for /swupdate/softwareList.
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
        $i18n = new I18n("base-swupdate", $CI->BX_SESSION['loginUser']['localePreference']);
        $system = $CI->getSystem();

        // Initialize Capabilities so that we can poll the access rights as well:
        $Capabilities = new Capabilities($CI->cceClient, $CI->BX_SESSION['loginName'], $CI->BX_SESSION['sessionId']);

        // Required array setup:
        $errors = array();
        $extra_headers = array();

        // -- Actual page logic start:

        // Not 'managePackage'? Bye, bye!
        if (!$Capabilities->getAllowed('managePackage')) {
            // Nice people say goodbye, or CCEd waits forever:
            $CI->cceClient->bye();
            $CI->serverScriptHelper->destructor();
            Log403Error("/gui/Forbidden403");
        }
        else {

            // Start with an empty siteList:
            $pkgList = array();

            // Get a list of installed PKGs:
            $pkgOIDs = $CI->cceClient->findNSorted("Package", 'version', array('installState' => 'Installed'));

            // Prepare Page:
            $factory = $CI->serverScriptHelper->getHtmlComponentFactory("base-swupdate", "/swupdate/softwareList");
            $BxPage = $factory->getPage();
            $i18n = $factory->getI18n();

            for($i = 0; $i < count($pkgOIDs); $i++) {
                $package = $CI->cceClient->get($pkgOIDs[$i]);

                $packageName = $package["nameTag"] ? $i18n->interpolate($package["nameTag"]) : $package["name"];
                $version = $package["versionTag"] ? $i18n->interpolate($package["versionTag"]) : substr($package["version"], 1);
                $vendorName = $package["vendorTag"] ? $i18n->interpolate($package["vendorTag"]) : $package["vendor"];
                $description = $i18n->interpolate($package["shortDesc"]);
                $uninstallable = strstr($package['options'], 'uninstallable');
                $oid = &$pkgOIDs[$i];

                //
                // Create the 'Uninstall'-button. We could use getUninstallButton(), but this will do:
                //

                // Escape PKG info for usage in URL:
                $escName=$i18n->interpolateJs("[[VAR.foo,foo=\"$packageName\"]]");
                if ($uninstallable) {

                    // Only allow uninstall if we're not in DEMO mode:
                    if (!is_file("/etc/DEMO")) {

                        // PKG is uninstallable:
                        $button = '<a class="lb' . $oid. '" href="/swupdate/uninstallHandler?nameField=' . $escName . '&packageOID=' . $oid . '"><button class="small icon_only ui-icon-circle-close tooltip hover dialog_button" title="' . $i18n->getWrapped("uninstall_help", "palette") . '"><div class="ui-icon ui-icon-trash"></div></button></a>';

                        // Extra header for the "do you really want to uninstall " dialog Modal:
                        $BxPage->setExtraHeaders('
                                <script type="text/javascript">
                                $(document).ready(function () {

                                  $("#dialog' . $oid . '").dialog({
                                    modal: true,
                                    bgiframe: true,
                                    width: 500,
                                    height: 200,
                                    autoOpen: false
                                  });

                                  $(".lb' . $oid . '").click(function (e) {
                                    e.preventDefault();
                                    var hrefAttribute = $(this).attr("href");

                                    $("#dialog' . $oid . '").dialog(\'option\', \'buttons\', {
                                      "' . $i18n->getHtml("[[base-swupdate.uninstall]]") . '": function () {
                                        window.location.href = hrefAttribute;
                                      },
                                      "' . $i18n->getHtml("[[palette.cancel]]") . '": function () {
                                        $(this).dialog("close");
                                      }
                                    });

                                    $("#dialog' . $oid . '").dialog("open");

                                  });
                                });
                                </script>');

                                // Add hidden Modal for Delete-Confirmation:
                                $page_body[] = '
                                    <!-- Start: Hidden uninstall confirm Modal for ' . $packageName . '-->
                                    <div class="display_none">
                                                <div id="dialog' . $oid . '" class="dialog_content narrow no_dialog_titlebar" title="' . $i18n->getHtml("[[base-swupdate.uninstall]]") . '">
                                                    <div class="block">
                                                            <div class="section">
                                                                    <h1>' . $i18n->getHtml("[[base-swupdate.uninstall]]") . '</h1>
                                                                    <div class="dashed_line"></div>
                                                                    <p>' . $i18n->interpolate("[[base-swupdate.uninstallConfirm]]", array('packageName' => $packageName)) . '</p>
                                                            </div>
                                                    </div>
                                                </div>
                                    </div>
                                    <!-- End: Hidden uninstall confirm Modal for ' . $packageName . '-->';
                    }
                    else {
                        $helptext = $i18n->getWrapped("uninstall_help", "palette") . " " . $i18n->getWrapped("demo_mode", "palette");
                        $button = '<button title="' . $helptext . '" class="close_dialog small tooltip right link_button" data-link="javascript: void()" target="_self"><div class="ui-icon ui-icon-trash"></div></button>';                        
                    }
                }
                else {
                    // Disable button if not uninstallable
                    $button = '<button title="' . $i18n->getWrapped("uninstall_disabled_help", "palette") . '" class="close_dialog small tooltip right link_button" data-link="javascript: void()" target="_self"><div class="ui-icon ui-icon-circle-close"></div></button>';
                }

                // Populate the output array with the results:
                $pkgList[0][$i] = $packageName;
                $pkgList[1][$i] = $version;
                $pkgList[2][$i] = $vendorName;
                $pkgList[3][$i] = $description;
                $pkgList[4][$i] = $button;
            }
        }

        //-- Generate page:

        // Set Menu items:
        $BxPage->setVerticalMenu('base_software');
        $BxPage->setVerticalMenuChild('base_softwareInstalled');
        $page_module = 'base_software';

        $scrollList = $factory->getScrollList("installedList", array("nameField", "versionField", "vendorField", "descriptionField", "uninstall"), $pkgList); 
        $scrollList->setAlignments(array("left", "left", "left", "left", "center"));
        $scrollList->setDefaultSortedIndex('0');
        $scrollList->setSortOrder('ascending');
        $scrollList->setSortDisabled(array('5'));
        $scrollList->setPaginateDisabled(FALSE);
        $scrollList->setSearchDisabled(FALSE);
        $scrollList->setSelectorDisabled(FALSE);
        $scrollList->enableAutoWidth(FALSE);
        $scrollList->setInfoDisabled(FALSE);
        $scrollList->setColumnWidths(array("180", "80", "200", "243", "35")); // Max: 739px

        $page_body[] = $scrollList->toHtml();

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