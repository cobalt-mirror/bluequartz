<?php

// Author:
//              Michael Stauber - Stauber Multimedia Design - http://www.solarspeed.net
// Copyright 2006-2009, Stauber Multimedia Design. All rights reserved.
// Copyright 2009 Team BlueOnyx. All rights reserved.
// Fri 03 Jul 2009 09:38:31 AM CEST
//

include_once("ServerScriptHelper.php");
include_once("AutoFeatures.php");

$helper =& new ServerScriptHelper($sessionId);

// Only adminUser should be here
if (!$helper->getAllowed('adminUser')) {
  header("location: /error/forbidden.html");
  return;
}

$cce =& $helper->getCceClient();

$vsiteOID = $cce->find("SOL_Console");

$cce->set($vsiteOID[0], "", 
	  array(
		"user_kill_console" => $console,
		"user_kill_user" => $username,
		"user_kill_pid" => $pid,
		"user_kill_trigger" => time()
	  )
	 );

$errors = $cce->errors();

$errors = array_merge($errors, $cce->errors());

print $helper->toHandlerHtml("/base/console/console_logins.php", $errors);

// nice people say aufwiedersehen
$helper->destructor();

?>

