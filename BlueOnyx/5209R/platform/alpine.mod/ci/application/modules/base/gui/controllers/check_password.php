<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * BlueOnyx Password strength check utility
 *
 * @package   Check_Password
 * @author    Michael Stauber
 * @link      http://www.solarspeed.net
 * @license   http://devel.blueonyx.it/pub/BlueOnyx/licenses/SUN-modified-BSD-License.txt
 * @version   2.0
 */

class Check_Password extends MX_Controller {

    public function index() {

        $CI =& get_instance();

        // We load the BlueOnyx helper library first of all, as we heavily depend on it:
        $this->load->helper('blueonyx');
        init_libraries();

        // Get $sessionId and $loginName from Cookie (if they are set) and store them in $CI->BX_SESSION:
        $CI->BX_SESSION['sessionId'] = $CI->input->cookie('sessionId');
        $CI->BX_SESSION['loginName'] = $CI->input->cookie('loginName');

        // Line up the ducks for CCE-Connection and store them for re-usability in $CI:
        include_once('ServerScriptHelper.php');
        $CI->serverScriptHelper = new ServerScriptHelper($CI->BX_SESSION['sessionId'], $CI->BX_SESSION['loginName']);
        $CI->cceClient = $CI->serverScriptHelper->getCceClient();

        $user = $CI->BX_SESSION['loginUser'];

        // locale and charset setup:
        $ini_langs = initialize_languages(TRUE);
        $locale = $ini_langs['locale'];
        $charset = $ini_langs['charset'];

        // Now set the locale based on the users localePreference - if specified and known:
        if ($user['localePreference']) {
            $locale = $user['localePreference'];
        }

        // Set headers:
        $CI->output->set_header("Cache-Control: no-store, no-cache, must-revalidate");
        $CI->output->set_header("Cache-Control: post-check=0, pre-check=0");
        $CI->output->set_header("Pragma: no-cache"); 
        $CI->output->set_header("Content-language: $locale");
        $CI->output->set_header("Content-type: text/html; charset=$charset");

        // Initialize I18n:
        $i18n = new I18n("palette", $locale);

        $form_data = $CI->input->post();

        // Handle form data:
        if (isset($form_data["password"])) {
            $password = $form_data["password"];
        }
        else {
            $password = "";
        }

        // Start sane:
        $data['result'] = $i18n->getHtml("[[palette.pw_way_too_short]]");

        if (function_exists('crack_opendict')) {
            // Poll cracklib:
            $dictionary = crack_opendict('/usr/share/dict/pw_dict') or die('Unable to open CrackLib dictionary');
            $check = crack_check($dictionary, $password);
            $diag = crack_getlastmessage();
            crack_closedict($dictionary);

            // Note to self: We don't check for '&', because we can't. The XSS filter is on globally
            // due to $config['global_xss_filtering'] = TRUE; in application/config/config.php.
            // So it gets filtered out. So you enter a '&' and we get nothing. Fun and games!

            // Standard error text for illegal chars: "Password contains illegal characters."

            // Check if Password matches our 'password' regexp:
            $illegal_chars = "0";
            if (preg_match("/\s/", $password)) {
                $data['result'] = $i18n->getHtml("[[base-alpine.pw_illegal_chars]]");
                $illegal_chars = "1";
            }
            elseif (preg_match("/\"/", $password)) {
                $data['result'] = $i18n->getHtml("[[base-alpine.pw_illegal_chars]]");
                $illegal_chars = "1";
            }
            elseif (preg_match("/'/", $password)) {
                $data['result'] = $i18n->getHtml("[[base-alpine.pw_illegal_chars]]");
                $illegal_chars = "1";
            }
            elseif (preg_match("/\//", $password)) {
                $data['result'] = $i18n->getHtml("[[base-alpine.pw_illegal_chars]]");
                $illegal_chars = "1";
            }
            elseif (preg_match("/</", $password)) {
                $data['result'] = $i18n->getHtml("[[base-alpine.pw_illegal_chars]]");
                $illegal_chars = "1";
            }
            elseif (preg_match("/>/", $password)) {
                $data['result'] = $i18n->getHtml("[[base-alpine.pw_illegal_chars]]");
                $illegal_chars = "1";
            }
            elseif (preg_match("/\?/", $password)) {
                $data['result'] = $i18n->getHtml("[[base-alpine.pw_illegal_chars]]");
                $illegal_chars = "1";
            }
            elseif (preg_match("/\@/", $password)) {
                $data['result'] = $i18n->getHtml("[[base-alpine.pw_illegal_chars]]");
                $illegal_chars = "1";
            }
            elseif (preg_match("/\[/", $password)) {
                $data['result'] = $i18n->getHtml("[[base-alpine.pw_illegal_chars]]");
                $illegal_chars = "1";
            }
            elseif (preg_match("/\]/", $password)) {
                $data['result'] = $i18n->getHtml("[[base-alpine.pw_illegal_chars]]");
                $illegal_chars = "1";
            }
            elseif (preg_match("/\^/", $password)) {
                $data['result'] = $i18n->getHtml("[[base-alpine.pw_illegal_chars]]");
                $illegal_chars = "1";
            }
            elseif (preg_match("/\_/", $password)) {
                $data['result'] = $i18n->getHtml("[[base-alpine.pw_illegal_chars]]");
                $illegal_chars = "1";
            }
            elseif (preg_match("/`/", $password)) {
                $data['result'] = $i18n->getHtml("[[base-alpine.pw_illegal_chars]]");
                $illegal_chars = "1";
            }
            elseif (preg_match("/{/", $password)) {
                $data['result'] = $i18n->getHtml("[[base-alpine.pw_illegal_chars]]");
                $illegal_chars = "1";
            }
            elseif (preg_match("/\|/", $password)) {
                $data['result'] = $i18n->getHtml("[[base-alpine.pw_illegal_chars]]");
                $illegal_chars = "1";
            }
            elseif (preg_match("/}/", $password)) {
                $data['result'] = $i18n->getHtml("[[base-alpine.pw_illegal_chars]]");
                $illegal_chars = "1";
            }
            elseif (preg_match("/~/", $password)) {
                $data['result'] = $i18n->getHtml("[[base-alpine.pw_illegal_chars]]");
                $illegal_chars = "1";
            }
            elseif ($illegal_chars == "0") {

                // Parse the return strings from cracklib and localize them:
                if (preg_match('/^it\'s WAY too short$/', $diag)) {
                    $data['result'] = $i18n->getHtml("[[palette.pw_way_too_short]]");
                }
                elseif (preg_match('/^it is too short$/', $diag)) {
                    $data['result'] = $i18n->getHtml("[[palette.pw_too_short]]");
                }
                elseif (preg_match('/^it does not contain enough DIFFERENT characters$/', $diag)) {
                    $data['result'] = $i18n->getHtml("[[palette.pw_not_nuff_different]]");
                }
                elseif (preg_match('/^it is all whitespace$/', $diag)) {
                    $data['result'] = $i18n->getHtml("[[palette.pw_all_whitespace]]");
                }
                elseif (preg_match('/^it is too simplistic\/systematic$/', $diag)) {
                    $data['result'] = $i18n->getHtml("[[palette.pw_too_simple]]");
                }
                elseif (preg_match('/^it looks like a National Insurance (.*)$/', $diag)) {
                    $data['result'] = $i18n->getHtml("[[palette.pw_insurance_number]]");
                }
                elseif (preg_match('/^it is based on a dictionary word$/', $diag)) {
                    $data['result'] = $i18n->getHtml("[[palette.pw_dictionary_word]]");
                }
                elseif (preg_match('/^it is based on a \(reversed\) dictionary word$/', $diag)) {
                    $data['result'] = $i18n->getHtml("[[palette.pw_reversed_dictionary_word]]");
                }
                elseif (preg_match('/^strong password$/', $diag)) {
                    $data['result'] = $i18n->getHtml("[[palette.pw_strong_password]]");
                }
                else {
                    // In case the localization fails, return the cracklib output directly:
                    $data['result'] = $diag;
                }
            }
        }
        else {
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
            if ($sp->validate($password) === false) {
                $PWerrors = $sp->get_errors();
                $data['result'] = $PWerrors[0];
            }
            else {
                $data['result'] = $i18n->getHtml("[[palette.pw_strong_password]]");
            }
        }

        // Show the results:
        $this->load->view('check_password_view', $data);

    }
}

/*
Copyright (c) 2016-2017 Michael Stauber, SOLARSPEED.NET
Copyright (c) 2016-2017 Team BlueOnyx, BLUEONYX.IT
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