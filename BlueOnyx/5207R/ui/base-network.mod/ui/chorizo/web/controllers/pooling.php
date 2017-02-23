<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Pooling extends MX_Controller {

    /**
     * Index Page for this controller.
     *
     * Past the login page this loads the page for /network/pooling.
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
        $i18n = new I18n("base-network", $CI->BX_SESSION['loginUser']['localePreference']);
        $system = $CI->getSystem();

        // Initialize Capabilities so that we can poll the access rights as well:
        $Capabilities = new Capabilities($CI->cceClient, $CI->BX_SESSION['loginName'], $CI->BX_SESSION['sessionId']);

        // -- Actual page logic start:

        // Not 'serverNetwork'? Bye, bye!
        if (!$Capabilities->getAllowed('serverNetwork')) {
            // Nice people say goodbye, or CCEd waits forever:
            $CI->cceClient->bye();
            $CI->serverScriptHelper->destructor();
            Log403Error("/gui/Forbidden403");
        }

        //
        //--- Get CODB-Object of interest: 
        //

        $network = $CI->cceClient->get($system['OID'], 'Network');
        $enabled = $network['pooling'];

        // Get all adminUsers:
        $oids = $CI->cceClient->findx('User', 
                                        array('capLevels' => 'adminUser'), 
                                        array(), 
                                        'ascii', 
                                        'name'); 
         
        foreach ($oids as $oid) { 
            $admins[$oid] = $CI->cceClient->get($oid); 
        } 

        // Add 'admin' as well:
        $oids = $CI->cceClient->findx('User', 
                                        array('name' => 'admin'), 
                                        array(), 
                                        'ascii', 
                                        'name');
        foreach ($oids as $oid) { 
            $admins[$oid] = $CI->cceClient->get($oid); 
        }

        $oids = $CI->cceClient->findx('IPPoolingRange', array(), array(), 'old_numeric', 'creation_time');
        $ranges = array();

        foreach ($oids as $oid) {
          $ranges[$oid] = $CI->cceClient->get($oid);
        }

        // We start without any active errors:
        $errors = array();
        $extra_headers =array();
        $ci_errors = array();
        $my_errors = array();

        //
        //--- Handle form validation:
        //

        // Shove submitted input into $form_data after passing it through the XSS filter:
        $form_data = $CI->input->post(NULL, TRUE);

        // Form fields that are required to have input:
        $required_keys = array('');

        // Set up rules for form validation. These validations happen before we submit to CCE and further checks based on the schemas are done:

        // Empty array for key => values we want to submit to CCE:
        $attributes = array();
        // Items we do NOT want to submit to CCE:
        $ignore_attributes = array("");
        if (is_array($form_data)) {
            // Function GetFormAttributes() walks through the $form_data and returns us the $parameters we want to
            // submit to CCE. It intelligently handles checkboxes, which only have "on" set when they are ticked.
            // In that case it pulls the unticked status from the hidden checkboxes and addes them to $parameters.
            // It also transformes the value of the ticked checkboxes from "on" to "1". 
            //
            // Additionally it generates the form_validation rules for CodeIgniter.
            //
            // params: $i18n                i18n Object of the error messages
            // params: $form_data           array with form_data array from CI
            // params: $required_keys       array with keys that must have data in it. Needed for CodeIgniter's error checks
            // params: $ignore_attributes   array with items we want to ignore. Such as Labels.
            // return:                      array with keys and values ready to submit to CCE.
            $attributes = GetFormAttributes($i18n, $form_data, $required_keys, $ignore_attributes, $i18n);
        }
        //Setting up error messages:
        $CI->form_validation->set_message('required', $i18n->get("[[palette.val_is_required]]", false, array("field" => "\"%s\"")));        

        // Do we have validation related errors?
        if ($CI->form_validation->run() == FALSE) {

            if (validation_errors()) {
                // Set CI related errors:
                $ci_errors = array(validation_errors('<div class="alert dismissible alert_red"><img width="40" height="36" src="/.adm/images/icons/small/white/alarm_bell.png"><strong>', '</strong></div>'));
            }           
            else {
                // No errors. Pass empty array along:
                $ci_errors = array();
            }
        }

        //
        //--- Own error checks:
        //

        if ($CI->input->post(NULL, TRUE)) {
            // Not needed. Thank you, jQuery!
        }

        //
        //--- At this point all checks are done. If we have no errors, we can submit the data to CODB:
        //

        // Join the various error messages:
        $errors = array_merge($ci_errors, $my_errors);

        // If we have no errors and have POST data, we submit to CODB:
        if ((count($errors) == "0") && ($CI->input->post(NULL, TRUE))) {

            // We have no errors. We submit to CODB.

            // Actual submit to CODB:
            $CI->cceClient->set($system['OID'], "Network",  array('pooling' => $attributes['enabled']));

            // CCE errors that might have happened during submit to CODB:
            $CCEerrors = $CI->cceClient->errors();
            foreach ($CCEerrors as $object => $objData) {
                // When we fetch the CCE errors it tells us which field it bitched on. And gives us an error message, which we can return:
                $errors[] = ErrorMessage($i18n->get($objData->message, true, array('key' => $objData->key)) . '&nbsp;');
            }
        }

        //
        //-- Generate page:
        //

        // Prepare Page:
        $factory = $CI->serverScriptHelper->getHtmlComponentFactory("base-network", "/network/pooling");
        $BxPage = $factory->getPage();
        $BxPage->setErrors($errors);
        $i18n = $factory->getI18n();

        // Set Menu items:
        $BxPage->setVerticalMenu('base_serverconfig');
        $BxPage->setVerticalMenuChild('base_sitepooling');
        $page_module = 'base_sysmanage';

        $defaultPage = "basicSettingsTab";

        $block =& $factory->getPagedBlock("pooling_block", array($defaultPage));

        $block->setToggle("#");
        $block->setSideTabs(FALSE);
        $block->setDefaultPage($defaultPage);

        $block->setDisplayErrors(FALSE);

        $block->addFormField(
            $factory->getBoolean("enabled", $enabled),
            $factory->getLabel("enabledField"),
            $defaultPage
        );

        // Add the buttons
        $block->addButton($factory->getSaveButton($BxPage->getSubmitAction()));
        $block->addButton($factory->getCancelButton("/network/pooling"));


        // Add-Button:
        $block2 =& $factory->getPagedBlock("rangeList", array($defaultPage));
        $block2->setDisplayErrors(FALSE);
        $addAlias = "/network/poolingModify";
        $addbutton = $factory->getAddButton($addAlias, '[[base-network.add]]', "DEMO-OVERRIDE");
        $buttonContainer = $factory->getButtonContainer("", $addbutton);
        $block2->addFormField(
            $buttonContainer,
            $factory->getLabel("add"),
            $defaultPage
        );

        // Set up the ScrollList:
        $scrollList = $factory->getScrollList("rangeList", array("min", "max", "admin", " "), array()); 
        $scrollList->setAlignments(array("center", "center", "center", "center"));
        $scrollList->setDefaultSortedIndex('0');
        $scrollList->setSortOrder('descending');
        $scrollList->setSortDisabled(array('3'));
        $scrollList->setPaginateDisabled(FALSE);
        $scrollList->setSearchDisabled(FALSE);
        $scrollList->setSelectorDisabled(FALSE);
        $scrollList->enableAutoWidth(FALSE);
        $scrollList->setInfoDisabled(FALSE);
        $scrollList->setColumnWidths(array("155", "155", "270", "150")); // Max: 739px      

        reset($ranges);
        while (list($oid, $range) = each($ranges)) {

            // Loop through data and add the entries to scroll list
            // If we need to edit, make the $act_on field read/write, with save buttons
            // Else, just display the data in $range_mins, $range_maxes
            $min_string = "range_min$oid";
            $max_string = "range_max$oid";

            $minField = $range['min'];
            $maxField = $range['max'];

            // Create the buttons
            $modButt = $factory->getModifyButton("/network/poolingModify?ACTION=M&_oid=$oid");
            $modButt->setImageOnly(TRUE);
            $delButt = $factory->getRemoveButton("/network/poolingModify?ACTION=D&_oid=$oid");
            $delButt->setImageOnly(TRUE);

            if ((isset($range['admin'])) && ($range['admin'] != "")) {
                $adminField = join(', ', $CI->cceClient->scalar_to_array($range['admin']));
            }
            else {
                $adminField = "admin";
            }

            // Finally, add the entry to the list
            $scrollList->addEntry(array($minField, $maxField, $adminField, $factory->getCompositeFormField(
                                                array(
                                                  $modButt,
                                                  $delButt
                                                  )
                                                )));
        }

        // Push out the Scrollist:
        $block2->addFormField(
            $factory->getRawHTML("rangeList", $scrollList->toHtml()),
            $factory->getLabel("rangeList"),
            $defaultPage
        );

        $page_body[] = $block->toHtml();
        $page_body[] = $block2->toHtml();

        // Out with the page:
        $BxPage->HaveErrorMsgDisplayArea(FALSE);
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