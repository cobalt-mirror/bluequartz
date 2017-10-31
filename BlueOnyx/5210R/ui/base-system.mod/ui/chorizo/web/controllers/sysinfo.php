<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Sysinfo extends MX_Controller {

	/**
	 * Index Page for this controller.
	 *
	 * Past the login page this loads the page for /system/sysinfo.
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
		$i18n = new I18n("base-system", $CI->BX_SESSION['loginUser']['localePreference']);
		$system = $CI->getSystem();

		// Initialize Capabilities so that we can poll the access rights as well:
		$Capabilities = new Capabilities($CI->cceClient, $CI->BX_SESSION['loginName'], $CI->BX_SESSION['sessionId']);

		// -- Actual page logic start:

		// Not 'serverInformation'? Bye, bye!
		if (!$Capabilities->getAllowed('serverInformation')) {
			// Nice people say goodbye, or CCEd waits forever:
			$CI->cceClient->bye();
			$CI->serverScriptHelper->destructor();
			Log403Error("/gui/Forbidden403");
		}

		//
		//--- Get CODB-Objects of interest: 
		//

		// refresh information
		$unique = microtime();
		$CI->cceClient->set($system['OID'], "Memory",  array("refresh" => $unique));

		$product = new Product($CI->cceClient);

		if (!$product->isRaq()) {
			$CI->cceClient->set($system['OID'], "Disk",  array("refresh" => $unique));
		}

		// get objects
		$systemDisk = $CI->cceClient->get($system['OID'], "Disk");
		$systemMemory = $CI->cceClient->get($system['OID'], "Memory");
		$eth0 = $CI->cceClient->getObject("Network", array("device" => "eth0"));
		$eth1 = $CI->cceClient->getObject("Network", array("device" => "eth1"));

		//
	    //-- Generate page:
	    //

		// Prepare Page:
		$factory = $CI->serverScriptHelper->getHtmlComponentFactory("base-system", "/system/sysinfo");
		$BxPage = $factory->getPage();
		$BxPage->setErrors(array());
		$i18n = $factory->getI18n();

		// Set Menu items:
		$BxPage->setVerticalMenu('base_serverconfig');
		$page_module = 'base_sysmanage';

		$defaultPage = "basicSettingsTab";

		$block =& $factory->getPagedBlock("systemInformation", array($defaultPage));

		$block->setToggle("#");
		$block->setSideTabs(FALSE);
		$block->setDefaultPage($defaultPage);

		if ($system["productName"] != "") {
		  $block->addFormField(
		    $factory->getTextField("productNameField", $system["productName"], "r"),
		    $factory->getLabel("productNameField")
		  );
		}

		if ($system['productBuild']) {
		  $block->addFormField(
		    $factory->getTextField("productBuildField", $system["productBuild"], "r"),
		    $factory->getLabel("productBuildField")
		  );
		}

		// System may contain the literal "Uninitialized"
		$formattedSerial = $system["productSerialNumber"];
		if ($formattedSerial != "") {
		  if ($formattedSerial == 'Uninitialized') {
		    $formattedSerial = $i18n->get("serialUninitialized");
		  }

		  $block->addFormField(
		    $factory->getTextField("productSerialNumberField",
		      $formattedSerial, "r"),
		    $factory->getLabel("productSerialNumberField")
		  );
		}

		if ($system["serialNumber"] != "") {
		  $block->addFormField(
		    $factory->getTextField("serialNumberField", $system["serialNumber"], "r"),
		    $factory->getLabel("serialNumberField")
		  );
		}

		if ($eth0["mac"] != "") {
		  $block->addFormField(
		    $factory->getMacAddress("mac0Field", $eth0["mac"], "r"),
		    $factory->getLabel("mac0Field")
		  );
		}

		if ($eth1["mac"] != "") {
		  $block->addFormField(
		    $factory->getMacAddress("mac1Field", $eth1["mac"], "r"),
		    $factory->getLabel("mac1Field")
		  );
		}

		// convert to GB
		if (isset($systemDisk["disk1Total"])) {
			$diskTotal = round($systemDisk["disk1Total"]*10/1024/1024)/10;
			if ($diskTotal != 0) {
			  $block->addFormField(
			    $factory->getInteger("diskField", $diskTotal, "", "", "r"),
			    $factory->getLabel("diskField")
			  );
			}
		}

		if ($systemMemory["physicalMemTotal"] != "") {
		  $block->addFormField(
		    $factory->getInteger("memoryField", $systemMemory["physicalMemTotal"], "", "", "r"),
		    $factory->getLabel("memoryField")
		  );
		}

		// Add Button-Link for the www.BlueOnyx.it website:
		$webLink = $factory->getButton($i18n->get("webLink"), "webLinkText");
		$webLink->setTarget("_blank");
		$buttonContainer = $factory->getButtonContainer("", array($webLink));

		$page_body[] = $block->toHtml();
		$page_body[] = $buttonContainer->toHtml();

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