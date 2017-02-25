<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class PluginsMin extends MX_Controller {

    /**
     * Index Page for this controller.
     */

    public function index() {

        $CI =& get_instance();
        
        // We load the BlueOnyx helper library first of all, as we heavily depend on it:
        $this->load->helper('blueonyx');
        init_libraries();

        // Start sane:
        $locale = "en_US";
        $charset = "UTF-8";

        // Get $sessionId and $loginName from Cookie (if they are set):
        $sessionId = $CI->input->cookie('sessionId');
        $loginName = $CI->input->cookie('loginName');
        $locale = $CI->input->cookie('locale');

        if (!isset($locale)) {
            $locale = 'en_US';
        }
        if ($locale == '') {
            $locale = 'en_US';
        }

        // Set headers:
        $CI->output->set_header("Cache-Control: no-store, no-cache, must-revalidate");
        $CI->output->set_header("Cache-Control: post-check=0, pre-check=0");
        $CI->output->set_header("Pragma: no-cache"); 
        $CI->output->set_header("Content-language: $locale");
        $CI->output->set_header("Content-type: text/html; charset=$charset");

        $i18n = new I18n("palette", $locale);

        // Prepare the messages output for our jQuery script:

        // These are for the checks that are already included in the stock validator.js:
        $messages = array(
                    'addAll' => $i18n->get("[[palette.addAll]]"),
                    'removeAll' => $i18n->get("[[palette.removeAll]]"),
                    'itemsCount' => $i18n->get("[[palette.itemsCount]]")
            );

        // Show the data:
        $this->load->view('pluginsmin', $messages);

    }
}

/*
Copyright (c) 2014-2017 Michael Stauber, SOLARSPEED.NET
Copyright (c) 2014-2017 Team BlueOnyx, BLUEONYX.IT
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