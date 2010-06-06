<?php
	// Author: Brian N. Smith
	// Copyright 2006, NuOnce Networks, Inc. All rights reserved.
	// $Id: mysql.php,v 1.3 2006/08/09 10:18:00 Exp $

	// Thanks to "Apmuthu" for the download fix.  Every bit of help is appreciated!

	include_once("ServerScriptHelper.php");
	include_once("Product.php");

	$serverScriptHelper = new ServerScriptHelper();
	$cceClient = $serverScriptHelper->getCceClient();

	if (!$serverScriptHelper->getAllowed('adminUser')) {
		header("location: /error/forbidden.html");
		return;
	}

	$serverScriptHelper->shell("/bin/cat /usr/sausalito/ui/web/base/mysql/mysql-dump.sql", $content, "root");
	$content = str_replace("\n", "\r\n", $content);

	$filename = 'mysql-dump-' . date(YmdHis) . '.sql'; 

	if (preg_match('/Opera(\/| )([0-9].[0-9]{1,2})/', $_SERVER['HTTP_USER_AGENT'])) $UserBrowser = "Opera"; 
		elseif  (preg_match('/MSIE ([0-9].[0-9]{1,2})/', $_SERVER['HTTP_USER_AGENT'])) $UserBrowser = "IE"; 
		else 	$UserBrowser = ''; 
 
	// important for download im most browser 
	$chg_mime_type = ($UserBrowser == 'IE' || $UserBrowser == 'Opera') ? 'application/octetstream' : 'application/octet-stream'; 
	 
	header("Expires: Mon, 26 Nov 1997 05:00:00 GMT"); 
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT"); 
	header("Cache-Control: no-store, no-cache, must-revalidate"); // HTTP/1.1  
	header("Cache-Control: post-check=0, pre-check=0", false);  
//	header("Pragma: no-cache"); // Removed for MSIE - may work for others also - since https is iused, cache is needed for sessions and other work 
	header("Content-Type: $chg_mime_type"); 
	header('Content-Disposition: attachment; filename="' . $filename . '"'); 
//	header('Content-Disposition: inline; filename="' . $filename . '"'); // also works in MSIE 
 
	print $content; 
?>
