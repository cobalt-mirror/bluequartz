<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Pusher extends MX_Controller {

    /**
     * Index Page for this controller.
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
        $i18n = new I18n("base-disk", $CI->BX_SESSION['loginUser']['localePreference']);
        $system = $CI->getSystem();

        // Initialize Capabilities so that we can poll the access rights as well:
        $Capabilities = new Capabilities($CI->cceClient, $CI->BX_SESSION['loginName'], $CI->BX_SESSION['sessionId']);       

        // Required array setup:
        $errors = array();
        $extra_headers = array();

        // Get URL params:
        $get_form_data = $CI->input->get(NULL, TRUE);

        // Check if we have a $pm cookie:
        $pm_cookie = $CI->input->cookie('pm');
        if (isset($pm_cookie)) {
            // Yes? Use it!
            $pm = $CI->input->cookie('pm');
        }
        if (isset($get_form_data['pm'])) {
            $pm = $get_form_data['pm'];
        }

        // -- Actual page logic start:
        if ($Capabilities->getAllowed('systemAdministrator')) {
            if ((isset($pm)) && (isset($get_form_data['group']))) {
                // Get MYSQL_Vsite settings for this site:
                list($sites) = $CI->cceClient->find("Vsite", array("name" => $get_form_data['group']));
                $MYSQL_Vsite = $CI->cceClient->get($sites, 'MYSQL_Vsite');
                // Fetch MySQL details for this site:
                $db_enabled = $MYSQL_Vsite['enabled'];
                $db_username = $MYSQL_Vsite['username'];
                $db_pass = $MYSQL_Vsite['pass'];
                $db_host = $MYSQL_Vsite['host'];
            }
            else {
                $systemOid = $CI->cceClient->get($system['OID'], "mysql");
                $db_username = $systemOid{'mysqluser'};
                $mysqlOid = $CI->cceClient->find("MySQL");
                $mysqlData = $CI->cceClient->get($mysqlOid[0]);
                $db_pass = $mysqlData{'sql_rootpassword'};
                $db_host = $mysqlData{'sql_host'};
            }
        }
        elseif ($Capabilities->getAllowed('siteAdmin')) {
            if ($Capabilities->getAllowed('manageSite')) {
                if (isset($get_form_data['group'])) {
                    $group = $get_form_data['group'];
                }
            }
            else {
                $group = $user["site"];
            }

            if (isset($group)) {
                // Get MYSQL_Vsite settings for this site:
                list($sites) = $CI->cceClient->find("Vsite", array("name" => $group));
                $MYSQL_Vsite = $CI->cceClient->get($sites, 'MYSQL_Vsite');

                // Fetch MySQL details for this site:
                $db_enabled = $MYSQL_Vsite['enabled'];
                $db_username = $MYSQL_Vsite['username'];
                $db_pass = $MYSQL_Vsite['pass'];
                $db_host = $MYSQL_Vsite['host'];
            }
            else {
                $db_enabled = "0";
            }

            if ($db_enabled == "0") {
                $db_host = "localhost";
                $db_username = "";
                $db_pass = "";
            }
        }

        // Sanity checks:
        if (!isset($db_host)) {
            // Nice people say goodbye, or CCEd waits forever:
            $CI->cceClient->bye();
            $CI->serverScriptHelper->destructor();
            header("Location: /phpmyadmin/signon");
            exit;
        }

        //-- Generate page:

        // Tell BxPage which module we are currently in:
        $page_module = 'base_programs';

        // Assemble page_body:
        $BxPage = new BxPage();

        $BxPage->setVerticalMenu('base_phpmyadmin');
        $BxPage->setOutOfStyle('yes');      

        // Page body for auto-logins
        $page_body[] = '
            <form action="signon" method="post" name="frm" onLoad="document.frm.submit()">
            <input type="hidden" name="PMA_user" value="' . $db_username . '">
            <input type="hidden" name="PMA_password" value="' . $db_pass . '">
            <input type="hidden" name="hostname" value="' . $db_host . '">
            <input type="image" name="" value="">
            </form>
            <script language="JavaScript">
                   document.frm.submit();
            </script>
        ';

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