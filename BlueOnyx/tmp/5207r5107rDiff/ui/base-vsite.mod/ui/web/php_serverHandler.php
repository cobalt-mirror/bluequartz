<?php

// Author: Michael Stauber <mstauber@solarspeed.net>
// Copyright 2006-2008, Stauber Multimedia Design. All rights reserved.

include_once("ServerScriptHelper.php");

$serverScriptHelper = new ServerScriptHelper();

//Only users with adminUser capability should be here
if (!$serverScriptHelper->getAllowed('adminUser')) {
    header("location: /error/forbidden.html");
    return;
}

// Write the contends of $php_ini to /tmp/php.ini: (function disabled for now)
$disabled = "1";
if (($_PagedBlock_selectedId_php_server_head == "php_ini_expert_mode") && ($disabled == "1")) {
    if ($php_ini != "") {
	// Test if file exists:
	if (is_file("/tmp/php.ini")) {
	    // Problem: File is already there? Check if it's writeable:
	    if (is_writeable("/tmp/php.ini")) {
		// Ok, it is writeable. So we delete it:
		$ret = unlink("/tmp/php.ini");
	    }
	    // Check if it is gone now:
	    if (is_file("/tmp/php.ini")) {
		// It's still there? Someone is screwing with us. Report error and return to previous page:
                $msg = '[[base-vsite.cannot_del_tmp_php_ini]]';
        	print $serverScriptHelper->toHandlerHtml("/base/vsite/php_server.php", array(new Error($msg)), false);
	    }
	}
	// Do the actual write:
	$temp_out = "/tmp/php.ini";
	$fp = fopen($temp_out,"w");
	flock($fp,2);
	$array_zwo = $php_ini;
	fputs($fp, $php_ini);
	flock($fp,3);
	fclose($fp);
    }
}

$cceClient = $serverScriptHelper->getCceClient();

$phpOID = $cceClient->find("PHP", array("applicable" => "server"));

// Clean up 'open_basedir' user additions - and just the user additions:
$open_basedir_cleaned = str_replace(array("\r\n", "\r", "\n"), ':', $open_basedir_mandatory);

// Bare metal minimals for 'open_basedir':
$open_basedir_minimal = array('/tmp/', '/var/lib/php/session/', '/usr/sausalito/configs/php/');

// Turn it into an array:
$open_basedir_temp = explode(":", $open_basedir_cleaned);

// Walk through the array to filter out anything that doesn't look like a valid path:
foreach ($open_basedir_temp as $entry) {
    // Valid paths must start with a slash and end with a slash and certainly not with two slashes at the beginning:
    if ((preg_match("/^\/(.*)\/?$/", $entry, $regs)) && (!preg_match("/^\/\/(.*)$/", $entry, $regs))) {
        array_push($open_basedir_minimal, $entry);
    }
}

// Remove duplicates:
$open_basedir_unique = array_unique($open_basedir_minimal);

// Assemble the results into a workable format:
array_multisort($open_basedir_unique, SORT_ASC);
$open_basedir = implode(":", $open_basedir_unique);

// Make sure our 'safe_mode_allowed_env_vars' has the bare metal minimums in it:
$safe_mode_allowed_env_vars_pieces = explode (',', $safe_mode_allowed_env_vars);
if ($safe_mode_allowed_env_vars_pieces[0] == "") {
    $safe_mode_allowed_env_vars_pieces = array();
}
$safe_mode_allowed_env_vars_minimal = array('PHP_','_HTTP_HOST','_SCRIPT_NAME','_SCRIPT_FILENAME','_DOCUMENT_ROOT','_REMOTE_ADDR','_SOWNER');
$safe_mode_allowed_env_vars_merged = array_merge($safe_mode_allowed_env_vars_pieces, $safe_mode_allowed_env_vars_minimal);
$new_safe_mode_allowed_env_vars = array_unique($safe_mode_allowed_env_vars_merged);
$safe_mode_allowed_env_vars = implode(',', $new_safe_mode_allowed_env_vars);

// Find out what platform this is:
list($myplatform) = $cceClient->find('PHP');
$mysystem = $cceClient->get($myplatform);
$platform = $mysystem["PHP_version"];

if ($platform >= "5.4") {
    $register_globals = "Off";
}

if ($platform >= "5.3") {
    // We need to skip updating some legacy PHP settings that no longer work in PHP-5.3 or better:
    $cceClient->set($phpOID[0], "",
        array(
              "force_update" => $force_update,
              "register_globals" => $register_globals,
              "open_basedir" => $open_basedir,
              "disable_functions" => $disable_functions,
              "disable_classes" => $disable_classes,
              "upload_max_filesize" => $upload_max_filesize,
              "post_max_size" => $post_max_size,
              "allow_url_fopen" => $allow_url_fopen,
              "max_execution_time" => $max_execution_time,
              "max_input_time" => $max_input_time,
              "memory_limit" => $memory_limit,
              "force_update" => time())
	      );
    }
else {
    $cceClient->set($phpOID[0], "",
        array(
              "force_update" => $force_update,
              "register_globals" => $register_globals,
              "safe_mode" => $safe_mode,
              "safe_mode_gid" => $safe_mode_gid,
              "safe_mode_include_dir" => $safe_mode_include_dir,
              "safe_mode_exec_dir" => $safe_mode_exec_dir,
              "safe_mode_allowed_env_vars" => $safe_mode_allowed_env_vars,
              "safe_mode_protected_env_vars" => $safe_mode_protected_env_vars,
              "open_basedir" => $open_basedir,
              "disable_functions" => $disable_functions,
              "disable_classes" => $disable_classes,
              "upload_max_filesize" => $upload_max_filesize,
              "post_max_size" => $post_max_size,
              "allow_url_fopen" => $allow_url_fopen,
              "max_execution_time" => $max_execution_time,
              "max_input_time" => $max_input_time,
              "memory_limit" => $memory_limit,
              "force_update" => time())
	      );
}

$errors = $cceClient->errors();

print($serverScriptHelper->toHandlerHtml("/base/vsite/php_server.php", $errors));

$serverScriptHelper->destructor();

?>
