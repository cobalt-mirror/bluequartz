<?php
        // Copyright 2011, Team BlueOnyx. All rights reserved.
        // $Id: redirector.php,v 1.0.0-1 Tue 26 Apr 2011 02:22:10 AM CEST mstauber Exp $

	include_once("ServerScriptHelper.php");
	$serverScriptHelper = new ServerScriptHelper();
	$cceClient = $serverScriptHelper->getCceClient();

	if (!$serverScriptHelper->getAllowed('adminUser')) {
		header("location: /error/forbidden.html");
		return;
	}
	else {
	    print($serverScriptHelper->toHandlerHtml("/base/mailman/system.php?redirected=1", $errors, "base-mailman"));
	}
	$serverScriptHelper->destructor();
?>


