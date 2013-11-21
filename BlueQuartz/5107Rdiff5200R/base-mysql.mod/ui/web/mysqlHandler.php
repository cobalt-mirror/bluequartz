<?php
	// Authors: Brian N. Smith and Michael Stauber
	// Copyright 2006, NuOnce Networks, Inc and Solarspeed Ltd. All rights reserved.
	// $Id: mysqlHandler.php,v 1.2 Fri Dec  5 02:12:19 2008	Exp $

	include_once("ServerScriptHelper.php");
	$serverScriptHelper = new ServerScriptHelper();
	$cceClient = $serverScriptHelper->getCceClient();

	if (!$serverScriptHelper->getAllowed('adminUser')) {
		header("location: /error/forbidden.html");
		return;
	}


	$page = $_POST["_PagedBlock_selectedId_mysql_header"];


	switch ($page) {

		case "server":
			$int = date("U");
			$cfg = array(
				"enabled" => $enabled,
				"onoff" => $int);
			break;

		case "sqldump":
			$dump = date("U");
			$cfg = array(
				"username" => $sql_root, 
				"password" => $sql_rootpassword, 
				"dump" => $dump);
			break;

		case "sqlpass":
			$changepass = date("U");
			if ( $oldpass == "" ) { $oldpass = "-1"; }
			$cfg = array(
				"changepass" => $changepass,
				"mysqluser" => $sql_root,
				"oldpass" => $oldpass,
				"newpass" => $newpass);
			
			$sql_rootpassword = $newpass;
			
			break;
	}

	$cceClient->setObject("System", $cfg, "mysql");
	$errors[] = $cceClient->errors();

	// Find and validate presence of 'MySQL':
	$helper =& new ServerScriptHelper($sessionId);
	$cceHelper =& $helper->getCceClient();
	$getthisOID = $cceHelper->find("MySQL");
	$mysql_settings_exists = 0;
	$mysql_settings = $cceClient->get($getthisOID[0]);
	if (!$mysql_settings['timestamp']) {
            $mysqlOID = $cceHelper->create("MySQL",
                    array(
                        'sql_host' => $sql_host,
                        'sql_port' => $sql_port,
                        'sql_root' => $sql_root,
                        'sql_rootpassword' => $sql_rootpassword,
                        'savechanges' => time(),
                        'timestamp' => time()
                    )
            );
	}
	else {
            $mysqlOID = $cceHelper->find("MySQL");
            $cceHelper->set($mysqlOID[0], "",
                    array(
                        'sql_host' => $sql_host,
                        'sql_port' => $sql_port,
                        'sql_root' => $sql_root,
                        'sql_rootpassword' => $sql_rootpassword,
                        'savechanges' => time(),
                        'timestamp' => time()
                    )
            );
	}
        $errors[] = $cceHelper->errors();

	// Redirecting to make sure values update on UI:
	print($serverScriptHelper->toHandlerHtml("/base/mysql/redirector.php", $errors, "base-mysql"));
	$serverScriptHelper->destructor();
?>


