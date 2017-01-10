<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * BlueOnyx API
 *
 * BlueOnyx API Index Page
 *
 * @package   BlueOnyx base-api.mod
 * @author    Michael Stauber
 * @link      http://www.solarspeed.net
 * @license   http://devel.blueonyx.it/pub/BlueOnyx/licenses/SUN-modified-BSD-License.txt
 * @version   2.1
 *
 * @info      Creation of this module was sponsored by VIRTBIZ Internet Services: http://www.virtbiz.com
 *
 */

// This module provides rudimentary API functions to BlueOnyx. This allows server administrators and
// especially ISP's to set up some kind of automated account creation and provisioning for BlueOnyx.
//
// This module was created with WHMCS (see http://www.whmcs.com/) in mind and there is also a module 
// for WHMCS available which allows WHMCS to "talk" to BlueOnyx servers for provisioning and management.
//
// However, even if you don't use WHMCS, you can still use the API to perfom remote provisioning of 
// BlueOnyx such as ...
//
// - Create a Vsite and a user for it
// - Configure various options for that Vsite
// - Suspend that Vsite
// - Unsuspend that Vsite
// - Delete that Vsite
// - Check the server status remotely
// - Shutdown or Reboot the server
// - Poll Active Monitor
// - Poll Active Monitor for a detailed status report
//
// The API documentation (and the module for WHMCS) can be found at http://www.blueonyx.it 

class Apiindex extends MX_Controller {

	/**
	 * Index Page for this controller.
	 *
	 * Past the login page this loads the page for /api/apiindex.
	 *
	 */

	public function index() {

		// Get CodeIgniter Instance:
		$CI =& get_instance();
		$MX =& get_instance();

		//
		//--- Manual loading of all required CodeIgniter Libraries and Helpers:
		//

		include_once("ServerScriptHelper.php");
		include_once("AutoFeatures.php");
		include_once("CceClient.php");
		include_once("ArrayPacker.php");
		include_once("I18n.php");

		// Need to load 'user_agent' as we need to access the browser info:
		$CI->load->library('user_agent');
		// Need to load 'parser' to load our template parser:
		$CI->load->library('parser');
		// Need to load the 'cookie' helper:
		$CI->load->helper('cookie');
		// Load the array helper:
		$CI->load->helper('array');
		// Load the string helper:
		$CI->load->helper('string');
		// Load URL helper:
		$CI->load->helper('url');
		// Load the text helper:
		$CI->load->helper('text');

		// Load CI helper and libraries for form validation and handling:
		$CI->load->helper(array('form', 'url'));
		$CI->load->library('form_validation');

		// Load Directory helper:
		$CI->load->helper('directory');
		// Load File helper:
		$CI->load->helper('file');

		// Load UIFC NG library:
		$CI->load->helper('uifc_ng');

		// Load BlueOnyx Helper Library:
		$CI->load->helper('blueonyx_helper');

		// Load BlueOnyx API Library:
		$CI->load->helper('bxapi_helper');

	    // We start without any active errors:
	    $errors = array();
	    $extra_headers =array();
	    $ci_errors = array();
	    $my_errors = array();

	    // We neither have a sessionId nor login at this point:
		$sessionId = "";

		//
		//--- Handle form validation:
		//

	    // My output array starts empty:
	    $data = array();

		// Shove submitted input into $form_data after passing it through the XSS filter:
		$form_data = $CI->input->post(NULL, TRUE);

		if ($CI->input->post(NULL, TRUE)) {

			// Check whether we have form data or not:
			if ((isset($form_data['login'])) && (isset($form_data['pass']))) {
			  // We do. Check the login credentials:
			  $cceClient = new CceClient();
			  $cceClient->connect();
			  $sessionId = $cceClient->auth($form_data['login'], $form_data['pass']);
			}
			else {
				$data['result'] = "BlueOnyx API: You're not doing this right.";
				Log403Error();
				$CI->load->view('apiindex_view', $data);
				return;
			}

			// If we don't have a valid $sessionId and a matching $form_data['login'], then
			// ServerScriptHelper will kick us back to the login page of the GUI:
			$helper = new ServerScriptHelper($sessionId, $form_data['login']);

			// Initialize Capabilities so that we can poll the access rights as well:
			$Capabilities = new Capabilities($cceClient, $form_data['login'], $sessionId);

			// Only adminUser should be here:
			if (!$Capabilities->getAllowed('adminUser')) {
				$data['result'] = "BlueOnyx API: You're not doing this right.";
				$cceClient->bye();
				$helper->destructor();
				Log403Error();
				$CI->load->view('apiindex_view', $data);
				return;
			}
		}
		else {
			// We don't have POST data. Bye, bye, stranger!
			$data['result'] = "BlueOnyx API: You're not doing this right.";
			Log403Error();
			$CI->load->view('apiindex_view', $data);
			return;
		}

		// Initialize Capabilities so that we can poll the access rights as well:
		$Capabilities = new Capabilities($cceClient, $form_data['login'], $sessionId);

		// Only adminUser should be here:
		if (!$Capabilities->getAllowed('adminUser')) {
			$cceClient->bye();
			$helper->destructor();
			Log403Error("/gui/Forbidden403");
			return;
		}

		//
		// -- Check if the API is enabled and we are allowed to pass on further:
		//

		$ip = getenv('REMOTE_ADDR');
		$secure_connection = FALSE;
		if ($_SERVER['SERVER_PORT'] == "81") {
		  $secure_connection = TRUE;
		}

		$sysoid = $cceClient->find("System");
		$APISettings = $cceClient->get($sysoid[0], 'API');

		if ($APISettings['enabled'] == "0") {
			$data['result'] = "BlueOnyx API: API is disabled on this BlueOnyx.";
			error_log("BlueOnyx API: API is disabled, but we got accessed from this IP: $ip");
			Log403Error();
			$cceClient->bye();
			$helper->destructor();
			$CI->load->view('apiindex_view', $data);
			return;
		}

		if (($APISettings['forceHTTPS'] == "1") && ($secure_connection == FALSE)) {
		  $data['result'] = "BlueOnyx API: This API responds only to HTTPS connections!";
		  error_log("BlueOnyx API: API requries HTTPS, but we got a HTTP accessed from this IP: $ip");
		  // nice people say aufwiedersehen
		  $helper->destructor();
		  exit;
		}

		if (($APISettings['apiHosts'] != "") && (isset($ip))) {
		  $api_hosts = stringToArray($APISettings['apiHosts']);
		  // Check if the IP of the visitor is in the array of allowed hosts:
		  if (!in_array($ip, $api_hosts)) {
		    $data['result'] = "BlueOnyx API: You are not allowed to access this API.";
		    error_log("BlueOnyx API: API access from unauthorized IP: $ip");
		    // nice people say aufwiedersehen
		    $helper->destructor();
		    exit;
		  }
		}

		//
		// -- Decode Payload:
		//

		error_log("BlueOnyx API: Access from $ip to port " . $_SERVER['SERVER_PORT']);

		if ((isset($form_data['payload'])) && (($form_data['action'] != "reboot") || ($form_data['action'] != "shutdown") || ($form_data['action'] != "usage") || ($form_data['action'] != "status") || ($form_data['action'] != "destroy")))  {
		  	$payload = json_decode($form_data['payload']);
		  if (isset($payload->clientsdetails)) {
			$clientsdetails = json_decode($payload->clientsdetails);
			$payload->clientsdetails = "";
		  }

		  if ($payload == NULL) {
		  	error_log("BlueOnyx API: JSON decoding returned NULL!");
		  	error_log("BlueOnyx API: JSON error: " . json_last_error());
		    error_log("BlueOnyx API: Not continuing without JSON data!");
			$data['result'] = "BlueOnyx API: JSON decoding error.";
			Log403Error();
			$cceClient->bye();
			$helper->destructor();
			$CI->load->view('apiindex_view', $data);
		    exit;    
		  }

		}
		elseif ((!isset($form_data['payload'])) && (($form_data['action'] == "reboot") ||($form_data['action'] == "shutdown") || ($form_data['action'] == "usage") || ($form_data['action'] == "status") || ($form_data['action'] == "destroy")))  {
		  	//error_log("BlueOnyx API: " . $form_data['action'] . " requested.");
		}
		else {
		  	// No payload? Something went wrong!
			$data['result'] = "BlueOnyx API: You're not doing this right.";
			error_log("BlueOnyx API: API access without payload.");
			Log403Error();
			$cceClient->bye();
			$helper->destructor();
			$CI->load->view('apiindex_view', $data);
			return;
		}

		//
		// -- Check transaction type:
		//

		if (($form_data['action'] == "create") && ($payload->producttype != "hostingaccount")) {
			$data['result'] = "BlueOnyx API: At this time only producttype 'hostingaccount' is supported.";
			error_log("BlueOnyx API: At this time only producttype 'hostingaccount' is supported.");
			Log403Error();
			$cceClient->bye();
			$helper->destructor();
			$CI->load->view('apiindex_view', $data);
			return;
		}

		//
		// -- See if we have everything we need for a "create" transaction:
		//

		if ($form_data['action'] == "create") {
		  if ((isset($payload->domain)) &&
		      (isset($payload->ipaddr)) &&
		      (isset($payload->username)) &&
		      (isset($payload->password)) &&  
		      (isset($payload->disk)) &&  
		      (isset($payload->users)) &&
		      (isset($payload->auto_dns)) &&
		      (isset($clientsdetails->firstname)) &&  
		      (isset($clientsdetails->lastname)) &&  
		      (isset($clientsdetails->email))) 
		    {

		      // Create Vsite:
		      $result = do_create_vsite($payload, $clientsdetails, $helper);

		      // If that went well, we create the User, too:
		      if (is_array($result)) {
		      	//error_log("BlueOnyx API: Vsite $payload->domain created successfully.");
				$result = do_create_user($payload, $clientsdetails, $helper, $result);
				if ($result == "success") {
					// nice people say aufwiedersehen
					$data['result'] = $result;
					//error_log("BlueOnyx API: Done, reporting: $result");
					$cceClient->bye();
					$helper->destructor();
					$CI->load->view('apiindex_view', $data);
					return;
				}
				else {
				  	// This should never fire, as other errors trigger first. But one never knows:
				  	$data['result'] = "BlueOnyx API: Unknown error during Vsite and User creation, sorry.";
				  	error_log("BlueOnyx API: Failed, reporting: " . $data['result']);
					$cceClient->bye();
					$helper->destructor();
					$CI->load->view('apiindex_view', $data);
					return;
				}
		      }
		      else {
					$data['result'] = "BlueOnyx API: Sorry, the Vsite was not created properly.";
					error_log("BlueOnyx API: Failed, reporting: " . $data['result']);
					$cceClient->bye();
					$helper->destructor();
					$CI->load->view('apiindex_view', $data);
					return;
		      }
		    }
		    else {
				$data['result'] = "BlueOnyx API: Did not receive sufficient data to finish this transaction.";
				error_log("BlueOnyx API: Failed, reporting: " . $data['result']);
				$cceClient->bye();
				$helper->destructor();
				$CI->load->view('apiindex_view', $data);
				return;
		    }
		}
		elseif ($form_data['action'] == "changepass") {
		  if ((isset($payload->domain)) &&
		      (isset($payload->ipaddr)) &&
		      (isset($payload->username)) &&
		      (isset($payload->password)) &&  
		      (isset($clientsdetails->firstname)) &&  
		      (isset($clientsdetails->lastname)) &&  
		      (isset($clientsdetails->email))) 
		    {
		      $cceClient->setObject("User", array("password" => $payload->password), "", array("name" => $payload->username));
		      $errors = $cceClient->errors();

		      // nice people say aufwiedersehen
		      $helper->destructor();

		      if (count($errors) >= "1") {
				$data['result'] = "BlueOnyx API: An error happened during the password change.";
				error_log($data['result']);
				$cceClient->bye();
				$helper->destructor();
				$CI->load->view('apiindex_view', $data);
				return;
		      }
		      else {
				$data['result'] = "success";
				$cceClient->bye();
				$helper->destructor();
				$CI->load->view('apiindex_view', $data);
				return;
		      }
		    }
		}
		elseif ($form_data['action'] == "suspend") {
		  if ((isset($payload->domain)) &&
		      (isset($payload->ipaddr)) &&
		      (isset($payload->username)) &&
		      (isset($payload->password)) &&  
		      (isset($clientsdetails->firstname)) &&  
		      (isset($clientsdetails->lastname)) &&  
		      (isset($clientsdetails->email))) 
		    {

		      $host_details = get_fqdn_details($payload->domain);
		      $cceClient->setObject("Vsite", array("suspend" => "1"), "", array("fqdn" => $host_details['fqdn']));
		      $errors = $cceClient->errors();

		      // nice people say aufwiedersehen
		      $helper->destructor();

		      if (count($errors) >= "1") {
				$data['result'] = "BlueOnyx API: An error happened during the suspension of the Vsite.";
				error_log($data['result']);
				$cceClient->bye();
				$helper->destructor();
				$CI->load->view('apiindex_view', $data);
				return;
		      }
		      else {
				$data['result'] = "success";
				//error_log("BlueOnyx API: " . $data['result']);
				$cceClient->bye();
				$helper->destructor();
				$CI->load->view('apiindex_view', $data);
				return;
		      }
		    }
		}
		elseif ($form_data['action'] == "unsuspend") {
		  if ((isset($payload->domain)) &&
		      (isset($payload->ipaddr)) &&
		      (isset($payload->username)) &&
		      (isset($payload->password)) &&  
		      (isset($clientsdetails->firstname)) &&  
		      (isset($clientsdetails->lastname)) &&  
		      (isset($clientsdetails->email))) 
		    {
		      $host_details = get_fqdn_details($payload->domain);
		      $cceClient->setObject("Vsite", array("suspend" => "0"), "", array("fqdn" => $host_details['fqdn']));
		      $errors = $cceClient->errors();

		      // nice people say aufwiedersehen
		      $helper->destructor();

		      if (count($errors) >= "1") {
				$data['result'] = "BlueOnyx API: An error happened during unsuspension of the Vsite.";
				error_log($data['result']);
				$cceClient->bye();
				$helper->destructor();
				$CI->load->view('apiindex_view', $data);
				return;
		      }
		      else {
				$data['result'] = "success";
				error_log("BlueOnyx API: " . $data['result']);
				$cceClient->bye();
				$helper->destructor();
				$CI->load->view('apiindex_view', $data);
				return;
		      }
		    }
		}
		elseif ($form_data['action'] == "destroy") {
			//error_log("Processing 'destroy' request ...");
		  	if ((isset($payload->domain)) &&
				(isset($payload->ipaddr)) &&
				(isset($payload->username)) &&
				(isset($payload->password)) &&  
				(isset($clientsdetails->firstname)) &&  
				(isset($clientsdetails->lastname)) &&  
				(isset($clientsdetails->email))) 
		    {
	    		//error_log("Processing 'destroy' request for $payload->domain - $payload->ipaddr");
				// Get Vsite OID:
				$host_details = get_fqdn_details($payload->domain);
				//error_log("Calculated FQDN for $payload->domain is: " . $host_details['fqdn']);
				$vsiteOID = $cceClient->find("Vsite", array("fqdn" => $host_details['fqdn']));
				//error_log("Found OID for $payload->domain is: " . $vsiteOID[0]);

				// Get Vsite Settings:
				$VsiteSettings = $cceClient->get($vsiteOID[0], '');

				// Get Vsite's MySQL settings:
				$VsiteMySQL = $cceClient->get($vsiteOID[0], "MYSQL_Vsite");

				if ($VsiteMySQL['enabled'] == "1") {
					// Get Server's MySQL access details:
					$getthisOID = $cceClient->find("MySQL");
					$mysql_settings = $cceClient->get($getthisOID[0]);

					// Server MySQL settings:
					$sql_root               = $mysql_settings['sql_root'];
					$sql_rootpassword       = $mysql_settings['sql_rootpassword'];

					// Store the setings in $VsiteSettings as well:
					$VsiteSettings['sql_username'] = $VsiteMySQL['username'];
					$VsiteSettings['sql_database'] = $VsiteMySQL['DB'];
					$VsiteSettings['sql_host'] = $VsiteMySQL['host'];
					$VsiteSettings['sql_root'] = $sql_root;
					$VsiteSettings['sql_rootpassword'] = $sql_rootpassword;

					delete_mysql_stuff($VsiteSettings, $cceClient);
				}

				// Find out if the site is suspended. In that case we unsuspend it first:
				if ($VsiteSettings['suspend'] == "1") {
					$host_details = get_fqdn_details($payload->domain);
					$cceClient->setObject("Vsite", array("suspend" => "0"), "", array("fqdn" => $host_details['fqdn']));
					$errors = $cceClient->errors();
				}

				// Destroy the Vsite and all its Users and data:
				if (isset($VsiteSettings['name'])) {
					//error_log("Running /usr/sausalito/sbin/vsite_destroy.pl " . $VsiteSettings['name']);
					$cmd = "/usr/sausalito/sbin/vsite_destroy.pl " . $VsiteSettings['name'];
					$no_return = '';
					$helper->shell($cmd, $no_return, 'root', $sessionId);
				}
				else {
					error_log("Site not there!");
					$errors = array("error" => "Site not there!");
				}

				// nice people say aufwiedersehen
				$helper->destructor();

				if (count($errors) >= "1") {
					$data['result'] = "BlueOnyx API: An error happened during the deletion of the Vsite.";
					error_log($data['result']);
					$cceClient->bye();
					$helper->destructor();
					$CI->load->view('apiindex_view', $data);
					return;
				}
				else {
					$data['result'] = "success";
					//error_log("BlueOnyx API: " . $data['result']);
					$cceClient->bye();
					$helper->destructor();
					$CI->load->view('apiindex_view', $data);
					return;
				}
		    }
		}
		elseif ($form_data['action'] == "modify") {
		  if ((isset($payload->domain)) &&
		      (isset($payload->ipaddr)) &&
		      (isset($payload->username)) &&
		      (isset($payload->password)) &&  
		      (isset($payload->disk)) &&  
		      (isset($payload->users)) &&
		      (isset($payload->auto_dns)) &&
		      (isset($clientsdetails->firstname)) &&  
		      (isset($clientsdetails->lastname)) &&  
		      (isset($clientsdetails->email))) 
		    {

		      // Create Vsite:
		      $result = do_modify_vsite($payload, $clientsdetails, $helper, "modify");

		      // If that went well, we create the User, too:
		      if ($result == "success") {
				$data['result'] = "success";
				$cceClient->bye();
				$helper->destructor();
				$CI->load->view('apiindex_view', $data);
				return;
		      }
		      else {
				// This should never fire, as other errors trigger first. But one never knows:
				$data['result'] = "BlueOnyx API: Unknown error during modification of the Vsite. Sorry.";
				error_log($data['result']);
				$cceClient->bye();
				$helper->destructor();
				$CI->load->view('apiindex_view', $data);
				return;
		      }
		    }
		    else {
				// This should never fire, as other errors trigger first. But one never knows:
				$data['result'] = "BlueOnyx API: Unknown error during modification of the Vsite. Not enough data.";
				error_log($data['result']);
				$cceClient->bye();
				$helper->destructor();
				$CI->load->view('apiindex_view', $data);
				return;
		    }
		}
		elseif ($form_data['action'] == "reboot") {

		      $sysoid = $cceClient->find("System");
		      $cceClient->set($sysoid[0], "Power", array("reboot" => time()));
		      $errors = $cceClient->errors();

		      // nice people say aufwiedersehen
		      $helper->destructor();

		      if (count($errors) >= "1") {
				$data['result'] = "BlueOnyx API: An error happened while attempting to reboot the server.";
				error_log($data['result']);
				$cceClient->bye();
				$helper->destructor();
				$CI->load->view('apiindex_view', $data);
				return;
		      }
		      else {
				$data['result'] = "success";
				error_log("BlueOnyx API: " . $data['result']);
				$cceClient->bye();
				$helper->destructor();
				$CI->load->view('apiindex_view', $data);
				return;
		      }
		}
		elseif ($form_data['action'] == "shutdown") {

		      $sysoid = $cceClient->find("System");
		      $cceClient->set($sysoid[0], "Power", array("halt" => time()));
		      $errors = $cceClient->errors();

		      // nice people say aufwiedersehen
		      $helper->destructor();

		      if (count($errors) >= "1") {
				$data['result'] = "BlueOnyx API: An error happened while attempting to shutdown the server.";
				error_log($data['result']);
				$cceClient->bye();
				$helper->destructor();
				$CI->load->view('apiindex_view', $data);
				return;
		      }
		      else {
				$data['result'] = "success";
				error_log("BlueOnyx API: " . $data['result']);
				$cceClient->bye();
				$helper->destructor();
				$CI->load->view('apiindex_view', $data);
				return;
		      }
		}
		elseif ($form_data['action'] == "statusdetailed") {

			$factory = $helper->getHtmlComponentFactory("base-am");
			$i18n = $factory->i18n;

			// Force run of Swatch:
			$no_return = '';
			$helper->shell("/usr/sbin/swatch -c /etc/swatch.conf", $no_return, 'root', $sessionId);

			// Poll CCE for our ActiveMonitor details:
			$amobj = $cceClient->getObject("ActiveMonitor");
			$am_names = $cceClient->names("ActiveMonitor");
			$System = $cceClient->getObject("System");
			$amenabled = $amobj["enabled"];

			$stmap = array(
			"N" => "N/A", 
			"G" => "Normal", 
			"Y" => "Problem", 
			"R" => "Severe Problem");

			$status = "Status for BlueOnyx (" . $System['productBuild'] . ")<br>\n\n";

		      for ($i=0; $i < count($am_names); ++$i) {
			  	$nspace = $cceClient->get($amobj["OID"], $am_names[$i]);
				  if (!isset($nspace["hideUI"])) {
				      $iname = $i18n->interpolate($nspace["nameTag"]);

				      if (!$amenabled) {
					  	$icon = "Not Monitored";
				      } else if (!$nspace["enabled"]) {
					  	$icon = "Disabled";
				      } else if (!$nspace["monitor"]) {
					  	$icon = "Not Monitored";
				      } else {
					  	$icon = $stmap[$nspace["currentState"]];
				      }

				      if ($nspace["UIGroup"] == "system") {
					  	$status_system .= $iname . ": " . $icon . "<br>\n";
				      } else if ($nspace["UIGroup"] == "service") {
					  	$status_service .= $iname . ": " . $icon . "<br>\n";
				      }
				  }
		      }

		      $result = $status;
		      $result .= "System:<br>\n\n";
		      $result .= $status_system;
		      $result .= "<br><br>\n\nService:<br>\n\n";
		      $result .= $status_service;

			  $data['result'] = $result;
			  error_log("BlueOnyx API: " . $data['result']);
			  $cceClient->bye();
			  $helper->destructor();
			  $CI->load->view('apiindex_view', $data);
			  return;

		}
		elseif ($form_data['action'] == "status") {

		      $factory = $helper->getHtmlComponentFactory("base-am");
		      $i18n = $factory->i18n;

		      // Poll CCE for our ActiveMonitor details:
		      $amobj = $cceClient->getObject("ActiveMonitor");
		      $am_names = $cceClient->names("ActiveMonitor");
		      $System = $cceClient->getObject("System");
		      $amenabled = $amobj["enabled"];

		      $stmap = array(
							  "N" => "N/A", 
							  "G" => "Normal", 
							  "Y" => "Problem", 
							  "R" => "Severe Problem"
					  		);

		      $yellow = "0";
		      $red = "0";

		      for ($i=0; $i < count($am_names); ++$i) {
			  	$nspace = $cceClient->get($amobj["OID"], $am_names[$i]);
				if (!isset($nspace["hideUI"])) {
				  if ($nspace["currentState"] == "Y") {
				  	$yellow++;
				  }
				  if ($nspace["currentState"] == "R") {
				  	$red++;
				  }
				}
		      }

		      if (($yellow == "0") || ($red == "0")) {
				$result = "G";
		      }
		      elseif (($yellow == "1") || ($red == "0")) {
				$result = "Y";
		      }
		      elseif (($yellow == "1") || ($red == "1")) {
				$result = "R";
		      }
		      elseif (($yellow == "0") || ($red == "1")) {
				$result = "R";
		      }
		      else {
				$result = "G";
		      }
			  $data['result'] = $result;
			  error_log("BlueOnyx API: " . $data['result']);
			  $cceClient->bye();
			  $helper->destructor();
			  $CI->load->view('apiindex_view', $data);
			  return;
		}
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