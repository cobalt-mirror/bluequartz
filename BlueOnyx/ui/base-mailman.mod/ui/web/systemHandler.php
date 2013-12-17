<?php
        // Copyright 2011, Team BlueOnyx. All rights reserved.
        // $Id: systemHandler.php,v 1.0.0-1 Tue 26 Apr 2011 02:22:10 AM CEST mstauber Exp $

	include_once("ServerScriptHelper.php");
	$serverScriptHelper = new ServerScriptHelper();
	$cceClient = $serverScriptHelper->getCceClient();

	if (!$serverScriptHelper->getAllowed('serverServerDesktop')) {
		header("location: /error/forbidden.html");
		return;
	}


	$page = $_POST["_PagedBlock_selectedId_MailMan_header"];


	switch ($page) {

		case "server":
			$int = date("U");
			$cfg = array(
				"enabled" => $enabled,
				"onoff" => $int);
			break;
	}

        $sysoid = $cceClient->find('System');
        $ret = $cceClient->set($sysoid, 'MailListStatus',
                array('enabled' => $enabled, 'force_update' => $int));
	$errors[] = $cceClient->errors();

	// Redirecting to make sure values update on UI:
	print($serverScriptHelper->toHandlerHtml("/base/mailman/redirector.php", $errors, "base-mailman"));
	$serverScriptHelper->destructor();
?>


