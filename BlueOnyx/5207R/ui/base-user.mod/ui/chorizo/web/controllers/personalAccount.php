<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class PersonalAccount extends MX_Controller {

    /**
     * Index Page for this controller.
     *
     * Maps to the following URL
     *      http://example.com/index.php/personalAccount
     *  - or -  
     *      http://example.com/index.php/personalAccount/index
     *  - or -
     *      http://example.com/personalAccount/
     *
     * Past the login page this loads the page for personalAccount.
     *
     */

    public function index() {

        $CI =& get_instance();

        // We load the BlueOnyx helper library first of all, as we heavily depend on it:
        $this->load->helper('blueonyx');
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

        $i18n = new I18n("base-vsite", $CI->BX_SESSION['loginUser']['localePreference']); // really? base-vsite??
        $system = $CI->getSystem();
        $user = $CI->BX_SESSION['loginUser'];

        // Make the users fullName safe for all charsets:
        $user['fullName'] = Utf8Encode($user['fullName']);

        //
        //-- Start: Chorizo's Style handling:
        //

        // Read the Chorizo's Style from User's CODB object:
        $usersChorizoStyleObject = json_decode(urldecode($user['ChorizoStyle']));

        // Turn Style Object into an Array:
        $usersChorizoStyle = (array) $usersChorizoStyleObject;

        // Default Style:
        $ChorizoDefaultStyle =  array(
                'theme_switcher_php-style'   => 'theme_blue.css',
                'layout_switcher_php-style'  => 'layout_fixed.css',
                'nav_switcher_php-style'     => 'switcher.css',
                'skin_switcher_php-style'    => 'skin_light.css',
                'bg_switcher_php-style'      => 'switcher.css'
            );

        // Get currently used Style from Browser Cookie:
        $ChorizoCurrentStyle =  array(
                'theme_switcher_php-style'   => $CI->input->cookie('theme_switcher_php-style'),
                'layout_switcher_php-style'  => $CI->input->cookie('layout_switcher_php-style'),
                'nav_switcher_php-style'     => $CI->input->cookie('nav_switcher_php-style'),
                'skin_switcher_php-style'    => $CI->input->cookie('skin_switcher_php-style'),
                'bg_switcher_php-style'      => $CI->input->cookie('bg_switcher_php-style')
            );

        // Clone default Style:
        $ChorizoNewStyle = $ChorizoDefaultStyle;

        // Walk through the differences between default and current style and update the new Style-Array:
        foreach ($ChorizoDefaultStyle as $key => $value) {
            if (($ChorizoCurrentStyle[$key] != $key) && ($ChorizoCurrentStyle[$key] != "")) {
                $ChorizoNewStyle[$key] = $ChorizoCurrentStyle[$key];
            }
            else {
                $ChorizoNewStyle[$key] = $value;
            }
        }

        // Push out cookies for the new Style:
        foreach ($ChorizoNewStyle as $key => $value) {
          $theme_cookie = array('name' => $key, 'path' => '/', 'value' => $value, 'expire' => '31572500');
          $this->input->set_cookie($theme_cookie);
        }

        // If this is NOT a Demo, then store the updated Style in CODB, too:
        if (!is_file('/etc/DEMO')) {
            $CI->cceClient->set($user['OID'], "", array('ChorizoStyle' => urlencode(json_encode($ChorizoNewStyle))));
        }

        //
        //-- End: Chorizo's Style handling:
        //

        // Required array setup:
        $errors = array();
        $extra_headers = array();

        // -- Actual page logic start:

        // find all possible locales
        $possibleLocales = array();
        $possibleLocales = stringToArray($system["locales"]);
        /*
         * don't show browser option for admin, because then it becomes unclear
         * what the system locale is.
         */
        if ($CI->BX_SESSION['loginName'] != "admin") {
            $possibleLocales = array_merge(array("browser"), $possibleLocales);
        }

        //-- Handle form validation:

        // We start without any active errors:
        $errors = array();
        $extra_headers =array();
        $ci_errors = array();
        $my_errors = array();

        // Shove submitted input into $form_data after passing it through the XSS filter:
        $form_data = $CI->input->post(NULL, TRUE);

        //Set up rules for form validation. These validations happen before we submit to CCE and further checks based on the schemas are done:
        $CI->form_validation->set_rules('fullNameField', $i18n->get("[[fullNameField]]"), 'trim|required|xss_clean');
        $CI->form_validation->set_rules('languageField', $i18n->get("[[languageField]]"), 'trim|xss_clean');
        $CI->form_validation->set_rules('newPasswordField', $i18n->get("[[newPasswordField]]"), 'trim|xss_clean');
        $CI->form_validation->set_rules('_newPasswordField_repeat', $i18n->get("[[_newPasswordField_repeat]]"), 'trim|xss_clean');

        //Setting up error messages:
        $CI->form_validation->set_message('required', $i18n->get("[[palette.val_is_required]]", false, array("field" => "\"%s\"")));

        // Now that we're done with the internal checks, we also do some checks of our own:
        if ($form_data['newPasswordField'] != $form_data['_newPasswordField_repeat']) {
                // The two entered passwords are not identical:
                $my_errors[] = ErrorMessage($i18n->get("[[base-user.error-password-invalid]]"). '<br>' . $i18n->get("[[palette.pw_not_identical]]"));
        }
        elseif (strcasecmp($CI->BX_SESSION['loginName'], $form_data['newPasswordField']) == 0) {
                // Username == Password? Baaaad idea!
                $my_errors[] = ErrorMessage($i18n->get("[[base-user.error-password-equals-username]]") . '<br>&nbsp;');
                $my_errors[] = ErrorMessage($i18n->get("[[base-user.error-invalid-password]]"));
        }
        elseif ($form_data['newPasswordField']) {

            if (function_exists('crack_opendict')) {

                // Open CrackLib Dictionary for usage:
                @$dictionary = crack_opendict('/usr/share/dict/pw_dict');

                // Perform password check with cracklib:
                $check = crack_check($dictionary, $form_data['newPasswordField']);

                // Retrieve messages from cracklib:
                $diag = crack_getlastmessage();

                if ($diag == 'strong password') {
                    // Nothing to do. Cracklib thinks it's a good password.
                }
                else {

                    // Parse the return strings from cracklib and localize them:
                    if (preg_match('/^it\'s WAY too short$/', $diag)) {
                        $diag_result = $i18n->getHtml("[[palette.pw_way_too_short]]");
                    }
                    elseif (preg_match('/^it is too short$/', $diag)) {
                        $diag_result = $i18n->getHtml("[[palette.pw_too_short]]");
                    }
                    elseif (preg_match('/^it does not contain enough DIFFERENT characters$/', $diag)) {
                        $diag_result = $i18n->getHtml("[[palette.pw_not_nuff_different]]");
                    }
                    elseif (preg_match('/^it is all whitespace$/', $diag)) {
                        $diag_result = $i18n->getHtml("[[palette.pw_all_whitespace]]");
                    }
                    elseif (preg_match('/^it is too simplistic\/systematic$/', $diag)) {
                        $diag_result = $i18n->getHtml("[[palette.pw_too_simple]]");
                    }
                    elseif (preg_match('/^it looks like a National Insurance (.*)$/', $diag)) {
                        $diag_result = $i18n->getHtml("[[palette.pw_insurance_number]]");
                    }
                    elseif (preg_match('/^it is based on a dictionary word$/', $diag)) {
                        $diag_result = $i18n->getHtml("[[palette.pw_dictionary_word]]");
                    }
                    elseif (preg_match('/^it is based on a \(reversed\) dictionary word$/', $diag)) {
                        $diag_result = $i18n->getHtml("[[palette.pw_reversed_dictionary_word]]");
                    }
                    elseif (preg_match('/^strong password$/', $diag)) {
                        $diag_result = $i18n->getHtml("[[palette.pw_strong_password]]");
                    }
                    else {
                        // In case the localization fails, return the cracklib output directly:
                        $diag_result = $diag;
                    }

                    $my_errors[] = ErrorMessage($i18n->get("[[base-user.error-password-invalid]]") . '<br>' . $diag_result);
                    $my_errors[] = ErrorMessage($i18n->get("[[base-user.error-invalid-password]]"));
                }

                // Close cracklib dictionary:
                crack_closedict($dictionary);
            }
            else {
                // No Cracklib support available. We have alternatives, though:

                $CI =& get_instance();
                $CI->load->library('StupidPass');
                // Override the default errors messages
                $hardlang = array(
                'length' => $i18n->getHtml("[[palette.pw_way_too_short]]"),
                'upper'  => $i18n->getHtml("[[palette.pw_not_nuff_different]]"),
                'lower'  => $i18n->getHtml("[[palette.pw_not_nuff_different]]"),
                'numeric'=> $i18n->getHtml("[[palette.pw_too_simple]]"),
                'special'=> $i18n->getHtml("[[palette.pw_too_simple]]"),
                'common' => $i18n->getHtml("[[palette.pw_dictionary_word]]"),
                'environ'=> $i18n->getHtml("[[palette.pw_too_simple]]"));

                // Supply reference of the environment (company, hostname, username, etc)
                $environmental = array('blueonyx', 'admin');
                $sp = new StupidPass(40, $environmental, '/usr/sausalito/ui/chorizo/ci/application/libraries/stupid-pass/StupidPass.default.dict', $hardlang);
                if ($sp->validate($form_data['newPasswordField']) === false) {
                    $PWerrors = $sp->get_errors();
                    $diag_result = $PWerrors[0];
                    $my_errors[] = ErrorMessage($i18n->get("[[base-user.error-password-invalid]]") . '<br>' . $diag_result);
                    $my_errors[] = ErrorMessage($i18n->get("[[base-user.error-invalid-password]]"));
                }
                else {
                    $diag_result = $i18n->getHtml("[[palette.pw_strong_password]]");
                }
            }
        }

        // We are not yet validating form data:
        if ($CI->form_validation->run() == FALSE) {

            if (validation_errors()) {
                // Set CI related errors:
                $ci_errors = array(validation_errors('<div class="alert dismissible alert_red"><img width="40" height="36" src="/.adm/images/icons/small/white/alarm_bell.png"><strong>', '</strong></div>'));
            }           
            else {
                // No errors. Pass empty array along:
                $ci_errors = array();
            }

            // Show form based on CODB data:
            $fullNameField = $user["fullName"];

        }
        else {
            // Form validation had no errors:
            $fullNameField = $form_data["fullNameField"];
        }

        //
        //--- At this point all checks are done. If we have no errors, we can submit the data to CODB:
        //

        // Join the various error messages:
        $errors = array_merge($ci_errors, $my_errors);

        // If we have no errors and have POST data, we submit to CODB:
        if ((count($errors) == "0") && ($CI->input->post(NULL, TRUE))) {
            // No errors, submit to CODB:

            // Assemble the data we want to submit:
            if ($user['localePreference'] == $form_data['languageField']) {
                $attributes = array("fullName" => $form_data["fullNameField"], "localePreference" => $form_data['languageField']);
            }
            else {
                $attributes = array("localePreference" => $form_data['languageField']);
            }
            if (($form_data['newPasswordField']) && (!is_file("/etc/DEMO"))) {
                $attributes["password"] = $form_data['newPasswordField'];
            }

            // Actual submit to CODB:
            if (isset($form_data['SID'])) {
                if ($form_data['SID'] == $CI->BX_SESSION['sessionId']) {
                    $CI->cceClient->setObject("User", $attributes, "", array("name" => $CI->BX_SESSION['loginName']));
                }
            }

            // CCE errors that might have happened during submit to CODB:
            $CCEerrors = $CI->cceClient->errors();
            foreach ($CCEerrors as $object => $objData) {
                // When we fetch the CCE errors it tells us which field it bitched on. And gives us an error message, which we can return:
                $errors[] = ErrorMessage($i18n->get($objData->message, true, array('key' => $objData->key)) . '<br>&nbsp;');
            }

            // Somewhat special: If we have no CCE errors after setting this stuff and the new locale
            // preference is different from the old one, then we redirect to /user/personalAccount to
            // reload the entire page. Otherwise we end up with the body being in the wrong locale:
            if ((count($errors) == "0") &&  ($form_data['languageField'] != $user['localePreference'])) {

                // Set new locale to cookie, too:
                $cookie = array('name' => 'locale', 'path' => '/', 'value' => $form_data['languageField'], 'expire' => '31572500');
                $CI->input->set_cookie($cookie);

                // Nice people say goodbye, or CCEd waits forever:
                $CI->cceClient->bye();
                $CI->serverScriptHelper->destructor();

                // Redirect:
                header("Location: /user/personalAccount");
                exit;
            }

        }

        //-- Generate page - Either with data out of CODB (no POST action) or with form submitted data (on POST action):

        // Prepare Page:
        $factory = $CI->serverScriptHelper->getHtmlComponentFactory("base-user", "/user/personalAccount");
        $BxPage = $factory->getPage();
        $BxPage->setErrors($errors);
        $i18n = $factory->getI18n();

        // Set Menu items:
        $BxPage->setVerticalMenu('base_controlpanel');
        $page_module = 'base_personalProfile';

        // Make the users fullName safe for all charsets:
        $user['fullName'] = bx_charsetsafe($user['fullName']);

        $defaultPage = "basicSettingsTab";

        $settings =& $factory->getPagedBlock("accountSettings", array($defaultPage));
        $settings->setCurrentLabel($factory->getLabel('accountSettings', false, array('userName' => $CI->BX_SESSION['loginName'])));        
        $settings->setToggle("#");
        $settings->setSideTabs(FALSE);

        // Full Name:
        $enter_fullName = $factory->getFullName("fullNameField", $fullNameField);
        $enter_fullName->setOptional(FALSE);
        $settings->addFormField(
                $enter_fullName,
                $factory->getLabel("fullNameField"),
                $defaultPage
                );

        // Locale selector:
        $locale = $factory->getLocale("languageField", $user['localePreference']);
        $locale->setPossibleLocales($possibleLocales);
        $settings->addFormField(
          $locale,
          $factory->getLabel("languageField"), $defaultPage
        );

        // Password:
        $mypw = $factory->getPassword("newPasswordField", "", "rw");
        $mypw->setConfirm(TRUE);
        $mypw->setOptional(TRUE);
        $mypw->setCheckPass(TRUE);
        $settings->addFormField(
          $mypw,
          $factory->getLabel("newPasswordField"), $defaultPage
        );

        // SID:
        $SID = $factory->getTextField("SID", $CI->BX_SESSION['sessionId'], "");
        $settings->addFormField(
          $SID,
          $factory->getLabel("SID"), $defaultPage
        );

        // Show Style Switcher:
        $settings->addFormField(
            $factory->getRawHTML("applet", showStyleSwitcher($i18n)),
            $factory->getLabel("AllowOverride_OptionsField"), 
            $defaultPage
        );

        // Add the buttons
        $settings->addButton($factory->getSaveButton($BxPage->getSubmitAction(), "DEMO-OVERRIDE"));
        $settings->addButton($factory->getCancelButton("/user/personalAccount"));

        $page_body[] = $settings->toHtml();

        // Out with the page:
        $BxPage->render($page_module, $page_body);

    }       
}
/*
Copyright (c) 2014-2018 Michael Stauber, SOLARSPEED.NET
Copyright (c) 2014-2018 Team BlueOnyx, BLUEONYX.IT
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