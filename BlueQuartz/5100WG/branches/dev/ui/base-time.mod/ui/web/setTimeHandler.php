<?php
// Author: Kevin K.M. Chiu
// Copyright 2000, Cobalt Networks.  All rights reserved.
// $Id: setTimeHandler.php 201 2003-07-18 19:11:07Z will $

include_once("ServerScriptHelper.php");
include_once('Error.php');

$serverScriptHelper = new ServerScriptHelper();
$cce = $serverScriptHelper->getCceClient();

if ($cce->suspended() !== false)
{
	$exit = true;
	$msg = $cce->suspended() ? $cce->suspended() : '[[base-cce.suspended]]';
	$errors = array((new Error($msg)));
}
else
{
	$exit = false;
	$errors = array();
}

print($serverScriptHelper->toHandlerHtml("/base/time/setTime.php", $errors));

if ($exit)
	exit;

if ($systemTimeZone != $oldTimeZone) {
	$timeZone = $systemTimeZone;
	putenv("TZ=$timeZone");
}

if (preg_match('/(\d+):(\d+):(\d+):(\d+):(\d+):(\d+)/', $systemDate, $matches)) {
	$date = mktime($matches[4], $matches[5], $matches[6], $matches[2], 
		       $matches[3], $matches[1]);
}
if ($date and ($date != $oldTime))
	$time = $date;

# "deferCommit" is used by the setup wizard, not here... clean up just in case
$cce->setObject('System', array('deferCommit' => '0'), 'Time');

$serverScriptHelper->shell("/usr/sausalito/sbin/setTime \"$time\" \"$timeZone\" \"$ntpAddress\" \"true\"", $output, "root");
print $output;

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

