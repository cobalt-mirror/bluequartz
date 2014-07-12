<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class ManualInstall extends MX_Controller {

	/**
	 * Index Page for this controller.
	 *
	 * Past the login page this loads the page for /swupdate/manualInstall.
	 *
	 */

	public function index() {

		$CI =& get_instance();

	    // We load the BlueOnyx helper library first of all, as we heavily depend on it:
	    $this->load->helper('blueonyx');
	    // This page also needs the helpers/updateLib_helper.php:
	    $this->load->helper('updatelib');
	    init_libraries();

  		// Need to load 'BxPage' for page rendering:
  		$this->load->library('BxPage');
		$MX =& get_instance();

	    // Get $sessionId and $loginName from Cookie (if they are set):
	    $sessionId = $CI->input->cookie('sessionId');
	    $loginName = $CI->input->cookie('loginName');
	    $locale = $CI->input->cookie('locale');

		// Adds settings to avoid changing php.ini
		ini_set('memory_limit', '256M');
		ini_set('post_max_size ', '200M');
		ini_set('upload_max_filesize', '200M');
		ini_set('max_execution_time', '0');
		ini_set('max_input_time', '0');

	    // Line up the ducks for CCE-Connection:
	    include_once('ServerScriptHelper.php');
		$serverScriptHelper = new ServerScriptHelper($sessionId, $loginName);
		$cceClient = $serverScriptHelper->getCceClient();
		$user = $cceClient->getObject("User", array("name" => $loginName));
		$i18n = new I18n("base-swupdate", $user['localePreference']);
		$system = $cceClient->getObject("System");

		// Initialize Capabilities so that we can poll the access rights as well:
		$Capabilities = new Capabilities($cceClient, $loginName, $sessionId);

		// -- Actual page logic start:

		// Not 'managePackage'? Bye, bye!
		if (!$Capabilities->getAllowed('managePackage')) {
			// Nice people say goodbye, or CCEd waits forever:
			$cceClient->bye();
			$serverScriptHelper->destructor();
			Log403Error("/gui/Forbidden403");
		}

		// Get URL params:
		$get_form_data = $CI->input->get(NULL, TRUE);
		$post_form_data = $CI->input->post(NULL, TRUE);

		// Get the backUrl:
		if (isset($get_form_data['backUrl'])) {
			// URL string:
			$backUrl = $get_form_data['backUrl'];
		}
		elseif (isset($post_form_data['backUrl'])) {
			// Alternatively POST value:
			$backUrl = $post_form_data['backUrl'];
		}
		else {
			// Nothing? Then it's empty:
			$backUrl = "";
		}

		// Just to make sure it isn't empty or someone tried to be clever:
		if (!preg_match('/^\/swupdate\//', $backUrl)) {
			$backUrl = "/swupdate/newSoftware";
		}

		// Declare some constants
		$prepare_cmd = "/usr/sausalito/sbin/pkg_prepare.pl";
		$packageDir = "/home/packages";
		$magic_cmd = "/usr/bin/file";

		//
		//--- Handle form validation:
		//

	    // We start without any active errors:
	    $errors = array();
	    $ci_errors = array();
	    $my_errors = array();

		// Shove submitted input into $form_data after passing it through the XSS filter:
		$form_data = $CI->input->post(NULL, TRUE);

		//
		//--- Own error checks:
		//

		if ($CI->input->post(NULL, TRUE)) {

			$runas = ($Capabilities->getAllowed('adminUser') ? 'root' : $loginName);

			// Check which install method was selected:
			if ($form_data['locationField'] == 'urlField') {

				//
				//-- Install from URL:
				//

				// Check if URL appears to be valid:
				if (substr($form_data['urlField'], 0, 8) != "https://" && substr($form_data['urlField'], 0, 7) != "http://" && substr($form_data['urlField'], 0, 6) != "ftp://") {
					$my_errors[] = ErrorMessage($i18n->get("[[base-swupdate.invalidUrl]]") . '<br>&nbsp;');
				}
				else {
					// We seem to have a valid URL. Package name is the last piece of the URL:
					$names = explode("/", $form_data['urlField']);
					$nameField = $names[count($names)-1];

					// Install:
					$urlField = $form_data['urlField'];

					// Check if we have a valid URL. Because someone could call this with ...
					// http://www.smd.net/1.pkg";touch "/tmp/yougot0wned;chmod 755 /tmp/yougot0wned;/bin/sh /tmp/yougot0wned
					// ... and we'd execute that right on the shell as 'admserv'. Sure, that's like user 'admin'
					// rooting the box that he has already 'root' access for. But no excuses here. Better safe
					// than sorry. Note to self: This check requires PHP-5.2 or better.
					$ret = -1;
					if (filter_var($urlField, FILTER_VALIDATE_URL)) {
						$ret = $serverScriptHelper->shell("$prepare_cmd -u \"$urlField\"", $output, $runas, $sessionId);
					}

				    if ($ret != 0) {
				        // Deal with errors:
				        $ci_errors[] = new CceError('huh', 0, 'urlField', "[[base-swupdate.badFormat]]");
				    }
				    else {
				    	// If the 'prepare_cmd' was sucessful, we now have the raw PKG info in CODB:
						$SWUpdate = $cceClient->getObject("System", array(), "SWUpdate");
						$raw_packageOID = preg_split('/=/', $SWUpdate['uiCMD']);
						$packageOID = $raw_packageOID[1];

						// Nice people say goodbye, or CCEd waits forever:
						$cceClient->bye();
						$serverScriptHelper->destructor();

						// Ob wir hier richtig sind, oder nicht, sagt uns gleich das Licht.
						// The "download" page will show us the PKG info and will ask to install.
						// From there on the further checks handle incorrect package formats and such:
				        header("Location: /swupdate/download?packageOID=" . $packageOID . "&backUrl=/swupdate/manualInstall?backUrl=$backUrl");
				        exit;
				    }
				}
			}
			elseif ($form_data['locationField'] == 'fileField') {

				//
				//-- Install from uploaded file:
				//

				$config['upload_path'] = '/tmp/';
				$config['allowed_types'] = '*'; // Can't set this to 'pkg', as we have no MIME-type for it!
				$config['encrypt_name'] = TRUE;
				$config['remove_spaces'] = TRUE;
				$this->load->library('upload', $config);
				$this->upload->do_upload("fileField");

				// Get the full path and encrypted/randomized file name:
				$data = $this->upload->data();
				$nameField = $data['client_name'];

			    if (!is_file($data['full_path'])) {
					// file opening problems
			        $ci_errors[] = new CceError('huh', 0, 'cert', "[[base-swupdate.invalidUpload]]");
			    }
			    else {
			    	$tmp_pkg = $data['full_path'];

				    // Install uploaded PKG:
				    $ret = $serverScriptHelper->shell("$prepare_cmd -f $tmp_pkg", $output, $runas, $sessionId);
				    if ($ret != 0) {
				        // Deal with errors:
				        $ci_errors[] = new CceError('huh', 0, 'fileField', "[[base-swupdate.badFormat]]");
						if (is_file($tmp_pkg)) {
						    unlink($tmp_pkg);
						}
				    }
				    else {
				    	// If the 'prepare_cmd' was sucessful, we now have the raw PKG info in CODB:
						$SWUpdate = $cceClient->getObject("System", array(), "SWUpdate");
						$raw_packageOID = preg_split('/=/', $SWUpdate['uiCMD']);
						$packageOID = $raw_packageOID[1];

						if (is_file($tmp_pkg)) {
						    unlink($tmp_pkg);
						}

						// Nice people say goodbye, or CCEd waits forever:
						$cceClient->bye();
						$serverScriptHelper->destructor();

						// Ob wir hier richtig sind, oder nicht, sagt uns gleich das Licht.
						// The "download" page will show us the PKG info and will ask to install.
						// From there on the further checks handle incorrect package formats and such:
				        header("Location: /swupdate/download?packageOID=" . $packageOID . "&backUrl=/swupdate/manualInstall?backUrl=$backUrl");
				        exit;
				    }
			    }
			}
			elseif ($form_data['locationField'] == 'loaded') {

				//
				//-- Install from /home/packages:
				//

				$nameField = $form_data['loaded'];

				// Install uploaded PKG:
				$ret = $serverScriptHelper->shell("$prepare_cmd -f \"$packageDir/$nameField\"", $output, $runas, $sessionId);
				if ($ret != 0) {
				    // Deal with errors:
				    $ci_errors[] = new CceError('huh', 0, 'fileField', "[[base-swupdate.badFormat]]");
				}
				else {
					// If the 'prepare_cmd' was sucessful, we now have the raw PKG info in CODB:
					$SWUpdate = $cceClient->getObject("System", array(), "SWUpdate");
					$raw_packageOID = preg_split('/=/', $SWUpdate['uiCMD']);
					$packageOID = $raw_packageOID[1];

					// Nice people say goodbye, or CCEd waits forever:
					$cceClient->bye();
					$serverScriptHelper->destructor();

					// Ob wir hier richtig sind, oder nicht, sagt uns gleich das Licht.
					// The "download" page will show us the PKG info and will ask to install.
					// From there on the further checks handle incorrect package formats and such:
				    header("Location: /swupdate/download?packageOID=" . $packageOID . "&backUrl=/swupdate/manualInstall?backUrl=$backUrl");
				    exit;
				}

			}
			else {
				// Nice people say goodbye, or CCEd waits forever:
				$cceClient->bye();
				$serverScriptHelper->destructor();

				// Wow. No method selected. Reload page and try that again:
		        header("Location: /swupdate/manualInstall?backUrl=$backUrl");
		        exit;
			}
		}

		// Join the various error messages:
		$errors = array_merge($ci_errors, $my_errors);

		//
		//--- Get all loaded packages. We'll match anything that's a tar file
		//

		$packages = array();
		if(is_dir($packageDir)) {
		  $dir = opendir($packageDir);
		  while($file = readdir($dir)) {
			if ($file[0] == '.') {
				continue;
			}
			$serverScriptHelper->shell("$magic_cmd $packageDir/$file", $output, 'root');
			if (preg_match("/(tar|compressed|PGP\s+armored|\sdata$)/", $output)) {
				$packages[] = $file;
			}
		  }
		  closedir($dir);
		}

		// Prepare Page:
		$factory = $serverScriptHelper->getHtmlComponentFactory("base-swupdate", "/swupdate/manualInstall");
		$BxPage = $factory->getPage();
		$BxPage->setErrors($errors);
		$i18n = $factory->getI18n();

	    //-- Generate page:

		// Set Menu items:
		$BxPage->setVerticalMenu('base_software');
		$BxPage->setVerticalMenuChild('base_softwareNew');
		$page_module = 'base_software';

		$defaultPage = "licenseField";

		$block =& $factory->getPagedBlock("manualInstall", array($defaultPage));
		$block->processErrors($serverScriptHelper->getErrors());

		$block->setToggle("#");
		$block->setSideTabs(FALSE);
		$block->setDefaultPage($defaultPage);

		// Add divider:
		$block->addFormField(
	        $factory->addBXDivider("warning_header", ""),
	        $factory->getLabel("warning_header", false),
	        $defaultPage
        );

		// 3rd party software warning:
		$my_TEXT = $i18n->getClean("3rdpartypkg_warning");
		$infotext = $factory->getTextField("BlueOnyx_Info_Text", $my_TEXT, 'r');
		$infotext->setLabelType("nolabel");
		$block->addFormField(
			$infotext,
			$factory->getLabel(" ", false),
			$defaultPage
		);

		// Set up MultiChoice:
		$location = $factory->getMultiChoice("locationField");

		// Add URL option:
		$url = $factory->getOption("url", true);
		$urlFieldx = $factory->getTextField("urlField");
		$urlFieldx->setOptional(TRUE);
		$urlFieldx->setType("");
		$url->addFormField($urlFieldx);
		$location->addOption($url);

		// Add Upload option:
		$upload = $factory->getOption("upload");
		$upload->addFormField($factory->getFileUpload("fileField", ""), $defaultPage);
		$location->addOption($upload);

		// Add /home/packages as an option if there are packages in there:
		if(count($packages) > 0) {
			$loaded = $factory->getOption("loaded");
			$loaded->addFormField($factory->getMultiChoice("loaded", $packages), $defaultPage);
			$location->addOption($loaded);
		}

		// Push out the MultiChoice:
		$block->addFormField(
			$location,
			$factory->getLabel("locationFieldEnter"),
			$defaultPage
		);

		// Submit backUrl as well:
		$block->addFormField(
			$factory->getTextField("backUrl", $backUrl, ""), 
			$defaultPage
		);

		// Add the buttons
		$block->addButton($factory->getSaveButton($BxPage->getSubmitAction()));
		$block->addButton($factory->getCancelButton($backUrl));

		// Nice people say goodbye, or CCEd waits forever:
		$cceClient->bye();
		$serverScriptHelper->destructor();

		// Page parts:
		$page_body[] = $block->toHtml();

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