<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class WarList extends MX_Controller {

    /**
     * Index Page for this controller.
     *
     * Past the login page this loads the page for /java/warList.
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
        $i18n = new I18n("base-java", $CI->BX_SESSION['loginUser']['localePreference']);
        $system = $CI->getSystem();

        // Initialize Capabilities so that we can poll the access rights as well:
        $Capabilities = new Capabilities($CI->cceClient, $CI->BX_SESSION['loginName'], $CI->BX_SESSION['sessionId']);

        // Required array setup:
        $errors = array();
        $extra_headers = array();

        // Get URL params:
        $get_form_data = $CI->input->get(NULL, TRUE);

        //
        //-- Validate GET data:
        //

        if (isset($get_form_data['group'])) {
            // We have a group URL string:
            $group = $get_form_data['group'];
        }
        else {
            // Don't play games with us!
            // Nice people say goodbye, or CCEd waits forever:
            $CI->cceClient->bye();
            $CI->serverScriptHelper->destructor();
            Log403Error("/gui/Forbidden403#1");
        }

        //
        //-- Access Rights Check for Vsite level pages:
        // 
        // 1.) Checks if the Group/Vsite exists.
        // 2.) Checks if the user is systemAdministrator
        // 3.) Checks if the user is Reseller of the given Group/Vsite
        // 4.) Checks if the iser is siteAdmin of the given Group/Vsite
        // Returns Forbidden403 if *none* of that is the case.
        if (!$Capabilities->getGroupAdmin($group)) {
            // Nice people say goodbye, or CCEd waits forever:
            $CI->cceClient->bye();
            $CI->serverScriptHelper->destructor();
            Log403Error("/gui/Forbidden403#2");
        }

        //
        //-- Get Vsite data
        //
        if ($group) {
            $JavaWarOids = $CI->cceClient->find("JavaWar", array("group" => "$group"));
            // Lookup the current site for the fqdn, used in the form/help text
            $vsite = $CI->cceClient->getObject('Vsite', array('name' => $group));
            $vsiteJava = $CI->cceClient->getObject('Vsite', array('name' => $group), "Java");
        }
        else {
            $JavaWarOids = array();
        }

        // Second stage of capability check. More thorough here:
        // Only adminUser and siteAdmin should be here
        // NOTE: Needs testing if this is restructive enough (!!!!!!!!!!!!!!!!!!!!!!!!!!)
        if ((!$Capabilities->getAllowed('adminUser')) && 
            (!$Capabilities->getAllowed('siteAdmin')) && 
            (!$Capabilities->getAllowed('manageSite')) && 
            (($user['site'] != $CI->serverScriptHelper->loginUser['site']) && $Capabilities->getAllowed('siteAdmin')) &&
            (($vsiteObj['createdUser'] != $CI->BX_SESSION['loginName']) && $Capabilities->getAllowed('manageSite'))
            ) {
            // Nice people say goodbye, or CCEd waits forever:
            $CI->cceClient->bye();
            $CI->serverScriptHelper->destructor();
            Log403Error("/gui/Forbidden403#4");
        }

        //
        //--- Handle form validation:
        //

        // We start without any active errors:
        $errors = array();
        $extra_headers =array();
        $ci_errors = array();
        $my_errors = array();

        if (isset($get_form_data['_REMOVE'])) {
            $delErrors = array();
            $_REMOVE = $get_form_data['_REMOVE'];
            if (intval($_REMOVE) > 0) {
                // Verify if it's an 'JavaWar' Object of this group:
                $obj = $CI->cceClient->get($_REMOVE);
                if (($obj['CLASS'] != "JavaWar") || ($obj['group'] != $group)) { 
                    // Yeah, it was a nice try. There is the door!
                    // Nice people say goodbye, or CCEd waits forever:
                    $CI->cceClient->bye();
                    $CI->serverScriptHelper->destructor();
                    Log403Error("/gui/Forbidden403#5");
                }
                // Destroy the Mailing-List Object:
                if (!is_file("/etc/DEMO")) {
                    // Delete the installation directory. Aggressive, eh? We're going to WAR here!! :-)
                    // Start by building the path with site volume, group, and war install path
                    if(($obj['name'] != '') && ($vsite['basedir'] != '')) {
                        $path = $vsite['basedir'].'/web/'.$obj['name'];
                        // Bombs away!
                        $runas = ($Capabilities->getAllowed('adminUser') ? 'root' : $CI->BX_SESSION['loginName']);
                        $ret = $CI->serverScriptHelper->shell("/bin/rm -rf \"$path\"", $output, $runas, $CI->BX_SESSION['sessionId']);
                    }
                    $ret = $CI->cceClient->destroy($_REMOVE);
                }

                // CCE errors that might have happened during submit to CODB:
                $CCEerrors = $CI->cceClient->errors();
                foreach ($CCEerrors as $object => $objData) {
                    // When we fetch the CCE errors it tells us which field it bitched on. And gives us an error message, which we can return:
                    $delErrors[] = ErrorMessage($i18n->get($objData->message, true, array('key' => $objData->key)) . '<br>&nbsp;');
                }

                // No errors during submit? Reload page:
                if (count($errors) == "0") {
                    $CI->cceClient->bye();
                    $CI->serverScriptHelper->destructor();
                    $redirect_URL = "/java/warList?group=$group";
                    header("location: $redirect_URL");
                    exit;
                }
            }
        }

        //-- Generate page:

        // Prepare Page:
        $factory = $CI->serverScriptHelper->getHtmlComponentFactory("base-java", "/java/warList?group=$group");
        $BxPage = $factory->getPage();
        $i18n = $factory->getI18n();

        $BxPage->setExtraHeaders('
                <script>
                    $(document).ready(function() {
                        $(".various").fancybox({
                            overlayColor: "#000",
                            fitToView   : false,
                            width       : "80%",
                            height      : "80%",
                            autoSize    : false,
                            closeClick  : false,
                            openEffect  : "none",
                            closeEffect : "none"
                        });
                    });
                </script>');

        // Extra header for the "do you really want to delete" dialog:
        $BxPage->setExtraHeaders('
                <script type="text/javascript">
                $(document).ready(function () {

                  $("#dialog").dialog({
                    modal: true,
                    bgiframe: true,
                    width: 500,
                    height: 280,
                    autoOpen: false
                  });

                  $(".lb").click(function (e) {
                    e.preventDefault();
                    var hrefAttribute = $(this).attr("href");

                    $("#dialog").dialog(\'option\', \'buttons\', {
                      "' . $i18n->getHtml("[[palette.remove]]") . '": function () {
                        window.location.href = hrefAttribute;
                      },
                      "' . $i18n->getHtml("[[palette.cancel]]") . '": function () {
                        $(this).dialog("close");
                      }
                    });

                    $("#dialog").dialog("open");

                  });
                });
                </script>');

        // Set Menu items:
        $BxPage->setVerticalMenu('base_siteadmin');
        $BxPage->setVerticalMenuChild('base_java_apps');
        $page_module = 'base_sitemanage';
        $defaultPage = 'pageID';

        $block =& $factory->getPagedBlock("warNames", array($defaultPage));
        $block->setLabel($factory->getLabel('warNames', false, array('fqdn' => $vsite['fqdn'])));

        $block->setToggle("#");
        $block->setSideTabs(FALSE);
        //$block->setShowAllTabs("#");
        $block->setDefaultPage($defaultPage);

        $scrollList = $factory->getScrollList("warList", array("warNameHeader", "warListPathHeader" ,"warListActionHeader"), array()); 
        $scrollList->setAlignments(array("left", "left", "center"));
        $scrollList->setDefaultSortedIndex('0');
        $scrollList->setSortOrder('ascending');
        $scrollList->setSortDisabled(array('2'));
        $scrollList->setPaginateDisabled(FALSE);
        $scrollList->setSearchDisabled(FALSE);
        $scrollList->setSelectorDisabled(FALSE);
        $scrollList->enableAutoWidth(FALSE);
        $scrollList->setInfoDisabled(FALSE);
        $scrollList->setColumnWidths(array("200", "488", "50")); // Max: 739px

        // Generate +Add button:
        $addAdminUser = "/java/warAdd?group=$group";
        $addbutton = $factory->getAddButton($addAdminUser, "", "DEMO-OVERRIDE");
        if ($vsiteJava['enabled'] != "1") {
            $addbutton->setDisabled(TRUE);
        }
        $buttonContainer = $factory->getButtonContainer("warNames_menu", $addbutton);
        $block->addFormField(
            $buttonContainer,
            $factory->getLabel('warNames_menu'),
            $defaultPage
        );

        if ($vsiteJava['enabled'] != "1") {
            // Info about JSP Status for this Vsite in case it is not enabled:
            $warning = $i18n->getClean("javaNotEnabled");
            $tom_not_running = $factory->getTextField("_", $warning, 'r');
            $tom_not_running->setLabelType("nolabel");
            $block->addFormField(
                $tom_not_running,
                $factory->getLabel(" "),
                $defaultPage
                );
        }

        //-- Populate ScrollList:
        foreach ($JavaWarOids as $i => $oid) {
            $warList = $CI->cceClient->get($oid, "");
            $path = 'http://'.$vsite['fqdn'].'/'.$warList['name'];
            $msg = $i18n->get("confirm_archive_removal", "", array('path' => $path));

            // Add 'Delete' button:
            $DelButt = '<a class="lb" href="/java/warList?group=' . $group . '&_REMOVE=' . $oid . '"><button class="tiny icon_only div_icon tooltip hover dialog_button" title="' . $i18n->getHtml("[[palette.remove_help]]") . '"><div class="ui-icon ui-icon-trash"></div></button></a><br>';

            $archive = preg_replace("/^.+\/([^\/]+)$/", "\\1", $warList["war"]);

            $scrollList->addEntry(array(
                $archive,
                $path,
                $DelButt), "", false, $i);
        }

        // Push out the Scrollist:
        $block->addFormField(
            $factory->getRawHTML("warNames_menu", $scrollList->toHtml()),
            $factory->getLabel('warNames_menu'),
            $defaultPage
        );

        $page_body[] = $block->toHtml();

        // Add hidden Modal for Delete-Confirmation:
        $page_body[] = '
            <div class="display_none">
                        <div id="dialog" class="dialog_content narrow no_dialog_titlebar" title="' . $i18n->getHtml("[[base-java.removeConfirmQuestion]]") . '">
                            <div class="block">
                                    <div class="section">
                                            <h1>' . $i18n->getHtml("[[base-java.removeConfirmQuestion]]") . '</h1>
                                            <div class="dashed_line"></div>
                                            <p>' . $i18n->getHtml("[[base-java.userRemoveConfirmInfo]]") . '</p>
                                    </div>
                            </div>
                        </div>
            </div>';

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