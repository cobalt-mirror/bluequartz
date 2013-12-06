<?php
	// Authors: Brian N. Smith and Michael Stauber
	// $Id: mysqlHandler.php

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
	$helper = new ServerScriptHelper($sessionId);
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

/*
Copyright (c) 2013 Michael Stauber, SOLARSPEED.NET
Copyright (c) 2013 Team BlueOnyx, BLUEONYX.IT
Copyright (c) 2003 Sun Microsystems, Inc. All  Rights Reserved.

Redistribution and use in source and binary forms, with or without modification, 
are permitted provided that the following conditions are met:

-Redistribution of source code must retain the above copyright notice, this  list of conditions and the following disclaimer.

-Redistribution in binary form must reproduce the above copyright notice, 
this list of conditions and the following disclaimer in the documentation and/or 
other materials provided with the distribution.

Neither the name of Sun Microsystems, Inc. or the names of contributors may 
be used to endorse or promote products derived from this software without 
specific prior written permission.

This software is provided "AS IS," without a warranty of any kind. ALL EXPRESS OR IMPLIED CONDITIONS, REPRESENTATIONS AND WARRANTIES, INCLUDING ANY IMPLIED WARRANTY OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE OR NON-INFRINGEMENT, ARE HEREBY EXCLUDED. SUN MICROSYSTEMS, INC. ("SUN") AND ITS LICENSORS SHALL NOT BE LIABLE FOR ANY DAMAGES SUFFERED BY LICENSEE AS A RESULT OF USING, MODIFYING OR DISTRIBUTING THIS SOFTWARE OR ITS DERIVATIVES. IN NO EVENT WILL SUN OR ITS LICENSORS BE LIABLE FOR ANY LOST REVENUE, PROFIT OR DATA, OR FOR DIRECT, INDIRECT, SPECIAL, CONSEQUENTIAL, INCIDENTAL OR PUNITIVE DAMAGES, HOWEVER CAUSED AND REGARDLESS OF THE THEORY OF LIABILITY, ARISING OUT OF THE USE OF OR INABILITY TO USE THIS SOFTWARE, EVEN IF SUN HAS BEEN ADVISED OF THE POSSIBILITY OF SUCH DAMAGES.

You acknowledge that  this software is not designed or intended for use in the design, construction, operation or maintenance of any nuclear facility.
*/
?>