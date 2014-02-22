<?php

/**
 * BlueOnyx API
 *
 * BlueOnyx API Index Page
 *
 * @package   BlueOnyx base-api.mod
 * @author    Michael Stauber
 * @copyright Copyright (c) 2013 Michael Stauber, SOLARSPEED.NET
 * @link      http://www.solarspeed.net
 * @license   http://devel.blueonyx.it/pub/BlueOnyx/licenses/SUN-modified-BSD-License.txt
 * @version   2.0
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
// The API documentation (and the module for WHMCS) can be found at http://www.blueonyx.it as soon as
// the module is more or less finished. At the time of the writing of this text this was still something
// that needed to be done.

include_once("ServerScriptHelper.php");
include_once("AutoFeatures.php");
include_once("CceClient.php");
include_once("ArrayPacker.php");
include_once("I18n.php");

//Check whether we have form data or not:
if (array_key_exists('login', $_POST)) {
  // We do. Check the login credentials:
  $cceClient = new CceClient();
  $cceClient->connect();
  $sessionId = $cceClient->auth($_POST['login'], $_POST['pass']);
}
else {
    echo "BlueOnyx API: You're not doing this right.";
    exit;
}
// If we don't have a valid $sessionId and a matching $_POST['login'], then
// ServerScriptHelper will kick us back to the login page of the GUI:
$helper = new ServerScriptHelper($sessionId, $_POST['login']);

// Only adminUser should be here:
if (!$helper->getAllowed('adminUser')) {
  header("location: /error/forbidden.html");
  return;
}

//
// -- Check if we're enabled and allowed to pass on further:
//

$ip = getenv('REMOTE_ADDR');
$secure_connection = FALSE;
if ($_SERVER['SERVER_PORT'] == "81") {
  $secure_connection = TRUE;
}

$sysoid = $cceClient->find("System");
$APISettings = $cceClient->get($sysoid[0], 'API');

if ($APISettings['enabled'] == "0") {
  echo "BlueOnyx API: API is disabled on this BlueOnyx.";
  error_log("BlueOnyx API: API is disabled, but we got accessed from this IP: $ip");
  // nice people say aufwiedersehen
  $helper->destructor();
  exit;
}

if (($APISettings['forceHTTPS'] == "1") && ($secure_connection == FALSE)) {
  echo "BlueOnyx API: This API responds only to HTTPS connections!";
  error_log("BlueOnyx API: API requries HTTPS, but we got a HTTP accessed from this IP: $ip");
  // nice people say aufwiedersehen
  $helper->destructor();
  exit;
}

if (($APISettings['apiHosts'] != "") && (isset($ip))) {
  $api_hosts = stringToArray($APISettings['apiHosts']);
  // Check if the IP of the visitor is in the array of allowed hosts:
  if (!in_array($ip, $api_hosts)) {
    echo "BlueOnyx API: You are not allowed to access this API.";
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

if ((isset($_POST['payload'])) && (($_POST['action'] != "reboot") || ($_POST['action'] != "shutdown") || ($_POST['action'] != "usage") || ($_POST['action'] != "status")))  {
  $payload = json_decode($_POST['payload']);
  if (isset($payload->clientsdetails)) {
    $clientsdetails = json_decode($payload->clientsdetails);
    $payload->clientsdetails = "";
  }
}
elseif ((!isset($_POST['payload'])) && (($_POST['action'] == "reboot") ||($_POST['action'] == "shutdown") || ($_POST['action'] == "usage") || ($_POST['action'] == "status")))  {
  //error_log("BlueOnyx API: " . $_POST['action'] . " requested.");
}
else {
  // No payload? Something went wrong!
  echo "BlueOnyx API: You're not doing this right.";
  error_log("BlueOnyx API: You're not doing this right.");
  // nice people say aufwiedersehen
  $helper->destructor();
  exit;
}

//
// -- Check transaction type:
//

if (($_POST['action'] == "create") && ($payload->producttype != "hostingaccount")) {
  echo "BlueOnyx API: At this time only producttype 'hostingaccount' is supported.";
  error_log("BlueOnyx API: At this time only producttype 'hostingaccount' is supported.");
  // nice people say aufwiedersehen
  $helper->destructor();
  exit;
}

//
// -- See if we have everything we need for a "create" transaction:
//

$minimum_data == FALSE;
if ($_POST['action'] == "create") {
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
      // We now have the bare minimum of required info to create a Vsite and a User.
      $minimum_data == TRUE;

      // Create Vsite:
      $result = do_create_vsite($payload, $clientsdetails, $helper);

      // If that went well, we create the User, too:
      if (is_array($result)) {
	$result = do_create_user($payload, $clientsdetails, $helper, $result);
	if ($result == "success") {
	  // nice people say aufwiedersehen
	  $helper->destructor();
	  echo $result;
	  exit;
	}
	else {
	  // This should never fire, as other errors trigger first. But one never knows:
	  echo "BlueOnyx API: Unknown error during Vsite and User creation, sorry.";
	  // nice people say aufwiedersehen
	  $helper->destructor();
	  exit;
	}
      }
      else {
	echo "BlueOnyx API: Sorry, the Vsite was not created properly.";
	// nice people say aufwiedersehen
	$helper->destructor();
	exit;
      }
    }
    else {
      echo "BlueOnyx API: Did not receive sufficient data to finish this transaction.";
	// nice people say aufwiedersehen
	$helper->destructor();
      exit;
    }
}
elseif ($_POST['action'] == "changepass") {
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
	  echo "BlueOnyx API: An error happened during the password change.";
	  exit;	  
      }
      else {
	echo "success";
	exit;
      }
    }
}
elseif ($_POST['action'] == "suspend") {
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
	  echo "BlueOnyx API: An error happened during the suspension of the Vsite.";
	  exit;	  
      }
      else {
	echo "success";
	exit;
      }
    }
}
elseif ($_POST['action'] == "unsuspend") {
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
	  echo "BlueOnyx API: An error happened during unsuspension of the Vsite.";
	  exit;	  
      }
      else {
	echo "success";
	exit;
      }
    }
}
elseif ($_POST['action'] == "destroy") {
  if ((isset($payload->domain)) &&
      (isset($payload->ipaddr)) &&
      (isset($payload->username)) &&
      (isset($payload->password)) &&  
      (isset($clientsdetails->firstname)) &&  
      (isset($clientsdetails->lastname)) &&  
      (isset($clientsdetails->email))) 
    {
      // Get Vsite OID:
      $host_details = get_fqdn_details($payload->domain);
      $vsiteOID = $cceClient->find("Vsite", array("fqdn" => $host_details['fqdn']));

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
	    $sql_host               = $mysql_settings['sql_host'];
	    $sql_port               = $mysql_settings['sql_port'];
	    $sql_host_and_port = $sql_host . ":" . $sql_port;

	    // Store the setings in $VsiteSettings as well:
	    $VsiteSettings['sql_username'] = $sql_username;
	    $VsiteSettings['sql_database'] = $sql_database;
	    $VsiteSettings['sql_host'] = $sql_host;
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
	$cmd = "/usr/sausalito/sbin/vsite_destroy.pl " . $VsiteSettings['name'];
	$helper->fork($cmd, 'root');
      }
      else {
	$errors = array("error" => "Site not there!");
      }

      // nice people say aufwiedersehen
      $helper->destructor();

      if (count($errors) >= "1") {
	  echo "BlueOnyx API: An error happened during the deletion of the Vsite.";
	  exit;	  
      }
      else {
	echo "success";
	exit;
      }
    }
}
elseif ($_POST['action'] == "modify") {
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
      // We now have the bare minimum of required info to create a Vsite and a User.
      $minimum_data == TRUE;

      // Create Vsite:
      $result = do_modify_vsite($payload, $clientsdetails, $helper);

      // If that went well, we create the User, too:
      if ($result == "success") {
	// nice people say aufwiedersehen
	$helper->destructor();
	echo $result;
	exit;
      }
      else {
	// This should never fire, as other errors trigger first. But one never knows:
	echo "BlueOnyx API: Unknown error during modification of the Vsite. Sorry.";
	// nice people say aufwiedersehen
	$helper->destructor();
	exit;
      }
      //error_log();
    }
    else {
	// This should never fire, as other errors trigger first. But one never knows:
	echo "BlueOnyx API: Unknown error during modification of the Vsite. Not enough data.";
	// nice people say aufwiedersehen
	$helper->destructor();
	exit;
    }
}
elseif ($_POST['action'] == "reboot") {

      $sysoid = $cceClient->find("System");
      $cceClient->set($sysoid[0], "Power", array("reboot" => time()));
      $errors = $cceClient->errors();

      // nice people say aufwiedersehen
      $helper->destructor();

      if (count($errors) >= "1") {
	  echo "BlueOnyx API: An error happened while attempting to reboot the server.";
	  exit;	  
      }
      else {
	echo "success";
	exit;
      }
}
elseif ($_POST['action'] == "shutdown") {

      $sysoid = $cceClient->find("System");
      $cceClient->set($sysoid[0], "Power", array("halt" => time()));
      $errors = $cceClient->errors();

      // nice people say aufwiedersehen
      $helper->destructor();

      if (count($errors) >= "1") {
	  echo "BlueOnyx API: An error happened while attempting to shutdown the server.";
	  exit;	  
      }
      else {
	echo "success";
	exit;
      }
}
elseif ($_POST['action'] == "statusdetailed") {

      $factory = $helper->getHtmlComponentFactory("base-am");
      $i18n = $factory->i18n;

      // Force run of Swatch:
      $helper->fork("/usr/sbin/swatch -c /etc/swatch.conf", "root");

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
	  if (!$nspace["hideUI"]) {
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

      // nice people say aufwiedersehen
      $helper->destructor();

      echo $result;

}
elseif ($_POST['action'] == "status") {

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
	  "R" => "Severe Problem");

      $yellow = "0";
      $red = "0";

      for ($i=0; $i < count($am_names); ++$i) {
	  $nspace = $cceClient->get($amobj["OID"], $am_names[$i]);
	  if (!$nspace["hideUI"]) {
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
      // nice people say aufwiedersehen
      $helper->destructor();

      echo $result;
}

//
//-- Function: Create Vsite
//

function do_modify_vsite ($payload, $clientsdetails, $helper) {

  // FQDN or domain? That is the question. WHMCS might have passed
  // us just a domain name like "company.com". BlueOnyx needs FQDN's
  // and so we might be missing the hostname part. If that's the case,
  // we will prefix $payload->domain with "www." just to be safe.
  // We use the function get_fqdn_details() for that:

  $host_details = get_fqdn_details($payload->domain);

  // Get the vsiteDefaults:
  $cceClient = $helper->getCceClient();
  $vsiteDefaults = $cceClient->getObject("System", array(), "VsiteDefaults");

  // Get Vsite OID:
  $vsiteOIDHelper = $cceClient->find("Vsite", array("fqdn" => $host_details['fqdn']));
  $vsiteOID = $vsiteOIDHelper[0];

  // Do the Vsite create CCE transaction:
  $cceClient->set($vsiteOID,  "",
			  array(
				  'hostname' => $host_details['hostname'],
				  'domain' => $host_details['domain'],
				  'fqdn' => ($host_details['hostname'] . '.' . $host_details['domain']),
				  'ipaddr' => $payload->ipaddr,
				  'webAliases' => $host_details['domain'],
				  'webAliasRedirects' => $vsiteDefaults['webAliasRedirects'],
				  'emailDisabled' => $vsiteDefaults['emailDisabled'],
				  'mailAliases' => $host_details['domain'],
				  "mailCatchAll" => "",
				  'volume' => "/home",
				  'maxusers' => $payload->users,
				  'dns_auto' => $payload->auto_dns,
				  'prefix' => "",
				  'site_preview' => $vsiteDefaults['site_preview']
			      )
			  );

  $errors = $cceClient->errors();

  // Setup disk-quota:
  if ($vsiteOID) {
	  $quota = $payload->disk;
	  $cceClient->set($vsiteOID, 'Disk', array('quota' => $quota));
	  $errors = array_merge($errors, $cceClient->errors());
  }

  // Find the siteAdmin for this site and update his disk-quota as well:
  $userHelper = $cceClient->find("User", array("name" => $payload->username));
  $cceClient->set($userHelper[0], 'Disk', array('quota' => $quota));
  $errors = array_merge($errors, $cceClient->errors());

  // Setup Email forwarding:
  if (($userHelper[0]) && ($payload->forwardemail == "1")) {
      $cceClient->set($userHelper[0], 'Email', array('forwardEnable' => "1", "forwardEmail" => "&" . $clientsdetails->email . "&", "forwardSave" => "0"));
      $errors = array_merge($errors, $cceClient->errors());
  }
  else {
      $cceClient->set($userHelper[0], 'Email', array('forwardEnable' => "0"));
      $errors = array_merge($errors, $cceClient->errors());
  }

  // Handle suPHP. If enabled, we set the web-ownerships to this User:
  if ($payload->php == "suPHP") {
	  $cceClient->set($vsiteOID, 'PHP', array('prefered_siteAdmin' => $payload->username));
	  $errors = array_merge($errors, $cceClient->errors());
  }
  else {
	  $cceClient->set($vsiteOID, 'PHP', array('prefered_siteAdmin' => "apache"));
	  $errors = array_merge($errors, $cceClient->errors());
  }

  // If Auto-DNS is enabled, we grant this user the caplevel of 'dnsAdmin' as well:
  if (isset($payload->auto_dns)) {
      if ($payload->auto_dns == "1") {
	  $cceClient->set($userHelper[0], '', array('capLevels' => "&siteAdmin&dnsAdmin&"));
	  $errors = array_merge($errors, $cceClient->errors());

	  // But beyond that we also need to add the domain name to the list of Domains under his DNS control:
	  $cceClient->set($vsiteOID, 'DNS', array('domains' => "&" . $host_details['domain'] . "&"));
	  $errors = array_merge($errors, $cceClient->errors());

	  // Update the DNS server with the new DNS zones:
	  list($sysoid) = $cceClient->find("System");
	  $cceClient->set($sysoid, "DNS", array("commit" => time()));
	  $errors = array_merge($errors, $cceClient->errors());
      }
      else {
	  $cceClient->set($userHelper[0], '', array('capLevels' => "&siteAdmin&"));
	  $errors = array_merge($errors, $cceClient->errors());

	  // But beyond that we also need to remove the domain name to the list of Domains under his DNS control:
	  $cceClient->set($vsiteOID, 'DNS', array('domains' => ""));
	  $errors = array_merge($errors, $cceClient->errors());

	  // Update the DNS server with the new DNS zones:
	  list($sysoid) = $cceClient->find("System");
	  $cceClient->set($sysoid, "DNS", array("commit" => time()));
	  $errors = array_merge($errors, $cceClient->errors());
      }
  }

  // Now set the various options:

  // JSP:
  if (isset($payload->jsp)) {
	  $cceClient->set($vsiteOID, 'Java', array('enabled' => $payload->jsp));
	  $errors = array_merge($errors, $cceClient->errors());
  }
  // PHP:
  if (isset($payload->php)) {
	  // Possible options: No,Yes,suPHP
	  $php = "0"; 
	  $suPHP = "0";
	  if ($payload->php == "Yes") { $php = "1"; $suPHP = "0"; }
	  if ($payload->php == "suPHP") { $php = "1"; $suPHP = "1"; }
	  $cceClient->set($vsiteOID, 'PHP', array('enabled' => $php, "suPHP_enabled" => $suPHP));
	  $errors = array_merge($errors, $cceClient->errors());
  }
  // MySQL:

  // Get Vsite's MySQL settings:
  $mysql_vsite_settings = $cceClient->get($vsiteOID, 'MYSQL_Vsite');

  error_log("Checking if MySQL needs to be handled: $payload->mysql vs " . $mysql_vsite_settings['enabled']);

  if ((isset($payload->mysql)) && ($payload->mysql == "1") && ($mysql_vsite_settings['enabled'] == "0")) {

	  error_log("MySQL was not enabled before, but should be on now.");

	  // IF we're here, MySQL wasn't enabled before. So we do that now:
    
	  // Find out group of Vsite:
	  $VsiteSettings = $cceClient->get($vsiteOID, '');

	  // Get MySQL Server settings from CCE:
	  $mysql_settings = $cceClient->getObject("MySQL", array());

	  // Set up MySQL username, DB-name and MySQL-password:
	  $sql_username = $VsiteSettings['name'];
	  $sql_database = $VsiteSettings['name'] . '_db';
	  $my_random_password = createRandomPassword();

	  // Do the deeds:
	  if ($payload->mysql == "1") {
	    $cceClient->set($vsiteOID, 'MYSQL_Vsite', array("username" => $sql_username,
							    "pass" => $my_random_password,
							    "host" => $mysql_settings['sql_host'],
							    "hidden" => time(),
							    "DB" => $sql_database,
							    "port" => $mysql_settings['sql_port'],
							    "enabled" => "1"));
	    $errors = array_merge($errors, $cceClient->errors());

	    // Actual MySQL setup:
	    // <sigh> Now comes the bother: We actually have to create the MySQL database and user on foot:
	    $db_rights = $cceClient->getObject("System", array(), "MYSQLUSERS_DEFAULTS");

	    $MAX_QUERIES_PER_HOUR = $db_rights['MAX_QUERIES_PER_HOUR'];
	    $MAX_CONNECTIONS_PER_HOUR = $db_rights['MAX_CONNECTIONS_PER_HOUR'];
	    $MAX_UPDATES_PER_HOUR = $db_rights['MAX_UPDATES_PER_HOUR'];	    

	    $the_user_rights = array();
	    if ($db_rights['SELECT'] == "1") {
		 array_push($the_user_rights, "SELECT");
	    }
	    if ($db_rights['INSERT'] == "1") {
		array_push($the_user_rights, "INSERT");
	    }
	    if ($db_rights['UPDATE'] == "1") {
		array_push($the_user_rights, "UPDATE");
	    }
	    if ($db_rights['DELETE'] == "1") {
		array_push($the_user_rights, "DELETE");
	    }
	    if ($db_rights['FILE'] == "1") {
		array_push($the_user_rights, "FILE");
	    }
	    if ($db_rights['CREATE'] == "1") {
		array_push($the_user_rights, "CREATE");
	    }
	    if ($db_rights['DROP'] == "1") {
		array_push($the_user_rights, "DROP");
	    }
	    if ($db_rights['INDEX'] == "1") {
		array_push($the_user_rights, "INDEX");
	    }
	    if ($db_rights['ALTER'] == "1") {
		array_push($the_user_rights, "ALTER");
	    }
	    if ($db_rights['TEMPORARY'] == "1") {
		array_push($the_user_rights, "CREATE TEMPORARY TABLES");
	    }

	    if ($db_rights['CREATE_VIEW'] == "1") {
		array_push($the_user_rights, "CREATE VIEW");
	    }
	    if ($db_rights['SHOW_VIEW'] == "1") {
		array_push($the_user_rights, "SHOW VIEW");
	    }
	    if ($db_rights['CREATE_ROUTINE'] == "1") {
		array_push($the_user_rights, "CREATE ROUTINE");
	    }
	    if ($db_rights['ALTER_ROUTINE'] == "1") {
		array_push($the_user_rights, "ALTER ROUTINE");
	    }
	    if ($db_rights['EXECUTE'] == "1") {
		array_push($the_user_rights, "EXECUTE");
	    }

	    $inhalt = count($the_user_rights);
	    $inhalt--;
	    for ( $x = 0; $x < count ( $the_user_rights ); $x++ ) {
		if ($inhalt > $x) {
		    $my_user_rights .= $the_user_rights[$x] . ", "; 
		}
		else {
		    $my_user_rights .= $the_user_rights[$x] . ""; 
		}
	    }
	    $the_user_rights = $my_user_rights;

	    $getthisOID = $cceClient->find("MySQL");
	    $mysql_settings_exists = 0;
	    $mysql_settings = $cceClient->get($getthisOID[0]);

	    // MySQL settings:
	    $sql_root               = $mysql_settings['sql_root'];
	    $sql_rootpassword       = $mysql_settings['sql_rootpassword'];
	    $sql_host               = $mysql_settings['sql_host'];
	    $sql_port               = $mysql_settings['sql_port'];
	    $sql_host_and_port = $sql_host . ":" . $sql_port;

	    // Store the setings in $VsiteSettings as well:
	    $VsiteSettings['sql_username'] = $sql_username;
	    $VsiteSettings['sql_database'] = $sql_database;
	    $VsiteSettings['sql_host'] = $sql_host;
	    $VsiteSettings['sql_root'] = $sql_root;
	    $VsiteSettings['sql_rootpassword'] = $sql_rootpassword;

	    // MySQL-Error:
	    $mysql_error = array();

	    // Make MySQL connection:
	    $fixed_mysql_link = mysql_connect($sql_host, $sql_root, $sql_rootpassword) or $mysql_error[] = mysql_error();

	    // Dropping primary database:
	    $query = "DROP DATABASE IF EXISTS `".$sql_database."`;\n";
	    $result = mysql_query($query);
	
	    // Create Vsite database:
	    $query = "CREATE DATABASE `".$sql_database."`;\n";
	    $result = mysql_query($query);

	    // Setup MySQL-User step1:
	    mysql_select_db("mysql") or $mysql_error[] = mysql_error();
	    $query = "GRANT USAGE ON * . * TO '$sql_username'@'$sql_host' IDENTIFIED BY '$my_random_password' WITH MAX_QUERIES_PER_HOUR $MAX_QUERIES_PER_HOUR MAX_CONNECTIONS_PER_HOUR $MAX_CONNECTIONS_PER_HOUR MAX_UPDATES_PER_HOUR $MAX_UPDATES_PER_HOUR;\n";
	    $result = mysql_query($query);
	    // Setup MySQL-User step2:
	    mysql_select_db("mysql") or $mysql_error[] = mysql_error();
	    $query = "GRANT $the_user_rights ON `$sql_database`. * TO '$sql_username'@'$sql_host';\n";
    	    $result = mysql_query($query);

	    // Setup MySQL-User step3:
	    mysql_select_db("mysql") or $mysql_error[] = mysql_error();
	    $query = "FLUSH PRIVILEGES;\n";
	    $result = mysql_query($query);

	    // Closing connection
	    mysql_close($fixed_mysql_link);

	    // Did we have MySQL errors?
	    if (count($mysql_error) >= "1") {
		// Delete MySQL-User and Database:
		error_log("Deleting MySQL database and user during API related Vsite modifcation.");
		delete_mysql_stuff($VsiteSettings, $cceClient);
	    }
	  }
  }
  else {

    error_log("MySQL was enabled before, but should be off now.");

    // If we're here, MySQL was enabled, but it is now supposed to be off.
    // In that case we need to remove the database and user:

	// Get Vsite Settings:
	$VsiteSettings = $cceClient->get($vsiteOID, '');

	// Get Vsite's MySQL settings:
	$VsiteMySQL = $cceClient->get($vsiteOID, "MYSQL_Vsite");

	if ($VsiteMySQL['enabled'] == "1") {
	      // Get Server's MySQL access details:
	      $getthisOID = $cceClient->find("MySQL");
	      $mysql_settings = $cceClient->get($getthisOID[0]);

	      // Server MySQL settings:
	      $sql_root               = $mysql_settings['sql_root'];
	      $sql_rootpassword       = $mysql_settings['sql_rootpassword'];
	      $sql_host               = $mysql_settings['sql_host'];
	      $sql_port               = $mysql_settings['sql_port'];
	      $sql_host_and_port = $sql_host . ":" . $sql_port;

	      // Store the setings in $VsiteSettings as well:
	      $VsiteSettings['sql_username'] = $VsiteMySQL['username'];
	      $VsiteSettings['sql_database'] = $VsiteMySQL['DB'];
	      $VsiteSettings['sql_host'] = $sql_host;
	      $VsiteSettings['sql_root'] = $sql_root;
	      $VsiteSettings['sql_rootpassword'] = $sql_rootpassword;

	      error_log("Running delete_mysql_stuff function.");
	      delete_mysql_stuff($VsiteSettings, $cceClient);
	}
  }

  // CGI:
  if (isset($payload->cgi)) {
	  $cceClient->set($vsiteOID, 'CGI', array('enabled' => $payload->cgi));
	  $errors = array_merge($errors, $cceClient->errors());
  }

  // SSI:
  if (isset($payload->ssi)) {
	  $cceClient->set($vsiteOID, 'SSI', array('enabled' => $payload->ssi));
	  $errors = array_merge($errors, $cceClient->errors());
  }

  // bwlimit:
  if ((isset($payload->bwlimit)) && (isset($payload->bwlimitVal))) {
	  $cceClient->set($vsiteOID, 'ApacheBandwidth', array('enabled' => $payload->bwlimit, 'speed' => $payload->bwlimitVal));
	  $errors = array_merge($errors, $cceClient->errors());
  }

  // FTP:
  if (isset($payload->ftp)) {
	  $cceClient->set($vsiteOID, 'FTPNONADMIN', array('enabled' => $payload->ftp));
	  $errors = array_merge($errors, $cceClient->errors());
  }

  // userwebs:
  if (isset($payload->userwebs)) {
	  $cceClient->set($vsiteOID, 'USERWEBS', array('enabled' => $payload->userwebs));
	  $errors = array_merge($errors, $cceClient->errors());
  }

  // Shell-access:
  if (isset($payload->shell)) {
	  $cceClient->set($vsiteOID, 'Shell', array('enabled' => $payload->shell));
	  $errors = array_merge($errors, $cceClient->errors());
  }
  
  // Sub-Domains:
  if ((isset($payload->subdomains)) && (isset($payload->subdomainsMax))) {
	  $cceClient->set($vsiteOID, 'subdomains', array('vsite_enabled' => $payload->subdomains, 'max_subdomains' => $payload->subdomainsMax));
	  $errors = array_merge($errors, $cceClient->errors());
  }

  // Auto-DNS:
  if (isset($payload->auto_dns)) {
      if ($payload->auto_dns == "1") {
	  $cceClient->set($vsiteOID, 'DNS', array('domains' => "&" . $host_details['domain'] . "&"));
	  list($sysoid) = $cceClient->find("System");
	  $cceClient->set($sysoid, "DNS", array("commit" => time()));
	  $errors = array_merge($errors, $cceClient->errors());
      }
  }

  if (count($errors) >= "1") {

      // We have an error and an incompletly created Vsite. So we destroy the Vsite:
      $cceClient->destroy($vsiteOID);

      // Delete MySQL-User and Database:
      delete_mysql_stuff($VsiteSettings, $cceClient);

      echo "BlueOnyx API: Sorry, an error occured while modifying the various services for the Vsite.";
  }
  elseif (count($mysql_error) >= "1") {

      // We have an error and an incompletly created Vsite. So we destroy the Vsite:
      $cceClient->destroy($vsiteOID);

      // Delete MySQL-User and Database:
      delete_mysql_stuff($VsiteSettings, $cceClient);

      echo "BlueOnyx API: Sorry, an error occured while setting up the MySQL database of this Vsite.";
      // nice people say aufwiedersehen
      $helper->destructor();
      exit;
  }
  else {
    $result = "success";
    return $result;
  }

}

//
//-- Function: Create Vsite
//

function do_create_vsite ($payload, $clientsdetails, $helper) {

  // FQDN or domain? That is the question. WHMCS might have passed
  // us just a domain name like "company.com". BlueOnyx needs FQDN's
  // and so we might be missing the hostname part. If that's the case,
  // we will prefix $payload->domain with "www." just to be safe.
  // We use the function get_fqdn_details() for that:

  $host_details = get_fqdn_details($payload->domain);

  // Get the vsiteDefaults:
  $cceClient = $helper->getCceClient();
  $vsiteDefaults = $cceClient->getObject("System", array(), "VsiteDefaults");

  // Do the Vsite create CCE transaction:
  $vsiteOID = $cceClient->create("Vsite", 
			  array(
				  'hostname' => $host_details['hostname'],
				  'domain' => $host_details['domain'],
				  'fqdn' => ($host_details['hostname'] . '.' . $host_details['domain']),
				  'ipaddr' => $payload->ipaddr,
				  'webAliases' => $host_details['domain'],
				  'webAliasRedirects' => $vsiteDefaults['webAliasRedirects'],
				  'emailDisabled' => $vsiteDefaults['emailDisabled'],
				  'mailAliases' => $host_details['domain'],
				  "mailCatchAll" => "",
				  'volume' => "/home",
				  'maxusers' => $payload->users,
				  'dns_auto' => $payload->auto_dns,
				  'prefix' => "",
				  'site_preview' => $vsiteDefaults['site_preview']
			      )
			  );

  $errors = $cceClient->errors();

  if (count($errors) >= "1") {
      echo "BlueOnyx API: Sorry, the Vsite did not create properly.";
      // nice people say aufwiedersehen
      $helper->destructor();
      exit;
  }

  // Setup disk-quota:
  if ($vsiteOID) {
	  $quota = $payload->disk;
	  $cceClient->set($vsiteOID, 'Disk', array('quota' => $quota));
	  $errors = array_merge($errors, $cceClient->errors());
  }
  if (count($errors) >= "1") {
      echo "BlueOnyx API: Sorry, could not set disk-quota information for Vsite.";
  }

  // Now set the various options:

  // JSP:
  if (isset($payload->jsp)) {
	  $cceClient->set($vsiteOID, 'Java', array('enabled' => $payload->jsp));
	  $errors = array_merge($errors, $cceClient->errors());
  }
  // PHP:
  if (isset($payload->php)) {
	  // Possible options: No,Yes,suPHP
	  $php = "0"; 
	  $suPHP = "0";
	  if ($payload->php == "Yes") { $php = "1"; $suPHP = "0"; }
	  if ($payload->php == "suPHP") { $php = "1"; $suPHP = "1"; }
	  $cceClient->set($vsiteOID, 'PHP', array('enabled' => $php, "suPHP_enabled" => $suPHP));
	  $errors = array_merge($errors, $cceClient->errors());
  }
  // MySQL:
  if (isset($payload->mysql)) {
	  // Find out group of Vsite:
	  $VsiteSettings = $cceClient->get($vsiteOID, '');

	  // Set up MySQL username, DB-name and MySQL-password:
	  $sql_username = $VsiteSettings['name'];
	  $sql_database = $VsiteSettings['name'] . '_db';
	  $my_random_password = createRandomPassword();

	  // Get MySQL Server settings from CCE:
	  $mysql_settings = $cceClient->getObject("MySQL", array());

	  // Do the deeds:
	  if ($payload->mysql == "1") {
	    $cceClient->set($vsiteOID, 'MYSQL_Vsite', array("username" => $sql_username,
							    "pass" => $my_random_password,
							    "host" => $mysql_settings['sql_host'],
							    "hidden" => time(),
							    "DB" => $sql_database,
							    "port" => $mysql_settings['sql_port'],
							    "enabled" => "1"));
	    $errors = array_merge($errors, $cceClient->errors());

	    // Actual MySQL setup:
	    // <sigh> Now comes the bother: We actually have to create the MySQL database and user on foot:
	    $db_rights = $cceClient->getObject("System", array(), "MYSQLUSERS_DEFAULTS");

	    $MAX_QUERIES_PER_HOUR = $db_rights['MAX_QUERIES_PER_HOUR'];
	    $MAX_CONNECTIONS_PER_HOUR = $db_rights['MAX_CONNECTIONS_PER_HOUR'];
	    $MAX_UPDATES_PER_HOUR = $db_rights['MAX_UPDATES_PER_HOUR'];	    

	    $the_user_rights = array();
	    if ($db_rights['SELECT'] == "1") {
		 array_push($the_user_rights, "SELECT");
	    }
	    if ($db_rights['INSERT'] == "1") {
		array_push($the_user_rights, "INSERT");
	    }
	    if ($db_rights['UPDATE'] == "1") {
		array_push($the_user_rights, "UPDATE");
	    }
	    if ($db_rights['DELETE'] == "1") {
		array_push($the_user_rights, "DELETE");
	    }
	    if ($db_rights['FILE'] == "1") {
		array_push($the_user_rights, "FILE");
	    }
	    if ($db_rights['CREATE'] == "1") {
		array_push($the_user_rights, "CREATE");
	    }
	    if ($db_rights['DROP'] == "1") {
		array_push($the_user_rights, "DROP");
	    }
	    if ($db_rights['INDEX'] == "1") {
		array_push($the_user_rights, "INDEX");
	    }
	    if ($db_rights['ALTER'] == "1") {
		array_push($the_user_rights, "ALTER");
	    }
	    if ($db_rights['TEMPORARY'] == "1") {
		array_push($the_user_rights, "CREATE TEMPORARY TABLES");
	    }

	    if ($db_rights['CREATE_VIEW'] == "1") {
		array_push($the_user_rights, "CREATE VIEW");
	    }
	    if ($db_rights['SHOW_VIEW'] == "1") {
		array_push($the_user_rights, "SHOW VIEW");
	    }
	    if ($db_rights['CREATE_ROUTINE'] == "1") {
		array_push($the_user_rights, "CREATE ROUTINE");
	    }
	    if ($db_rights['ALTER_ROUTINE'] == "1") {
		array_push($the_user_rights, "ALTER ROUTINE");
	    }
	    if ($db_rights['EXECUTE'] == "1") {
		array_push($the_user_rights, "EXECUTE");
	    }

	    $inhalt = count($the_user_rights);
	    $inhalt--;
	    for ( $x = 0; $x < count ( $the_user_rights ); $x++ ) {
		if ($inhalt > $x) {
		    $my_user_rights .= $the_user_rights[$x] . ", "; 
		}
		else {
		    $my_user_rights .= $the_user_rights[$x] . ""; 
		}
	    }
	    $the_user_rights = $my_user_rights;

	    $getthisOID = $cceClient->find("MySQL");
	    $mysql_settings_exists = 0;
	    $mysql_settings = $cceClient->get($getthisOID[0]);

	    // MySQL settings:
	    $sql_root               = $mysql_settings['sql_root'];
	    $sql_rootpassword       = $mysql_settings['sql_rootpassword'];
	    $sql_host               = $mysql_settings['sql_host'];
	    $sql_port               = $mysql_settings['sql_port'];
	    $sql_host_and_port = $sql_host . ":" . $sql_port;

	    // Store the setings in $VsiteSettings as well:
	    $VsiteSettings['sql_username'] = $sql_username;
	    $VsiteSettings['sql_database'] = $sql_database;
	    $VsiteSettings['sql_host'] = $sql_host;
	    $VsiteSettings['sql_root'] = $sql_root;
	    $VsiteSettings['sql_rootpassword'] = $sql_rootpassword;

	    // MySQL-Error:
	    $mysql_error = array();

	    // Make MySQL connection:
	    $fixed_mysql_link = mysql_connect($sql_host, $sql_root, $sql_rootpassword) or $mysql_error[] = mysql_error();

	    // Dropping primary database:
	    $query = "DROP DATABASE IF EXISTS `".$sql_database."`;\n";
	    $result = mysql_query($query);
	
	    // Create Vsite database:
	    $query = "CREATE DATABASE `".$sql_database."`;\n";
	    $result = mysql_query($query);

	    // Setup MySQL-User step1:
	    mysql_select_db("mysql") or $mysql_error[] = mysql_error();
	    $query = "GRANT USAGE ON * . * TO '$sql_username'@'$sql_host' IDENTIFIED BY '$my_random_password' WITH MAX_QUERIES_PER_HOUR $MAX_QUERIES_PER_HOUR MAX_CONNECTIONS_PER_HOUR $MAX_CONNECTIONS_PER_HOUR MAX_UPDATES_PER_HOUR $MAX_UPDATES_PER_HOUR;\n";
	    $result = mysql_query($query);
	    // Setup MySQL-User step2:
	    mysql_select_db("mysql") or $mysql_error[] = mysql_error();
	    $query = "GRANT $the_user_rights ON `$sql_database`. * TO '$sql_username'@'$sql_host';\n";
    	    $result = mysql_query($query);

	    // Setup MySQL-User step3:
	    mysql_select_db("mysql") or $mysql_error[] = mysql_error();
	    $query = "FLUSH PRIVILEGES;\n";
	    $result = mysql_query($query);

	    // Closing connection
	    mysql_close($fixed_mysql_link);

	    // Did we have MySQL errors?
	    if (count($mysql_error) >= "1") {
		// Delete MySQL-User and Database:
		delete_mysql_stuff($VsiteSettings, $cceClient);
	    }
	  }
  }

  // CGI:
  if (isset($payload->cgi)) {
	  $cceClient->set($vsiteOID, 'CGI', array('enabled' => $payload->cgi));
	  $errors = array_merge($errors, $cceClient->errors());
  }

  // SSI:
  if (isset($payload->ssi)) {
	  $cceClient->set($vsiteOID, 'SSI', array('enabled' => $payload->ssi));
	  $errors = array_merge($errors, $cceClient->errors());
  }

  // bwlimit:
  if ((isset($payload->bwlimit)) && (isset($payload->bwlimitVal))) {
	  $cceClient->set($vsiteOID, 'ApacheBandwidth', array('enabled' => $payload->bwlimit, 'speed' => $payload->bwlimitVal));
	  $errors = array_merge($errors, $cceClient->errors());
  }

  // FTP:
  if (isset($payload->ftp)) {
	  $cceClient->set($vsiteOID, 'FTPNONADMIN', array('enabled' => $payload->ftp));
	  $errors = array_merge($errors, $cceClient->errors());
  }

  // userwebs:
  if (isset($payload->userwebs)) {
	  $cceClient->set($vsiteOID, 'USERWEBS', array('enabled' => $payload->userwebs));
	  $errors = array_merge($errors, $cceClient->errors());
  }

  // Shell-access:
  if (isset($payload->shell)) {
	  $cceClient->set($vsiteOID, 'Shell', array('enabled' => $payload->shell));
	  $errors = array_merge($errors, $cceClient->errors());
  }
  
  // Sub-Domains:
  if ((isset($payload->subdomains)) && (isset($payload->subdomainsMax))) {
	  $cceClient->set($vsiteOID, 'subdomains', array('vsite_enabled' => $payload->subdomains, 'max_subdomains' => $payload->subdomainsMax));
	  $errors = array_merge($errors, $cceClient->errors());
  }

  // Auto-DNS:
  if (isset($payload->auto_dns)) {
      if ($payload->auto_dns == "1") {
	  $cceClient->set($vsiteOID, 'DNS', array('domains' => "&" . $host_details['domain'] . "&"));
	  list($sysoid) = $cceClient->find("System");
	  $cceClient->set($sysoid, "DNS", array("commit" => time()));
	  $errors = array_merge($errors, $cceClient->errors());
      }
  }

  if (count($errors) >= "1") {

      // We have an error and an incompletly created Vsite. So we destroy the Vsite:
      $cceClient->destroy($vsiteOID);

      // Delete MySQL-User and Database:
      delete_mysql_stuff($VsiteSettings, $cceClient);

      echo "BlueOnyx API: Sorry, an error occured while enabling the various services for the Vsite.";
  }
  elseif (count($mysql_error) >= "1") {

      // We have an error and an incompletly created Vsite. So we destroy the Vsite:
      $cceClient->destroy($vsiteOID);

      // Delete MySQL-User and Database:
      delete_mysql_stuff($VsiteSettings, $cceClient);

      echo "BlueOnyx API: Sorry, an error occured while setting up the MySQL database of this Vsite.";
      // nice people say aufwiedersehen
      $helper->destructor();
      exit;
  }
  else {
    return $VsiteSettings;
  }

}

//
//-- Function: Create User
//

function do_create_user ($payload, $clientsdetails, $helper, $VsiteSettings) {

  // Build comments:
  $comments = $clientsdetails->firstname . " " . $clientsdetails->lastname . "\n" . $clientsdetails->email . "\n" . $clientsdetails->companyname . "\n";

  // Do the User create CCE transaction:
  $cceClient = $helper->getCceClient();
  $userOID = $cceClient->create("User", 
			  array(
				"fullName" => $clientsdetails->firstname . " " . $clientsdetails->lastname,
				"ftpDisabled" => "0", 
				"capLevels" => "&siteAdmin&", 
				"sortName" => "", 
				"emailDisabled" => "0",
				"volume" => "/home",
				"description" => "",
				"name" => $payload->username,
				"password" => $payload->password,
				"stylePreference" => "BlueOnyx",
				"site" => $VsiteSettings['name'],
				"enabled" => "1",
				"localePreference" => "browser",
				"description" => $comments
			      )
			  );

  $errors = $cceClient->errors();

  if (count($errors) >= "1") {
      echo "BlueOnyx API: Sorry, the siteAdmin-User did not create properly.";

      // We have an error and an incompletly created Vsite. So we destroy the Vsite:
      $cceClient->destroy($VsiteSettings['OID']);

      // Delete MySQL-User and Database:
      delete_mysql_stuff($VsiteSettings, $cceClient);

      // nice people say aufwiedersehen
      $helper->destructor();
      exit;
  }

  // Setup disk-quota:
  if ($userOID) {
	  $quota = $payload->disk;
	  $cceClient->set($userOID, 'Disk', array('quota' => $quota));
	  $errors = array_merge($errors, $cceClient->errors());
  }
  if (count($errors) >= "1") {
      echo "BlueOnyx API: Sorry, could not set disk-quota information for the siteAdmin-User.";
  }

  // Setup Email forwarding:
  if (($userOID) && ($payload->forwardemail == "1")) {
      $cceClient->set($userOID, 'Email', array('forwardEnable' => "1", "forwardEmail" => "&" . $clientsdetails->email . "&", "forwardSave" => "0"));
      $errors = array_merge($errors, $cceClient->errors());
  }
  if (count($errors) >= "1") {
      echo "BlueOnyx API: Sorry, could not set up email forwarding for the siteAdmin-User.";
  }

  // Setup nicer email alias:
  if ($userOID) {
	  if (($clientsdetails->firstname) && ($clientsdetails->firstname != "")) {
	    $firstname = "&" . strtolower($clientsdetails->firstname) . "&";
	    $cceClient->set($userOID, 'Email', array("aliases" => $firstname));
	    $errors = array_merge($errors, $cceClient->errors());
	  }
  }

  // Handle suPHP. If enabled, we set the web-ownerships to this User:
  if ($payload->php == "suPHP") {
	  $cceClient->set($VsiteSettings['OID'], 'PHP', array('prefered_siteAdmin' => $payload->username));
	  $errors = array_merge($errors, $cceClient->errors());
  }

  // If Auto-DNS is enabled, we grant this user the caplevel of 'dnsAdmin' as well:
  if (isset($payload->auto_dns)) {
      if ($payload->auto_dns == "1") {
	  $cceClient->set($userOID, '', array('capLevels' => "&siteAdmin&dnsAdmin&"));
	  $errors = array_merge($errors, $cceClient->errors());
      }
  }

  if (count($errors) >= "1") {

      // We have an error and an incompletly created User. So we destroy the Vsite AND the User:
      $cceClient->destroy($VsiteSettings['OID']);

      // Delete MySQL-User and Database:
      delete_mysql_stuff($VsiteSettings, $cceClient);

      echo "BlueOnyx API: Sorry, an error happened during User creation.";
      // nice people say aufwiedersehen
      $helper->destructor();
      exit;
  }
  else {
    // Yaay!
    return "success";
  }
}

function createRandomPassword() {
    /**
     * The letter l (lowercase L) and the number 1
     * have been removed, as they can be mistaken
     * for each other.
    */

    $chars = "abcFGHJKhijkmnovdefgABRSTUVWXCDEwxyzLMNOPQYZ023pqrstu456789";
    srand((double)microtime()*1000000);
    $i = 0;
    $pass = '' ;

    while ($i <= 7) {
        $num = rand() % 33;
        $tmp = substr($chars, $num, 1);
        $pass = $pass . $tmp;
        $i++;
    }
    return $pass;
}

function delete_mysql_stuff ($VsiteSettings, $cceClient) {

    $sql_username = $VsiteSettings['sql_username'];
    $sql_database = $VsiteSettings['sql_database'];
    $sql_host = $VsiteSettings['sql_host'];
    $sql_root = $VsiteSettings['sql_root'];
    $sql_rootpassword = $VsiteSettings['sql_rootpassword'];

    // Set up new MySQL-Error array:
    $mysql_error = array();

    // Make MySQL connection:
    $fixed_mysql_link = mysql_connect($sql_host, $sql_root, $sql_rootpassword) or $mysql_error[] = mysql_error();

    // Revoke privileges and drop user:
    mysql_select_db("mysql") or $mysql_error[] = mysql_error();
    $query = "REVOKE ALL PRIVILEGES ON * . * FROM '$sql_username'@'$sql_host';\n";
    $result = mysql_query($query);

    mysql_select_db("mysql") or $mysql_error[] = mysql_error();
    $query = "REVOKE ALL PRIVILEGES ON `$sql_database` . * FROM '$sql_username'@'$sql_host';\n";
    $result = mysql_query($query);

    mysql_select_db("mysql") or $mysql_error[] = mysql_error();
    $query = "REVOKE GRANT OPTION ON * . * FROM '$sql_username'@'$sql_host';\n";
    $result = mysql_query($query);

    mysql_select_db("mysql") or $mysql_error[] = mysql_error();
    $query = "DROP USER '$sql_username'@'$sql_host';\n";
    $result = mysql_query($query);

    mysql_select_db("mysql") or $mysql_error[] = mysql_error();
    $query = "FLUSH PRIVILEGES;\n";
    $result = mysql_query($query);

    $query = "DROP DATABASE IF EXISTS `".$sql_database."`;\n";
    $result = mysql_query($query);

    // Closing connection
    mysql_close($fixed_mysql_link);

    // Set MySQL_Vsite to disabled, too:
    $cceClient->set($VsiteSettings['OID'], 'MYSQL_Vsite', array("enabled" => "0"));
    $errors = $cceClient->errors();

}

function get_fqdn_details($domain_to_check) {

  // FQDN or domain? That is the question. WHMCS might have passed
  // us just a domain name like "company.com". BlueOnyx needs FQDN's
  // and so we might be missing the hostname part. If that's the case,
  // we will prefix $payload->domain with "www." just to be safe.

  // Array with sample hostnames that we might see:
  $sample_hostnames = array("www", "mail", "ftp", "smtp", "imap", "pop", "pop3", "ns", "ns0", "ns1", "ns2", "ns3", "ns4");

  // Get Host and Domain name:
  if ($domain_to_check) {
    // Get Host from FQDN:
    $matches = preg_split("/\./", $domain_to_check);
    // Is the extracted Hostname in the array of known hostnames?
    if (in_array($matches[0], $sample_hostnames)) {
      // It is:
      $hostname = $matches[0];
      // Get Domain from FQDN:
      array_shift($matches);
      $domain = implode(".", $matches);
      // Build FQDN:
      $fqdn = $hostname . "." . $domain;
    }
    else {
      // We don't appear to have a hostname. So we set the hostname to "www":
      $hostname = "www";
      $domain = $domain_to_check;
      $fqdn = $hostname . "." . $domain;
    }
  }

  // Build output array:
  $result = array("hostname" => $hostname, "domain" => $domain, "fqdn" => $fqdn);

  // Return result:
  return $result;

}

// nice people say aufwiedersehen
$helper->destructor();

/*
Copyright (c) 2013 Michael Stauber, SOLARSPEED.NET
Copyright (c) 2013 Team BlueOnyx, BLUEONYX.IT
Copyright (c) 2003 Sun Microsystems, Inc. All  Rights Reserved.

Redistribution and use in source and binary forms, with or without modification, 
are permitted provided that the following conditions are met:

-Redistribution of source code must retain the above copyright notice, this  list of conditions and the following disclaimer.

-Redistribution in binary form must reproduce the above copyright notice, 
this list of conditions and the following disclaimer in the documentation and/or 
other materials provided with the distribution.

Neither the name of Sun Microsystems, Inc. or the names of contributors may 
be used to endorse or promote products derived from this software without 
specific prior written permission.

This software is provided "AS IS," without a warranty of any kind. ALL EXPRESS OR IMPLIED CONDITIONS, REPRESENTATIONS AND WARRANTIES, INCLUDING ANY IMPLIED WARRANTY OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE OR NON-INFRINGEMENT, ARE HEREBY EXCLUDED. SUN MICROSYSTEMS, INC. ("SUN") AND ITS LICENSORS SHALL NOT BE LIABLE FOR ANY DAMAGES SUFFERED BY LICENSEE AS A RESULT OF USING, MODIFYING OR DISTRIBUTING THIS SOFTWARE OR ITS DERIVATIVES. IN NO EVENT WILL SUN OR ITS LICENSORS BE LIABLE FOR ANY LOST REVENUE, PROFIT OR DATA, OR FOR DIRECT, INDIRECT, SPECIAL, CONSEQUENTIAL, INCIDENTAL OR PUNITIVE DAMAGES, HOWEVER CAUSED AND REGARDLESS OF THE THEORY OF LIABILITY, ARISING OUT OF THE USE OF OR INABILITY TO USE THIS SOFTWARE, EVEN IF SUN HAS BEEN ADVISED OF THE POSSIBILITY OF SUCH DAMAGES.

You acknowledge that  this software is not designed or intended for use in the design, construction, operation or maintenance of any nuclear facility.
*/
?>