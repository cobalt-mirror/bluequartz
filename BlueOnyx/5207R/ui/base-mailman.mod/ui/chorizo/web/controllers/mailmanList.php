<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class mailmanList extends MX_Controller {

    /**
     * Index Page for this controller.
     *
     * Past the login page this loads the page for /mailman/mailmanList.
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
        $i18n = new I18n("base-mailman", $CI->BX_SESSION['loginUser']['localePreference']);
        $system = $CI->getSystem();

        // Initialize Capabilities so that we can poll the access rights as well:
        $Capabilities = new Capabilities($CI->cceClient, $CI->BX_SESSION['loginName'], $CI->BX_SESSION['sessionId']);

        // -- Actual page logic start:

        // Get URL strings:
        $get_form_data = $CI->input->get(NULL, TRUE);

        //
        //-- Validate GET data:
        //

        if (isset($get_form_data['group'])) {
            // We have a delete transaction:
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
        //-- Prepare data:
        //

        // Get data for the Vsite:
        $vsite = $CI->cceClient->getObject('Vsite', array('name' => $group));

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
                // Verify if it's an 'MailList' Object:
                $obj = $CI->cceClient->get($_REMOVE);
                if ($obj['CLASS'] != "MailList") { 
                    // Yeah, it was a nice try. There is the door!
                    // Nice people say goodbye, or CCEd waits forever:
                    $CI->cceClient->bye();
                    $CI->serverScriptHelper->destructor();
                    Log403Error("/gui/Forbidden403#3");
                }
                // Destroy the Mailing-List Object:
                if (!is_file("/etc/DEMO")) {
                    $ret = $CI->cceClient->destroy($_REMOVE);
                }

                // CCE errors that might have happened during submit to CODB:
                $CCEerrors = $CI->cceClient->errors();
                foreach ($CCEerrors as $object => $objData) {
                    // When we fetch the CCE errors it tells us which field it bitched on. And gives us an error message, which we can return:
                    $delErrors[] = ErrorMessage($i18n->get($objData->message, true, array('key' => $objData->key)) . '<br>&nbsp;');
                }

                // Need to commit to "System" Object "MailList" as well:
                $ret = $CI->cceClient->setObject("System", array('commit' => time()), "MailList");

                // No errors during submit? Reload page:
                if (count($errors) == "0") {
                    $CI->cceClient->bye();
                    $CI->serverScriptHelper->destructor();
                    $redirect_URL = "/mailman/mailmanList?group=$group";
                    header("location: $redirect_URL");
                    exit;
                }
            }
        }

        //
        //-- Generate page:
        //

        // Prepare Page:
        $factory = $CI->serverScriptHelper->getHtmlComponentFactory("base-mailman", "/mailman/mailmanList?group=$group");
        $BxPage = $factory->getPage();
        $BxPage->setErrors($errors);
        $i18n = $factory->getI18n();

        // Set Menu items:
        $BxPage->setVerticalMenu('base_siteservices');
        $BxPage->setVerticalMenuChild('base_mailmans');
        $page_module = 'base_sitemanage';

        $defaultPage = "basicSettings";
        $block =& $factory->getPagedBlock("mailmanList", array($defaultPage));
        $block->setLabel($factory->getLabel('mailmanList', false, array('group' => $vsite['fqdn'])));

        $block->setToggle("#");
        $block->setSideTabs(FALSE);
        $block->setDefaultPage($defaultPage);

        // Only 'manageSite' can modify things on this page.
        // Site admins can view it for informational purposes.
        if ($Capabilities->getAllowed('manageSite')) {
            $is_site_admin = FALSE;
            $access = 'rw';
        }
        elseif (($Capabilities->getAllowed('siteAdmin')) && ($group == $Capabilities->loginUser['site'])) {
            $access = 'r';
            $is_site_admin = TRUE;
        }
        else {
            // Nice people say goodbye, or CCEd waits forever:
            $CI->cceClient->bye();
            $CI->serverScriptHelper->destructor();
            Log403Error("/gui/Forbidden403#4");
        }

        $ScrollList = $factory->getScrollList("mailmanList", array("mailmanNameHeader", "recipientsHeader", "mailmanDescHeader" ,"mailmanActionHeader"), array()); 
        $ScrollList->setAlignments(array("left", "left", "left", "center"));
        $ScrollList->setDefaultSortedIndex('0');
        $ScrollList->setSortOrder('ascending');
        $ScrollList->setSortDisabled(array('3', '4'));
        $ScrollList->setPaginateDisabled(FALSE);
        $ScrollList->setSearchDisabled(FALSE);
        $ScrollList->setSelectorDisabled(FALSE);
        $ScrollList->enableAutoWidth(FALSE);
        $ScrollList->setInfoDisabled(FALSE);
        $ScrollList->setColumnWidths(array("244", "150", "244", "100")); // Max: 739px

        // Add the +Add-Button:
        $ScrollList->addButton($factory->getAddButton("/mailman/mailmanMod?group=$group", '[[palette.add_help]]', "DEMO-OVERRIDE"));

        // Find the MailingLists of this Vsite:
        $oids = $CI->cceClient->findSorted("MailList", "name", array('site' => $group));

        if(count($oids) > 0) { 
            for ($i = 0; $i < count($oids); $i++) {

                $oid = $oids[$i];
                $ml = $CI->cceClient->get($oid, "");

                $members = array();
                // magic variables! if subscriber list is empty, then 'nobody' is the sole recipient
                // parse it out so that we don't show it to the user
                if ($ml['local_recips'] != '&nobody&') {
                    $members = stringToArray($ml['local_recips']);
                }
                $members = array_merge($members, stringToArray($ml['remote_recips']));
                $members = array_merge($members, stringToArray($ml['remote_recips_digest']));
                if ($ml['group']) {
                    $groupText = $i18n->get("groupSubscriber", "", array("group"=>$ml['group']));
                    $members = array_merge($members, (array)"". $groupText . (array)"");
                }

                if ($ml["description"] == "") {
                    $desc = "";
                }
                else {
                    $desc = $i18n->interpolate($ml["description"]);
                }
                $msg = $i18n->get("confirm_removal_of_list", "", array('list' => $ml["name"]));

                // Add Buttons for Edit/View and Delete:
                $buttons = '<button title="' . $i18n->getHtml("[[palette.modify]]") . '" class="tiny icon_only div_icon tooltip hover right link_button" data-link="/mailman/mailmanMod?group=' . $group . '&_TARGET=' . $ml['OID'] . '" target="_self" formtarget="_self"><div class="ui-icon ui-icon-pencil"></div></button><br>';
                // Add 'Delete' button:
                $buttons .= '<a class="lb" href="/mailman/mailmanList?group=' . $group . '&_REMOVE=' . $ml['OID'] . '"><button class="tiny icon_only div_icon tooltip hover dialog_button" title="' . $i18n->getHtml("[[palette.remove_help]]") . '"><div class="ui-icon ui-icon-trash"></div></button></a><br>';

                // Cool beans with I18N singular/plural. But we can't get this relieably done in all languages.
                // Hence it's all singular here:
                $ScrollList->addEntry(array(
                $ml["name"], $i18n->interpolate("[[base-mailman.numSubs]]", array('num' => count($members), 'plural' => '')),
                $desc,
                $buttons
                ), "", false, $i);
            }
        }

        // Show the ScrollList of the MailingLists:
        $block->addFormField(
            $factory->getRawHTML("mailmanList", $ScrollList->toHtml()),
            $factory->getLabel('mailmanList', false, array('group' => $vsite['fqdn'])),
            $defaultPage
        );

        // Add the buttons for those who can edit this page:
        if ($access == 'rw') {
            $block->addButton($factory->getSaveButton($BxPage->getSubmitAction()));
            $block->addButton($factory->getCancelButton("/mailman/mailmanList?group=$group"));
        }

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

        // Add hidden Modal for Delete-Confirmation:
        $page_body[] = '
            <div class="display_none">
                        <div id="dialog" class="dialog_content narrow no_dialog_titlebar" title="' . $i18n->getHtml("[[base-mailman.removeConfirmQuestion]]") . '">
                            <div class="block">
                                    <div class="section">
                                            <h1>' . $i18n->getHtml("[[base-mailman.removeConfirmQuestion]]") . '</h1>
                                            <div class="dashed_line"></div>
                                            <p>' . $i18n->getHtml("[[base-mailman.ListRemoveConfirmInfo]]") . '</p>
                                    </div>
                            </div>
                        </div>
            </div>';

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