<?php

// Author:
//              Michael Stauber - Stauber Multimedia Design - http://www.solarspeed.net
// Copyright 2006-2009, Stauber Multimedia Design. All rights reserved.
// Copyright 2009 Team BlueOnyx. All rights reserved.
// Thu 02 Jul 2009 02:57:48 AM CEST
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
		"kill_pid" => $pid,
		"kill_trigger" => time()
	  )
	 );

$errors = $cce->errors();

$errors = array_merge($errors, $cce->errors());

print $helper->toHandlerHtml("/base/console/console_procs.php", $errors);

// nice people say aufwiedersehen
$helper->destructor();

?>
