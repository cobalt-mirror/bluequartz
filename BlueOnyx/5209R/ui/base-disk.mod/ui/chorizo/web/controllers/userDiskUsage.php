<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class UserDiskUsage extends MX_Controller {

    /**
     * Index Page for this controller.
     *
     * Maps to the following URL
     *      http://example.com/index.php/userDiskUsage
     *  - or -  
     *      http://example.com/index.php/userDiskUsage/index
     *  - or -
     *      http://example.com/userDiskUsage/
     *
     * Past the login page this loads the page for userDiskUsage.
     *
     */

    public function index() {

        $CI =& get_instance();

        // We load the BlueOnyx helper library first of all, as we heavily depend on it:
        $this->load->helper('blueonyx');
        init_libraries();

        // Need to load 'BxPage' for page rendering:
        $this->load->library('BxPage');

        // Get $CI->BX_SESSION['sessionId'] and $CI->BX_SESSION['loginName'] from Cookie (if they are set) and store them in $CI->BX_SESSION:
        $CI->BX_SESSION['sessionId'] = $CI->input->cookie('sessionId');
        $CI->BX_SESSION['loginName'] = $CI->input->cookie('loginName');

        // Line up the ducks for CCE-Connection and store them for re-usability in $CI:
        include_once('ServerScriptHelper.php');
        $CI->serverScriptHelper = new ServerScriptHelper($CI->BX_SESSION['sessionId'], $CI->BX_SESSION['loginName']);
        $CI->cceClient = $CI->serverScriptHelper->getCceClient();

        $i18n = new I18n("base-disk", $CI->BX_SESSION['loginUser']['localePreference']);
        $system = $CI->getSystem();
        $user = $CI->BX_SESSION['loginUser'];

        // Make the users fullName safe for all charsets:
        $user['fullName'] = bx_charsetsafe($user['fullName']);

        // Required array setup:
        $errors = array();
        $extra_headers = array();

        //-- Get Diskspace info:
        $CI->cceClient->setObject("User", array("refresh" => time()), "Disk", array("name" => $CI->BX_SESSION['loginName']));

        // get objects
        $userDisk = $CI->cceClient->get($user['OID'], "Disk");
        $user = $CI->cceClient->get($user['OID']);

        // get user disk information
        $used = $userDisk["used"];
        $available = $userDisk["quota"] * 1024;

        $overquota = 0;
        // fix to correspond to new quota scheme, negative number means no quota set
        // 0 means 0, and any positive number is that number
        if ($available < 0) {
            $home = $CI->cceClient->getObject(
                        'Disk', 
                        array('mountPoint' => $user['volume'])
                    );
            $available = $home['total'] - $home['used'];
            $free = $available;
            $percentage = 0;
        } 
        else {
            // calculate free space for user and if they are over quota
            if (($available - $used) >= 0) {
                $free = $available-$used;
            }
            else {
                $overquota = 1;
                $free = 0;
            }

            // find out percentage used
            $percentage = round(100 * $used / $available);
            // don't show percentages greater than 100 because it 
            // could go way off the screen
            if ($percentage > 100) {
                $percentage = 100;
            }
        }

        // convert into MB / GB, TB:
        $available = simplify_number_diskspace($available, "kb", "2", "B");
        $used = simplify_number_diskspace($used, "kb", "2", "B");
        if ($used == "") {
            $used = "0B";
        }
        $free = simplify_number_diskspace($free, "kb", "2", "B");

        // Show over quota notification:
        if ($overquota) {
            $quotamessage = addTopTextField("userOverQuota", "", $i18n->getHtml("[[base-disk.overQuotaMsg]]"), "base-disk", "", "r", $i18n);
            $errors[] = '<div class="alert dismissible alert_red"><img width="40" height="36" src="/.adm/images/icons/small/white/alarm_bell.png"><strong>' . $i18n->get("[[base-disk.userOverQuota]]") . ": " . $i18n->get("[[base-disk.overQuotaMsg]]") . '</strong></div>';
        }
        else {
            $quotamessage = "";
        }

        //-- Generate page:

        // Tell BxPage which module we are currently in:
        $page_module = 'base_personalProfile';

        // Assemble page_body:
        $BxPage = new BxPage();

        $page_body[] = addInputForm(
                                    $i18n->get("diskUsageFor", "base-disk", array("userName" => $CI->BX_SESSION['loginName'])), 
                                    array("toggle" => "#"),
                                    addTextField("userDiskUsed", "", $used, "base-disk", "", "r", $i18n) .
                                    addTextField("userDiskFree", "", $free, "base-disk", "", "r", $i18n) .
                                    getBar("base-disk", "userDiskPercentage", $percentage, $i18n->getHtml("[[base-disk.userDiskPercentage_moreInfo]]", false, array("percentage" => $percentage, "used" =>$used, "total" => $available)),$i18n) .
                                    $quotamessage,
                                    "",
                                    $i18n,
                                    $BxPage,
                                    $errors
                                    );

        // Out with the page:
        $BxPage->render($page_module, $page_body);

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