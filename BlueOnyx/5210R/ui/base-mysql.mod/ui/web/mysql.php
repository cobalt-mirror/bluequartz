<?php
	// Authors: Brian N. Smith and Michael Stauber
	// This is a quick and very dirty merge of the NuOnce MySQL and the Solarspeed MySQL modules.
	// There are quite a few redundancies here. Wonder if a rewrite from scratch might have been 
	// better instead. :o/ But for now it works.
	// $Id: mysql.php

	function format_bytes ( $size ) {
		switch ( $size ) {
			case $size > 1000000:
				return number_format(ceil($size / 1000000)) . "mb";
				break;
			case $size > 1000:
				return number_format(ceil($size / 1000)) . "k";
				break;
			default:
				return number_format($size) . "b";
				break;
		}
	}

	include_once("ServerScriptHelper.php");

	$serverScriptHelper = new ServerScriptHelper();
	$i18n = $serverScriptHelper->getI18n("base-mysql");
	$cceClient = $serverScriptHelper->getCceClient();

	if (!$serverScriptHelper->getAllowed('serverNetwork')) {
		header("location: /error/forbidden.html");
		return;
	}

	$backup_file = "/usr/sausalito/ui/web/base/mysql/mysql-dump.sql";

	$factory = $serverScriptHelper->getHtmlComponentFactory("base-mysql", "/base/mysql/mysqlHandler.php");

	$pid = `/sbin/pidof mysqld`;
	$pid = str_replace("\n", "", $pid);

	if ( $pid ) {
		$my_enabled = 1;
		$access="rw";
	} else {
		$my_enabled = 0;
		$access="r";
	}

	if ( file_exists($backup_file) ) {
		$last_ran = date("M j Y - g:i a", filemtime($backup_file));
		$fs = format_bytes(filesize($backup_file));
	}
	else {
	    $last_ran = "- n/a -";
	    $fs = "- n/a -";	    
	}

        $dump = date("U");
        $cfg = array(
    	    "dumpdate" => $last_ran,
            "dumpsize" => $fs,
            "enabled" => $my_enabled);

        $cceClient->setObject("System", $cfg, "mysql");

	$nuMYSQL = $cceClient->getObject("System", array(), "mysql");

	if ($my_enabled == 1) {
		$nuMYSQL["enabled"] = "1";
	}

	$getthisOID = $cceClient->find("MySQL");
	$mysql_settings_exists = 0;
	$mysql_settings = $cceClient->get($getthisOID[0]);

	if ($mysql_settings['timestamp'] != '') {
	    $mysql_settings_exists = 1;
	}

	// MySQL settings:
	$sql_root               = $mysql_settings['sql_root'];
	$sql_rootpassword       = $mysql_settings['sql_rootpassword'];
	$sql_host               = $mysql_settings['sql_host'];
	$sql_port               = $mysql_settings['sql_port'];

	// Configure defaults:
	if (!$sql_root) { $sql_root = "root"; }
	if (!$sql_host) { $sql_host = "localhost"; }
	if (!$sql_port) { $sql_port = "3306"; }

	if (($sql_host != "localhost") || ($sql_host != "127.0.0.1")) {
	    $mysql_is_local = "1";
    	    $my_sql_host = $sql_host . ":" . $sql_port;
    	    $con_sql_host = $my_sql_host;
	}
	else {
	    $mysql_is_local = "0";
	}
	
	// Test MySQL connection:
	$ret = ini_set("display_errors", "Off");
	$mysql_link = mysql_connect($con_sql_host, $sql_root, $sql_rootpassword) or $mysql_error = mysql_error();
	$ret = ini_set("display_errors", "On");
	if (!$mysql_error) {
	    mysql_select_db("mysql") or $mysql_error = mysql_error();
	    mysql_close($mysql_link);
	}
	$mysql_no_connect = "0";
	if ($mysql_error) {
	    // MySQL connection not possible:	    
	    $mysql_status = $i18n->interpolate("[[base-mysql.mysql_status_incorrect]]");
	    $mysql_no_connect = "1";
	}
	else {
	    // MySQL connection can be established:
	    $mysql_status = $i18n->interpolate("[[base-mysql.mysql_status_ok]]");
	    $mysql_no_connect = "0";
	    // Connection is OK, but no root password configured. Append suggestion to set password:
	    if ($sql_rootpassword == "") {
		$mysql_status .= $i18n->interpolate("[[base-mysql.root_has_no_pwd]]");
		$mysql_no_connect = "2";
	    }
	}

	$page = $factory->getPage();
	$block = $factory->getPagedBlock("mysql_header", array("server", "sqlpass", "sqldump"));

    	////// Local MySQL Server:
    	$block->addDivider($factory->getLabel("MySQL_Local_divider", false), "server");

	$block->addFormField(
		$factory->getBoolean("enabled", $nuMYSQL["enabled"]),
		$factory->getLabel("mysql_enabled"),
		"server");

    	////// Remote MySQL Server:
    	$block->addDivider($factory->getLabel("MySQL_Remote_divider", false), "server");

	// sql_host:
	$line_sql_host = $factory->getTextField("sql_host", $sql_host);
	$line_sql_host->setMaxLength(30);
	$block->addFormField($line_sql_host, $factory->getLabel("sql_host"), "server");

	// sql_port:
	$line_sql_port = $factory->getTextField("sql_port", $sql_port);
	$line_sql_port->setMaxLength(30);
	$block->addFormField($line_sql_port, $factory->getLabel("sql_port"), "server");

	// People apparently get confused by the username / password dialogue on the first tab
	// and attempt to change the password there - not on the 2nd tab instead.
	// So we now hide the login details for MySQL user "root" and only show it if a 
	// MySQL-connection cannot be established:
	//
	// Possible $mysql_no_connect values:
	//
	// 0 = MySQL connection OK
	// 1 = MySQL connection not OK
	// 2 = MySQL connection OK, but "root" has no password set.

        if ($mysql_no_connect == "1") {
	    // Show 'enter password' dialogue in first tab:
            $db_details_visibility = "server";
        }
        else {
	    // Hide 'enter password' dialogue in first tab:
            $db_details_visibility = "hidden";
        }

    	////// Login Details:
    	$block->addDivider($factory->getLabel("MySQL_Login_divider", false), "$db_details_visibility");

	// sql_root:
	$line_sql_root = $factory->getTextField("sql_root", $sql_root);
	$line_sql_root->setMaxLength(30);
	$block->addFormField($line_sql_root, $factory->getLabel("sql_root"), $db_details_visibility);

	// sql_rootpassword:
	$line_sql_rootpassword = $factory->getPassword("sql_rootpassword", $sql_rootpassword);
	$line_sql_rootpassword->setOptional(silent);
	$block->addFormField($line_sql_rootpassword, $factory->getLabel("sql_rootpassword"), $db_details_visibility);

    	////// Status:
    	$block->addDivider($factory->getLabel("MySQL_Status_divider", false), "server");

	// sql_status:
	$line_sql_status = $factory->getTextField("sql_status", $mysql_status, 'r');
	$block->addFormField($line_sql_status, $factory->getLabel("sql_status"), "server");

	$block->addFormField(
		$factory->getTextField("mysqluser", $sql_root, $access),
		$factory->getLabel("username"),
		"hidden");

	$old_pass = $factory->getPassword("oldpass", "", FALSE, $access);
	$old_pass->setOptional('silent');
	$block->addFormField(
		$old_pass,
		$factory->getLabel("current_pass"),
		"sqlpass");

	$new_pass = $factory->getPassword("newpass", "", TRUE, $access);
	$new_pass->setOptional('silent');
	$block->addFormField(
		$new_pass,
		$factory->getLabel("mysqlpass"),
		"sqlpass");

	// SQLdump
	$user = $factory->getTextField("username", $sql_root, $access);
	$user->setOptional('silent');
	$block->addFormField(
		$user,
		$factory->getLabel("username"),
		"hidden");

	$pass = $factory->getPassword("password", $sql_rootpassword, FALSE, $access);
	$pass->setOptional('silent');
	$block->addFormField(
		$pass,
		$factory->getLabel("password"),
		"hidden");


	// Get results:
	$last_ran = $nuMYSQL["dumpdate"];
	$fs = $nuMYSQL["dumpsize"];

	if ( file_exists($backup_file) ) {
		$last_ran = date("M j Y - g:i a", filemtime($backup_file));
		$fs = format_bytes(filesize($backup_file));
	}

	$block->addFormField(
		$factory->getTextField("last_backup", $nuMYSQL["dumpdate"], "r"),
		$factory->getLabel("last_backup"),
		"sqldump");

	$block->addFormField(
		$factory->getTextField("filesize", $nuMYSQL["dumpsize"], "r"),
		$factory->getLabel("filesize"),
		"sqldump");


	echo $page->toHeaderHtml();
	if ( $HTTP_POST_VARS["_PagedBlock_selectedId_mysql_header"] == "sqldump" ) {
		if ( $access == "rw" ) {
			$block->addButton($factory->getButton("javascript: document.form.submit();", "mysqldump"));
		}
		if ( file_exists($backup_file) ) {
			$block->addButton($factory->getButton("/base/mysql/download.php", "download_backup"));
			$block->addButton($factory->getButton("/base/mysql/delete.php", "delete_backup"));
		}
	} else {
		$block->addButton($factory->getSaveButton($page->getSubmitAction()));
	}
	echo $block->toHtml();
	echo $page->toFooterHtml();

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