<?php

// Author: Michael Stauber <mstauber@solarspeed.net>
// Copyright 2006-2008, Stauber Multimedia Design. All rights reserved.

include("ServerScriptHelper.php");

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

$errors = $cceClient->errors();

print($serverScriptHelper->toHandlerHtml("/base/vsite/php_server.php", $errors));

$serverScriptHelper->destructor();

?>
