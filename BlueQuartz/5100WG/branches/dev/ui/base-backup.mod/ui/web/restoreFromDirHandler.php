<?
// Author: Brenda Mula, Tim Hockin
// Copyright 2000 Cobalt Networks.  All rights reserved

include("ServerScriptHelper.php");

$serverScriptHelper = new ServerScriptHelper($sessionId);
$i18n   = $serverScriptHelper->getI18n("base-backup");
$errors = 0;
$errmsg = array();

// get the command line
$options =  "--method='local' --location='$restoreDirField'";

// if they asked us to create a restore fileshare...
if ($restorePlace == "restoreTemp") {
	// Verify restore share exists..
	$verify = "/usr/sausalito/handlers/base/backup/vrfy_fileshare.pl";
	if ( $serverScriptHelper->shell($verify, $ret) ) {
		$errors = 1;

		// don't blame me, this should be streamlined
		array_push( $errmsg,
			new CceError("","","","[[base-backup.backupFileshareMissing]]"));
	}


	// Generate date
	$t = localtime(time(),1);
	$date = ($t[tm_year] + 1900) .
		sprintf( "%02d", ($t[tm_mon] + 1) ) .
		sprintf( "%02d", $t[tm_mday] ) .
		sprintf( "%02d", $t[tm_hour] ) .
		sprintf( "%02d", $t[tm_min] ) .
		sprintf( "%02d", $t[tm_sec] );
	
	$options .= " --relocate='/home/groups/restore/$date' ";
}

// Build command
$cmd = "/usr/local/sbin/crestore $options";

// now start the restore going
if ( ! $errors ) {
	$serverScriptHelper->fork($cmd);
}

// go back to the restoreList
print($serverScriptHelper->toHandlerHtml(
	"/base/backup/restoreList.php?" .
		"restoreTo=" . $restorePlace .
		"&restoreName=" . $i18n->get("unknown"), 
	$errmsg, 
	"base-backup")
);

$serverScriptHelper->destructor();

/*
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

