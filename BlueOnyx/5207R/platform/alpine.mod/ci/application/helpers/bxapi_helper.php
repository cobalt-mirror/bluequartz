<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * BlueOnyx API Helper
 *
 * BlueOnyx API Helper for Codeigniter
 *
 * @package   CI Blueonyx
 * @author    Michael Stauber
 * @link      http://www.solarspeed.net
 * @license   http://devel.blueonyx.it/pub/BlueOnyx/licenses/SUN-modified-BSD-License.txt
 * @version   2.2
 */

//
//-- Function: Create Vsite
//

function do_modify_vsite ($payload, $clientsdetails, $helper, $action="unknown") {

	// Get CodeIgniter Instance:
	$CI =& get_instance();

	$errors = array();
	$mysql_error = array();


  // FQDN or domain? That is the question. WHMCS might have passed
  // us just a domain name like "company.com". BlueOnyx needs FQDN's
  // and so we might be missing the hostname part. If that's the case,
  // we will prefix $payload->domain with "www." just to be safe.
  // We use the function get_fqdn_details() for that:

  $host_details = get_fqdn_details($payload->domain);

  // Get the vsiteDefaults:
  $cceClient = $helper->getCceClient();
  $vsiteDefaults = $cceClient->getObject("System", array(), "VsiteDefaults");

  $buildcheck = `cat /etc/build|grep 5209R|wc -l`;
  if ($buildcheck == "1\n") {
  	$build = "5209R";
  	error_log("Build:" . $build);
  }
  else {
  	$build = "520XR";
  	error_log("Build:" . $build);
  }

  // Get Vsite OID:
  $vsiteOIDHelper = $cceClient->find("Vsite", array("fqdn" => $host_details['fqdn']));
  $vsiteOID = $vsiteOIDHelper[0];

  error_log("BlueOnyx API: OID for Vsite " . $host_details['fqdn'] . " is: " . $vsiteOID);
  error_log("Error count before Vsite modification: " . count($errors));

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
  // Commented out, as this will always return an error array with one element,
  // even if there *was* no error to begin with. Funny how that goes.
  //$errors = $cceClient->errors();

  error_log("Error count past Vsite modification: undetermined, as it will always bugger out.");

  // Setup disk-quota:
  if ($vsiteOID) {
	  $quota = $payload->disk;
	  $cceClient->set($vsiteOID, 'Disk', array('quota' => $quota));
	  $errors = array_merge($errors, $cceClient->errors());
	  error_log("Error count past Quota set: " . count($errors));
  }

  // Find the siteAdmin for this site and update his disk-quota as well:
  $userHelper = $cceClient->find("User", array("name" => $payload->username));
  $cceClient->set($userHelper[0], 'Disk', array('quota' => $quota));
  $errors = array_merge($errors, $cceClient->errors());

  // Setup Email forwarding:
  if (($userHelper[0]) && ($payload->forwardemail == "1")) {
	  $cceClient->set($userHelper[0], 'Email', array('forwardEnable' => "1", "forwardEmail" => "&" . $clientsdetails->email . "&", "forwardSave" => "0"));
	  $errors = array_merge($errors, $cceClient->errors());
	  error_log("Error count past Email forwarding: " . count($errors));
  }
  else {
	  $cceClient->set($userHelper[0], 'Email', array('forwardEnable' => "0"));
	  $errors = array_merge($errors, $cceClient->errors());
	  error_log("Error count past Email forwarding: " . count($errors));
  }

  // Handle suPHP. If enabled, we set the web-ownerships to this User:
  if (($payload->php == "suPHP") || ($payload->php == "ModRUID") || ($payload->php == "FPM")) {
	  $cceClient->set($vsiteOID, 'PHP', array('prefered_siteAdmin' => $payload->username));
	  $errors = array_merge($errors, $cceClient->errors());
  }
  else {
	  $cceClient->set($vsiteOID, 'PHP', array('prefered_siteAdmin' => "apache"));
	  $errors = array_merge($errors, $cceClient->errors());
  }

  error_log("Error count past prefered_siteAdmin settings: " . count($errors));

  // If Auto-DNS is enabled, we grant this user the caplevel of 'dnsAdmin' as well:
  if (isset($payload->auto_dns)) {
	  if ($payload->auto_dns == "1") {
		$cceClient->set($userHelper[0], '', array('capLevels' => "&siteAdmin&dnsAdmin&"));
		$errors = array_merge($errors, $cceClient->errors());
		error_log("Error count past Auto-DNS settings #1: " . count($errors));

		// But beyond that we also need to add the domain name to the list of Domains under his DNS control:
		$cceClient->set($vsiteOID, 'DNS', array('domains' => "&" . $host_details['domain'] . "&"));
		$errors = array_merge($errors, $cceClient->errors());
		error_log("Error count past Auto-DNS settings #2: " . count($errors));

		// Update the DNS server with the new DNS zones:
		list($sysoid) = $cceClient->find("System");
		$cceClient->set($sysoid, "DNS", array("commit" => time()));
		$errors = array_merge($errors, $cceClient->errors());
		error_log("Error count past Auto-DNS settings #3: " . count($errors));
	  }
	  else {
		$cceClient->set($userHelper[0], '', array('capLevels' => "&siteAdmin&"));
		$errors = array_merge($errors, $cceClient->errors());
		error_log("Error count past Auto-DNS settings #1b: " . count($errors));

		// But beyond that we also need to remove the domain name to the list of Domains under his DNS control:
		$cceClient->set($vsiteOID, 'DNS', array('domains' => ""));
		$errors = array_merge($errors, $cceClient->errors());
		error_log("Error count past Auto-DNS settings #2b: " . count($errors));

		// Update the DNS server with the new DNS zones:
		list($sysoid) = $cceClient->find("System");
		$cceClient->set($sysoid, "DNS", array("commit" => time()));
		$errors = array_merge($errors, $cceClient->errors());
		error_log("Error count past Auto-DNS settings #3b: " . count($errors));
	  }
  }

  // Now set the various options:

  // JSP:
  if (isset($payload->jsp)) {
	  $cceClient->set($vsiteOID, 'Java', array('enabled' => $payload->jsp));
	  $errors = array_merge($errors, $cceClient->errors());
	  error_log("Error count past JSP settings: " . count($errors));
  }
  // PHP:
  if (isset($payload->php)) {
	// Possible options: No,Yes,ModRUID,suPHP,FPM
	$php = "0"; 
	$suPHP = "0";
	$mod_ruid_enabled = "0";
	$fpm_enabled = "0";

	if ($build == "5209R") {
		error_log("PHP handling for productBuild: " . $build);
		if ($payload->php == "Yes") { $php = "1"; $suPHP = "0"; $mod_ruid_enabled = "0"; $fpm_enabled = "0"; }
		if ($payload->php == "suPHP") { $php = "1"; $suPHP = "1"; $mod_ruid_enabled = "0"; $fpm_enabled = "0"; }
		if ($payload->php == "ModRUID") { $php = "1"; $suPHP = "0"; $mod_ruid_enabled = "1"; $fpm_enabled = "0"; }
		if ($payload->php == "FPM") { $php = "1"; $suPHP = "0"; $mod_ruid_enabled = "0"; $fpm_enabled = "1"; }
		$cceClient->set($vsiteOID, 'PHP', array('enabled' => $php, "suPHP_enabled" => $suPHP, "mod_ruid_enabled" => $mod_ruid_enabled, "fpm_enabled" => $fpm_enabled));
	}
	else {
		error_log("PHP handling for productBuild: " . $build);
		if ($payload->php == "Yes") { $php = "1"; $suPHP = "0"; }
		if ($payload->php == "suPHP") { $php = "1"; $suPHP = "1"; }
		$cceClient->set($vsiteOID, 'PHP', array('enabled' => $php, "suPHP_enabled" => $suPHP));
	}
	$errors = array_merge($errors, $cceClient->errors());
	error_log("Error count past PHP settings: " . count($errors));
  }

  // MySQL:

  // Get Vsite's MySQL settings:
  $mysql_vsite_settings = $cceClient->get($vsiteOID, 'MYSQL_Vsite');
  error_log("BlueOnyx API: Checking if MySQL needs to be handled: $payload->mysql vs " . $mysql_vsite_settings['enabled']);

  if ((isset($payload->mysql)) && ($payload->mysql == "1") && ($mysql_vsite_settings['enabled'] == "0")) {

	  error_log("BlueOnyx API: MySQL was not enabled before, but should be on now.");

	  // IF we're here, MySQL wasn't enabled before. So we do that now:
	
	  // Find out group of Vsite:
	  $VsiteSettings = $cceClient->get($vsiteOID, '');

	  // Get MySQL Server settings from CCE:
	  $mysql_settings = $cceClient->getObject("MySQL", array());

	  // Set up MySQL username, DB-name and MySQL-password:
	  $sql_username = "vsite_" . createRandomPassword('7');
	  $sql_database = $sql_username . '_db';
	  $my_random_password = createRandomPassword("8", "alpha");

	  if ($payload->mysql == "1") {
	  	$sql_action = 'create';
	  }
	  else {
	  	$sql_action = 'destroy';
	  }

	  // Do the deeds:
	  if ($payload->mysql == "1") {
		$cceClient->set($vsiteOID, 'MYSQL_Vsite', array("username" => $sql_username,
								"pass" => $my_random_password,
								"host" => $mysql_settings['sql_host'],
								"hidden" => time(),
								"DB" => $sql_database,
								"port" => $mysql_settings['sql_port'],
								"enabled" => "1",
								$sql_action => time()
								));
		$errors = array_merge($errors, $cceClient->errors());
		error_log("Error count past MySQL settings: " . count($errors));
	  }
  }
  elseif ((isset($payload->mysql)) && ($payload->mysql == "1") && ($mysql_vsite_settings['enabled'] == "1")) {
	error_log("BlueOnyx API: MySQL was enabled before and should still be enabled. Doing nothing.");
  }
  else {

	error_log("BlueOnyx API: MySQL was enabled before, but should be off now.");

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
		  error_log("Error count past MySQL deletion: " . count($errors));
	}
  }

  // CGI:
  if (isset($payload->cgi)) {
	  $cceClient->set($vsiteOID, 'CGI', array('enabled' => $payload->cgi));
	  $errors = array_merge($errors, $cceClient->errors());
	  error_log("Error count past CGI settings: " . count($errors));
  }

  // SSI:
  if (isset($payload->ssi)) {
	  $cceClient->set($vsiteOID, 'SSI', array('enabled' => $payload->ssi));
	  $errors = array_merge($errors, $cceClient->errors());
	  error_log("Error count past SSI settings: " . count($errors));
  }

  // bwlimit:
  if ((isset($payload->bwlimit)) && (isset($payload->bwlimitVal))) {
  	error_log("ApacheBandwidth handling for productBuild: " . $build);
  	if ($build != "5209R") {
		$cceClient->set($vsiteOID, 'ApacheBandwidth', array('enabled' => $payload->bwlimit, 'speed' => $payload->bwlimitVal));
		$errors = array_merge($errors, $cceClient->errors());
		error_log("Error count past ApacheBandwidth settings: " . count($errors));
	}
  }

  // FTP:
  if (isset($payload->ftp)) {
	  $cceClient->set($vsiteOID, 'FTPNONADMIN', array('enabled' => $payload->ftp));
	  $errors = array_merge($errors, $cceClient->errors());
	  error_log("Error count past FTPNONADMIN settings: " . count($errors));
  }

  // userwebs:
  if (isset($payload->userwebs)) {
	  $cceClient->set($vsiteOID, 'USERWEBS', array('enabled' => $payload->userwebs));
	  $errors = array_merge($errors, $cceClient->errors());
	  error_log("Error count past USERWEBS settings: " . count($errors));
  }

  // Shell-access:
  if (isset($payload->shell)) {
	  $cceClient->set($vsiteOID, 'Shell', array('enabled' => $payload->shell));
	  $errors = array_merge($errors, $cceClient->errors());
	  error_log("Error count past Shell settings: " . count($errors));
  }
  
  // Sub-Domains:
  if ((isset($payload->subdomains)) && (isset($payload->subdomainsMax))) {
	  $cceClient->set($vsiteOID, 'subdomains', array('vsite_enabled' => $payload->subdomains, 'max_subdomains' => $payload->subdomainsMax));
	  $errors = array_merge($errors, $cceClient->errors());
	  error_log("Error count past subdomains settings: " . count($errors));
  }

  // Auto-DNS:
  if (isset($payload->auto_dns)) {
	  if ($payload->auto_dns == "1") {
		$cceClient->set($vsiteOID, 'DNS', array('domains' => "&" . $host_details['domain'] . "&"));
		list($sysoid) = $cceClient->find("System");
		$cceClient->set($sysoid, "DNS", array("commit" => time()));
		$errors = array_merge($errors, $cceClient->errors());
		error_log("Error count past auto_dns settings: " . count($errors));
	  }
  }

  if (count($errors) >= "1") {

	  	// We have an error and an incompletly created Vsite. So we destroy the Vsite. But not if this is a modify:
		if ($action != "modify") {
			$cceClient->destroy($vsiteOID);

			// Delete MySQL-User and Database:
			delete_mysql_stuff($VsiteSettings, $cceClient);
			error_log("BlueOnyx API: Sorry, an error occured while modifying the various services for the Vsite.");
			error_log("Total Error count: " . count($errors));
			error_log("Errors: " . print_rp($errors));
		}
		else {
			error_log("BlueOnyx API: Sorry, an error occured while modifying the various services for the Vsite.");
			error_log("Total Error count: " . count($errors));
			error_log("Errors: " . print_rp($errors));
		}

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

	// Get CodeIgniter Instance:
	$CI =& get_instance();

	$errors = array();
	$mysql_error = array();

	// FQDN or domain? That is the question. WHMCS might have passed
	// us just a domain name like "company.com". BlueOnyx needs FQDN's
	// and so we might be missing the hostname part. If that's the case,
	// we will prefix $payload->domain with "www." just to be safe.
	// We use the function get_fqdn_details() for that:

	$host_details = get_fqdn_details($payload->domain);

	// Get the vsiteDefaults:
	$cceClient = $helper->getCceClient();
	$vsiteDefaults = $cceClient->getObject("System", array(), "VsiteDefaults");

	$buildcheck = `cat /etc/build|grep 5209R|wc -l`;
	if ($buildcheck == "1\n") {
		$build = "5209R";
		error_log("Build:" . $build);
	}
	else {
		$build = "520XR";
		error_log("Build:" . $build);
	}

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

	array_merge($errors, $cceClient->errors());

	if (count($errors) >= "1") {
		error_log("BlueOnyx API: Sorry, the Vsite did not create properly.");
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
	  error_log("BlueOnyx API: Sorry, could not set disk-quota information for Vsite.");
	}

	// Now set the various options:

	// JSP:
	if (isset($payload->jsp)) {
	  $cceClient->set($vsiteOID, 'Java', array('enabled' => $payload->jsp));
	  $errors = array_merge($errors, $cceClient->errors());
	}
	// PHP:
	if (isset($payload->php)) {
		// Possible options: No,Yes,ModRUID,suPHP,FPM
		$php = "0"; 
		$suPHP = "0";
		$mod_ruid_enabled = "0";
		$fpm_enabled = "0";

		if ($build == "5209R") {
			error_log("PHP handling for productBuild: " . $build);
			if ($payload->php == "Yes") { $php = "1"; $suPHP = "0"; $mod_ruid_enabled = "0"; $fpm_enabled = "0"; }
			if ($payload->php == "suPHP") { $php = "1"; $suPHP = "1"; $mod_ruid_enabled = "0"; $fpm_enabled = "0"; }
			if ($payload->php == "ModRUID") { $php = "1"; $suPHP = "0"; $mod_ruid_enabled = "1"; $fpm_enabled = "0"; }
			if ($payload->php == "FPM") { $php = "1"; $suPHP = "0"; $mod_ruid_enabled = "0"; $fpm_enabled = "1"; }
			$cceClient->set($vsiteOID, 'PHP', array('enabled' => $php, "suPHP_enabled" => $suPHP, "mod_ruid_enabled" => $mod_ruid_enabled, "fpm_enabled" => $fpm_enabled));
		}
		else {
			error_log("PHP handling for productBuild: " . $build);
			if ($payload->php == "Yes") { $php = "1"; $suPHP = "0"; }
			if ($payload->php == "suPHP") { $php = "1"; $suPHP = "1"; }
			$cceClient->set($vsiteOID, 'PHP', array('enabled' => $php, "suPHP_enabled" => $suPHP));
		}
		$errors = array_merge($errors, $cceClient->errors());
		error_log("Error count past PHP settings: " . count($errors));
	}
	// MySQL:
	if (isset($payload->mysql)) {
	  // Find out group of Vsite:
	  $VsiteSettings = $cceClient->get($vsiteOID, '');

	  // Set up MySQL username, DB-name and MySQL-password:
	  $sql_username = "vsite_" . createRandomPassword('7');
	  $sql_database = $sql_username . '_db';
	  $my_random_password = createRandomPassword("8", "alpha");

	  // Get MySQL Server settings from CCE:
	  $mysql_settings = $cceClient->getObject("MySQL", array());

	  if ($payload->mysql == "1") {
	  	$sql_action = 'create';
	  }
	  else {
	  	$sql_action = 'destroy';
	  }

	  // Do the deeds:
	  if ($payload->mysql == "1") {
		$cceClient->set($vsiteOID, 'MYSQL_Vsite', array("username" => $sql_username,
								"pass" => $my_random_password,
								"host" => $mysql_settings['sql_host'],
								"hidden" => time(),
								"DB" => $sql_database,
								"port" => $mysql_settings['sql_port'],
								"enabled" => "1",
								$sql_action => time()
								));

		$errors = array_merge($errors, $cceClient->errors());
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
		error_log("ApacheBandwidth handling for productBuild: " . $build);
		if ($build != "5209R") {
			$cceClient->set($vsiteOID, 'ApacheBandwidth', array('enabled' => $payload->bwlimit, 'speed' => $payload->bwlimitVal));
			$errors = array_merge($errors, $cceClient->errors());
		}
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
	  error_log("BlueOnyx API: Sorry, an error occured while enabling the various services for the Vsite.");
	}
	elseif (count($mysql_error) >= "1") {

	  // We have an error and an incompletly created Vsite. So we destroy the Vsite:
	  $cceClient->destroy($vsiteOID);

	  // Delete MySQL-User and Database:
	  delete_mysql_stuff($VsiteSettings, $cceClient);

	  error_log("BlueOnyx API: Sorry, an error occured while setting up the MySQL database of this Vsite.");
	  // nice people say aufwiedersehen
	  $helper->destructor();
	  exit;
	}
	else {
		//error_log("BlueOnyx API-Helper: No errors. Vsite apparently created just fine.");
		return $VsiteSettings;
	}

}

//
//-- Function: Create User
//

function do_create_user ($payload, $clientsdetails, $helper, $VsiteSettings) {

	$errors = array();

	// Get CodeIgniter Instance:
	$CI =& get_instance();

	// Build comments:
	$comments = $clientsdetails->firstname . " " . $clientsdetails->lastname . "\n" . $clientsdetails->email . "\n";

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

	$errors = array_merge($errors, $cceClient->errors());

	if (count($errors) >= "1") {
	  error_log("BlueOnyx API: Sorry, the siteAdmin-User did not create properly.");

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
	  error_log("BlueOnyx API: Sorry, could not set disk-quota information for the siteAdmin-User.");
	}

	// Setup Email forwarding:
	if (($userOID) && ($payload->forwardemail == "1")) {
	  $cceClient->set($userOID, 'Email', array('forwardEnable' => "1", "forwardEmail" => "&" . $clientsdetails->email . "&", "forwardSave" => "0"));
	  $errors = array_merge($errors, $cceClient->errors());
	}
	if (count($errors) >= "1") {
	  error_log("BlueOnyx API: Sorry, could not set up email forwarding for the siteAdmin-User.");
	}

	// Setup nicer email alias:
	if ($userOID) {
	  if (($clientsdetails->firstname) && ($clientsdetails->firstname != "")) {
		$firstname = "&" . strtolower($clientsdetails->firstname) . "&";
		$cceClient->set($userOID, 'Email', array("aliases" => $firstname));
		$errors = array_merge($errors, $cceClient->errors());
	  }
	}

	// Handle PHP ownerships. If suitable PHP enabled, we set the web-ownerships to this User:
	if (($payload->php == "suPHP") || ($payload->php == "ModRUID") || ($payload->php == "FPM")) {
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

		error_log("BlueOnyx API: Sorry, an error happened during User creation.");
		// nice people say aufwiedersehen
		$helper->destructor();
		exit;
	}
	else {
		// Yaay!
		//error_log("BlueOnyx API: User '$payload->username' created sucessfully.");
		return "success";
	}
}

function delete_mysql_stuff ($VsiteSettings, $cceClient) {

	$sql_username = $VsiteSettings['sql_username'];
	$sql_database = $VsiteSettings['sql_database'];
	$sql_host = $VsiteSettings['sql_host'];
	$sql_root = $VsiteSettings['sql_root'];
	$sql_rootpassword = $VsiteSettings['sql_rootpassword'];

	// Set up new MySQL-Error array:
	$mysql_error = array();

	// Set MySQL_Vsite to disabled, too:
	$cceClient->set($VsiteSettings['OID'], 'MYSQL_Vsite', array("enabled" => "0", 'destroy' => time()));
	$mysql_error = $cceClient->errors();

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