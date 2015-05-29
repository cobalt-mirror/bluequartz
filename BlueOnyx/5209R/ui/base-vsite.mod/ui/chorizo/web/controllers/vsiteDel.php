<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class VsiteDel extends MX_Controller {

    /**
     * Index Page for this controller.
     *
     * Past the login page this loads the page for /vsite/vsiteDel.
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
        $i18n = new I18n("base-vsite", $user['localePreference']);
        $system = $cceClient->getObject("System");

        // Initialize Capabilities so that we can poll the access rights as well:
        $Capabilities = new Capabilities($cceClient, $loginName, $sessionId);

        // -- Actual page logic start:

        // Not 'manageSite'? Bye, bye!
        if (!$Capabilities->getAllowed('manageSite')) {
            // Nice people say goodbye, or CCEd waits forever:
            $cceClient->bye();
            $serverScriptHelper->destructor();
            Log403Error("/gui/Forbidden403#1");
        }

        //
        //--- Handle form validation:
        //

        // Get URL params:
        $get_form_data = $CI->input->get(NULL, TRUE);

        //
        //-- Validate GET data:
        //

        if (isset($get_form_data['group'])) {
            // We have a delete transaction:
            $delSite = $get_form_data['group'];
        }
        else {
            // Don't play games with us!
            // Nice people say goodbye, or CCEd waits forever:
            $cceClient->bye();
            $serverScriptHelper->destructor();
            Log403Error("/gui/Forbidden403#2");
        }

        //
        //----- Security checks:
        //
        //      We need to find out if the Vsite with that 'name' exists.
        //      But we also need to make sure that it is under the ownership
        //      of the currently logged in 'createdUser'. Of course user
        //      'admin' has rights to delete all Vsites.
        //

        // Prep search array:
        $exact = array('name' => $delSite);

        // We're not 'systemAdministrator', so we limit the search to 'createdUser' => $loginName:
        if (!$Capabilities->getAllowed('systemAdministrator')) {
                // If the user is not 'systemAdministrator', then we only return Vsites that this user owns:
                $exact = array_merge($exact, array('createdUser' => $loginName));  
        }

        // Get a list of Vsite OID's:
        $vsites = $cceClient->findx('Vsite', $exact, array(), "", "");

        // At this point we should have one object. Not more and not less:
        if (count($vsites) != "1") {
            // Don't play games with us!
            // Nice people say goodbye, or CCEd waits forever:
            $cceClient->bye();
            $serverScriptHelper->destructor();
            Log403Error("/gui/Forbidden403#3");
        }
        elseif (is_file('/etc/DEMO')) {
            // We are in DEMO mode. So we don't delete:
            $cceClient->bye();
            $serverScriptHelper->destructor();
            $redirect_URL = "/vsite/vsiteList";
            header("location: $redirect_URL");
            exit;
        }
        else {
            // We continue with the deletion:
            // Initialize status to avoid race conditions
            // ... actually, let's not do that now.
            //fopen("http://localhost:444/status.php?statusId=remove$delSite&title=[[base-vsite.deletingSite]]&message=[[base-vsite.removingUsers]]&progress=0", "r");

            // Command to execute:
            $cmd = "/usr/sausalito/sbin/vsite_destroy.pl $delSite \"/vsite/vsiteList\"";

            // Do the dirty deeds:
            $serverScriptHelper->fork($cmd, "root", $sessionId);

            // Nice people say goodbye, or CCEd waits forever:
            $cceClient->bye();
            $serverScriptHelper->destructor();

            // Redirect to the processing page to follow the status of this transaction:
            header("Location: /gui/processing?statusId=remove$delSite&title=[[base-vsite.deletingSite]]&message=[[base-vsite.removingUsers]]&progress=0");
            exit;

        }

        // Nice people say goodbye, or CCEd waits forever:
        $cceClient->bye();
        $serverScriptHelper->destructor();

        // Can't imagine why we would get to this line.
        // But if we do, log a 403 and call it a day:
        Log403Error("/gui/Forbidden403#4");

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