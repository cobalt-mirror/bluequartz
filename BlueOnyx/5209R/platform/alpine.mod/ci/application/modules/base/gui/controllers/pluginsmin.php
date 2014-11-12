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


	    // Get $sessionId and $loginName from Cookie (if they are set):
	    $sessionId = $CI->input->cookie('sessionId');
	    $loginName = $CI->input->cookie('loginName');

	    // Call 'ServerScriptHelper.php' and check if the login is still valid:
	    // And bloody hell! We can't use the load->helper() function for this one or it blows up:
	    include_once('ServerScriptHelper.php');
	    $serverScriptHelper = new ServerScriptHelper($sessionId, $loginName);
	    //$this->cceClient->authkey($loginName, $sessionId);
	    $this->cceClient = $serverScriptHelper->getCceClient();

		$user = $this->cceClient->getObject("User", array("name" => $loginName));
		$access = $serverScriptHelper->getAccessRights($this->cceClient);

		// I cannot stress how important this is: Say 'bye' and use the deconstructor() whenever
		// you are done talking to CCE. If you don't and the script buggers out, the cced-child
		// process will hang around forever. So we do this religiously here, just to be damn sure:
		$this->cceClient->bye();
		$serverScriptHelper->destructor();

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