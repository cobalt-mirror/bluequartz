<?php
// Authors: Brian N. Smith & Michael Stauber
// Copyright 2007-2008, Solarspeed Ltd. and NuOnce Networks, Inc.  All rights reserved.
// $Id: tomcat-managerHandler.php,v 2.0 Wed Nov 26 17:02:08 2008 mstauber Exp $

include_once("ArrayPacker.php");
include_once("ServerScriptHelper.php");
include_once("Product.php");
include_once("System.php");

$serverScriptHelper = new ServerScriptHelper();
$i18n = $serverScriptHelper->getI18n("base-java");
$factory = $serverScriptHelper->getHtmlComponentFactory("base-java",
                                '/base/java/tomcat-managerHandler.php');

if (!$serverScriptHelper->getAllowed('adminUser')) {
    header("location: /error/forbidden.html");
    return;
}

if (!$_save) {
    header("location: /error/forbidden.html");
    return;
}

$serverScriptHelper = new ServerScriptHelper();
$cceClient = $serverScriptHelper->getCceClient();
$oids = $cceClient->find("System");

// Start sane:
$errors = array();

// Only run cracklib checks if something was entered into the password field:
if ($password) {

    // Open CrackLib Dictionary for usage:
    $dictionary = crack_opendict('/usr/share/dict/pw_dict') or die('Unable to open CrackLib dictionary');

    // Perform password check with cracklib:
    $check = crack_check($dictionary, $password);

    // Retrieve messages from cracklib:
    $diag = crack_getlastmessage();

    if ($diag == 'strong password') {
	// Shove it in an array:
        $java_config = array(
    	    "JavaAdminPass" => $password
	);

	// Write pass off to CCE:
	$cceClient->set($oids[0], "Java", $java_config);
	$errors = array_merge($errors, $cceClient->errors());
    }
    else {
        $attributes["password"] = "1";
        $errors[] = new Error("[[base-user.error-password-invalid]]" . $diag . ". " . "[[base-user.error-invalid-password]]");
    }

    // Close cracklib dictionary:
    crack_closedict($dictionary);

}

$serverScriptHelper->destructor();
print($serverScriptHelper->toHandlerHtml("/base/java/tomcat-manager.php", $errors, false));

?>


