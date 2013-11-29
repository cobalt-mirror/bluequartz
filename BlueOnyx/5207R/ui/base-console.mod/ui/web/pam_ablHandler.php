<?php

// Author: Michael Stauber <mstauber@solarspeed.net>
// Copyright 2006-2009, Stauber Multimedia Design. All rights reserved.
// Copyright Team BlueOnyx 2009. All rights reserved.

include_once("ServerScriptHelper.php");

$serverScriptHelper = new ServerScriptHelper();

//Only users with adminUser capability should be here
if (!$serverScriptHelper->getAllowed('adminUser')) {
    header("location: /error/forbidden.html");
    return;
}

$cceClient = $serverScriptHelper->getCceClient();

$phpOID = $cceClient->find("pam_abl_settings");

// Convert "disabled" back to the right format:
if ($user_rule == "disabled") {
    $user_rule = "50000/1m";
}
if ($host_rule == "disabled") {
    $host_rule = "50000/1m";
}

// Build rules:
$user_rule_new = "!admin/cced=10000/1h," . $user_rule;
$host_rule_new = "*=" . $host_rule;

$cceClient->set($phpOID[0], "",
        array(
              "force_update" => $force_update,
              "user_purge" => $user_purge,
              "host_purge" => $host_purge,
              "user_rule" => $user_rule_new,
              "host_rule" => $host_rule_new,
              "update_config" => time(),
              "force_update" => time())
	      );

$errors = $cceClient->errors();

print($serverScriptHelper->toHandlerHtml("/base/console/pam_abl.php", $errors));

$serverScriptHelper->destructor();

?>
