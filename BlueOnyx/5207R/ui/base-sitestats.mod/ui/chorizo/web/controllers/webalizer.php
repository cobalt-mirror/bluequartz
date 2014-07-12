<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Webalizer extends MX_Controller {

	/**
	 * Index Page for this controller.
	 *
	 * Past the login page this loads the page for /sitestats/webalizer.
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
		$i18n = new I18n("base-sitestats", $user['localePreference']);
		$system = $cceClient->getObject("System");

		// Initialize Capabilities so that we can poll the access rights as well:
		$Capabilities = new Capabilities($cceClient, $loginName, $sessionId);

		// Required array setup:
		$errors = array();
		$extra_headers = array();

		// -- Actual page logic start:

		//
		//--- URL String parsing:
		//
		$group = 'server';
		$file = "index.html";
		$inframe = "0";
		$get_form_data = $CI->input->get(NULL, TRUE);
		if (isset($get_form_data['group'])) {
			$group = $CI->security->xss_clean($get_form_data['group']);
			$group = $CI->security->sanitize_filename($group);
		}
		if (isset($get_form_data['file'])) {
			$file = $CI->security->xss_clean($get_form_data['file']);
			$file = $CI->security->sanitize_filename($file);
		}
		if (isset($get_form_data['inframe'])) {
			$inframe = $get_form_data['inframe'];
		}

		// Only menuServerServerStats, manageSite and siteAdmin should be here:
		if (!$Capabilities->getAllowed('menuServerServerStats') &&
			!$Capabilities->getAllowed('manageSite') &&
		    !($Capabilities->getAllowed('siteAdmin') &&
		      $group == $Capabilities->loginUser['site'])) {
			// Nice people say goodbye, or CCEd waits forever:
			$cceClient->bye();
			$serverScriptHelper->destructor();
			Log403Error("/gui/Forbidden403");
		}

		if ($inframe == "1") {
			if ($group) {
			    if ($group != 'server') {
			        @list($oid) = $cceClient->find('Vsite', array('name' => $group));
			        if (count($oid) == "0") {
						// Nice people say goodbye, or CCEd waits forever:
						$cceClient->bye();
						$serverScriptHelper->destructor();			        	
						header("Location: /404");
						exit;
			        }
			        $vsite_info = $cceClient->get($oid);
			        $fqdn = $vsite_info['fqdn'];
			        $fullPath = "/home/sites/" . $vsite_info['fqdn'] . "/webalizer/" . $file;
			    }
			    else {
			        if (is_dir("/var/www/html/usage")) {
			            $fullPath = "/var/www/html/usage/" . $file;
			        } else {
			            $fullPath = "/var/www/usage/" . $file;
			        }
			    }
			}
			else {
				// Nice people say goodbye, or CCEd waits forever:
				$cceClient->bye();
				$serverScriptHelper->destructor();
				header("Location: /404");
				exit;
			}

			// Nice people say goodbye, or CCEd waits forever:
			$cceClient->bye();
			$serverScriptHelper->destructor();

			if (file_exists($fullPath)) {
			    $fp = fopen ($fullPath, "r");
			    $data = array();
			    $data['result'] = "";
			    while(!feof($fp)) {
			        $string = fgets($fp, 4096);
			        $string=str_replace("<A HREF=\"./", "<A HREF=\"/sitestats/webalizer?inframe=" . $inframe . "&group=" . $group . "&file=", $string); 
			        $string=str_replace("<A HREF=\"usage", "<A HREF=\"/sitestats/webalizer?inframe=" . $inframe . "&group=" . $group . "&file=usage", $string); 
			        $string=str_replace("<IMG SRC=\"", "<IMG SRC=\"/sitestats/webalizer?inframe=" . $inframe . "&group=" . $group . "&file=", $string); 
			        $data['result'] .= $string;
			    }
			    $CI->output->set_header("Cache-Control: no-store, no-cache, must-revalidate");
			    $CI->output->set_header("Cache-Control: post-check=0, pre-check=0");
			    $CI->output->set_header("Pragma: no-cache"); 
				if (preg_match("/.png$/", $file)) {
					$CI->output->set_content_type('png');
				}
	    		// Show the results:
				$CI->load->view('check_password_view', $data);
			    @fclose($fp);
			}
			else{

				// Prepare Page:
				$factory = $serverScriptHelper->getHtmlComponentFactory("base-sitestats", "/sitestats/statSettings?group=$group");
				$BxPage = $factory->getPage();
				$BxPage->setErrors($errors);
				$BxPage->setOutOfStyle('yes');
				$i18n = $factory->getI18n();

				// Set Menu items:
				if ($group != 'server') {
					$BxPage->setVerticalMenu('base_siteusage');
					$BxPage->setVerticalMenuChild('base_webalizer');
					$page_module = 'base_sitemanage';
				}
				else {
					$BxPage->setVerticalMenu('base_serverusage');
					$BxPage->setVerticalMenuChild('base_server_webalizer');
					$page_module = 'base_sysmanage';
				}

				$defaultPage = "pageID";
				$block =& $factory->getPagedBlock("webusageDescription", array($defaultPage));

				$block->setToggle("#");
				$block->setSideTabs(FALSE);
				$block->setDefaultPage($defaultPage);

				// Stretch the PagedBlock() to a width of 720 pixels:
				$block->addFormField(
					$factory->getRawHTML("Spacer", '<IMG BORDER="0" WIDTH="720" HEIGHT="0" SRC="/libImage/spaceHolder.gif">'),
					$factory->getLabel("Spacer"),
					$defaultPage
				);

				$warning = $i18n->getClean("[[palette.sZeroRecords]]");
				$nodata = $factory->getTextField("_", $warning, 'r');
				$nodata->setLabelType("nolabel");
				$block->addFormField(
				    $nodata,
				    $factory->getLabel(" "),
				    $defaultPage
				    );

				// Nice people say goodbye, or CCEd waits forever:
				$cceClient->bye();
				$serverScriptHelper->destructor();

				$page_body[] = "<p>&nbsp;</p>" . $block->toHtml();

				// Out with the page:
			    $BxPage->render($page_module, $page_body);
			}
		}
		else {

		    //-- Generate page:

			// Prepare Page:
			$BxPage = new BxPage();

			// Set Menu items:
			if ($group != 'server') {
				$BxPage->setVerticalMenu('base_siteusage');
				$BxPage->setVerticalMenuChild('base_webalizer');
				$page_module = 'base_sitemanage';
			}
			else {
				$BxPage->setVerticalMenu('base_serverusage');
				$BxPage->setVerticalMenuChild('base_server_webalizer');
				$page_module = 'base_sysmanage';
			}

			$url = "/sitestats/webalizer?inframe=1&group=" . $group;

			// Nice people say goodbye, or CCEd waits forever:
			$cceClient->bye();
			$serverScriptHelper->destructor();

			// Page body:
			$page_body[] = addInputForm(
											$i18n->get("[[base-websitestats.summaryStats]]"),
											array("window" => $url, "toggle" => "#"), 
											addIframe($url, "1200", $BxPage),
											"",
											$i18n,
											$BxPage,
											$errors
										);


			// Out with the page:
		    $BxPage->render($page_module, $page_body);
		}

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