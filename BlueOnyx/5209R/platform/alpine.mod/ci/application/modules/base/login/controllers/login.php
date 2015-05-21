<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Login extends MX_Controller {

    /**
     * Index Page for this controller.
     */

    public function index() {
        // We load the BlueOnyx helper library first of all, as we heavily depend on it:
        $this->load->helper('blueonyx');
        init_libraries();

        // Profiling and Benchmarking:
        bx_profiler();

        // Find out if CCEd is running. If it is not, we display an error message and quit:
        $cceClient = new CceClient();

        $CI =& get_instance();

        // locale and charset setup:
        $ini_langs = initialize_languages(TRUE);
        $locale = $ini_langs['locale'];
        $charset = $ini_langs['charset'];

        // Set cookie for locale if we do NOT have one yet. This HAS to be done here, as the entire
        // i18n she-bang heavily depends on it. 
        $cookie = array('name' => 'locale', 'path' => '/', 'value' => $locale, 'expire' => '31536000');
        $this->input->set_cookie($cookie);

        // Get the IP address of the user accessing the GUI:
        $userip = $this->input->ip_address();

        // Form GET data to see if this is a round about from base-wizard:
        $get_form_data = $CI->input->get(NULL, TRUE);
        $wizard = FALSE;
        $URLaddParams = '';
        if (isset($get_form_data['action'])) {
            if ($get_form_data['action'] == 'wizard') {
                $wizard = TRUE;
                $URLaddParams = '?action=wizard';
            }
        }

        // Call me a dirty little bastard. But CCEd gets stuck sometimes.
        // Which throws a wrench into the entire GUI. So this is how we
        // handle it: This script tries to establish a connection to CCEd.
        // It has a timeout of five seconds. After which it reports 'TIMEOUT':
        $cce_check = shell_exec('/usr/sausalito/bin/check_cce.pl');
        // If we do have a 'TIMEOUT' or no output at all, then we get down and dirty:
        if (($cce_check == "TIMEOUT") || ($cce_check == "")) {
            // We run the unstuck script vi SUDO. This is the ONLY command that user
            // 'admserv' has sudo capabilities for and it kills off stray CCEd processes,
            // pperld and cced.init. It then does a fast cced.init rehash to get us back up:
            $cce_unstuck = shell_exec('/usr/bin/sudo /usr/sausalito/bin/cced_unstuck.sh');
        }
        // If we get here, CCEd should be running. Either again, or because it was fine.

        if(!$cceClient->connect()) {
            if($locale == "") {
                $this->load->library('System');
                $system = new System();
                $locale = $system->getConfig("defaultLocale");
            }
            $i18n = new I18n("palette", $locale);

            // Set headers:
            $this->output->set_header("Cache-Control: no-store, no-cache, must-revalidate");
            $this->output->set_header("Cache-Control: post-check=0, pre-check=0");
            $this->output->set_header("Pragma: no-cache"); 
            $this->output->set_header("Content-language: $locale");
            $this->output->set_header("Content-type: text/html; charset=$charset");

            // Display the error message and quit:
            $cceDown = "<div style=\"text-align: center;\"><br><br><br><br><span style=\"color: #990000;\">" . $i18n->get("cceDown") . "</span></div>";
            echo "$cceDown";
            error_log("index.php: $cceDown");
            exit;
        }

        // Check to see if the user is already logged in as another user:
        if ($this->input->cookie('loginName') && $this->input->cookie('sessionId')) {
          // Release the old session id:
          $cceClient->authkey($this->input->cookie('loginName'),
          $this->input->cookie('sessionId'));
          $cceClient->endkey(); # release the session id
          $cceClient = new CceClient();
          $cceClient->connect();
        }

        // Get default theme cookie (if it exists):
        if ($this->input->cookie('skin_switcher_php-style')) {
            $skin = $this->input->cookie('skin_switcher_php-style');
        }
        else {
            $skin = 'skin_light.css';
        }

        // Set page title:
        $i18n = new I18n("base-alpine", $locale);
        preg_match("/^([^:]+)/", $_SERVER['HTTP_HOST'], $matches);
        $hostname = $matches[0];

        // Strip out the :444 or :81 from the hostname - if present:
        if (preg_match('/:/', $hostname)) {
            $hn_pieces = explode(":", $hostname);
            $hostname = $hn_pieces[0];
        }
        $page_title = $i18n->getHtml("loginPageTitle", "base-alpine", array("hostname" => $hostname));

        // I18n for our text elements on the login page:
        $WelcomeMsg = $i18n->getHtml("login","base-alpine",array("hostname" =>$hostname));
        $login_text = $i18n->getHtml("loginPageLogin");
        $Username =  $i18n->getHtml("loginPageUsername");
        $Password =  $i18n->getHtml("loginPagePassword");
        $SecureConnect = $i18n->getHtml("loginPageSecurity");
        $loginMessage = $i18n->getHtml("loginOkMessage");
        $loginFailed = $i18n->getHtml("loginAuthFailed");
        $my_yes = $i18n->getHtml("[[base-swupdate.yes]]");
        $my_no = $i18n->getHtml("[[base-swupdate.no]]");
        $noJS = $i18n->getHtml("[[base-alpine.loginNoJsMessage]]");

        // Get 'System' object
        $system = $cceClient->getObject('System');
        if ((!$system['isLicenseAccepted']) && ($wizard == FALSE)) {
            // Web based setup has not been completed. Redirect to /wizard
            header("Location: /wizard?from=login");
            exit;
        }

        // Get Form data:
        $form_data = $CI->input->post(NULL, TRUE);

        // Get URI string:
        $get_uri_string = uri_string();

        // URI string extraction:
        $uri_elements = mb_split("\/", $get_uri_string);

        // Login has expired. Show "Your login has expired ..." instead:
        if (($uri_elements[0] == "expired") && ($uri_elements[1] == "true")) {
          $loginMessage = $i18n->getHtml("loginExpiredMessage");
        }

        // Set up redirector to the expired page for later:
        if ((isset($uri_elements[0])) && (isset($uri_elements[1])) && (isset($uri_elements[2])) && (isset($uri_elements[3])) && (isset($uri_elements[4]))) {
            if (($uri_elements[0] == "expired") && ($uri_elements[1] == "true") && ($uri_elements[2] == "target")) {
                // Redirect to last visited page before expiry:
                $redirect_target = "/" . $uri_elements[3] . "/" . $uri_elements[4];
            }
        }
        elseif ($wizard == TRUE) {
            $redirect_target = "/wizard?from=urlparser";
        }
        else {
            // Redirect to GUI and let BxPage do another redirect. Yeah, this is lazy.
            $redirect_target = "/gui";
        }

        // If we have form data for a 'redirect_target' from the login form, we must take it into account as well:
        if (($form_data) && (isset($form_data['redirect_target']))) {
            if (($form_data['redirect_target'] != "/gui") && ($form_data['redirect_target'] != "/login") && ($form_data['redirect_target'] != "")) {
                // Form data is neither /gui, /login or blank, so we redirect:
                $redirect_target =  $form_data['redirect_target'];
            }
        }

        // Willfully logged out of the system. Show the farewell message instead:
        if (($uri_elements[0] == "logout") && ($uri_elements[1] == "true")) {
          $loginMessage = $i18n->getHtml("loginByeMessage");
          // Delete the cookies:
          delete_cookie("loginName");
          delete_cookie("sessionId");
          delete_cookie("userip");
          // Logout from CCE:
          $cceClient->endkey(); # release the session id
          $cceClient = new CceClient();
          $cceClient->connect();
        }

        // Willfully logged out of the system. Show the farewell message instead:
        if (($uri_elements[0] == "usessl") && (isset($uri_elements[1]))) {
          $loginMessage = $i18n->getHtml("'Secure Connect' selection has changed. For security reasons re-enter the login details.");
        }

        // Set up rules for form validation. Ideally we'd like to use the rules from CCE schemas:
        $this->form_validation->set_rules('username_field', $Username, 'trim|required|xss_clean');
        $this->form_validation->set_rules('password_field', $Password, 'trim|required|xss_clean');
        $this->form_validation->set_rules('secureConnect', $SecureConnect, 'trim|required');

        // Handle 'secureConnect' changes (and yes, this sucks):
        if (($uri_elements[0] == "usessl") && ($uri_elements[1] == "true")) {
          $secureConnect = "1";
          $sc_yes_selected = ' checked="checked"';
          $sc_no_selected = '';
        }
        elseif (($uri_elements[0] == "usessl") && ($uri_elements[1] == "false")) {
          $secureConnect = "0";
          $sc_yes_selected = '';
          $sc_no_selected = ' checked="checked"';
        }
        else {
          $secureConnect = "0";
          $sc_yes_selected = '';
          $sc_no_selected = ' checked="checked"';
        }

        // If we're already on HTTPS, show the correct buttons ticked. Additionally insert the right onClick() 
        // redirect URL for toggling between secureConnect on and off:
        if (is_HTTPS() == TRUE) {
            $url = " onclick=\"document.location.href='" . 'http://' . $_SERVER['SERVER_NAME'] . ':444/login' . "'\"";
            $secureConnect = "1";
            $sc_yes_selected = ' checked="checked"';
            $sc_no_selected = '';           
        }
        else {
            $url = " onclick=\"document.location.href='" . 'https://' . $_SERVER['SERVER_NAME'] . ':81/login' . "'\"";  
            $secureConnect = "0";
            $sc_yes_selected = '';
            $sc_no_selected = ' checked="checked"';
        }

        // Get Theme information from Cookie:
        if (isset($_COOKIE['theme_switcher_php-style'])) {
            $primaryColor = $_COOKIE['theme_switcher_php-style'];
            if ($primaryColor != "") {
                if (preg_match('/^theme_(.*)\.css$/', $primaryColor, $treffer)) {
                    $colorArray = array("blue", "navy", "red", "green", "magenta", "brown");
                    if (in_array($treffer[1], $colorArray)) {
                        $primaryColor = $treffer[1];
                    }
                    else {
                        $primaryColor = 'blue';
                    }
                }
                if (preg_match('/^switcher\.css$/', $primaryColor)) {
                    $primaryColor = 'black';
                }
            }
        }
        else {
            // No cookie for color. Return default color:
            $primaryColor = 'blue';
        }

        // Set headers:
        $this->output->set_header("Cache-Control: no-store, no-cache, must-revalidate");
        $this->output->set_header("Cache-Control: post-check=0, pre-check=0");
        $this->output->set_header("Pragma: no-cache"); 
        $this->output->set_header("Content-language: $locale");
        $this->output->set_header("Content-type: text/html; charset=$charset");

        // We are not yet validating form data:
        if ($this->form_validation->run() == FALSE) {

            // Therefore we pre-populate the $data array with defaults:
            $data = array(
                  'username_field' => set_value('username_field'),
                  'password_field' => set_value('password_field'),
                  'secureConnect' => $secureConnect,
                  'sc_yes_selected' => $sc_yes_selected,
                  'sc_no_selected' => $sc_no_selected,
                  'page_title' => $page_title,
                  'WelcomeMsg' => $WelcomeMsg,
                  'Username' => $Username,
                  'Password' => $Password,
                  'SecureConnect' => $SecureConnect,
                  'redirect_target' => $redirect_target,
                  'loginMessage' => $loginMessage,
                  'loginFailed' => $loginFailed,
                  'login_text' => $login_text,
                  'noJS' => $noJS,
                  'yes' => $my_yes,
                  'no' => $my_no,
                  'url' => $url,
                  'URLaddParams' => $URLaddParams,
                  'primaryColor' => $primaryColor
            );

            // Show the login form:
            $this->load->view('login_view', $data);

        }
        else {
            // Form data has been sanitized and validated. Now we check if it matches:

            // get session ID
            $sessionId = $cceClient->auth(set_value('username_field'), set_value('password_field'));

            // Get 'System' object
            $system = $cceClient->getObject('System');

            if ((!$system['isLicenseAccepted']) && ($wizard == FALSE)) {
                // Web based setup has not been completed. Redirect to /wizard
                header("Location: /wizard?from=else");
                exit;
            }

            // auth failed?
            if($sessionId == "") {

              // Login failed. We need to show the login form again with error message.
              // Therefore we pre-populate the $data array with defaults:
              $data = array(
                'username_field' => set_value('username_field'),
                'password_field' => set_value('password_field'),
                'secureConnect' => $secureConnect,
                'sc_yes_selected' => $sc_yes_selected,
                'sc_no_selected' => $sc_no_selected,
                'page_title' => $page_title,
                'WelcomeMsg' => $WelcomeMsg,
                'Username' => $Username,
                'Password' => $Password,
                'SecureConnect' => $SecureConnect,
                'redirect_target' => $redirect_target,
                'loginMessage' => $loginFailed,
                'loginFailed' => $loginFailed,
                'login_text' => $login_text,
                'noJS' => $noJS,
                'yes' => $my_yes,
                'no' => $my_no,
                'url' => $url,
                'URLaddParams' => $URLaddParams,
                'primaryColor' => $primaryColor
              );
              // Show the login form again:
              $this->load->view('login_view', $data);
            }
            else {
              //
              // If we get this far, username and password were correct.
              //

              // Now if this user is known (and at this point he is), then we check his CODB object to 
              // learn which locale (and charset) he's usually using and use that one instead. After all,
              // it might be different from what his browser tells us:
              $user = $cceClient->getObject("User", array("name" => set_value('username_field')));
              // Now set the locale based on the users localePreference - if specified and known:
              if ($user['localePreference']) {
                $locale = $user['localePreference'];
              }

              // Send cookies that expire at end of the browser session. 
              setcookie("loginName", set_value('username_field'), time()+60*60*24*365, "/");
              setcookie("sessionId", $sessionId, "0", "/");
              setcookie("userip", $userip, "0", "/");
              //setcookie("skin_switcher_php-style", $skin);

              // Set new locale to cookie, too, but set an expiry of 365 days:
              $cookie = array('name' => 'locale', 'path' => '/', 'value' => $locale, 'expire' => '31536000');
              $this->input->set_cookie($cookie);

              //
              //-- Start: Chorizo's Style handling:
              //
    
              // Read the Chorizo's Style from User's CODB object:
              $usersChorizoStyleObject = json_decode(urldecode($user['ChorizoStyle']));
    
              // Turn Style Object into an Array:
              $usersChorizoStyle = (array) $usersChorizoStyleObject;

              // If the user uses a mobile device, we override the fixed layout and switch
              // to the fluid one for a better user-experience:
              if ($CI->agent->is_mobile()) {
                $usersChorizoStyle['layout_switcher_php-style'] = 'layout_fluid.css';
              }

              // Push out cookies for the Users known Style:
              foreach ($usersChorizoStyle as $key => $value) {
                $theme_cookie = array('name' => $key, 'path' => '/', 'value' => $value, 'expire' => '31536000');
                $this->input->set_cookie($theme_cookie);
              }
    
              //
              //-- End: Chorizo's Style handling.
              //

              $nav_body = "<pre>Logging in ...</pre>";

              $data = array(
                'username_field' => set_value('username_field'),
                'password_field' => set_value('password_field'),
                'secureConnect' => $secureConnect,
                'sc_yes_selected' => $sc_yes_selected,
                'sc_no_selected' => $sc_no_selected,
                'page_title' => $page_title,
                'sessionId' => $sessionId,
                'loginName' => set_value('username_field'),
                'page_body' => $nav_body,
                'URLaddParams' => $URLaddParams,
                'primaryColor' => $primaryColor
              );

              // Gandalf is not shouting "Thou shall not pass!", so we proceed:

              // Redirect to the SSL port:
              if ((set_value('secureConnect') == "1") && ($_SERVER['SERVER_PORT'] != '81')) {
                  header("Location: https://$hostname:81/usessl/true");
                  exit;
              }
              if ((set_value('secureConnect') == "0") && ($_SERVER['SERVER_PORT'] != '444')) {
                  header("Location: http://$hostname:444/usessl/false");
                  exit;
              }

              if ($wizard == TRUE) {
                $redirect_target = '/wizard?from=alldone';
              }

              // Redirect to /gui:
              header("Location: $redirect_target");
              exit;
            }
        }
    }
}

/*
Copyright (c) 2015 Michael Stauber, SOLARSPEED.NET
Copyright (c) 2015 Team BlueOnyx, BLUEONYX.IT
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